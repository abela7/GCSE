<?php
// Include necessary files
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/templates/due_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Add basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Due Notification Test - GCSE Study App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Due Notification Test - GCSE Study App</h1>';

try {
    // Prepare sample data for a task due soon
    $taskData = [
        'type' => 'task',
        'id' => 999,
        'title' => 'Complete Practice Test',
        'description' => 'Finish the math practice test before your study session tomorrow.',
        'due_time' => 'Tomorrow at 3:00 PM',
        'priority' => 'high',
        'category' => 'Mathematics',
        'hours_remaining' => 20,
        'name' => 'Abel',
        'date' => date('l, F j, Y')
    ];
    
    // Generate email content
    $notification = new DueNotification();
    $taskEmail = $notification->generateEmail($taskData);
    
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
    
    // Add custom headers to reduce spam score
    $mail->XMailer = 'GCSE Study App Mailer';
    $mail->addCustomHeader('X-Application', 'GCSE Study App');
    $mail->addCustomHeader('X-Domain-ID', 'abel.abuneteklehaymanot.org');
    
    // Recipients
    $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
    $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
    $mail->addAddress('abelgoytom77@gmail.com', 'Abel Goytom'); // Sending to your Gmail
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test: Task Due Soon - Complete Practice Test';
    $mail->Body = $taskEmail;
    $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $taskEmail));
    
    // Send the email
    $mail->send();
    echo '<div class="alert alert-success">
            <h4 class="alert-heading">Success!</h4>
            <p>Test task due notification sent successfully! Please check your inbox at abelgoytom77@gmail.com</p>
            <hr>
            <p class="mb-0">If you don\'t see the email, please check your spam folder.</p>
          </div>';
    
    // Now test a habit notification
    $habitData = [
        'type' => 'habit',
        'id' => 888,
        'title' => 'Review Science Notes',
        'description' => 'Daily review of key science concepts to reinforce learning.',
        'due_time' => 'Today at 7:00 PM',
        'category' => 'Science',
        'hours_remaining' => 2,
        'name' => 'Abel',
        'date' => date('l, F j, Y')
    ];
    
    // Generate habit email content
    $habitEmail = $notification->generateEmail($habitData);
    
    // Create a new PHPMailer instance for habit
    $habitMail = new PHPMailer(true);
    
    // Server settings
    $habitMail->isSMTP();
    $habitMail->Host = SMTP_HOST;
    $habitMail->SMTPAuth = SMTP_AUTH;
    $habitMail->Username = SMTP_USERNAME;
    $habitMail->Password = SMTP_PASSWORD;
    $habitMail->SMTPSecure = SMTP_SECURE;
    $habitMail->Port = SMTP_PORT;
    
    // Add custom headers
    $habitMail->XMailer = 'GCSE Study App Mailer';
    $habitMail->addCustomHeader('X-Application', 'GCSE Study App');
    $habitMail->addCustomHeader('X-Domain-ID', 'abel.abuneteklehaymanot.org');
    
    // Recipients
    $habitMail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
    $habitMail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
    $habitMail->addAddress('abelgoytom77@gmail.com', 'Abel Goytom');
    
    // Content
    $habitMail->isHTML(true);
    $habitMail->Subject = 'Test: Habit Reminder - Review Science Notes';
    $habitMail->Body = $habitEmail;
    $habitMail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $habitEmail));
    
    // Send the habit email
    $habitMail->send();
    echo '<div class="alert alert-success mt-4">
            <h4 class="alert-heading">Success!</h4>
            <p>Test habit reminder notification sent successfully! Please check your inbox at abelgoytom77@gmail.com</p>
            <hr>
            <p class="mb-0">This completes the due notification test.</p>
          </div>';
    
    // Display preview of the emails
    echo '<div class="card mt-4">
            <div class="card-header">
                <h3>Task Notification Preview</h3>
            </div>
            <div class="card-body">
                <iframe srcdoc="' . htmlspecialchars($taskEmail) . '" style="width: 100%; height: 500px; border: 1px solid #ddd; border-radius: 4px;"></iframe>
            </div>
          </div>';
          
    echo '<div class="card mt-4">
            <div class="card-header">
                <h3>Habit Notification Preview</h3>
            </div>
            <div class="card-body">
                <iframe srcdoc="' . htmlspecialchars($habitEmail) . '" style="width: 100%; height: 500px; border: 1px solid #ddd; border-radius: 4px;"></iframe>
            </div>
          </div>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">
            <h4 class="alert-heading">Error!</h4>
            <p>Email could not be sent. Error details:</p>
            <pre>' . $e->getMessage() . '</pre>
          </div>';
}

echo '<div class="mt-4">
        <h3>Setting Up Cron Job</h3>
        <p>To set up the cron job for due notifications, run the following command to edit your crontab:</p>
        <pre class="bg-light p-3">crontab -e</pre>
        
        <p>Then add this line to run the script every hour:</p>
        <pre class="bg-light p-3">0 * * * * php /path/to/your/website/emails/cron/due_notifications.php</pre>
        
        <p>You\'ll need to:</p>
        <ol>
            <li>Replace "/path/to/your/website" with your actual website path</li>
            <li>Make sure that the PHP CLI is in your path or use the full path to PHP</li>
            <li>Make sure the script has appropriate permissions to run</li>
        </ol>
        
        <p>For Windows servers, you can use Windows Task Scheduler instead:</p>
        <ol>
            <li>Open Task Scheduler</li>
            <li>Create a Basic Task with a Trigger set to "Daily" and repeat every 1 hour for 24 hours</li>
            <li>Action: Start a Program</li>
            <li>Program/script: C:\path\to\php\php.exe</li>
            <li>Arguments: C:\path\to\your\website\emails\cron\due_notifications.php</li>
        </ol>
      </div>';

echo '</div>
</body>
</html>';
?> 