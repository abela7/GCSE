<?php
/**
 * Task Notification Script
 * This script checks for tasks that are due soon and sends notification emails
 * It should be run by a cron job every 5-15 minutes
 */

// Include required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../templates/task_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Application URL for links in emails
$app_url = 'http://abel.abuneteklehaymanot.org';

// Only proceed if email notifications are enabled
if (!ENABLE_EMAIL_NOTIFICATIONS) {
    error_log("Task notifications are disabled in config. Exiting.");
    exit;
}

// Get current time plus buffer (for tasks due within the next 5-15 minutes)
$buffer_minutes = 15; // Notification ahead of time
$current_time = date('H:i:s');
$notification_window_start = date('H:i:s');
$notification_window_end = date('H:i:s', strtotime("+{$buffer_minutes} minutes"));
$today = date('Y-m-d');

error_log("Checking for tasks due between {$notification_window_start} and {$notification_window_end} on {$today}");

// Find tasks that are due within the notification window
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
    WHERE 
        t.status IN ('pending', 'in_progress') 
        AND t.due_date = ?
        AND t.due_time BETWEEN ? AND ?
        AND tnt.id IS NULL
    ORDER BY 
        t.due_time ASC, 
        FIELD(t.priority, 'high', 'medium', 'low')
";

$stmt = $conn->prepare($tasks_query);
$stmt->bind_param("sss", $today, $notification_window_start, $notification_window_end);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("No due tasks found for notification. Exiting.");
    exit;
}

while ($current_task = $result->fetch_assoc()) {
    // Log task found
    error_log("Preparing notification for task: {$current_task['id']} - {$current_task['title']} due at {$current_task['due_time']}");
    
    // Format task time for display
    $current_task['due_time'] = date('h:i A', strtotime($current_task['due_time']));
    
    // Get overdue tasks
    $overdue_tasks_query = "
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
            AND ((t.due_date < ? OR (t.due_date = ? AND t.due_time < ?)))
            AND t.id != ?
        ORDER BY 
            t.due_date ASC, 
            t.due_time ASC, 
            FIELD(t.priority, 'high', 'medium', 'low')
        LIMIT 5
    ";
    
    $overdue_stmt = $conn->prepare($overdue_tasks_query);
    $current_time_only = date('H:i:s');
    $overdue_stmt->bind_param("sssi", $today, $today, $current_time_only, $current_task['id']);
    $overdue_stmt->execute();
    $overdue_result = $overdue_stmt->get_result();
    
    $overdue_tasks = [];
    while ($task = $overdue_result->fetch_assoc()) {
        $task['due_time'] = $task['due_date'] != $today 
            ? date('M j, Y', strtotime($task['due_date'])) . ' at ' . date('h:i A', strtotime($task['due_time']))
            : date('h:i A', strtotime($task['due_time']));
        $overdue_tasks[] = $task;
    }
    
    // Get other tasks for today
    $upcoming_tasks_query = "
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
            AND t.due_time > ?
            AND t.id != ?
        ORDER BY 
            t.due_time ASC, 
            FIELD(t.priority, 'high', 'medium', 'low')
        LIMIT 5
    ";
    
    $upcoming_stmt = $conn->prepare($upcoming_tasks_query);
    $upcoming_stmt->bind_param("ssi", $today, $notification_window_end, $current_task['id']);
    $upcoming_stmt->execute();
    $upcoming_result = $upcoming_stmt->get_result();
    
    $upcoming_tasks = [];
    while ($task = $upcoming_result->fetch_assoc()) {
        $task['due_time'] = date('h:i A', strtotime($task['due_time']));
        $upcoming_tasks[] = $task;
    }
    
    // Prepare email data
    $emailData = [
        'current_task' => $current_task,
        'overdue_tasks' => $overdue_tasks,
        'upcoming_tasks' => $upcoming_tasks,
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
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress(SMTP_USERNAME); // Sending to user's email
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Task Due: " . $current_task['title'];
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
        
        // Send the email
        $mail->send();
        error_log("Task notification email sent for task ID {$current_task['id']} at " . date('Y-m-d H:i:s'));
        
        // Record that notification has been sent
        $tracking_query = "
            INSERT INTO task_notification_tracking (task_id, notification_type, sent_at)
            VALUES (?, 'due', NOW())
        ";
        $tracking_stmt = $conn->prepare($tracking_query);
        $tracking_stmt->bind_param("i", $current_task['id']);
        $tracking_stmt->execute();
        
    } catch (Exception $e) {
        error_log("PHPMailer Error for task ID {$current_task['id']}: " . $e->getMessage());
    }
}

$conn->close();
error_log("Task notification check completed at " . date('Y-m-d H:i:s'));
?> 