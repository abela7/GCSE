<?php
/**
 * ULTRA SIMPLE Task Notification Script
 * NO fancy calculations, just basic string comparison
 */

// Basic logging
error_log("==== TASK NOTIFICATION SCRIPT STARTED ====");
error_log("Running at: " . date('Y-m-d H:i:s'));

// Include required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../templates/task_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verify database connection
if ($conn->connect_error) {
    error_log("DATABASE ERROR: " . $conn->connect_error);
    exit;
}

// Only proceed if email notifications are enabled
if (!ENABLE_EMAIL_NOTIFICATIONS) {
    error_log("Notifications disabled in config. Exiting.");
    exit;
}

// Get current time
$today = date('Y-m-d');
$now_hour = (int)date('H');
$now_minute = (int)date('i');
error_log("Current time: {$now_hour}:{$now_minute}");

// ULTRA-SIMPLE APPROACH: 
// Just get ALL tasks due today and manually check time match
$query = "SELECT * FROM tasks t WHERE t.status IN ('pending', 'in_progress') AND t.due_date = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

error_log("Found " . $result->num_rows . " tasks due today");

// PLAIN MATCHING: Find tasks due in next 3 minutes
$to_notify = [];
while ($task = $result->fetch_assoc()) {
    // Extract task time components
    $time_parts = explode(':', $task['due_time']);
    $task_hour = (int)$time_parts[0];
    $task_minute = (int)$time_parts[1];
    
    // Calculate next 3 minutes window 
    $min_plus_1 = ($now_minute + 1) % 60;
    $min_plus_2 = ($now_minute + 2) % 60;
    $min_plus_3 = ($now_minute + 3) % 60;
    
    // Handle hour rollover
    $hour_plus_1 = ($min_plus_1 < $now_minute) ? ($now_hour + 1) % 24 : $now_hour;
    $hour_plus_2 = ($min_plus_2 < $now_minute) ? ($now_hour + 1) % 24 : $now_hour;
    $hour_plus_3 = ($min_plus_3 < $now_minute) ? ($now_hour + 1) % 24 : $now_hour;
    
    // Exact matches for next 3 minutes
    $is_match = false;
    
    // Current minute
    if ($task_hour == $now_hour && $task_minute == $now_minute) {
        $is_match = true;
        error_log("Task #{$task['id']} matches CURRENT minute!");
    }
    // 1 minute from now
    else if ($task_hour == $hour_plus_1 && $task_minute == $min_plus_1) {
        $is_match = true;
        error_log("Task #{$task['id']} matches +1 minute!");
    }
    // 2 minutes from now
    else if ($task_hour == $hour_plus_2 && $task_minute == $min_plus_2) {
        $is_match = true;
        error_log("Task #{$task['id']} matches +2 minute!");
    }
    // 3 minutes from now
    else if ($task_hour == $hour_plus_3 && $task_minute == $min_plus_3) {
        $is_match = true;
        error_log("Task #{$task['id']} matches +3 minute!");
    }
    
    error_log("Task #{$task['id']} - Due: {$task_hour}:{$task_minute} - Match: " . ($is_match ? "YES" : "NO"));
    
    if ($is_match) {
        $to_notify[] = $task;
    }
}

if (empty($to_notify)) {
    error_log("No tasks match the exact time window. Exiting.");
    exit;
}

error_log("Found " . count($to_notify) . " tasks to notify!");

// Send notifications for matching tasks
foreach ($to_notify as $task) {
    error_log("Sending notification for task #{$task['id']} - {$task['title']} due at {$task['due_time']}");
    
    // Get category name
    $cat_query = "SELECT name FROM task_categories WHERE id = ? LIMIT 1";
    $cat_stmt = $conn->prepare($cat_query);
    $cat_stmt->bind_param("i", $task['category_id']);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    $category_name = ($cat_result && $cat_result->num_rows > 0) 
        ? $cat_result->fetch_assoc()['name'] 
        : 'Uncategorized';
    
    // Format task for template
    $current_task = [
        'id' => $task['id'],
        'title' => $task['title'],
        'description' => $task['description'],
        'priority' => $task['priority'],
        'estimated_duration' => $task['estimated_duration'],
        'category_id' => $task['category_id'],
        'due_date' => $task['due_date'],
        'due_time' => date('h:i A', strtotime($task['due_time'])),
        'category_name' => $category_name
    ];
    
    // Prepare email data
    $emailData = [
        'current_task' => $current_task,
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
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress(SMTP_USERNAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "⏰ Task Due Soon: " . $task['title'];
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
}

$conn->close();
error_log("==== TASK NOTIFICATION SCRIPT ENDED ====");
?> 