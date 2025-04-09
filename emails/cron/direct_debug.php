<?php
/**
 * Direct Task Notification Debugging
 * This script helps debug why scheduled tasks aren't sending notifications
 */

// Include database connection
require_once __DIR__ . '/../../includes/db_connect.php';

// Output basic HTML
echo '<!DOCTYPE html>
<html>
<head>
    <title>Notification Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
        tr.notify { background-color: #dff0d8; }
        tr.now { background-color: #fcf8e3; }
        tr.past { background-color: #f2dede; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Task Notification System Debug</h1>';

// Get current time info
$current_time = date('H:i:s');
$one_min_ago = date('H:i:s', strtotime("-1 minute"));
$five_min_future = date('H:i:s', strtotime("+5 minutes"));
$today = date('Y-m-d');

echo "<h2>Time Information</h2>
<p><strong>Current Date:</strong> {$today}</p>
<p><strong>Current Time:</strong> {$current_time}</p>
<p><strong>Notification Window:</strong> {$one_min_ago} to {$five_min_future}</p>";

// Show tasks due today
$tasks_query = "
    SELECT 
        t.id, 
        t.title, 
        t.description, 
        t.priority, 
        t.due_date, 
        t.due_time,
        t.status,
        CASE WHEN tc.name IS NOT NULL THEN tc.name ELSE 'Uncategorized' END AS category_name,
        CASE WHEN tnt.id IS NOT NULL THEN 'Yes' ELSE 'No' END as notification_sent
    FROM 
        tasks t
    LEFT JOIN 
        task_categories tc ON t.category_id = tc.id
    LEFT JOIN 
        task_notification_tracking tnt ON t.id = tnt.task_id AND tnt.notification_type = 'due'
    WHERE 
        t.due_date = ?
    ORDER BY 
        t.due_time ASC
";

$stmt = $conn->prepare($tasks_query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Tasks Due Today</h2>";

if ($result->num_rows === 0) {
    echo "<p>No tasks scheduled for today.</p>";
} else {
    echo "<table>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Due Time</th>
            <th>Status</th>
            <th>Notification</th>
            <th>Eligible</th>
        </tr>";
    
    while ($task = $result->fetch_assoc()) {
        $due_time = $task['due_time'];
        
        // Determine if this task is eligible for notification
        $eligible = 'No';
        $row_class = '';
        
        if ($task['notification_sent'] === 'No' && $task['status'] === 'pending') {
            if ($due_time >= $one_min_ago && $due_time <= $five_min_future) {
                $eligible = 'Yes - In notification window!';
                $row_class = 'notify';
            } elseif ($due_time < $current_time) {
                $eligible = 'No - Time passed';
                $row_class = 'past';
            } elseif ($due_time > $five_min_future) {
                $eligible = 'No - Due later';
            }
        } elseif ($task['notification_sent'] === 'Yes') {
            $eligible = 'No - Already notified';
        } elseif ($task['status'] !== 'pending') {
            $eligible = 'No - Not pending';
        }
        
        // Highlight current tasks
        if ($due_time <= $current_time && $due_time >= $one_min_ago) {
            $row_class = 'now';
        }
        
        echo "<tr class=\"{$row_class}\">
            <td>{$task['id']}</td>
            <td>{$task['title']}</td>
            <td>" . date('h:i A', strtotime($due_time)) . " ({$due_time})</td>
            <td>{$task['status']}</td>
            <td>{$task['notification_sent']}</td>
            <td>{$eligible}</td>
        </tr>";
    }
    
    echo "</table>";
}

// Create a test task section
echo "<h2>Create Test Task</h2>
<form method='post'>
    <p>
        <label>Task Title: <input type='text' name='task_title' value='Debug Test " . date('h:i A') . "' required></label>
    </p>
    <p>
        <label>Minutes from now: <input type='number' name='minutes' value='2' min='1' max='60'></label>
    </p>
    <p>
        <button type='submit' name='create_task'>Create Test Task</button>
    </p>
</form>";

// Handle test task creation
if (isset($_POST['create_task'])) {
    $title = $_POST['task_title'];
    $minutes = (int)$_POST['minutes'];
    $due_time = date('H:i:s', strtotime("+{$minutes} minutes"));
    
    // Find a category
    $cat_query = "SELECT id FROM task_categories LIMIT 1";
    $cat_result = $conn->query($cat_query);
    $category_id = 1;
    
    if ($cat_result && $cat_result->num_rows > 0) {
        $category_id = $cat_result->fetch_assoc()['id'];
    }
    
    $sql = "INSERT INTO tasks (title, description, priority, status, due_date, due_time, category_id, is_active) 
            VALUES (?, 'Debug test task', 'medium', 'pending', ?, ?, ?, 1)";
    
    $insert_stmt = $conn->prepare($sql);
    $insert_stmt->bind_param("sssi", $title, $today, $due_time, $category_id);
    
    if ($insert_stmt->execute()) {
        $task_id = $conn->insert_id;
        echo "<p style='color:green'>Created task #{$task_id} due at " . date('h:i A', strtotime($due_time)) . 
             ". <a href='direct_debug.php'>Refresh</a> to see it in the list.</p>";
    } else {
        echo "<p style='color:red'>Error creating task: " . $insert_stmt->error . "</p>";
    }
}

// Test run section
echo "<h2>Test Run Notification Script</h2>
<form method='post'>
    <button type='submit' name='run_script'>Run Notification Script Now</button>
</form>";

// Handle script run
if (isset($_POST['run_script'])) {
    // Run the notification script and capture output
    $output = shell_exec('php ' . __DIR__ . '/task_notifications.php 2>&1');
    
    echo "<h3>Output:</h3>
    <pre>" . htmlspecialchars($output) . "</pre>";
}

// Show notification tracking table info
$tracking_query = "SELECT COUNT(*) as count FROM task_notification_tracking";
$tracking_result = $conn->query($tracking_query);

echo "<h2>Notification Tracking Table</h2>";

if ($tracking_result && $tracking_result->num_rows > 0) {
    $count = $tracking_result->fetch_assoc()['count'];
    echo "<p>{$count} notification records in tracking table</p>";
    
    if ($count > 0) {
        $recent_query = "SELECT task_id, notification_type, sent_at FROM task_notification_tracking 
                          ORDER BY sent_at DESC LIMIT 5";
        $recent_result = $conn->query($recent_query);
        
        echo "<h3>Recent Notifications:</h3>
        <table>
            <tr>
                <th>Task ID</th>
                <th>Type</th>
                <th>Sent At</th>
            </tr>";
        
        while ($row = $recent_result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['task_id']}</td>
                <td>{$row['notification_type']}</td>
                <td>{$row['sent_at']}</td>
            </tr>";
        }
        
        echo "</table>";
    }
} else {
    echo "<p>Error accessing tracking table or table doesn't exist.</p>";
}

echo "</body></html>";
?> 