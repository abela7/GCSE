<?php
/**
 * Habit Notification Script
 * This script checks for habits that are due soon and sends notification emails
 * It should be run by a cron job every 5-15 minutes
 */

// Enable verbose error logging
error_log("==== HABIT NOTIFICATION SCRIPT STARTED ====");
error_log("Script running at: " . date('Y-m-d H:i:s'));

// Include required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../templates/habit_notification.php';

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
    error_log("Habit notifications are disabled in config. Exiting.");
    exit;
}

// Get current time plus buffer (for habits due within the next 5 minutes)
$buffer_minutes = 5; // Changed from 15 to 5 minutes
$current_time = date('H:i:s');
$notification_window_start = date('H:i:s');
$notification_window_end = date('H:i:s', strtotime("+{$buffer_minutes} minutes"));
$today = date('Y-m-d');

error_log("Checking for habits due between {$notification_window_start} and {$notification_window_end}");
error_log("Current time is: {$current_time}");

// Find habits that are due within the notification window
$habits_query = "
    SELECT 
        h.id, 
        h.name AS title, 
        h.description, 
        hpr.completion_points AS points,
        h.target_time AS due_time,
        h.category_id,
        CASE 
            WHEN hpr.completion_points >= 20 THEN 'high'
            WHEN hpr.completion_points >= 10 THEN 'medium'
            ELSE 'low'
        END AS priority,
        CASE 
            WHEN hc.name IS NOT NULL THEN hc.name 
            ELSE 'Uncategorized' 
        END AS category_name
    FROM 
        habits h
    LEFT JOIN 
        habit_categories hc ON h.category_id = hc.id
    LEFT JOIN
        habit_point_rules hpr ON h.point_rule_id = hpr.id
    LEFT JOIN 
        habit_completions hcp ON h.id = hcp.habit_id AND hcp.completion_date = ?
    LEFT JOIN
        task_notification_tracking tnt ON h.id = tnt.task_id AND tnt.notification_type = 'habit' AND DATE(tnt.sent_at) = ?
    WHERE 
        h.is_active = 1
        AND hcp.id IS NULL -- Not completed today
        AND h.target_time BETWEEN ? AND ?
        AND tnt.id IS NULL -- No notification sent today
    ORDER BY 
        h.target_time ASC
";

// Log the actual SQL before executing
error_log("SQL Query: " . str_replace(['?', '  '], [$today, ' '], $habits_query));

try {
    $stmt = $conn->prepare($habits_query);
    if (!$stmt) {
        error_log("PREPARE ERROR: " . $conn->error);
        exit;
    }
    
    $stmt->bind_param("ssss", $today, $today, $notification_window_start, $notification_window_end);
    $stmt->execute();
    
    if ($stmt->error) {
        error_log("EXECUTE ERROR: " . $stmt->error);
        exit;
    }
    
    $result = $stmt->get_result();
    error_log("Query executed successfully. Found " . $result->num_rows . " habits due for notification.");
    
    if ($result->num_rows === 0) {
        error_log("No due habits found for notification. Exiting.");
        exit;
    }
} catch (Exception $e) {
    error_log("SQL ERROR: " . $e->getMessage());
    exit;
}

// DEBUG: Let's query the habits table directly to check what habits exist
$debug_query = "SELECT id, name, target_time FROM habits WHERE is_active = 1 ORDER BY target_time";
$debug_result = $conn->query($debug_query);
if ($debug_result) {
    error_log("DEBUG - Active habits: " . $debug_result->num_rows . " habits found");
    while ($row = $debug_result->fetch_assoc()) {
        error_log("DEBUG - Habit ID: {$row['id']}, Name: {$row['name']}, Time: {$row['target_time']}");
    }
} else {
    error_log("DEBUG - Failed to query habits: " . $conn->error);
}

