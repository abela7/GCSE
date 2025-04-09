<?php
/**
 * Task Notification Script - ULTRA SIMPLIFIED VERSION
 * This script checks for tasks due today and sends notification emails regardless of exact timing
 * It should be run by a cron job every minute
 */

// Enable verbose error logging
error_log("==== TASK NOTIFICATION SCRIPT STARTED ====");
error_log("Script running at: " . date('Y-m-d H:i:s'));

// Include required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../templates/task_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verify database connection
if ($conn->connect_error) {
    error_log("DATABASE CONNECTION ERROR: " . $conn->connect_error);
    exit;
} else {
    error_log("Database connection successful");
}

// Application URL for links in emails
$app_url = 'http://abel.abuneteklehaymanot.org';

// Only proceed if email notifications are enabled
if (!ENABLE_EMAIL_NOTIFICATIONS) {
    error_log("Task notifications are disabled in config. Exiting.");
    exit;
}

// Get current time info
$current_full_time = date('H:i:s');
$current_hour_minute = date('H:i');
$today = date('Y-m-d');

error_log("Current date: {$today}");
error_log("Current full time: {$current_full_time}");
error_log("Current hour:minute: {$current_hour_minute}");

// First, let's check the database to see ALL pending tasks with their due times
$all_tasks_query = "SELECT id, title, due_date, due_time, status FROM tasks WHERE status IN ('pending', 'in_progress') ORDER BY due_date, due_time";
$all_tasks_result = $conn->query($all_tasks_query);

error_log("=== DEBUG: ALL PENDING TASKS IN DATABASE ===");
if ($all_tasks_result && $all_tasks_result->num_rows > 0) {
    while($row = $all_tasks_result->fetch_assoc()) {
        error_log("Task #{$row['id']}: {$row['title']} - Due: {$row['due_date']} {$row['due_time']} - Status: {$row['status']}");
    }
} else {
    error_log("No pending tasks found in database at all!");
}

// ULTRA-simplified query - Get ALL tasks due today that haven't been notified in the last hour
// This is a fallback approach to ensure notifications are sent
$tasks_query = "
    SELECT 
        t.id, 
        t.title, 
        t.description, 
        t.priority, 
        t.estimated_duration,
        t.category_id,
        t.due_date, 
        t.due_time,
        CASE WHEN tc.name IS NOT NULL THEN tc.name ELSE 'Uncategorized' END AS category_name
    FROM 
        tasks t
    LEFT JOIN 
        task_categories tc ON t.category_id = tc.id
    LEFT JOIN 
        task_notification_tracking tnt ON t.id = tnt.task_id AND tnt.notification_type = 'due' 
                                      AND tnt.sent_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    WHERE 
        t.status IN ('pending', 'in_progress') 
        AND t.due_date = ?
        AND tnt.id IS NULL
    ORDER BY 
        t.due_time ASC,
        FIELD(t.priority, 'high', 'medium', 'low')
    LIMIT 1
";

error_log("SQL Query for today's tasks with params: [date={$today}]");

try {
    $stmt = $conn->prepare($tasks_query);
    if (!$stmt) {
        error_log("PREPARE ERROR: " . $conn->error);
        exit;
    }
    
    $stmt->bind_param("s", $today);
    $stmt->execute();
    
    if ($stmt->error) {
        error_log("EXECUTE ERROR: " . $stmt->error);
        exit;
    }
    
    $result = $stmt->get_result();
    error_log("Query executed successfully. Found " . $result->num_rows . " tasks due for notification.");
    
    if ($result->num_rows === 0) {
        error_log("No tasks found that need notification. Checking for exact time matches now...");
        
        // Try a different approach - exact time matching based on hour:minute
        $exact_time_query = "
            SELECT 
                t.id, 
                t.title, 
                t.description, 
                t.priority, 
                t.estimated_duration,
                t.category_id,
                t.due_date, 
                t.due_time,
                CASE WHEN tc.name IS NOT NULL THEN tc.name ELSE 'Uncategorized' END AS category_name
            FROM 
                tasks t
            LEFT JOIN 
                task_categories tc ON t.category_id = tc.id
            WHERE 
                t.status IN ('pending', 'in_progress') 
                AND t.due_date = ?
                AND SUBSTRING(t.due_time, 1, 5) = ?
            ORDER BY 
                FIELD(t.priority, 'high', 'medium', 'low')
            LIMIT 1
        ";
        
        $exact_stmt = $conn->prepare($exact_time_query);
        $exact_stmt->bind_param("ss", $today, $current_hour_minute);
        $exact_stmt->execute();
        $exact_result = $exact_stmt->get_result();
        
        error_log("Exact time query executed. Found " . $exact_result->num_rows . " tasks matching current time " . $current_hour_minute);
        
        if ($exact_result->num_rows > 0) {
            $result = $exact_result;
            error_log("Found task with exact time match. Proceeding with notification.");
        } else {
            error_log("No tasks found with exact time match. Exiting.");
            exit;
        }
    }
} catch (Exception $e) {
    error_log("SQL ERROR: " . $e->getMessage());
    exit;
}

// Process each task found
while ($current_task = $result->fetch_assoc()) {
    // Log task found
    error_log("SENDING NOTIFICATION for task: {$current_task['id']} - {$current_task['title']} due at {$current_task['due_time']}");
    
    // Format task time for display
    $current_task['due_time'] = date('h:i A', strtotime($current_task['due_time']));
    
    // Send just the current task without additional data
    $emailData = [
        'current_task' => $current_task,
        'overdue_tasks' => [],
        'upcoming_tasks' => [],
        'app_url' => $app_url
    ];
    
    // Generate email content
    $notification = new TaskNotification();
    $emailContent = $notification->generateEmail($emailData);
    
    // Send email
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Enable debugging
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP Debug: $str");
        };
        
        // Anti-spam measures
        $mail->XMailer = 'GCSE Study App Mailer';
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
        $mail->addCustomHeader('Precedence', 'bulk');
        $mail->addCustomHeader('X-Priority', '3');
        $mail->addCustomHeader('X-Mailer', 'GCSE-Study-App-PHP-Mailer');
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress(SMTP_USERNAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "⏰ TASK DUE: " . $current_task['title'];
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
        
        // Send the email
        if (!$mail->send()) {
            error_log("Email could not be sent: " . $mail->ErrorInfo);
            continue;
        }
        
        error_log("✅ Email sent successfully for task ID {$current_task['id']} at " . date('Y-m-d H:i:s'));
        
        // Still record in tracking table, but just for history
        $tracking_query = "
            INSERT INTO task_notification_tracking (task_id, notification_type, sent_at)
            VALUES (?, 'due', NOW())
        ";
        
        $tracking_stmt = $conn->prepare($tracking_query);
        if ($tracking_stmt) {
            $tracking_stmt->bind_param("i", $current_task['id']);
            $tracking_stmt->execute();
            $tracking_stmt->close();
        }
        
    } catch (Exception $e) {
        error_log("PHPMailer Error for task ID {$current_task['id']}: " . $e->getMessage());
    }
}

$conn->close();
error_log("Task notification check completed at " . date('Y-m-d H:i:s'));
error_log("==== TASK NOTIFICATION SCRIPT ENDED ====");
?> 