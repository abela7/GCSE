<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../templates/morning_briefing.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to get time-based greeting
function getGreeting() {
    return "Goooood Morning Abela 😇";
}

try {
    // Get tasks for today
    $query = "SELECT t.id, t.title, t.description, t.due_date, t.due_time, t.priority, t.status,
                     c.name as category_name, c.icon as category_icon, c.color as category_color
              FROM tasks t
              JOIN task_categories c ON t.category_id = c.id
              WHERE t.is_active = 1
              AND t.status = 'pending'
              AND t.due_date = CURRENT_DATE
              ORDER BY t.due_time ASC";

    $result = $conn->query($query);
    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    
    // Get habits for today
    $query = "SELECT h.id, h.name, h.description, h.icon, h.target_time,
                     c.name as category_name, c.icon as category_icon, c.color as category_color
              FROM habits h
              JOIN habit_categories c ON h.category_id = c.id
              WHERE h.is_active = 1
              AND h.target_time IS NOT NULL
              ORDER BY h.target_time ASC";

    $result = $conn->query($query);
    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $habits = [];
    while ($row = $result->fetch_assoc()) {
        $habits[] = $row;
    }
    
    // Fetch overdue tasks
    $overdueQuery = "SELECT t.*, tc.name as category_name, tc.color as category_color, t.description 
                    FROM tasks t 
                    LEFT JOIN task_categories tc ON t.category_id = tc.id 
                    WHERE t.due_date < CURRENT_DATE
                    AND t.status = 'pending' 
                    ORDER BY t.due_date ASC";
    $overdueResult = $conn->query($overdueQuery);
    $overdue = $overdueResult->fetch_all(MYSQLI_ASSOC);
    
    // Prepare email data
    $emailData = [
        'tasks' => array_map(function($task) {
            return [
                'title' => $task['title'] . ' (' . $task['category_name'] . ')',
                'description' => $task['description'] ?: 'No description provided',
                'due_time' => $task['due_time'] ? date('h:i A', strtotime($task['due_time'])) : 'No time set',
                'priority' => $task['priority'] ?? 'medium'
            ];
        }, $tasks),
        'habits' => array_map(function($habit) {
            return [
                'title' => $habit['name'] . ' (' . $habit['category_name'] . ')',
                'description' => $habit['description'] ?: 'No description provided',
                'due_time' => $habit['target_time'] ? date('h:i A', strtotime($habit['target_time'])) : 'No time set'
            ];
        }, $habits),
        'date' => date('l, F j, Y'),
        'greeting' => getGreeting(),
        'overdue' => array_map(function($task) {
            return [
                'title' => $task['title'] . ' (' . $task['category_name'] . ')',
                'description' => $task['description'] ?: 'No description provided',
                'due_time' => date('M j, Y', strtotime($task['due_date'])) . 
                         ($task['due_time'] ? ' ' . date('h:i A', strtotime($task['due_time'])) : '')
            ];
        }, $overdue)
    ];
    
    // Generate email content
    $briefing = new MorningBriefing();
    $emailContent = $briefing->generateEmail($emailData);
    
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
        $mail->addAddress(SMTP_USERNAME); // Sending to user's email
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Goooood Morning Abela! Your Daily Briefing for " . date('l, F j');
        $mail->Body = $emailContent;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
        
        // Send the email
        $mail->send();
        error_log("Email sent successfully at " . date('Y-m-d H:i:s'));
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in morning briefing script: " . $e->getMessage());
    throw $e;
}

// Close database connection
$conn->close();
?> 