while ($current_habit = $result->fetch_assoc()) {
    // Log habit found
    error_log("Preparing notification for habit: {$current_habit['id']} - {$current_habit['title']} due at {$current_habit['due_time']}");
    
    // Format habit time for display
    $current_habit['due_time'] = date('h:i A', strtotime($current_habit['due_time']));
    
    // Get completed habits for today
    $completed_habits_query = "
        SELECT 
            h.id, 
            h.name AS title, 
            h.description, 
            hcp.status,
            hcp.completion_time AS due_time,
            h.category_id,
            CASE 
                WHEN hpr.completion_points >= 20 THEN 'high'
                WHEN hpr.completion_points >= 10 THEN 'medium'
                ELSE 'low'
            END AS priority,
            CASE 
                WHEN hc.name IS NOT NULL THEN hc.name 
                ELSE 'Uncategorized' 
            END AS category_name
        FROM 
            habits h
        LEFT JOIN 
            habit_categories hc ON h.category_id = hc.id
        LEFT JOIN
            habit_point_rules hpr ON h.point_rule_id = hpr.id
        JOIN 
            habit_completions hcp ON h.id = hcp.habit_id AND hcp.completion_date = ?
        WHERE 
            h.is_active = 1
            AND h.id != ?
        ORDER BY 
            hcp.completion_time DESC
        LIMIT 5
    ";
    
    $completed_stmt = $conn->prepare($completed_habits_query);
    $completed_stmt->bind_param("si", $today, $current_habit['id']);
    $completed_stmt->execute();
    $completed_result = $completed_stmt->get_result();
    
    $completed_habits = [];
    while ($habit = $completed_result->fetch_assoc()) {
        $habit['due_time'] = date('h:i A', strtotime($habit['due_time']));
        $habit['estimated_duration'] = 'Completed';
        $completed_habits[] = $habit;
    }
    error_log("Found " . count($completed_habits) . " completed habits");
    
    // Get pending habits for today
    $pending_habits_query = "
        SELECT 
            h.id, 
            h.name AS title, 
            h.description, 
            h.target_time AS due_time,
            h.category_id,
            CASE 
                WHEN hpr.completion_points >= 20 THEN 'high'
                WHEN hpr.completion_points >= 10 THEN 'medium'
                ELSE 'low'
            END AS priority,
            CASE 
                WHEN hc.name IS NOT NULL THEN hc.name 
                ELSE 'Uncategorized' 
            END AS category_name
        FROM 
            habits h
        LEFT JOIN 
            habit_categories hc ON h.category_id = hc.id
        LEFT JOIN
            habit_point_rules hpr ON h.point_rule_id = hpr.id
        LEFT JOIN 
            habit_completions hcp ON h.id = hcp.habit_id AND hcp.completion_date = ?
        WHERE 
            h.is_active = 1
            AND hcp.id IS NULL
            AND h.target_time > ?
            AND h.id != ?
        ORDER BY 
            h.target_time ASC
        LIMIT 5
    ";
    
    $pending_stmt = $conn->prepare($pending_habits_query);
    $pending_stmt->bind_param("ssi", $today, $notification_window_end, $current_habit['id']);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result();
    
    $pending_habits = [];
    while ($habit = $pending_result->fetch_assoc()) {
        $habit['due_time'] = date('h:i A', strtotime($habit['due_time']));
        $habit['estimated_duration'] = 'Pending';
        $pending_habits[] = $habit;
    }
    error_log("Found " . count($pending_habits) . " pending habits");
    
    // Add estimate duration for current habit
    $current_habit['estimated_duration'] = 'Points: +' . $current_habit['points'];
    
    // Prepare email data
    $emailData = [
        'current_task' => $current_habit,
        'overdue_tasks' => [], // No overdue habits concept
        'upcoming_tasks' => $pending_habits,
        'app_url' => $app_url
    ];
    
    // Generate email content
    $notification = new HabitNotification();
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
        
        // Add anti-spam measures if not already in the script
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
        $mail->Subject = "Habit Due Now: " . $current_habit['title'];
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
        
        // Send the email
        $mail->send();
        error_log("Email sent successfully for habit ID {$current_habit['id']} at " . date('Y-m-d H:i:s'));
        
        // Record that notification has been sent - moved after successful email sending
        error_log("Updating notification tracking for habit ID: " . $current_habit['id']);
        
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
            VALUES (?, 'habit', NOW())
        ";
        
        $tracking_stmt = $conn->prepare($tracking_query);
        if (!$tracking_stmt) {
            error_log("ERROR preparing tracking statement: " . $conn->error);
        } else {
            $tracking_stmt->bind_param("i", $current_habit['id']);
            if ($tracking_stmt->execute()) {
                error_log("Tracking record inserted successfully with ID: " . $conn->insert_id);
            } else {
                error_log("ERROR inserting tracking record: " . $tracking_stmt->error);
            }
            $tracking_stmt->close();
        }
        
    } catch (Exception $e) {
        error_log("PHPMailer Error for habit ID {$current_habit['id']}: " . $e->getMessage());
    }
}

$conn->close();
error_log("Habit notification check completed at " . date('Y-m-d H:i:s'));
error_log("==== HABIT NOTIFICATION SCRIPT ENDED ====");
?> 