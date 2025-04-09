<?php
/**
 * FINAL NUCLEAR OPTION NOTIFICATION SCRIPT
 * Simply sends notifications for ALL tasks due today, ignoring time
 */

// Basic logging
error_log("==== TASK NOTIFICATION SCRIPT - NUCLEAR OPTION ====");
error_log("Running at: " . date('Y-m-d H:i:s'));

// Include required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../templates/task_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Just get today's date
$today = date('Y-m-d');
error_log("Today's date: {$today}");

// Just get ALL tasks due TODAY, period.
$query = "
    SELECT 
        t.*, 
        CASE WHEN tc.name IS NOT NULL THEN tc.name ELSE 'Uncategorized' END AS category_name
    FROM 
        tasks t
    LEFT JOIN 
        task_categories tc ON t.category_id = tc.id
    WHERE 
        t.status IN ('pending', 'in_progress') 
        AND t.due_date = ?
        AND NOT EXISTS (
            SELECT 1 FROM task_notification_tracking tnt 
            WHERE tnt.task_id = t.id 
            AND tnt.notification_type = 'due'
            AND DATE(tnt.sent_at) = ?
        )
";

try {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("DATABASE ERROR: " . $conn->error);
        exit;
    }
    
    $stmt->bind_param("ss", $today, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("Found " . $result->num_rows . " tasks due today that haven't been notified yet");
    
    if ($result->num_rows === 0) {
        error_log("No tasks to notify about today. Exiting.");
        exit;
    }
} catch (Exception $e) {
    error_log("SQL ERROR: " . $e->getMessage());
    exit;
}

// Send a notification for EACH task due today
while ($task = $result->fetch_assoc()) {
    error_log("Sending notification for task #{$task['id']} - {$task['title']} due at {$task['due_time']}");
    
    // Format task for template
    $task['due_time'] = date('h:i A', strtotime($task['due_time']));
    
    // Prepare email data
    $emailData = [
        'current_task' => $task,
        'overdue_tasks' => [],
        'upcoming_tasks' => [],
        'app_url' => 'http://abel.abuneteklehaymanot.org'
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
        
        // Set high priority 
        $mail->Priority = 1;
        $mail->AddCustomHeader("X-MSMail-Priority: High");
        $mail->AddCustomHeader("Importance: High");
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress(SMTP_USERNAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "⚠️ TASK DUE TODAY: " . $task['title'] . " (" . $task['due_time'] . ")";
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
        
        if ($mail->send()) {
            error_log("✅ Email sent successfully for task #{$task['id']}");
            
            // Record in tracking table
            $tracking_query = "INSERT INTO task_notification_tracking (task_id, notification_type, sent_at) VALUES (?, 'due', NOW())";
            $tracking_stmt = $conn->prepare($tracking_query);
            $tracking_stmt->bind_param("i", $task['id']);
            $tracking_stmt->execute();
        } else {
            error_log("❌ Email error: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        error_log("PHPMailer error: " . $e->getMessage());
    }
    
    // Sleep briefly to avoid flooding the mail server
    sleep(1);
}

$conn->close();
error_log("==== TASK NOTIFICATION SCRIPT ENDED ====");
?> 