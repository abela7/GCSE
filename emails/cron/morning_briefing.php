<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../templates/morning_briefing.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Define lock file path
$lockFile = __DIR__ . '/morning_briefing.lock';

// Function to check if script is already running
function isScriptRunning($lockFile) {
    if (file_exists($lockFile)) {
        $lockTime = filemtime($lockFile);
        // If lock file is older than 5 minutes, consider it stale
        if (time() - $lockTime > 300) {
            unlink($lockFile);
            return false;
        }
        return true;
    }
    return false;
}

// Create lock file
if (isScriptRunning($lockFile)) {
    error_log("Morning briefing script is already running. Exiting.");
    exit(0);
}

// Create lock file
touch($lockFile);

try {
    // Get today's date
    $today = date('Y-m-d');
    
    // Check if email was already sent today
    $lastSentFile = __DIR__ . '/last_sent.txt';
    if (file_exists($lastSentFile)) {
        $lastSentDate = trim(file_get_contents($lastSentFile));
        if ($lastSentDate === $today) {
            error_log("Morning briefing already sent today. Exiting.");
            unlink($lockFile);
            exit(0);
        }
    }
    
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
    $habits = [];
    while ($row = $result->fetch_assoc()) {
        $habits[] = $row;
    }
    
    // Fetch overdue tasks
    $overdueQuery = "SELECT t.*, tc.name as category_name, tc.color as category_color, t.description 
                    FROM tasks t 
                    LEFT JOIN task_categories tc ON t.category_id = tc.id 
                    WHERE t.due_date < ? 
                    AND t.status = 'pending' 
                    ORDER BY t.due_date ASC";
    $stmt = $conn->prepare($overdueQuery);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $overdueResult = $stmt->get_result();
    $overdue = $overdueResult->fetch_all(MYSQLI_ASSOC);
    
    // Prepare email data
    $emailData = [
        'tasks' => $tasks,
        'habits' => $habits,
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
    $mail->addAddress(SMTP_USERNAME); // Sending to user's email
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = "Goooood Morning Abela! Your Daily Briefing for " . date('l, F j');
    $mail->Body = $emailContent;
    $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
    
    // Send the email
    $mail->send();
    
    // Update last sent date
    file_put_contents($lastSentFile, $today);
    
    // Log success
    error_log("Morning briefing sent successfully at " . date('Y-m-d H:i:s'));
    
} catch (Exception $e) {
    // Log error
    error_log("Error sending morning briefing: " . $e->getMessage());
    throw $e; // Re-throw to see the error in browser during testing
} finally {
    // Always remove lock file
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}

// Close database connection
$conn->close();
?> 