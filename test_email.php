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
    
    // Enable debugging if needed
    // $mail->SMTPDebug = 2;
    
    // Recipients
    $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
    $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
    $mail->addAddress(SMTP_USERNAME); // Sending to yourself for testing
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from Amha-Silassie';
    $mail->Body = '
        <h1>Email Test Successful!</h1>
        <p>This is a test email from Amha-Silassie.</p>
        <p>If you are receiving this email, your email configuration is working correctly!</p>
        <p>Time sent: ' . date('Y-m-d H:i:s') . '</p>
        <p>Domain: abel.abuneteklehaymanot.org</p>
    ';
    
    // Send the email
    $mail->send();
    echo '<div class="alert alert-success">
            <h4 class="alert-heading">Success!</h4>
            <p>Test email sent successfully! Please check your inbox at ' . SMTP_USERNAME . '</p>
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