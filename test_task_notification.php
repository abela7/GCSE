<?php
/**
 * Test Task Notification Script
 * This script allows manually testing the task notification email
 */

// Include required files
require_once __DIR__ . '/config/email_config.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/emails/templates/task_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Application URL for links in emails
$app_url = 'http://abel.abuneteklehaymanot.org';

// Add basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Task Notification Test - Amha-Silassie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Task Notification Test - Amha-Silassie</h1>';

// Get all tasks for today or select a specific task
$task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : null;

// If form submitted, send a test notification
if (isset($_POST['send_test']) && isset($_POST['task_id'])) {
    $task_id = (int)$_POST['task_id'];
    
    // Get the task details
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
                AND t.due_date < ?
                AND t.id != ?
            ORDER BY 
                t.due_date ASC, 
                t.due_time ASC, 
                FIELD(t.priority, 'high', 'medium', 'low')
            LIMIT 3
        ";
        
        $today = date('Y-m-d');
        $overdue_stmt = $conn->prepare($overdue_tasks_query);
        $overdue_stmt->bind_param("si", $today, $task_id);
        $overdue_stmt->execute();
        $overdue_result = $overdue_stmt->get_result();
        
        $overdue_tasks = [];
        while ($task = $overdue_result->fetch_assoc()) {
            $task['due_time'] = date('M j, Y', strtotime($task['due_date'])) . ' at ' . date('h:i A', strtotime($task['due_time']));
            $overdue_tasks[] = $task;
        }
        
        // Get upcoming tasks
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
                AND t.id != ?
            ORDER BY 
                t.due_time ASC, 
                FIELD(t.priority, 'high', 'medium', 'low')
            LIMIT 3
        ";
        
        $upcoming_stmt = $conn->prepare($upcoming_tasks_query);
        $upcoming_stmt->bind_param("si", $today, $task_id);
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
            
            // Add custom headers to reduce spam score
            $mail->XMailer = 'GCSE Study App Mailer';
            $mail->addCustomHeader('X-Application', 'GCSE Study App');
            $mail->addCustomHeader('X-Domain-ID', 'abel.abuneteklehaymanot.org');
            
            // Recipients
            $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
            $mail->addAddress('Abelgoytom77@gmail.com'); // Send to Abel's Gmail
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "TEST - Task Due: " . $current_task['title'];
            $mail->Body = $emailContent;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
            
            // Send the email
            $mail->send();
            echo '<div class="alert alert-success">
                    <h4 class="alert-heading">Success!</h4>
                    <p>Test task notification email sent successfully! Please check your inbox at Abelgoytom77@gmail.com</p>
                    <hr>
                    <p class="mb-0">If you don\'t see the email, please check your spam folder.</p>
                  </div>';
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">
                    <h4 class="alert-heading">Error!</h4>
                    <p>Email could not be sent. Error details:</p>
                    <pre>' . $mail->ErrorInfo . '</pre>
                  </div>';
        }
    }
}

// Display form to select a task
echo '<div class="card">
        <div class="card-header">
            <h2>Send Test Task Notification</h2>
        </div>
        <div class="card-body">
            <form method="post" action="test_task_notification.php">
                <div class="mb-3">
                    <label for="task_id" class="form-label">Select a Task:</label>
                    <select name="task_id" id="task_id" class="form-select" required>';

// Get tasks to populate dropdown
$tasks_query = "
    SELECT 
        t.id, 
        t.title, 
        t.due_date, 
        t.due_time,
        t.priority,
        CASE WHEN tc.name IS NOT NULL THEN tc.name ELSE 'Uncategorized' END AS category_name
    FROM 
        tasks t
    LEFT JOIN 
        task_categories tc ON t.category_id = tc.id
    WHERE 
        t.status IN ('pending', 'in_progress')
    ORDER BY 
        t.due_date ASC, 
        t.due_time ASC
    LIMIT 20
";

$tasks_result = $conn->query($tasks_query);
if ($tasks_result->num_rows > 0) {
    while ($task = $tasks_result->fetch_assoc()) {
        $selected = ($task_id == $task['id']) ? 'selected' : '';
        $due_time = $task['due_time'] ? date('h:i A', strtotime($task['due_time'])) : 'No time';
        echo '<option value="' . $task['id'] . '" ' . $selected . '>' . 
             htmlspecialchars($task['title']) . ' - ' . 
             date('M j, Y', strtotime($task['due_date'])) . ' at ' . $due_time . 
             ' (' . ucfirst($task['priority']) . ' priority, ' . $task['category_name'] . ')' .
             '</option>';
    }
} else {
    echo '<option value="">No tasks found</option>';
}

echo '          </select>
                </div>
                <button type="submit" name="send_test" class="btn btn-primary">Send Test Notification</button>
            </form>
        </div>
    </div>
    
    <div class="mt-4 text-center">
        <a href="index.php" class="btn btn-secondary">Return to Dashboard</a>
    </div>';

echo '</div>
</body>
</html>';
?> 