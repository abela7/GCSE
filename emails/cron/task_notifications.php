<?php
/**
 * Task Notification Script - SIMPLIFIED VERSION
 * This script checks for tasks due exactly at the current time and sends notification emails
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

// Get current time 
$current_time_rounded = date('H:i:00'); // Round to the current minute
$today = date('Y-m-d');

error_log("Current date: {$today}");
error_log("Current time (rounded to minute): {$current_time_rounded}");

// Ultra-simplified query to get tasks due exactly at the current minute
// No time window calculations, no tracking table checks
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
    WHERE 
        t.status IN ('pending', 'in_progress') 
        AND t.due_date = ?
        AND t.due_time LIKE ?
    ORDER BY 
        FIELD(t.priority, 'high', 'medium', 'low')
    LIMIT 5
";

// Match exactly the hour and minute, regardless of seconds
$time_pattern = date('H:i') . '%';
error_log("SQL Query with params: [date={$today}, time_pattern={$time_pattern}]");

try {
    $stmt = $conn->prepare($tasks_query);
    if (!$stmt) {
        error_log("PREPARE ERROR: " . $conn->error);
        exit;
    }
    
    $stmt->bind_param("ss", $today, $time_pattern);
    $stmt->execute();
    
    if ($stmt->error) {
        error_log("EXECUTE ERROR: " . $stmt->error);
        exit;
    }
    
    $result = $stmt->get_result();
    error_log("Query executed successfully. Found " . $result->num_rows . " tasks due for notification.");
    
    if ($result->num_rows === 0) {
        error_log("No due tasks found for notification. Exiting.");
        
        // DEBUG: Show all tasks due today
        $debug_query = "SELECT id, title, due_date, due_time, status FROM tasks WHERE due_date = '{$today}' ORDER BY due_time";
        $debug_result = $conn->query($debug_query);
        
        if ($debug_result && $debug_result->num_rows > 0) {
            error_log("All tasks due today:");
            while($row = $debug_result->fetch_assoc()) {
                error_log("Task #{$row['id']}: {$row['title']} - Due: {$row['due_date']} {$row['due_time']} - Status: {$row['status']}");
            }
        } else {
            error_log("No tasks found for today");
        }
        
        exit;
    }
} catch (Exception $e) {
    error_log("SQL ERROR: " . $e->getMessage());
    exit;
}

// Process each task found
while ($current_task = $result->fetch_assoc()) {
    // Log task found
    error_log("Processing notification for task: {$current_task['id']} - {$current_task['title']} due at {$current_task['due_time']}");
    
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
        $mail->Subject = "⏰ Task Due Now: " . $current_task['title'];
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
        
        // Send the email
        if (!$mail->send()) {
            error_log("Email could not be sent: " . $mail->ErrorInfo);
            continue;
        }
        
        error_log("Email sent successfully for task ID {$current_task['id']} at " . date('Y-m-d H:i:s'));
        
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