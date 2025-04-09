<?php
/**
 * Task Notification Script
 * This script checks for tasks that are due soon and sends notification emails
 * It should be run by a cron job every 5-15 minutes
 */

// Enable verbose error logging
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

// Expand the window even more to catch tasks within a 20-minute window of current time
$current_time = date('H:i:s');
$today = date('Y-m-d');

error_log("Current date: {$today}");
error_log("Current time: {$current_time}");

// MAJOR CHANGE: Completely bypass the time window checks and just get pending tasks for today
// Limit to 2 tasks per run to avoid notification flooding
$tasks_query = "
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
    WHERE 
        t.status IN ('pending', 'in_progress') 
        AND t.due_date = ?
        AND tnt.id IS NULL
    ORDER BY 
        t.due_time ASC, 
        FIELD(t.priority, 'high', 'medium', 'low')
    LIMIT 2
";

// Log the actual SQL before executing
error_log("SQL Query: " . str_replace(['?', '  '], [$today, ' '], $tasks_query));

try {
    $stmt = $conn->prepare($tasks_query);
    if (!$stmt) {
        error_log("PREPARE ERROR: " . $conn->error);
        exit;
    }
    
    $stmt->bind_param("s", $today);
    $stmt->execute();
    
    if ($stmt->error) {
        error_log("EXECUTE ERROR: " . $stmt->error);
        exit;
    }
    
    $result = $stmt->get_result();
    error_log("Query executed successfully. Found " . $result->num_rows . " tasks due for notification.");
    
    if ($result->num_rows === 0) {
        error_log("No due tasks found for notification. Exiting.");
        
        // DEBUG: List all tasks to see if we're missing something
        $all_tasks_query = "SELECT id, title, due_date, due_time, status FROM tasks ORDER BY due_date DESC, due_time ASC LIMIT 10";
        $all_tasks_result = $conn->query($all_tasks_query);
        
        if ($all_tasks_result && $all_tasks_result->num_rows > 0) {
            error_log("Recent tasks in database:");
            while($row = $all_tasks_result->fetch_assoc()) {
                error_log("Task #{$row['id']}: {$row['title']} - Due: {$row['due_date']} {$row['due_time']} - Status: {$row['status']}");
            }
        } else {
            error_log("No tasks found in database at all!");
        }
        
        exit;
    }
} catch (Exception $e) {
    error_log("SQL ERROR: " . $e->getMessage());
    exit;
}

// Process each task found
while ($current_task = $result->fetch_assoc()) {
    // Log task found
    error_log("Processing notification for task: {$current_task['id']} - {$current_task['title']} due at {$current_task['due_time']}");
    
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
            AND ((t.due_date < ? OR (t.due_date = ? AND t.due_time < ?)))
            AND t.id != ?
        ORDER BY 
            t.due_date ASC, 
            t.due_time ASC, 
            FIELD(t.priority, 'high', 'medium', 'low')
        LIMIT 5
    ";
    
    $overdue_stmt = $conn->prepare($overdue_tasks_query);
    $current_time_only = date('H:i:s');
    $overdue_stmt->bind_param("sssi", $today, $today, $current_time_only, $current_task['id']);
    $overdue_stmt->execute();
    $overdue_result = $overdue_stmt->get_result();
    
    $overdue_tasks = [];
    while ($task = $overdue_result->fetch_assoc()) {
        $task['due_time'] = $task['due_date'] != $today 
            ? date('M j, Y', strtotime($task['due_date'])) . ' at ' . date('h:i A', strtotime($task['due_time']))
            : date('h:i A', strtotime($task['due_time']));
        $overdue_tasks[] = $task;
    }
    error_log("Found " . count($overdue_tasks) . " overdue tasks");
    
    // Get other tasks for today
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
        LIMIT 5
    ";
    
    $upcoming_stmt = $conn->prepare($upcoming_tasks_query);
    $upcoming_stmt->bind_param("si", $today, $current_task['id']);
    $upcoming_stmt->execute();
    $upcoming_result = $upcoming_stmt->get_result();
    
    $upcoming_tasks = [];
    while ($task = $upcoming_result->fetch_assoc()) {
        $task['due_time'] = date('h:i A', strtotime($task['due_time']));
        $upcoming_tasks[] = $task;
    }
    error_log("Found " . count($upcoming_tasks) . " upcoming tasks");
    
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
    error_log("Email content generated successfully");
    
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
        $mail->addCustomHeader('X-Priority', '3'); // Normal priority
        $mail->addCustomHeader('X-Mailer', 'GCSE-Study-App-PHP-Mailer');
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress(SMTP_USERNAME); // Use SMTP_USERNAME instead of hardcoded email address
        error_log("Sending email to: " . SMTP_USERNAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Task Due Today: " . $current_task['title'];
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
        
        // Send the email
        if (!$mail->send()) {
            error_log("Email could not be sent: " . $mail->ErrorInfo);
            continue; // Skip to next task if email fails
        }
        
        error_log("Email sent successfully for task ID {$current_task['id']} at " . date('Y-m-d H:i:s'));
        
        // Record that notification has been sent - moved after successful email sending
        error_log("Updating notification tracking for task ID: " . $current_task['id']);
        
        // Check if task_notification_tracking table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'task_notification_tracking'");
        if ($table_check->num_rows == 0) {
            error_log("ERROR: task_notification_tracking table does not exist");
            
            // Try to create the table
            $create_table_sql = "
                CREATE TABLE IF NOT EXISTS task_notification_tracking (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    task_id INT NOT NULL,
                    notification_type VARCHAR(50) NOT NULL,
                    sent_at DATETIME NOT NULL,
                    INDEX (task_id),
                    INDEX (notification_type)
                )
            ";
            
            if ($conn->query($create_table_sql)) {
                error_log("Created task_notification_tracking table");
            } else {
                error_log("Failed to create table: " . $conn->error);
            }
        } else {
            error_log("task_notification_tracking table exists");
        }
        
        $tracking_query = "
            INSERT INTO task_notification_tracking (task_id, notification_type, sent_at)
            VALUES (?, 'due', NOW())
        ";
        
        $tracking_stmt = $conn->prepare($tracking_query);
        if (!$tracking_stmt) {
            error_log("ERROR preparing tracking statement: " . $conn->error);
        } else {
            $tracking_stmt->bind_param("i", $current_task['id']);
            if ($tracking_stmt->execute()) {
                error_log("Tracking record inserted successfully with ID: " . $conn->insert_id);
            } else {
                error_log("ERROR inserting tracking record: " . $tracking_stmt->error);
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