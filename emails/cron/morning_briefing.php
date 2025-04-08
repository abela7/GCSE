<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../templates/morning_briefing.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Get today's date
    $today = date('Y-m-d');
    
    // Fetch today's tasks
    $taskQuery = "SELECT t.*, s.subject_name 
                 FROM tasks t 
                 LEFT JOIN subjects s ON t.subject_id = s.id 
                 WHERE DATE(t.due_date) = ? 
                 AND t.status = 'pending' 
                 ORDER BY t.due_date ASC, t.priority DESC";
    $stmt = $conn->prepare($taskQuery);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $tasksResult = $stmt->get_result();
    $tasks = $tasksResult->fetch_all(MYSQLI_ASSOC);
    
    // Fetch today's habits
    $habitQuery = "SELECT h.*, s.subject_name 
                  FROM habits h 
                  LEFT JOIN subjects s ON h.subject_id = s.id 
                  WHERE h.status = 'active' 
                  ORDER BY h.time ASC";
    $habitsResult = $conn->query($habitQuery);
    $habits = $habitsResult->fetch_all(MYSQLI_ASSOC);
    
    // Fetch overdue tasks
    $overdueQuery = "SELECT t.*, s.subject_name 
                    FROM tasks t 
                    LEFT JOIN subjects s ON t.subject_id = s.id 
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
        'tasks' => array_map(function($task) {
            return [
                'title' => $task['title'] . ' (' . $task['subject_name'] . ')',
                'due_time' => date('h:i A', strtotime($task['due_date'])),
                'priority' => $task['priority'] ?? 'medium'
            ];
        }, $tasks),
        'habits' => array_map(function($habit) {
            return [
                'title' => $habit['title'] . ' (' . $habit['subject_name'] . ')',
                'time' => date('h:i A', strtotime($habit['time']))
            ];
        }, $habits),
        'overdue' => array_map(function($task) {
            return [
                'title' => $task['title'] . ' (' . $task['subject_name'] . ')',
                'due_time' => date('M j, Y h:i A', strtotime($task['due_date']))
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
    $mail->Subject = "Good Morning! Your Daily Briefing for " . date('l, F j');
    $mail->Body = $emailContent;
    $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
    
    // Send the email
    $mail->send();
    
    // Log success
    error_log("Morning briefing sent successfully at " . date('Y-m-d H:i:s'));
    
} catch (Exception $e) {
    // Log error
    error_log("Error sending morning briefing: " . $e->getMessage());
    throw $e; // Re-throw to see the error in browser during testing
}

// Close database connection
$conn->close();
?> 