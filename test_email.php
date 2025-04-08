<?php
// Include necessary files
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Add basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Email Test - Amha-Silassie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Email Test - Amha-Silassie</h1>';

try {
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
    $mail->Subject = 'Important: GCSE Study App Test Email';
    $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333;">GCSE Study App Email Configuration Test</h2>
            <p>Dear Abel,</p>
            <p>This is a test email from the GCSE Study App system to verify email functionality.</p>
            <p>Details:</p>
            <ul>
                <li>Sent from: '.EMAIL_FROM_NAME.'</li>
                <li>Time: '.date('Y-m-d H:i:s').'</li>
                <li>Domain: abel.abuneteklehaymanot.org</li>
            </ul>
            <p>If you received this email, the email configuration is working correctly.</p>
            <hr>
            <p style="font-size: 12px; color: #666;">
                This is an automated message from the GCSE Study App system.<br>
                Please do not reply to this email.
            </p>
        </div>
    ';
    $mail->AltBody = 'This is a test email from the GCSE Study App system. If you received this email, the configuration is working correctly.';
    
    // Send the email
    $mail->send();
    echo '<div class="alert alert-success">
            <h4 class="alert-heading">Success!</h4>
            <p>Test email sent successfully! Please check your inbox at abelgoytom77@gmail.com</p>
            <hr>
            <p class="mb-0">If you don\'t see the email, please check your spam folder.</p>
          </div>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">
            <h4 class="alert-heading">Error!</h4>
            <p>Email could not be sent. Error details:</p>
            <pre>' . $mail->ErrorInfo . '</pre>
          </div>';
}

echo '</div>
</body>
</html>';
?> 