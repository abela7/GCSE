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
    $taskQuery = "SELECT * FROM tasks 
                 WHERE DATE(due_date) = ? 
                 AND is_completed = 0 
                 ORDER BY due_time ASC";
    $stmt = $conn->prepare($taskQuery);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $tasksResult = $stmt->get_result();
    $tasks = $tasksResult->fetch_all(MYSQLI_ASSOC);
    
    // Fetch today's habits
    $habitQuery = "SELECT * FROM habits 
                  WHERE is_active = 1 
                  ORDER BY scheduled_time ASC";
    $habitsResult = $conn->query($habitQuery);
    $habits = $habitsResult->fetch_all(MYSQLI_ASSOC);
    
    // Fetch overdue tasks
    $overdueQuery = "SELECT * FROM tasks 
                    WHERE due_date < ? 
                    AND is_completed = 0 
                    ORDER BY due_date ASC";
    $stmt = $conn->prepare($overdueQuery);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $overdueResult = $stmt->get_result();
    $overdue = $overdueResult->fetch_all(MYSQLI_ASSOC);
    
    // Prepare email data
    $emailData = [
        'tasks' => $tasks,
        'habits' => $habits,
        'overdue' => $overdue
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
    
    // Retry logic could be added here
}

// Close database connection
$conn->close();
?> 