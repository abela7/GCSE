<?php
/**
 * Task Notification Script - FINAL SIMPLIFIED VERSION
 * This script checks for tasks due today and sends notification emails
 * It should be run by a cron job every minute
 */

// Enable extremely verbose error logging
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

// Get current time info in various formats for robust time matching
$current_full_time = date('H:i:s');
$current_hour_minute = date('H:i');
$current_time_mysql = date('H:i:s');
$five_min_before = date('H:i:s', strtotime("-5 minutes"));
$five_min_after = date('H:i:s', strtotime("+5 minutes"));
$today = date('Y-m-d');
$now_timestamp = time();

error_log("Current date: {$today}");
error_log("Current time (full): {$current_full_time}");
error_log("Current time (H:i): {$current_hour_minute}");
error_log("5-min window: {$five_min_before} to {$five_min_after}");
error_log("Current UNIX timestamp: {$now_timestamp}");

// First, let's check the database to see ALL pending tasks with their due times
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

// Completely overhauled approach - Multiple parallel methods to find tasks:

// 1. METHOD 1: Get tasks due within a 5-minute window of current time
$window_query = "
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

try {
    $window_stmt = $conn->prepare($window_query);
    if (!$window_stmt) {
        error_log("PREPARE ERROR (window query): " . $conn->error);
    } else {
        $window_stmt->bind_param("sssss", $today, $five_min_before, $five_min_after, $current_hour_minute, $current_time_mysql);
        $window_stmt->execute();
        
        if ($window_stmt->error) {
            error_log("EXECUTE ERROR (window query): " . $window_stmt->error);
        } else {
            $window_result = $window_stmt->get_result();
            error_log("Window query executed. Found " . $window_result->num_rows . " tasks due in ±5 minute window.");
            
            if ($window_result->num_rows > 0) {
                error_log("SUCCESS: Found a task in the time window! Sending notification...");
                $result = $window_result;
                $method_used = "Time window (±5 min)";
                goto send_notification;
            }
        }
    }
} catch (Exception $e) {
    error_log("ERROR in window query: " . $e->getMessage());
}

// 2. METHOD 2: Get any task due today that hasn't been notified recently
$recent_query = "
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
                                      AND tnt.sent_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    WHERE 
        t.status IN ('pending', 'in_progress') 
        AND t.due_date = ?
        AND tnt.id IS NULL
    ORDER BY 
        t.due_time ASC,
        FIELD(t.priority, 'high', 'medium', 'low')
    LIMIT 1
";

try {
    $recent_stmt = $conn->prepare($recent_query);
    if (!$recent_stmt) {
        error_log("PREPARE ERROR (recent query): " . $conn->error);
    } else {
        $recent_stmt->bind_param("s", $today);
        $recent_stmt->execute();
        
        if ($recent_stmt->error) {
            error_log("EXECUTE ERROR (recent query): " . $recent_stmt->error);
        } else {
            $recent_result = $recent_stmt->get_result();
            error_log("Recent tasks query executed. Found " . $recent_result->num_rows . " tasks due today not recently notified.");
            
            if ($recent_result->num_rows > 0) {
                error_log("SUCCESS: Found a task due today not recently notified! Sending notification...");
                $result = $recent_result;
                $method_used = "Not recently notified";
                goto send_notification;
            }
        }
    }
} catch (Exception $e) {
    error_log("ERROR in recent query: " . $e->getMessage());
}

// 3. METHOD 3: LAST RESORT - Get ANY pending task due today that is closest to now
$any_query = "
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
    ORDER BY 
        ABS(TIME_TO_SEC(TIMEDIFF(t.due_time, ?))) ASC,
        FIELD(t.priority, 'high', 'medium', 'low')
    LIMIT 1
";

try {
    $any_stmt = $conn->prepare($any_query);
    if (!$any_stmt) {
        error_log("PREPARE ERROR (any query): " . $conn->error);
    } else {
        $any_stmt->bind_param("ss", $today, $current_time_mysql);
        $any_stmt->execute();
        
        if ($any_stmt->error) {
            error_log("EXECUTE ERROR (any query): " . $any_stmt->error);
        } else {
            $any_result = $any_stmt->get_result();
            error_log("Any task query executed. Found " . $any_result->num_rows . " tasks due today.");
            
            if ($any_result->num_rows > 0) {
                error_log("SUCCESS: Found a task due today (last resort)! Sending notification...");
                $result = $any_result;
                $method_used = "Any task (last resort)";
                goto send_notification;
            } else {
                error_log("No tasks found for today at all. Nothing to notify about.");
                exit;
            }
        }
    }
} catch (Exception $e) {
    error_log("ERROR in any task query: " . $e->getMessage());
    exit;
}

// LABEL FOR NOTIFICATION PROCESSING
send_notification:

// Process each task found
while ($current_task = $result->fetch_assoc()) {
    // Log task found
    error_log("SENDING NOTIFICATION for task: #{$current_task['id']} - \"{$current_task['title']}\" due at {$current_task['due_time']}");
    error_log("Method used to find task: {$method_used}");
    
    // Convert original due time to timestamp for comparison
    $task_timestamp = strtotime($current_task['due_date'] . ' ' . $current_task['due_time']);
    $time_diff = $task_timestamp - $now_timestamp;
    $minutes_diff = round($time_diff / 60);
    
    error_log("Task due in {$minutes_diff} minutes from now");
    
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
        
        // Subject line varies based on how close the task is to due time
        if ($minutes_diff < 0) {
            $mail->Subject = "⚠️ OVERDUE TASK: " . $current_task['title'];
        } elseif ($minutes_diff < 5) {
            $mail->Subject = "⏰ TASK DUE NOW: " . $current_task['title'];
        } else {
            $mail->Subject = "📌 TASK REMINDER: " . $current_task['title'] . " (due in " . $minutes_diff . " min)";
        }
        
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
            if ($tracking_stmt->execute()) {
                error_log("Tracking record inserted with ID: " . $conn->insert_id);
            } else {
                error_log("Error inserting tracking record: " . $tracking_stmt->error);
            }
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