<?php
/**
 * Habit Notifications Script
 * This script checks for habits that are due now or soon and sends notification emails
 * Uses tracking table to avoid duplicate notifications
 */

// Set timezone to London time
date_default_timezone_set('Europe/London');

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

// Get current time plus buffer (for habits due within the next 3 minutes)
$buffer_minutes = 3; // Time window for notifications
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
        END AS category_name,
        tnt.sent_at AS last_notified
    FROM 
        habits h
    LEFT JOIN 
        habit_categories hc ON h.category_id = hc.id
    LEFT JOIN
        habit_point_rules hpr ON h.point_rule_id = hpr.id
    LEFT JOIN 
        habit_completions hcp ON h.id = hcp.habit_id AND hcp.completion_date = ?
    LEFT JOIN (
        SELECT task_id, MAX(sent_at) AS sent_at 
        FROM task_notification_tracking 
        WHERE notification_type = 'habit' 
        GROUP BY task_id
    ) tnt ON h.id = tnt.task_id
    WHERE 
        h.is_active = 1
        AND hcp.id IS NULL -- Not completed today
        AND h.target_time BETWEEN ? AND ?
    ORDER BY 
        h.target_time ASC
    LIMIT 1
";

// Log the query parameters
error_log("SQL Query with params: [date={$today}, start_time={$notification_window_start}, end_time={$notification_window_end}]");

try {
    $stmt = $conn->prepare($habits_query);
    if (!$stmt) {
        error_log("PREPARE ERROR: " . $conn->error);
        exit;
    }
    
    $stmt->bind_param("sss", $today, $notification_window_start, $notification_window_end);
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

while ($current_habit = $result->fetch_assoc()) {
    // Check if habit was recently notified (within last 5 minutes)
    $recently_notified = false;
    
    if (!empty($current_habit['last_notified'])) {
        $last_notified_time = strtotime($current_habit['last_notified']);
        $time_since_notification = time() - $last_notified_time;
        $recently_notified = ($time_since_notification < 300); // 5 minutes = 300 seconds
        
        if ($recently_notified) {
            error_log("Habit ID {$current_habit['id']} was recently notified at " . date('Y-m-d H:i:s', $last_notified_time) . ". Skipping.");
            continue;
        }
    }
    
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
            END AS category_name,
            TIMESTAMPDIFF(MINUTE, hcp.completion_time, NOW()) as minutes_ago
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
        // Format the completed time in a human-readable way
        $minutes_ago = $habit['minutes_ago'];
        if ($minutes_ago < 60) {
            $habit['completed_text'] = "Completed {$minutes_ago} minutes ago";
        } else if ($minutes_ago < 1440) { // Less than 24 hours
            $hours = floor($minutes_ago / 60);
            $mins = $minutes_ago % 60;
            if ($mins > 0) {
                $habit['completed_text'] = "Completed {$hours}h {$mins}m ago";
            } else {
                $habit['completed_text'] = "Completed {$hours} hours ago";
            }
        } else { // More than 24 hours
            $days = floor($minutes_ago / 1440);
            $habit['completed_text'] = "Completed {$days} days ago";
        }
        
        $habit['due_time'] = date('h:i A', strtotime($habit['due_time']));
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
            END AS category_name,
            TIMESTAMPDIFF(MINUTE, NOW(), h.target_time) as minutes_until_due
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
    $pending_stmt->bind_param("ssi", $today, $current_time, $current_habit['id']);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result();
    
    $pending_habits = [];
    while ($habit = $pending_result->fetch_assoc()) {
        // Format the upcoming time in a human-readable way
        $minutes_until_due = $habit['minutes_until_due'];
        if ($minutes_until_due < 60) {
            $habit['upcoming_text'] = "Due in {$minutes_until_due} minutes";
        } else if ($minutes_until_due < 1440) { // Less than 24 hours
            $hours = floor($minutes_until_due / 60);
            $mins = $minutes_until_due % 60;
            if ($mins > 0) {
                $habit['upcoming_text'] = "Due in {$hours}h {$mins}m";
            } else {
                $habit['upcoming_text'] = "Due in {$hours} hours";
            }
        } else { // More than 24 hours
            $days = floor($minutes_until_due / 1440);
            $hours = floor(($minutes_until_due % 1440) / 60);
            if ($hours > 0) {
                $habit['upcoming_text'] = "Due in {$days}d {$hours}h";
            } else {
                $habit['upcoming_text'] = "Due in {$days} days";
            }
        }
        
        $habit['due_time'] = date('h:i A', strtotime($habit['due_time']));
        $pending_habits[] = $habit;
    }
    error_log("Found " . count($pending_habits) . " pending habits");
    
    // Add points for current habit
    $current_habit['estimated_duration'] = 'Points: +' . $current_habit['points'];
    
    // Prepare email data
    $emailData = [
        'current_task' => $current_habit,
        'completed_tasks' => $completed_habits,
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
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress(SMTP_USERNAME);
        $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
        
        // Anti-spam and deliverability improvements
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->XMailer = ' ';  // Hide PHPMailer version
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $current_habit['title'] . " is due";
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
        
        // Send the email
        if ($mail->send()) {
            error_log("Email sent successfully for habit ID {$current_habit['id']} at " . date('Y-m-d H:i:s'));
            
            // Record that notification has been sent
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
        } else {
            error_log("Email sending failed without exception");
        }
    } catch (Exception $e) {
        error_log("PHPMailer Error for habit ID {$current_habit['id']}: " . $e->getMessage());
    }
}

$conn->close();
error_log("Habit notification check completed at " . date('Y-m-d H:i:s'));
error_log("==== HABIT NOTIFICATION SCRIPT ENDED ====");
?> 