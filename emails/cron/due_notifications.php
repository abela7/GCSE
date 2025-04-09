<?php
/**
 * Due Task and Habit Notification Script
 * 
 * This script checks for upcoming tasks and habits and sends reminder emails.
 * Run this script via cron job every hour.
 */

// Include necessary files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../templates/due_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize error logging
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('Europe/London');

// Create log entry
$log_file = __DIR__ . '/../../logs/email_notifications.log';
error_log(date('Y-m-d H:i:s') . " - Due notifications script started\n", 3, $log_file);

// Check if email notifications are enabled
if (!ENABLE_EMAIL_NOTIFICATIONS) {
    error_log(date('Y-m-d H:i:s') . " - Email notifications are disabled in config\n", 3, $log_file);
    exit();
}

// Connect to database
try {
    $conn = get_database_connection();
} catch (Exception $e) {
    error_log(date('Y-m-d H:i:s') . " - Database connection error: " . $e->getMessage() . "\n", 3, $log_file);
    exit();
}

// Get user settings
$userEmail = null;
$userName = 'User';
try {
    $stmt = $conn->prepare("SELECT email, name FROM users WHERE id = 1 LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $userEmail = $user['email'];
        $userName = $user['name'] ?: 'User';
    }
} catch (Exception $e) {
    error_log(date('Y-m-d H:i:s') . " - Error fetching user settings: " . $e->getMessage() . "\n", 3, $log_file);
}

// Use admin email if user email is not set
if (!$userEmail) {
    $userEmail = SMTP_USERNAME;
    error_log(date('Y-m-d H:i:s') . " - Using admin email as fallback\n", 3, $log_file);
}

// Current time
$current_time = time();

// Send due task notifications
try {
    sendTaskNotifications($conn, $userEmail, $userName, $current_time);
} catch (Exception $e) {
    error_log(date('Y-m-d H:i:s') . " - Error sending task notifications: " . $e->getMessage() . "\n", 3, $log_file);
}

// Send due habit notifications
try {
    sendHabitNotifications($conn, $userEmail, $userName, $current_time);
} catch (Exception $e) {
    error_log(date('Y-m-d H:i:s') . " - Error sending habit notifications: " . $e->getMessage() . "\n", 3, $log_file);
}

// Close connection
$conn = null;
error_log(date('Y-m-d H:i:s') . " - Due notifications script completed\n", 3, $log_file);

/**
 * Send notifications for tasks due soon
 */
