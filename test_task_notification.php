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

// Test data
$testData = [
    'current_task' => [
        'id' => 1,
        'title' => 'Complete Math Assignment',
        'description' => 'Solve problems from Chapter 5: Quadratic Equations',
        'priority' => 'high',
        'due_time' => 'Now',
        'estimated_duration' => '45'
    ],
    'upcoming_tasks' => [
        [
            'id' => 2,
            'title' => 'Physics Lab Report',
            'description' => 'Write up the results from today\'s experiment',
            'priority' => 'medium',
            'due_time' => '06:00 PM',
            'estimated_duration' => '30'
        ],
        [
            'id' => 3,
            'title' => 'English Essay Review',
            'description' => 'Final proofreading of Shakespeare analysis',
            'priority' => 'low',
            'due_time' => '08:00 PM',
            'estimated_duration' => '20'
        ]
    ],
    'overdue_tasks' => [
        [
            'id' => 4,
            'title' => 'Chemistry Homework',
            'description' => 'Complete exercises from Chapter 3: Chemical Bonds',
            'priority' => 'high',
            'due_time' => 'Yesterday at 03:00 PM',
            'estimated_duration' => '40'
        ]
    ]
];

// Add app URL to the test data
$testData['app_url'] = $app_url;

// Create instance of TaskNotification
$notification = new TaskNotification();

// Generate email content
$emailContent = $notification->generateEmail($testData);

// Create PHPMailer instance
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
    
    // Anti-spam measures
    $mail->XMailer = 'GCSE Study App Mailer';
    $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
    $mail->addCustomHeader('Precedence', 'bulk');
    $mail->addCustomHeader('X-Priority', '3'); // Normal priority
    $mail->addCustomHeader('X-Mailer', 'GCSE-Study-App-PHP-Mailer');
    
    // Make sure subject isn't too spammy
    $mail->Subject = 'Task Reminder: ' . substr($testData['current_task']['title'], 0, 40);
    
    // Add a text version to reduce spam score
    $textContent = strip_tags(str_replace(['<br>', '<p>', '</p>', '<div>', '</div>'], ["\n", "\n", "\n", "\n", "\n"], $emailContent));
    $mail->AltBody = $textContent;
    
    // Recipients - only send to the test recipient
    $mail->setFrom(SMTP_USERNAME, 'Amha-Silassie Study App');
    $mail->addAddress(SMTP_USERNAME);
    
    // Content
    $mail->isHTML(true);
    $mail->Body = $emailContent;
    
    $mail->send();
    echo '<div class="alert alert-success mb-4">Test email sent successfully!</div>';
} catch (Exception $e) {
    echo '<div class="alert alert-danger mb-4">Message could not be sent. Mailer Error: ' . $mail->ErrorInfo . '</div>';
}

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
            
            // Anti-spam measures
            $mail->XMailer = 'GCSE Study App Mailer';
            $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
            $mail->addCustomHeader('Precedence', 'bulk');
            $mail->addCustomHeader('X-Priority', '3'); // Normal priority
            $mail->addCustomHeader('X-Mailer', 'GCSE-Study-App-PHP-Mailer');
            
            // Make sure subject isn't too spammy
            $mail->Subject = 'Task Reminder: ' . substr($current_task['title'], 0, 40);
            
            // Add a text version to reduce spam score
            $textContent = strip_tags(str_replace(['<br>', '<p>', '</p>', '<div>', '</div>'], ["\n", "\n", "\n", "\n", "\n"], $emailContent));
            $mail->AltBody = $textContent;
            
            // Recipients - only send to the test recipient
            $mail->setFrom(SMTP_USERNAME, 'Amha-Silassie Study App');
            $mail->addAddress(SMTP_USERNAME);
            
            // Content
            $mail->isHTML(true);
            $mail->Body = $emailContent;
            
            $mail->send();
            echo '<div class="alert alert-success mb-4">Test email sent successfully!</div>';
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger mb-4">Message could not be sent. Mailer Error: ' . $mail->ErrorInfo . '</div>';
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