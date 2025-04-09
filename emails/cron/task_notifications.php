<?php
/**
 * Task Notifications Script
 * Shows all tasks due today and sends notifications for tasks due in the next 3 minutes
 * Uses tracking table to avoid duplicate notifications
 */

// Set timezone to London time
date_default_timezone_set('Europe/London');

// Set content type to HTML for browser display
header('Content-Type: text/html; charset=utf-8');

// Include required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../templates/task_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verify database connection
if ($conn->connect_error) {
    die("<p style='color:red'>DATABASE ERROR: " . $conn->connect_error . "</p>");
}

// Get current time
$today = date('Y-m-d');
$now_hour = (int)date('H');
$now_minute = (int)date('i');
$current_time = date('H:i:s');
$current_datetime = date('Y-m-d H:i:s');
$app_url = 'http://abel.abuneteklehaymanot.org';

// Track if any notifications were sent during this run
$notifications_sent = [];

// Start HTML output
echo "<!DOCTYPE html>
<html>
<head>
    <title>Task Notification Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .current { background-color: #d4edda; }
        .due-soon { background-color: #fff3cd; }
        .overdue { background-color: #f8d7da; }
        .debug-section { margin-bottom: 30px; border: 1px solid #ddd; padding: 10px; background-color: #f8f9fa; }
        .timezone-warning { background-color: #f8d7da; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .success-section { background-color: #d4edda; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .notification-sent { background-color: #d4edda; }
        .notification-skipped { background-color: #f8d7da; }
    </style>
</head>
<body>
    <h1>Task Notification System</h1>
    
    <div class='debug-section'>
        <h2>Current Time Information</h2>
        <p><strong>Server Date:</strong> " . date('Y-m-d') . "</p>
        <p><strong>Server Time:</strong> " . date('H:i:s') . " (" . date('h:i A') . ")</p>
        <p><strong>PHP Version:</strong> " . phpversion() . "</p>
        <p><strong>Timezone Setting:</strong> " . date_default_timezone_get() . "</p>
    </div>";

// Get ALL tasks due today
$query = "
    SELECT 
        t.id, 
        t.title, 
        t.description, 
        t.priority, 
        t.status,
        t.category_id,
        t.due_date, 
        t.due_time,
        CASE WHEN tc.name IS NOT NULL THEN tc.name ELSE 'Uncategorized' END AS category_name,
        (SELECT MAX(tnt.sent_at) FROM task_notification_tracking tnt WHERE tnt.task_id = t.id AND tnt.notification_type = 'due') AS last_notified
    FROM 
        tasks t
    LEFT JOIN 
        task_categories tc ON t.category_id = tc.id
    WHERE 
        t.due_date = ? AND t.status IN ('pending', 'in_progress')
    ORDER BY 
        t.due_time ASC
";

try {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

    $task_count = $result->num_rows;
    
    echo "<div class='debug-section'>
        <h2>Tasks Due Today (" . $task_count . " found)</h2>";
    
    if ($task_count > 0) {
        echo "<table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Due Time</th>
                <th>Due Time (Raw)</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Time Until Due</th>
                <th>Last Notified</th>
                <th>Notification Status</th>
            </tr>";
        
        while ($task = $result->fetch_assoc()) {
            // Extract time components
            $time_parts = explode(':', $task['due_time']);
            $task_hour = (int)$time_parts[0];
            $task_minute = (int)$time_parts[1];
            
            // Calculate next 3 minutes
            $min_plus_1 = ($now_minute + 1) % 60;
            $min_plus_2 = ($now_minute + 2) % 60;
            $min_plus_3 = ($now_minute + 3) % 60;
            
            // Handle hour rollover
            $hour_plus_1 = ($min_plus_1 < $now_minute) ? ($now_hour + 1) % 24 : $now_hour;
            $hour_plus_2 = ($min_plus_2 < $now_minute) ? ($now_hour + 1) % 24 : $now_hour;
            $hour_plus_3 = ($min_plus_3 < $now_minute) ? ($now_hour + 1) % 24 : $now_hour;
            
            // Check if due within next 3 minutes
            $due_within_window = false;
            $notification_reason = "";
            
            // Current minute
            if ($task_hour == $now_hour && $task_minute == $now_minute) {
                $due_within_window = true;
                $notification_reason = "Due RIGHT NOW";
            }
            // 1 minute from now
            else if ($task_hour == $hour_plus_1 && $task_minute == $min_plus_1) {
                $due_within_window = true;
                $notification_reason = "Due in 1 minute";
            }
            // 2 minutes from now
            else if ($task_hour == $hour_plus_2 && $task_minute == $min_plus_2) {
                $due_within_window = true;
                $notification_reason = "Due in 2 minutes";
            }
            // 3 minutes from now
            else if ($task_hour == $hour_plus_3 && $task_minute == $min_plus_3) {
                $due_within_window = true;
                $notification_reason = "Due in 3 minutes";
            }
            
            // Calculate time difference for display
            $task_time = strtotime($today . ' ' . $task['due_time']);
            $current_time_seconds = strtotime($today . ' ' . $current_time);
            $time_diff_seconds = $task_time - $current_time_seconds;
            $time_diff_minutes = round($time_diff_seconds / 60);
            
            // Check if task was recently notified (within last 5 minutes)
            $recently_notified = false;
            $last_notified_readable = "Never";
            
            if (!empty($task['last_notified'])) {
                $last_notified_time = strtotime($task['last_notified']);
                $time_since_notification = time() - $last_notified_time;
                $recently_notified = ($time_since_notification < 300); // 5 minutes = 300 seconds
                $last_notified_readable = date('h:i:s A', $last_notified_time);
            }
            
            // Determine if notification should be sent
            $should_notify = $due_within_window && !$recently_notified;
            
            // Prepare notification status message
            $notification_status = "";
            $row_class = "";
            
            if ($due_within_window) {
                if ($recently_notified) {
                    $notification_status = "SKIPPED - Already notified at " . $last_notified_readable;
                    $row_class = "notification-skipped";
                } else {
                    // Actually send the notification
                    if ($due_within_window) {
                        // Format task for notification
                        $current_task = [
                            'id' => $task['id'],
                            'title' => $task['title'],
                            'description' => $task['description'],
                            'priority' => $task['priority'],
                            'estimated_duration' => null,
                            'category_id' => $task['category_id'],
                            'due_date' => $task['due_date'],
                            'due_time' => date('h:i A', strtotime($task['due_time'])),
                            'category_name' => $task['category_name']
                        ];
                        
                        // Get overdue tasks with time difference
                        $overdue_tasks = [];
                        $overdue_query = "
                            SELECT 
                                t.id, 
                                t.title, 
                                t.description, 
                                t.priority, 
                                t.due_date, 
                                t.due_time,
                                CASE WHEN tc.name IS NOT NULL THEN tc.name ELSE 'Uncategorized' END AS category_name,
                                TIMESTAMPDIFF(MINUTE, CONCAT(t.due_date, ' ', t.due_time), NOW()) as minutes_overdue
                            FROM 
                                tasks t
                            LEFT JOIN 
                                task_categories tc ON t.category_id = tc.id
                            WHERE 
                                t.status IN ('pending', 'in_progress')
                                AND (
                                    (t.due_date < CURDATE()) 
                                    OR 
                                    (t.due_date = CURDATE() AND t.due_time < TIME(DATE_SUB(NOW(), INTERVAL 5 MINUTE)))
                                )
                                AND t.id != ?
                            ORDER BY 
                                t.due_date ASC, t.due_time ASC
                            LIMIT 3
                        ";
                        
                        $overdue_stmt = $conn->prepare($overdue_query);
                        $overdue_stmt->bind_param("i", $task['id']);
                        $overdue_stmt->execute();
                        $overdue_result = $overdue_stmt->get_result();
                        
                        while ($overdue_task = $overdue_result->fetch_assoc()) {
                            // Format the overdue time in a human-readable way
                            $minutes_overdue = $overdue_task['minutes_overdue'];
                            $overdue_text = '';
                            
                            if ($minutes_overdue < 60) {
                                $overdue_text = "You were supposed to do this {$minutes_overdue} minutes ago";
                            } else if ($minutes_overdue < 1440) { // Less than 24 hours
                                $hours = floor($minutes_overdue / 60);
                                $mins = $minutes_overdue % 60;
                                if ($mins > 0) {
                                    $overdue_text = "You were supposed to do this {$hours}h {$mins}m ago";
                                } else {
                                    $overdue_text = "You were supposed to do this {$hours} hours ago";
                                }
                            } else { // More than 24 hours
                                $days = floor($minutes_overdue / 1440);
                                $hours = floor(($minutes_overdue % 1440) / 60);
                                if ($hours > 0) {
                                    $overdue_text = "You were supposed to do this {$days}d {$hours}h ago";
                                } else {
                                    $overdue_text = "You were supposed to do this {$days} days ago";
                                }
                            }
                            
                            $overdue_task['overdue_text'] = $overdue_text;
                            $overdue_task['due_time'] = date('h:i A', strtotime($overdue_task['due_time']));
                            $overdue_tasks[] = $overdue_task;
                        }
                        
                        // Get upcoming tasks with time difference
                        $upcoming_tasks = [];
                        $upcoming_query = "
                            SELECT 
                                t.id, 
                                t.title, 
                                t.description, 
                                t.priority, 
                                t.due_date, 
                                t.due_time,
                                CASE WHEN tc.name IS NOT NULL THEN tc.name ELSE 'Uncategorized' END AS category_name,
                                TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(t.due_date, ' ', t.due_time)) as minutes_until_due
                            FROM 
                                tasks t
                            LEFT JOIN 
                                task_categories tc ON t.category_id = tc.id
                            WHERE 
                                t.status IN ('pending', 'in_progress')
                                AND (
                                    (t.due_date > CURDATE()) 
                                    OR 
                                    (t.due_date = CURDATE() AND t.due_time > TIME(DATE_ADD(NOW(), INTERVAL 5 MINUTE)))
                                )
                                AND t.id != ?
                            ORDER BY 
                                t.due_date ASC, t.due_time ASC
                            LIMIT 3
                        ";
                        
                        $upcoming_stmt = $conn->prepare($upcoming_query);
                        $upcoming_stmt->bind_param("i", $task['id']);
                        $upcoming_stmt->execute();
                        $upcoming_result = $upcoming_stmt->get_result();
                        
                        while ($upcoming_task = $upcoming_result->fetch_assoc()) {
                            // Format the upcoming time in a human-readable way
                            $minutes_until_due = $upcoming_task['minutes_until_due'];
                            $upcoming_text = '';
                            
                            if ($minutes_until_due < 60) {
                                $upcoming_text = "Due in {$minutes_until_due} minutes";
                            } else if ($minutes_until_due < 1440) { // Less than 24 hours
                                $hours = floor($minutes_until_due / 60);
                                $mins = $minutes_until_due % 60;
                                if ($mins > 0) {
                                    $upcoming_text = "Due in {$hours}h {$mins}m";
                                } else {
                                    $upcoming_text = "Due in {$hours} hours";
                                }
                            } else { // More than 24 hours
                                $days = floor($minutes_until_due / 1440);
                                $hours = floor(($minutes_until_due % 1440) / 60);
                                if ($hours > 0) {
                                    $upcoming_text = "Due in {$days}d {$hours}h";
                                } else {
                                    $upcoming_text = "Due in {$days} days";
                                }
                            }
                            
                            $upcoming_task['upcoming_text'] = $upcoming_text;
                            $upcoming_task['due_time'] = date('h:i A', strtotime($upcoming_task['due_time']));
                            $upcoming_tasks[] = $upcoming_task;
                        }
    
                        // Prepare email data
                        $emailData = [
                            'current_task' => $current_task,
                            'overdue_tasks' => $overdue_tasks,
                            'upcoming_tasks' => $upcoming_tasks,
                            'app_url' => $app_url
                        ];
                        
                        $email_sent = false;
                        $email_error = "";
                        
                        // Only actually send if not in debug-only mode
                        if (!isset($_GET['debug_only'])) {
                            // Generate email content
                            $notification_template = new TaskNotification();
                            $emailContent = $notification_template->generateEmail($emailData);
                            
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
                                $mail->Subject = $task['title'] . " is due";
                                $mail->Body = $emailContent;
                                $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
                                
                                $email_sent = $mail->send();
                                
                                if ($email_sent) {
                                    // Record in tracking table
                                    $tracking_query = "INSERT INTO task_notification_tracking (task_id, notification_type, sent_at) VALUES (?, 'due', NOW())";
                                    $tracking_stmt = $conn->prepare($tracking_query);
                                    $tracking_stmt->bind_param("i", $task['id']);
                                    $tracking_stmt->execute();
                                    
                                    $notifications_sent[] = $task['title'];
                                    $notification_status = "SENT - Notification sent at " . date('h:i:s A');
                                    $row_class = "notification-sent";
                                }
                            } catch (Exception $e) {
                                $email_error = $e->getMessage();
                                $notification_status = "ERROR - Failed to send: " . $email_error;
                            }
                        } else {
                            $notification_status = "WOULD SEND - Debug mode enabled";
                            $row_class = "due-soon";
                        }
                    }
                }
            } else {
                $notification_status = "Not due within 3-minute window";
            }
            
            // Determine row class based on time
            if (empty($row_class)) {
                if ($time_diff_minutes < 0) {
                    $row_class = 'overdue';
                } else if ($time_diff_minutes <= 3) {
                    $row_class = 'due-soon';
                } else if ($time_diff_minutes == 0) {
                    $row_class = 'current';
                }
            }
            
            // Format time difference
            if ($time_diff_minutes < 0) {
                $time_diff_display = abs($time_diff_minutes) . " minutes ago";
            } else if ($time_diff_minutes == 0) {
                $time_diff_display = "Right now";
            } else {
                $time_diff_display = "In " . $time_diff_minutes . " minutes";
            }
            
            echo "<tr class='{$row_class}'>
                <td>{$task['id']}</td>
                <td>{$task['title']}</td>
                <td>" . date('h:i A', strtotime($task['due_time'])) . "</td>
                <td>{$task['due_time']} ({$task_hour}:{$task_minute})</td>
                <td>{$task['category_name']}</td>
                <td>{$task['priority']}</td>
                <td>{$task['status']}</td>
                <td>{$time_diff_display}</td>
                <td>{$last_notified_readable}</td>
                <td>{$notification_status}</td>
            </tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No tasks due today.</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
}

// Show notifications sent during this run
if (!empty($notifications_sent)) {
    echo "<div class='success-section'>
        <h2>Notifications Sent During This Run</h2>
        <ul>";
    foreach ($notifications_sent as $task_title) {
        echo "<li>Sent notification for: {$task_title}</li>";
    }
    echo "</ul>
    </div>";
}

// Display recent notifications from tracking table
echo "<div class='debug-section'>
    <h2>Recent Notifications Sent</h2>";

        $tracking_query = "
    SELECT 
        tnt.id,
        tnt.task_id,
        t.title,
        tnt.notification_type,
        tnt.sent_at,
        TIMESTAMPDIFF(MINUTE, tnt.sent_at, NOW()) as minutes_ago
    FROM 
        task_notification_tracking tnt
    LEFT JOIN
        tasks t ON tnt.task_id = t.id
    ORDER BY
        tnt.sent_at DESC
    LIMIT 10
";

try {
    $tracking_result = $conn->query($tracking_query);
    
    if ($tracking_result && $tracking_result->num_rows > 0) {
        echo "<table>
            <tr>
                <th>ID</th>
                <th>Task ID</th>
                <th>Task Title</th>
                <th>Type</th>
                <th>Sent At</th>
                <th>Minutes Ago</th>
            </tr>";
        
        while ($row = $tracking_result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['task_id']}</td>
                <td>{$row['title']}</td>
                <td>{$row['notification_type']}</td>
                <td>{$row['sent_at']}</td>
                <td>{$row['minutes_ago']}</td>
            </tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No notification history found.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
}

echo "</div>

<div class='debug-section'>
    <h2>Debug Tools</h2>
    <p>Control how this script runs:</p>
    <ul>
        <li><a href='?debug_only=1'>Debug Mode</a> - Shows what would happen without sending emails</li>
        <li><a href='?'>Normal Mode</a> - Sends actual notifications for tasks due soon</li>
        <li><a href='../force_task_notification.php'>Force Notification Tool</a> - Manually send a notification</li>
    </ul>
</div>

</body>
</html>";

$conn->close();
?> 