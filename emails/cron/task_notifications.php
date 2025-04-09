<?php
/**
 * Task Notification Script - STRICT TIME VERSION
 * This script ONLY sends notifications for tasks due RIGHT NOW (within 1 minute)
 * It should be run by a cron job every minute
 */

// Enable error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Get current time info for STRICT time matching
$current_time = date('H:i:s');
$current_hour_minute = date('H:i');
$one_min_before = date('H:i:s', strtotime("-1 minute"));
$one_min_after = date('H:i:s', strtotime("+1 minute"));
$today = date('Y-m-d');
$now_timestamp = time();

error_log("Current date: {$today}");
error_log("Current time: {$current_time}");
error_log("Strict time window: {$one_min_before} to {$one_min_after}");

// Log all pending tasks to help with debugging
$all_tasks_query = "SELECT id, title, due_date, due_time, status FROM tasks WHERE status IN ('pending', 'in_progress') ORDER BY due_date, due_time";
$all_tasks_result = $conn->query($all_tasks_query);
error_log("=== DEBUG: ALL PENDING TASKS IN DATABASE ===");
if ($all_tasks_result && $all_tasks_result->num_rows > 0) {
    while($row = $all_tasks_result->fetch_assoc()) {
        $task_time = strtotime($row['due_date'] . ' ' . $row['due_time']);
        $time_diff = $task_time - $now_timestamp;
        $diff_minutes = round($time_diff / 60);
        
        error_log("Task #{$row['id']}: {$row['title']} - Due: {$row['due_date']} {$row['due_time']} - Status: {$row['status']} - Due in: {$diff_minutes} minutes");
    }
} else {
    error_log("No pending tasks found in database at all!");
}

// STRICT METHOD: Get ONLY tasks due RIGHT NOW (±1 minute window)
// No fallbacks, no exceptions - only send notifications for tasks due right now
$strict_query = "
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
        AND (
            (t.due_time BETWEEN ? AND ?) 
            OR SUBSTRING(t.due_time, 1, 5) = ?
        )
    ORDER BY 
        ABS(TIME_TO_SEC(TIMEDIFF(t.due_time, ?))) ASC,
        FIELD(t.priority, 'high', 'medium', 'low')
    LIMIT 1
";

error_log("Executing STRICT time query with params: today={$today}, window={$one_min_before} to {$one_min_after}, hour:min={$current_hour_minute}");

try {
    $strict_stmt = $conn->prepare($strict_query);
    if (!$strict_stmt) {
        error_log("PREPARE ERROR: " . $conn->error);
        exit;
    }
    
    $strict_stmt->bind_param("sssss", $today, $one_min_before, $one_min_after, $current_hour_minute, $current_time);
    $strict_stmt->execute();
    
    if ($strict_stmt->error) {
        error_log("EXECUTE ERROR: " . $strict_stmt->error);
        exit;
    }
    
    $result = $strict_stmt->get_result();
    error_log("Query executed successfully. Found " . $result->num_rows . " tasks due RIGHT NOW.");
    
    if ($result->num_rows === 0) {
        error_log("No tasks due RIGHT NOW. Exiting without sending any notifications.");
        exit;
    }
} catch (Exception $e) {
    error_log("SQL ERROR: " . $e->getMessage());
    exit;
}

// Process each task found (should be at most 1)
while ($current_task = $result->fetch_assoc()) {
    // Convert due time to timestamp for verification
    $task_timestamp = strtotime($current_task['due_date'] . ' ' . $current_task['due_time']);
    $time_diff = $task_timestamp - $now_timestamp;
    $minutes_diff = round($time_diff / 60);
    
    // Double-check that this task is actually due right now
    if (abs($minutes_diff) > 2) {
        error_log("⚠️ SKIPPING task #{$current_task['id']} - Due time {$current_task['due_time']} is not within 2 minutes of current time ({$minutes_diff} minutes away)");
        continue;
    }
    
    error_log("✅ SENDING NOTIFICATION for task: #{$current_task['id']} - \"{$current_task['title']}\" due at {$current_task['due_time']}");
    error_log("Task is due RIGHT NOW ({$minutes_diff} minutes from current time)");
    
    // Format task time for display
    $current_task['due_time'] = date('h:i A', strtotime($current_task['due_time']));
    
    // Prepare email data (just the current task)
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
        
        // Set debugging level
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP Debug: $str");
        };
        
        // Anti-spam measures
        $mail->XMailer = 'GCSE Study App Mailer';
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
        $mail->addCustomHeader('Precedence', 'bulk');
        $mail->addCustomHeader('X-Priority', '1'); // Higher priority
        $mail->addCustomHeader('X-Mailer', 'GCSE-Study-App-PHP-Mailer');
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress(SMTP_USERNAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "⏰ TASK DUE NOW: " . $current_task['title'];
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
        
        // Send the email
        if (!$mail->send()) {
            error_log("❌ Email could not be sent: " . $mail->ErrorInfo);
            continue;
        }
        
        error_log("✅ Email sent successfully for task ID {$current_task['id']} at " . date('Y-m-d H:i:s'));
        
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
error_log("Task notification check completed at " . date('Y-m-d H:i:s'));
error_log("==== TASK NOTIFICATION SCRIPT ENDED ====");
?> 