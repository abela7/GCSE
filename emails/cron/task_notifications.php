<?php
/**
 * Task Notification Script - MANUAL TIME COMPARISON VERSION
 * Gets tasks due today and manually checks their due time
 * Runs every minute via cron
 */

// Enable error reporting for debugging
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
}

// Application URL for links in emails
$app_url = 'http://abel.abuneteklehaymanot.org';

// Only proceed if email notifications are enabled
if (!ENABLE_EMAIL_NOTIFICATIONS) {
    error_log("Task notifications are disabled in config. Exiting.");
    exit;
}

// Get current time info - ensure we use the EXACT same format as the database (HH:MM:SS)
$now = time(); // Current unix timestamp
$today = date('Y-m-d');
$current_time = date('H:i:s'); // Format: 17:07:00
$current_time_plus_3 = date('H:i:s', strtotime('+3 minutes'));

error_log("Current date: {$today}");
error_log("Current time (HH:MM:SS): {$current_time}");
error_log("Current time + 3min: {$current_time_plus_3}");

// Get tasks due today with explicit SQL time comparison
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
        CASE WHEN tc.name IS NOT NULL THEN tc.name ELSE 'Uncategorized' END AS category_name,
        TIME_TO_SEC(TIMEDIFF(t.due_time, ?)) as seconds_until_due
    FROM 
        tasks t
    LEFT JOIN 
        task_categories tc ON t.category_id = tc.id
    WHERE 
        t.status IN ('pending', 'in_progress') 
        AND t.due_date = ?
    ORDER BY 
        ABS(TIME_TO_SEC(TIMEDIFF(t.due_time, ?))) ASC
";

try {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $current_time, $today, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("Found " . $result->num_rows . " tasks due today");
    
    if ($result->num_rows === 0) {
        error_log("No tasks due today. Exiting.");
        exit;
    }
} catch (Exception $e) {
    error_log("SQL ERROR: " . $e->getMessage());
    exit;
}

// Array to collect tasks that are due now or in the next 3 minutes
$due_tasks = [];

// Process each task found and manually check the time
while ($task = $result->fetch_assoc()) {
    // The seconds_until_due field contains the time difference calculated by MySQL
    $seconds_until_due = $task['seconds_until_due'];
    $minutes_until_due = round($seconds_until_due / 60);
    
    // Also calculate using PHP for verification
    $db_time_parts = explode(':', $task['due_time']);
    $task_seconds = ($db_time_parts[0] * 3600) + ($db_time_parts[1] * 60) + $db_time_parts[2];
    
    $current_parts = explode(':', $current_time);
    $current_seconds = ($current_parts[0] * 3600) + ($current_parts[1] * 60) + $current_parts[2];
    
    $seconds_diff = $task_seconds - $current_seconds;
    if ($seconds_diff < -43200) { // Handle time wrapping (e.g. 23:59 vs 00:01)
        $seconds_diff += 86400;
    } elseif ($seconds_diff > 43200) {
        $seconds_diff -= 86400;
    }
    
    $minutes_diff = round($seconds_diff / 60);
    
    // Debug log each task's time difference
    error_log("Task #{$task['id']}: \"{$task['title']}\" due at {$task['due_time']} - MySQL: {$minutes_until_due} min, PHP: {$minutes_diff} min");
    
    // If task is due now or in the next 3 minutes
    if (($minutes_diff >= 0 && $minutes_diff <= 3) || ($minutes_until_due >= 0 && $minutes_until_due <= 3)) {
        error_log("✅ Task #{$task['id']} is due within 3 minutes - will send notification");
        $due_tasks[] = $task;
    } else {
        error_log("⏭ Task #{$task['id']} is not due in the next 3 minutes - skipping");
    }
}

if (empty($due_tasks)) {
    error_log("No tasks due in the next 3 minutes. Exiting.");
    exit;
}

error_log("Found " . count($due_tasks) . " tasks due in the next 3 minutes");

// Now process each due task
foreach ($due_tasks as $current_task) {
    error_log("Processing notification for task: #{$current_task['id']} - \"{$current_task['title']}\" due at {$current_task['due_time']}");
    
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
            error_log("ERROR: Email could not be sent: " . $mail->ErrorInfo);
            continue;
        }
        
        error_log("SUCCESS: Email sent for task ID {$current_task['id']}");
        
        // Record in tracking table
        $tracking_query = "
            INSERT INTO task_notification_tracking (task_id, notification_type, sent_at)
            VALUES (?, 'due', NOW())
        ";
        
        $tracking_stmt = $conn->prepare($tracking_query);
        if ($tracking_stmt) {
            $tracking_stmt->bind_param("i", $current_task['id']);
            $tracking_stmt->execute();
            $tracking_stmt->close();
            error_log("Tracking record inserted for task ID {$current_task['id']}");
        }
        
    } catch (Exception $e) {
        error_log("PHPMailer Error for task ID {$current_task['id']}: " . $e->getMessage());
    }
}

$conn->close();
error_log("Task notification check completed at " . date('Y-m-d H:i:s'));
error_log("==== TASK NOTIFICATION SCRIPT ENDED ====");
?> 