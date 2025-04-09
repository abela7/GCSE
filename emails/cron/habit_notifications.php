<?php
/**
 * Habit Notification Script
 * This script checks for habits that are due soon and sends notification emails
 * It should be run by a cron job every 5-15 minutes
 */

// Include required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../templates/habit_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

$stmt = $conn->prepare($habits_query);
$stmt->bind_param("ssss", $today, $today, $notification_window_start, $notification_window_end);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("No due habits found for notification. Exiting.");
    exit;
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
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress(SMTP_USERNAME); // Use SMTP_USERNAME instead of hardcoded email address
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Habit Due Now: " . $current_habit['title'];
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
        
        // Send the email
        $mail->send();
        error_log("Habit notification email sent for habit ID {$current_habit['id']} at " . date('Y-m-d H:i:s'));
        
        // Record that notification has been sent
        $tracking_query = "
            INSERT INTO task_notification_tracking (task_id, notification_type, sent_at)
            VALUES (?, 'habit', NOW())
        ";
        $tracking_stmt = $conn->prepare($tracking_query);
        $tracking_stmt->bind_param("i", $current_habit['id']);
        $tracking_stmt->execute();
        
    } catch (Exception $e) {
        error_log("PHPMailer Error for habit ID {$current_habit['id']}: " . $e->getMessage());
    }
}

$conn->close();
error_log("Habit notification check completed at " . date('Y-m-d H:i:s'));
?> 