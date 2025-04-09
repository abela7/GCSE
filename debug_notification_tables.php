<?php
/**
 * Debug Notification Tables
 * This script checks if the notification tracking table exists and creates it if not
 */

// Include database connection
require_once __DIR__ . '/includes/db_connect.php';

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Output basic HTML structure
echo '<!DOCTYPE html>
<html>
<head>
    <title>Debug Notification Tables</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Notification System Debug</h1>';

// 1. Check if the notification tracking table exists
$table_check = $conn->query("SHOW TABLES LIKE 'task_notification_tracking'");
if ($table_check->num_rows === 0) {
    echo '<div class="alert alert-warning">
        <strong>Warning:</strong> The task_notification_tracking table does not exist.
    </div>';
    
    // If asked to create the table
    if (isset($_POST['create_table'])) {
        $sql = "
            CREATE TABLE task_notification_tracking (
                id INT AUTO_INCREMENT PRIMARY KEY,
                task_id INT NOT NULL,
                notification_type VARCHAR(50) NOT NULL,
                sent_at DATETIME NOT NULL,
                INDEX (task_id),
                INDEX (notification_type)
            )
        ";
        
        if ($conn->query($sql)) {
            echo '<div class="alert alert-success">
                <strong>Success!</strong> The table has been created successfully.
            </div>';
        } else {
            echo '<div class="alert alert-danger">
                <strong>Error creating table:</strong> ' . $conn->error . '
            </div>';
        }
    } else {
        echo '<form method="post" class="mb-4">
            <button type="submit" name="create_table" class="btn btn-primary">
                Create Table
            </button>
        </form>';
    }
} else {
    echo '<div class="alert alert-success">
        <strong>Good news!</strong> The task_notification_tracking table exists.
    </div>';
    
    // Show table structure
    $result = $conn->query("DESCRIBE task_notification_tracking");
    if ($result) {
        echo '<h3>Table Structure:</h3>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Null</th>
                    <th>Key</th>
                    <th>Default</th>
                    <th>Extra</th>
                </tr>
            </thead>
            <tbody>';
        
        while ($row = $result->fetch_assoc()) {
            echo '<tr>';
            foreach ($row as $key => $value) {
                echo '<td>' . ($value ?: '<em>NULL</em>') . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    // Show sample records
    $result = $conn->query("SELECT * FROM task_notification_tracking ORDER BY sent_at DESC LIMIT 10");
    if ($result) {
        if ($result->num_rows > 0) {
            echo '<h3>Recent Records:</h3>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Task ID</th>
                        <th>Type</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>';
            
            while ($row = $result->fetch_assoc()) {
                echo '<tr>
                    <td>' . $row['id'] . '</td>
                    <td>' . $row['task_id'] . '</td>
                    <td>' . $row['notification_type'] . '</td>
                    <td>' . $row['sent_at'] . '</td>
                </tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<div class="alert alert-info">
                No records found in the task_notification_tracking table.
            </div>';
        }
    }
}

// Add a section to check for tasks due today
echo '<h3>Tasks Due Today</h3>';

$today = date('Y-m-d');
$current_time = date('H:i:s');
$window_start = date('H:i:s', strtotime('-15 minutes'));
$window_end = date('H:i:s', strtotime('+15 minutes'));

$sql = "
    SELECT 
        t.id, t.title, t.due_date, t.due_time, t.status,
        CASE WHEN tnt.id IS NOT NULL THEN 'Yes' ELSE 'No' END as notification_sent
    FROM 
        tasks t
    LEFT JOIN
        task_notification_tracking tnt ON t.id = tnt.task_id AND tnt.notification_type = 'due'
    WHERE 
        t.due_date = ? 
    ORDER BY 
        t.due_time
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-info">No tasks scheduled for today.</div>';
} else {
    echo '<table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Due Time</th>
                <th>Status</th>
                <th>Notification Sent</th>
                <th>Due In Window</th>
            </tr>
        </thead>
        <tbody>';
    
    while ($row = $result->fetch_assoc()) {
        $in_window = ($row['due_time'] >= $window_start && $row['due_time'] <= $window_end) ? 'Yes' : 'No';
        $time_class = $in_window ? 'table-warning' : '';
        
        echo '<tr class="' . $time_class . '">
            <td>' . $row['id'] . '</td>
            <td>' . htmlspecialchars($row['title']) . '</td>
            <td>' . date('h:i A', strtotime($row['due_time'])) . ' <small class="text-muted">(' . $row['due_time'] . ')</small></td>
            <td>' . $row['status'] . '</td>
            <td>' . $row['notification_sent'] . '</td>
            <td>' . $in_window . '</td>
        </tr>';
    }
    
    echo '</tbody></table>';
}

// Add test form to create a task due soon
echo '<div class="card mt-4">
    <div class="card-header bg-primary text-white">
        <h5>Create Test Task Due Soon</h5>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label for="task_title" class="form-label">Task Title</label>
                <input type="text" class="form-control" id="task_title" name="task_title" 
                    value="Test Task ' . date('h:i A') . '" required>
            </div>
            
            <div class="mb-3">
                <label for="minutes" class="form-label">Due in (minutes)</label>
                <input type="number" class="form-control" id="minutes" name="minutes" 
                    value="1" min="1" max="60">
            </div>
            
            <button type="submit" name="create_task" class="btn btn-success">
                Create Test Task
            </button>
        </form>
    </div>
</div>';

// Handle creating a test task
if (isset($_POST['create_task']) && isset($_POST['task_title']) && isset($_POST['minutes'])) {
    $title = $_POST['task_title'];
    $minutes = (int)$_POST['minutes'];
    $due_time = date('H:i:s', strtotime("+{$minutes} minutes"));
    
    // Find the first category
    $cat_result = $conn->query("SELECT id FROM task_categories LIMIT 1");
    $category_id = 1; // Default
    if ($cat_result && $cat_result->num_rows > 0) {
        $category_id = $cat_result->fetch_row()[0];
    }
    
    $insert_sql = "
        INSERT INTO tasks (title, description, priority, status, due_date, due_time, category_id, is_active)
        VALUES (?, 'Test task created for notification debugging', 'medium', 'pending', ?, ?, ?, 1)
    ";
    
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("sssi", $title, $today, $due_time, $category_id);
    
    if ($insert_stmt->execute()) {
        $task_id = $conn->insert_id;
        echo '<div class="alert alert-success mt-3">
            <strong>Success!</strong> Created task #' . $task_id . ' due at ' . date('h:i A', strtotime($due_time)) . '.
            <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-sm btn-primary ms-3">Refresh Page</a>
        </div>';
    } else {
        echo '<div class="alert alert-danger mt-3">
            <strong>Error:</strong> Failed to create task: ' . $insert_stmt->error . '
        </div>';
    }
}

// Add direct email test
echo '<div class="card mt-4 mb-4">
    <div class="card-header bg-info text-white">
        <h5>Send Direct Test Email</h5>
    </div>
    <div class="card-body">
        <form method="post">
            <button type="submit" name="run_script" class="btn btn-info">
                Run Notification Script Now
            </button>
        </form>
    </div>
</div>';

// Handle running the script directly
if (isset($_POST['run_script'])) {
    $output = shell_exec('php ' . __DIR__ . '/emails/cron/task_notifications.php 2>&1');
    
    echo '<div class="card mt-3">
        <div class="card-header bg-secondary text-white">
            <h5>Script Output</h5>
        </div>
        <div class="card-body">
            <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;">' . htmlspecialchars($output) . '</pre>
        </div>
    </div>';
}

// Add navigation links
echo '<div class="mt-4">
    <a href="pages/scheduled-notifications-test.php" class="btn btn-secondary">
        Back to Notification Dashboard
    </a>
    <a href="test_email_delivery.php" class="btn btn-warning ms-2">
        Email Delivery Test
    </a>
</div>';

echo '</div></body></html>';

// Close connection
$conn->close();
?> 