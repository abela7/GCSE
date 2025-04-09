<?php
/**
 * Task Notification Script - SUPER SIMPLE VERSION
 * Gets tasks due in the next 3 minutes and sends notifications
 * Runs every minute via cron
 */

// Basic error logging
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
}

// Application URL for links in emails
$app_url = 'http://abel.abuneteklehaymanot.org';

// Only proceed if email notifications are enabled
if (!ENABLE_EMAIL_NOTIFICATIONS) {
    error_log("Task notifications are disabled in config. Exiting.");
    exit;
}

// Get current time and the 3-minute future window
$current_time = date('H:i:s');
$three_min_future = date('H:i:s', strtotime("+3 minutes"));
$today = date('Y-m-d');

error_log("Current date: {$today}");
error_log("Current time: {$current_time}");
error_log("3-min future window: {$three_min_future}");

// Simple query: Get tasks due in the next 3 minutes
$query = "
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
        AND t.due_time BETWEEN ? AND ?
    ORDER BY 
        t.due_time ASC,
        FIELD(t.priority, 'high', 'medium', 'low')
    LIMIT 5
";

try {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $today, $current_time, $three_min_future);
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("Found " . $result->num_rows . " tasks due in the next 3 minutes");
    
    if ($result->num_rows === 0) {
        error_log("No tasks due in the next 3 minutes. Exiting.");
        exit;
    }
} catch (Exception $e) {
    error_log("SQL ERROR: " . $e->getMessage());
    exit;
}

// Process each task found
while ($current_task = $result->fetch_assoc()) {
    error_log("Sending notification for task: #{$current_task['id']} - \"{$current_task['title']}\" due at {$current_task['due_time']}");
    
    // Format task time for display
    $current_task['due_time'] = date('h:i A', strtotime($current_task['due_time']));
    
    // Prepare email data
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
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress(SMTP_USERNAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "⏰ Task Due Soon: " . $current_task['title'];
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
        
        // Send the email
        if (!$mail->send()) {
            error_log("Email could not be sent: " . $mail->ErrorInfo);
            continue;
        }
        
        error_log("Email sent successfully for task ID {$current_task['id']}");
        
        // Record in tracking table for history
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
error_log("Task notification check completed");
?> 