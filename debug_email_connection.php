<?php
// Basic Email Connection Test Script - No templates, just direct SMTP testing

// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load email configuration
require_once __DIR__ . '/config/email_config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo '<!DOCTYPE html>
<html>
<head>
    <title>Email Connection Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        pre { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Email Connection Test</h1>';

// Display SMTP configuration
echo '<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5>Email Configuration</h5>
    </div>
    <div class="card-body">
        <ul class="list-group">
            <li class="list-group-item"><strong>SMTP Host:</strong> ' . SMTP_HOST . '</li>
            <li class="list-group-item"><strong>SMTP Port:</strong> ' . SMTP_PORT . '</li>
            <li class="list-group-item"><strong>SMTP Secure:</strong> ' . SMTP_SECURE . '</li>
            <li class="list-group-item"><strong>SMTP Auth:</strong> ' . (SMTP_AUTH ? 'Yes' : 'No') . '</li>
            <li class="list-group-item"><strong>SMTP Username:</strong> ' . SMTP_USERNAME . '</li>
            <li class="list-group-item"><strong>From Address:</strong> ' . EMAIL_FROM_ADDRESS . '</li>
            <li class="list-group-item"><strong>Notifications Enabled:</strong> ' . (ENABLE_EMAIL_NOTIFICATIONS ? 'Yes' : 'No') . '</li>
        </ul>
    </div>
</div>';

// Process form submission
if (isset($_POST['test_email'])) {
    $test_method = $_POST['test_method'] ?? 'phpmailer';
    $recipient = $_POST['recipient'] ?? SMTP_USERNAME;
    
    echo '<div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5>Test Results</h5>
        </div>
        <div class="card-body">';
    
    if ($test_method === 'phpmailer') {
        // Test using PHPMailer
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_CONNECTION; // Detailed connection debugging
            
            // Capture debug output
            ob_start();
            $mail->Debugoutput = function($str, $level) {
                echo htmlspecialchars($str) . "<br>\n";
            };
            
            // Setup
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = SMTP_AUTH;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            
            // Set timeout values
            $mail->Timeout = 30; // 30 seconds timeout
            $mail->SMTPKeepAlive = true; // Keep connection open
            
            // Recipients
            $mail->setFrom(EMAIL_FROM_ADDRESS, 'Email Test');
            $mail->addAddress($recipient);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Simple SMTP Test - ' . date('Y-m-d H:i:s');
            $mail->Body    = 'This is a very basic email test. If you see this, SMTP is working correctly.<br>Time: ' . date('Y-m-d H:i:s');
            $mail->AltBody = 'This is a very basic email test. If you see this, SMTP is working correctly. Time: ' . date('Y-m-d H:i:s');
            
            // Now try to send
            if ($mail->send()) {
                echo '<div class="alert alert-success">Email sent successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Email could not be sent.</div>';
            }
            
            echo '<h6 class="mt-3">Debug Output:</h6>';
            echo '<pre>' . ob_get_clean() . '</pre>';
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">
                <strong>Mailer Error:</strong> ' . $e->getMessage() . '
            </div>';
            
            // Show any buffered debug output
            $debug_output = ob_get_clean();
            if (!empty($debug_output)) {
                echo '<h6 class="mt-3">Debug Output:</h6>';
                echo '<pre>' . $debug_output . '</pre>';
            }
        }
    } else {
        // Test using mail() function
        $subject = 'Basic mail() Test - ' . date('Y-m-d H:i:s');
        $message = "This is a basic test of PHP's mail() function.\nIf you see this, the mail() function is working correctly.\nTime: " . date('Y-m-d H:i:s');
        $headers = 'From: ' . EMAIL_FROM_ADDRESS . "\r\n" .
                  'Reply-To: ' . EMAIL_FROM_ADDRESS . "\r\n" .
                  'X-Mailer: PHP/' . phpversion();
        
        $result = mail($recipient, $subject, $message, $headers);
        
        if ($result) {
            echo '<div class="alert alert-success">mail() function reports success.</div>';
        } else {
            echo '<div class="alert alert-danger">mail() function failed to send.</div>';
        }
        
        echo '<pre>
Recipient: ' . htmlspecialchars($recipient) . '
Subject: ' . htmlspecialchars($subject) . '
Headers: ' . htmlspecialchars($headers) . '
Result: ' . ($result ? 'true' : 'false') . '
        </pre>';
    }
    
    echo '</div></div>';
}

// Email test form
echo '<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5>Send Test Email</h5>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label for="test_method" class="form-label">Test Method</label>
                <select class="form-select" id="test_method" name="test_method">
                    <option value="phpmailer">Test SMTP (PHPMailer)</option>
                    <option value="mail">Test mail() function</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="recipient" class="form-label">Recipient Email</label>
                <input type="email" class="form-control" id="recipient" name="recipient" 
                    value="' . htmlspecialchars(SMTP_USERNAME) . '" required>
            </div>
            
            <button type="submit" name="test_email" class="btn btn-primary">
                Send Test Email
            </button>
        </form>
    </div>
</div>';

// Test direct socket connection
echo '<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5>Test SMTP Connection</h5>
    </div>
    <div class="card-body">';

// Try a direct socket connection to the SMTP server
$port = SMTP_PORT;
$host = SMTP_HOST;
$timeout = 10; // 10 seconds timeout

echo '<h6>Testing direct connection to ' . $host . ':' . $port . '</h6>';
$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

if (!$socket) {
    echo '<div class="alert alert-danger">
        <strong>Connection failed:</strong> ' . $errstr . ' (Error: ' . $errno . ')
    </div>';
} else {
    echo '<div class="alert alert-success">
        Socket connection successful. SMTP server is reachable.
    </div>';
    
    // Read the welcome message
    $response = fgets($socket, 515);
    echo '<pre>' . htmlspecialchars($response) . '</pre>';
    
    // Close the connection
    fclose($socket);
}

echo '</div>
</div>';

// Verify PHP mail configuration
echo '<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5>PHP Mail Configuration</h5>
    </div>
    <div class="card-body">';

// Check if sendmail_path is configured
$sendmail_path = ini_get('sendmail_path');
echo '<p><strong>sendmail_path:</strong> ' . ($sendmail_path ? htmlspecialchars($sendmail_path) : '<span class="text-danger">Not configured</span>') . '</p>';

// Check if SMTP is configured in php.ini
$smtp = ini_get('SMTP');
$smtp_port = ini_get('smtp_port');
echo '<p><strong>SMTP in php.ini:</strong> ' . ($smtp ? htmlspecialchars($smtp) : '<span class="text-danger">Not configured</span>') . '</p>';
echo '<p><strong>smtp_port in php.ini:</strong> ' . ($smtp_port ? htmlspecialchars($smtp_port) : '<span class="text-danger">Not configured</span>') . '</p>';

echo '</div>
</div>';

// Navigation links
echo '<div class="mt-4">
    <a href="debug_notification_tables.php" class="btn btn-secondary">
        Debug Notification Tables
    </a>
    <a href="pages/scheduled-notifications-test.php" class="btn btn-secondary ms-2">
        Notification Dashboard
    </a>
</div>';

echo '</div>
</body>
</html>';
?> 