function sendTaskNotifications($conn, $userEmail, $userName, $current_time)
{
    $log_file = __DIR__ . '/../../logs/email_notifications.log';
    
    // Get tasks due in the next 24 hours that haven't had notifications sent yet
    $stmt = $conn->prepare("
        SELECT t.*, c.name as category_name 
        FROM tasks t 
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE 
            t.status != 'completed' 
            AND t.due_date IS NOT NULL
            AND (
                (t.due_date = CURDATE() AND (t.due_time IS NULL OR TIME_TO_SEC(TIMEDIFF(t.due_time, CURTIME())) BETWEEN 0 AND 86400))
                OR 
                (t.due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND (t.due_time IS NULL OR TIME_TO_SEC(TIMEDIFF(t.due_time, '00:00:00')) <= 86400))
            )
            AND (t.notification_sent IS NULL OR t.notification_sent = 0)
        ORDER BY t.due_date ASC, t.due_time ASC
    ");
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log(date('Y-m-d H:i:s') . " - Found " . count($tasks) . " tasks due soon\n", 3, $log_file);
    
    if (empty($tasks)) {
        return;
    }
    
    foreach ($tasks as $task) {
        try {
            // Calculate hours remaining
            $dueDatetime = strtotime($task['due_date'] . ' ' . ($task['due_time'] ?: '23:59:59'));
            $hoursRemaining = ceil(($dueDatetime - $current_time) / 3600);
            
            // Prepare email data
            $emailData = [
                'type' => 'task',
                'id' => $task['id'],
                'title' => $task['title'],
                'description' => $task['description'],
                'due_time' => date('l, F j, Y', strtotime($task['due_date'])) . 
                             ($task['due_time'] ? ' at ' . date('g:i A', strtotime($task['due_time'])) : ''),
                'priority' => $task['priority'],
                'category' => $task['category_name'] ?? null,
                'hours_remaining' => $hoursRemaining,
                'name' => $userName,
                'date' => date('l, F j, Y')
            ];
            
            // Generate and send email
            if (sendDueNotificationEmail($userEmail, 'Task Due Soon: ' . $task['title'], $emailData)) {
                // Mark notification as sent
                $updateStmt = $conn->prepare("UPDATE tasks SET notification_sent = 1 WHERE id = ?");
                $updateStmt->execute([$task['id']]);
                error_log(date('Y-m-d H:i:s') . " - Task notification sent for task ID: " . $task['id'] . "\n", 3, $log_file);
            }
        } catch (Exception $e) {
            error_log(date('Y-m-d H:i:s') . " - Error processing task " . $task['id'] . ": " . $e->getMessage() . "\n", 3, $log_file);
        }
    }
}

/**
 * Send notifications for habits due today
 */
function sendHabitNotifications($conn, $userEmail, $userName, $current_time)
{
    $log_file = __DIR__ . '/../../logs/email_notifications.log';
    
    // Get today's day of week (1 = Monday, 7 = Sunday)
    $today = date('N');
    
    // Get habits scheduled for today that haven't been completed or had notifications sent
    $stmt = $conn->prepare("
        SELECT h.*, c.name as category_name 
        FROM habits h 
        LEFT JOIN categories c ON h.category_id = c.id
        WHERE 
            h.status = 'active'
            AND h.days_of_week LIKE ?
            AND NOT EXISTS (
                SELECT 1 FROM habit_tracking ht 
                WHERE ht.habit_id = h.id AND DATE(ht.tracked_date) = CURDATE() AND ht.status = 'completed'
            )
            AND (h.notification_sent_date IS NULL OR h.notification_sent_date != CURDATE())
        ORDER BY h.time_of_day ASC
    ");
    $stmt->execute(['%' . $today . '%']);
    $habits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log(date('Y-m-d H:i:s') . " - Found " . count($habits) . " habits due today\n", 3, $log_file);
    
    if (empty($habits)) {
        return;
    }
    
    foreach ($habits as $habit) {
        try {
            // Calculate hours remaining if time_of_day is set
            $hoursRemaining = null;
            if ($habit['time_of_day']) {
                $habitTime = strtotime(date('Y-m-d') . ' ' . $habit['time_of_day']);
                if ($habitTime > $current_time) {
                    $hoursRemaining = ceil(($habitTime - $current_time) / 3600);
                } else {
                    // Habit time already passed today
                    $hoursRemaining = 0;
                }
            }
            
            // Prepare email data
            $emailData = [
                'type' => 'habit',
                'id' => $habit['id'],
                'title' => $habit['title'],
                'description' => $habit['description'],
                'due_time' => ($habit['time_of_day'] ? 'Today at ' . date('g:i A', strtotime($habit['time_of_day'])) : 'Today'),
                'category' => $habit['category_name'] ?? null,
                'hours_remaining' => $hoursRemaining,
                'name' => $userName,
                'date' => date('l, F j, Y')
            ];
            
            // Generate and send email
            if (sendDueNotificationEmail($userEmail, 'Habit Reminder: ' . $habit['title'], $emailData)) {
                // Mark notification as sent for today
                $updateStmt = $conn->prepare("UPDATE habits SET notification_sent_date = CURDATE() WHERE id = ?");
                $updateStmt->execute([$habit['id']]);
                error_log(date('Y-m-d H:i:s') . " - Habit notification sent for habit ID: " . $habit['id'] . "\n", 3, $log_file);
            }
        } catch (Exception $e) {
            error_log(date('Y-m-d H:i:s') . " - Error processing habit " . $habit['id'] . ": " . $e->getMessage() . "\n", 3, $log_file);
        }
    }
}

/**
 * Send due notification email
 */
function sendDueNotificationEmail($to, $subject, $data)
{
    $log_file = __DIR__ . '/../../logs/email_notifications.log';
    
    try {
        // Generate email content
        $notification = new DueNotification();
        $emailContent = $notification->generateEmail($data);
        
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Add custom headers
        $mail->XMailer = 'GCSE Study App Mailer';
        $mail->addCustomHeader('X-Application', 'GCSE Study App');
        $mail->addCustomHeader('X-Domain-ID', 'abel.abuneteklehaymanot.org');
        
        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
        
        // Send the email
        $mail->send();
        error_log(date('Y-m-d H:i:s') . " - Email sent successfully to: $to, Subject: $subject\n", 3, $log_file);
        return true;
        
    } catch (Exception $e) {
        error_log(date('Y-m-d H:i:s') . " - Email sending failed: " . $e->getMessage() . "\n", 3, $log_file);
        return false;
    }
}
?> 