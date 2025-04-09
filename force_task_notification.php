<?php
/**
 * Force Task Notification
 * This script sends a notification for a specific task ID, bypassing time/status checks
 */

// Display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include required files
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/email_config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/emails/templates/task_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Basic HTML
echo '<!DOCTYPE html>
<html>
<head>
    <title>Force Task Notification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        pre { background-color: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Force Task Notification</h1>';

// Form to select a task
if (!isset($_POST['task_id']) && !isset($_GET['task_id'])) {
    // List all pending tasks
    $query = "SELECT id, title, due_date, due_time FROM tasks 
              WHERE status IN ('pending', 'in_progress') 
              ORDER BY due_date DESC, due_time ASC 
              LIMIT 20";
    
    $result = $conn->query($query);
    
    echo '<div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5>Select a Task to Send Notification</h5>
        </div>
        <div class="card-body">';
    
    if ($result && $result->num_rows > 0) {
        echo '<form method="post">
            <div class="mb-3">
                <label for="task_id" class="form-label">Task</label>
                <select class="form-select" id="task_id" name="task_id" required>';
        
        while ($row = $result->fetch_assoc()) {
            $task_time = !empty($row['due_time']) ? ' at ' . date('h:i A', strtotime($row['due_time'])) : '';
            echo '<option value="' . $row['id'] . '">ID ' . $row['id'] . ': ' . 
                htmlspecialchars($row['title']) . ' (Due: ' . $row['due_date'] . $task_time . ')</option>';
        }
        
        echo '</select>
            </div>
            <div class="mb-3">
                <label for="recipient" class="form-label">Send To</label>
                <input type="email" class="form-control" id="recipient" name="recipient" 
                    value="' . htmlspecialchars(SMTP_USERNAME) . '" required>
                <small class="form-text text-muted">Email address to receive the notification</small>
            </div>
            <button type="submit" class="btn btn-primary">Send Notification</button>
        </form>';
    } else {
        echo '<div class="alert alert-warning">No pending tasks found.</div>';
    }
    
    echo '</div></div>';
    
    // Option to create test task
    echo '<div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5>Create Test Task</h5>
        </div>
        <div class="card-body">
            <form method="post" action="debug_notification_tables.php">
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
                <button type="submit" name="create_task" class="btn btn-success">Create Test Task</button>
            </form>
        </div>
    </div>';
} else {
    // Process notification request
    $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : intval($_GET['task_id']);
    $recipient = isset($_POST['recipient']) ? $_POST['recipient'] : SMTP_USERNAME;
    
    echo '<div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5>Sending Notification for Task #' . $task_id . '</h5>
        </div>
        <div class="card-body">';
    
    // Get task details
    $task_query = "
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
            t.id = ?
    ";
    
    $stmt = $conn->prepare($task_query);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo '<div class="alert alert-danger">Task not found!</div>';
    } else {
        $current_task = $result->fetch_assoc();
        
        // Format task time for display
        $current_task['due_time'] = date('h:i A', strtotime($current_task['due_time']));
        
        // Display task info
        echo '<div class="alert alert-info">
            <strong>Task:</strong> ' . htmlspecialchars($current_task['title']) . '<br>
            <strong>Due:</strong> ' . $current_task['due_date'] . ' ' . $current_task['due_time'] . '<br>
            <strong>Priority:</strong> ' . ucfirst($current_task['priority']) . '<br>
            <strong>Sending to:</strong> ' . htmlspecialchars($recipient) . '
        </div>';
        
        // Get other tasks for the notification context
        $today = date('Y-m-d');
        
        // Get overdue tasks
        $overdue_tasks = [];
        $current_time_only = date('H:i:s');
        $overdue_query = "
            SELECT t.id, t.title, t.description, t.priority, t.due_date, t.due_time,
                CASE WHEN tc.name IS NOT NULL THEN tc.name ELSE 'Uncategorized' END AS category_name,
                t.estimated_duration
            FROM tasks t
            LEFT JOIN task_categories tc ON t.category_id = tc.id
            WHERE t.status IN ('pending', 'in_progress')
                AND ((t.due_date < ? OR (t.due_date = ? AND t.due_time < ?)))
                AND t.id != ?
            ORDER BY t.due_date ASC, t.due_time ASC
            LIMIT 3
        ";
        
        $stmt = $conn->prepare($overdue_query);
        $stmt->bind_param("sssi", $today, $today, $current_time_only, $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($task = $result->fetch_assoc()) {
            $task['due_time'] = $task['due_date'] != $today 
                ? date('M j, Y', strtotime($task['due_date'])) . ' at ' . date('h:i A', strtotime($task['due_time']))
                : date('h:i A', strtotime($task['due_time']));
            $overdue_tasks[] = $task;
        }
        
        // Get upcoming tasks
        $upcoming_tasks = [];
        $upcoming_query = "
            SELECT t.id, t.title, t.description, t.priority, t.due_date, t.due_time,
                CASE WHEN tc.name IS NOT NULL THEN tc.name ELSE 'Uncategorized' END AS category_name,
                t.estimated_duration
            FROM tasks t
            LEFT JOIN task_categories tc ON t.category_id = tc.id
            WHERE t.status IN ('pending', 'in_progress')
                AND t.due_date = ?
                AND t.due_time > ?
                AND t.id != ?
            ORDER BY t.due_time ASC
            LIMIT 3
        ";
        
        $stmt = $conn->prepare($upcoming_query);
        $stmt->bind_param("ssi", $today, $current_time_only, $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($task = $result->fetch_assoc()) {
            $task['due_time'] = date('h:i A', strtotime($task['due_time']));
            $upcoming_tasks[] = $task;
        }
        
        // Prepare email data
        $app_url = 'http://abel.abuneteklehaymanot.org';
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
            $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
            ob_start(); // Capture debug output
            $mail->Debugoutput = function($str, $level) {
                echo htmlspecialchars($str) . "<br>\n";
            };
            
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = SMTP_AUTH;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            
            // Set timeout values
            $mail->Timeout = 30; // 30 seconds timeout
            $mail->SMTPKeepAlive = true; // Keep connection open
            
            // Anti-spam measures
            $mail->XMailer = 'GCSE Study App Mailer';
            $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
            $mail->addCustomHeader('Precedence', 'bulk');
            $mail->addCustomHeader('X-Priority', '3'); // Normal priority
            
            // Recipients
            $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
            $mail->addAddress($recipient);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "Task Due Now: " . $current_task['title'];
            $mail->Body = $emailContent;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
            
            // Send the email
            if ($mail->send()) {
                echo '<div class="alert alert-success">Email notification sent successfully!</div>';
                
                // Record that notification has been sent
                $tracking_query = "
                    INSERT INTO task_notification_tracking (task_id, notification_type, sent_at)
                    VALUES (?, 'due', NOW())
                ";
                
                $tracking_stmt = $conn->prepare($tracking_query);
                if (!$tracking_stmt) {
                    echo '<div class="alert alert-warning">Error preparing tracking statement: ' . $conn->error . '</div>';
                } else {
                    $tracking_stmt->bind_param("i", $task_id);
                    if ($tracking_stmt->execute()) {
                        echo '<div class="alert alert-success">Tracking record inserted successfully with ID: ' . $conn->insert_id . '</div>';
                    } else {
                        echo '<div class="alert alert-warning">Error inserting tracking record: ' . $tracking_stmt->error . '</div>';
                    }
                }
            } else {
                echo '<div class="alert alert-danger">Message could not be sent.</div>';
            }
            
            // Display debug output
            echo '<h6 class="mt-3">Debug Output:</h6>';
            echo '<pre>' . ob_get_clean() . '</pre>';
            
        } catch (Exception $e) {
            // Display any buffered debug output
            $debug_output = ob_get_clean();
            if (!empty($debug_output)) {
                echo '<h6 class="mt-3">Debug Output:</h6>';
                echo '<pre>' . $debug_output . '</pre>';
            }
            
            echo '<div class="alert alert-danger">
                <strong>Mailer Error:</strong> ' . $e->getMessage() . '
            </div>';
        }
    }
    
    echo '</div></div>';
}

// Navigation links
echo '<div class="mt-4">
    <a href="debug_email_connection.php" class="btn btn-secondary">
        Email Connection Test
    </a>
    <a href="debug_notification_tables.php" class="btn btn-secondary ms-2">
        Debug Notification Tables
    </a>
    <a href="pages/scheduled-notifications-test.php" class="btn btn-secondary ms-2">
        Notification Dashboard
    </a>
</div>';

echo '</div></body></html>';
?> 