<?php
/**
 * Task Notifications Debug Script
 * Shows all tasks due today and their times
 */

// Set content type to HTML for browser display
header('Content-Type: text/html; charset=utf-8');

// Include required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../../includes/db_connect.php';

// Verify database connection
if ($conn->connect_error) {
    die("<p style='color:red'>DATABASE ERROR: " . $conn->connect_error . "</p>");
}

// Get current time
$today = date('Y-m-d');
$now_hour = (int)date('H');
$now_minute = (int)date('i');
$current_time = date('H:i:s');

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
    </style>
</head>
<body>
    <h1>Task Notification Debug</h1>
    
    <div class='debug-section'>
        <h2>Current Time Information</h2>
        <p><strong>Server Date:</strong> " . date('Y-m-d') . "</p>
        <p><strong>Server Time:</strong> " . date('H:i:s') . " (" . date('h:i A') . ")</p>
        <p><strong>PHP Version:</strong> " . phpversion() . "</p>
        <p><strong>Timezone:</strong> " . date_default_timezone_get() . "</p>
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
        CASE WHEN tc.name IS NOT NULL THEN tc.name ELSE 'Uncategorized' END AS category_name
    FROM 
        tasks t
    LEFT JOIN 
        task_categories tc ON t.category_id = tc.id
    WHERE 
        t.due_date = ?
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
                <th>Would Notify?</th>
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
            $would_notify = false;
            $notification_reason = "";
            
            // Current minute
            if ($task_hour == $now_hour && $task_minute == $now_minute) {
                $would_notify = true;
                $notification_reason = "Due RIGHT NOW";
            }
            // 1 minute from now
            else if ($task_hour == $hour_plus_1 && $task_minute == $min_plus_1) {
                $would_notify = true;
                $notification_reason = "Due in 1 minute";
            }
            // 2 minutes from now
            else if ($task_hour == $hour_plus_2 && $task_minute == $min_plus_2) {
                $would_notify = true;
                $notification_reason = "Due in 2 minutes";
            }
            // 3 minutes from now
            else if ($task_hour == $hour_plus_3 && $task_minute == $min_plus_3) {
                $would_notify = true;
                $notification_reason = "Due in 3 minutes";
            }
            
            // Calculate time difference for display
            $task_time = strtotime($today . ' ' . $task['due_time']);
            $current_time_seconds = strtotime($today . ' ' . $current_time);
            $time_diff_seconds = $task_time - $current_time_seconds;
            $time_diff_minutes = round($time_diff_seconds / 60);
            
            // Determine row class based on time
            $row_class = '';
            if ($time_diff_minutes < 0) {
                $row_class = 'overdue';
            } else if ($time_diff_minutes <= 3) {
                $row_class = 'due-soon';
            } else if ($time_diff_minutes == 0) {
                $row_class = 'current';
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
                <td>" . ($would_notify ? "YES - {$notification_reason}" : "NO") . "</td>
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

// Display recent notifications from tracking table
echo "<div class='debug-section'>
    <h2>Recent Notifications Sent</h2>";

$tracking_query = "
    SELECT 
        tnt.id,
        tnt.task_id,
        t.title,
        tnt.notification_type,
        tnt.sent_at
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
            </tr>";
        
        while ($row = $tracking_result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['task_id']}</td>
                <td>{$row['title']}</td>
                <td>{$row['notification_type']}</td>
                <td>{$row['sent_at']}</td>
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
    <h2>Manual Notification Test</h2>
    <p>Use this form to send a test notification for a specific task:</p>
    <form method='post' action='../force_task_notification.php'>
        <button type='submit'>Go to Manual Notification Tool</button>
    </form>
</div>

</body>
</html>";

// Continue with normal script execution for cron
if (php_sapi_name() == 'cli' || isset($_GET['run_cron'])) {
    // The rest of the notification script for cron mode
    // would go here, but we're focusing on debug output for now
}

$conn->close();
?> 