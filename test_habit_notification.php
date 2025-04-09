<?php
/**
 * Test Habit Notification Script
 * This script allows manually testing the habit notification email
 */

// Include required files
require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/templates/habit_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Application URL for links in emails
$app_url = 'http://abel.abuneteklehaymanot.org';

// Test data
$testData = [
    'current_task' => [
        'id' => 1,
        'title' => 'Daily Prayer',
        'description' => 'Take time for spiritual reflection',
        'priority' => 'high',
        'due_time' => '05:00 AM',
        'points' => '50'
    ],
    'upcoming_tasks' => [
        [
            'id' => 2,
            'title' => 'Exercise',
            'description' => '30 minutes of physical activity',
            'priority' => 'medium',
            'due_time' => '06:00 AM',
            'points' => '30'
        ],
        [
            'id' => 3,
            'title' => 'Reading',
            'description' => 'Read a chapter from your textbook',
            'priority' => 'medium',
            'due_time' => '08:00 PM',
            'points' => '20'
        ]
    ]
];

// Create instance of HabitNotification
$notification = new HabitNotification();

// Generate email content
$emailContent = $notification->generateEmail($testData);

// Create PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = SMTP_PORT;

    // Recipients
    $mail->setFrom(SMTP_USERNAME, 'Amha-Silassie Study App');
    $mail->addAddress('Abelgoytom77@gmail.com');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Habit Reminder: ' . $testData['current_task']['title'];
    $mail->Body = $emailContent;

    $mail->send();
    echo 'Test habit notification email sent successfully!';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?> 