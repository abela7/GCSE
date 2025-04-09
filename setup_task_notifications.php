<?php
/**
 * Task Notifications Setup Script
 * 
 * This script will:
 * 1. Create the necessary database table for tracking task notifications
 * 2. Display instructions for setting up cron jobs
 */

require_once 'includes/db_connect.php';

echo '<!DOCTYPE html>
<html>
<head>
    <title>Task Notifications Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        pre { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Task Notifications Setup</h1>';

// Step 1: Create task_notification_tracking table
$table_created = false;
try {
    $sql = "
    CREATE TABLE IF NOT EXISTS `task_notification_tracking` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `task_id` int(11) NOT NULL,
      `notification_type` enum('due','reminder','habit') NOT NULL DEFAULT 'due',
      `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_task_notification` (`task_id`, `notification_type`, `sent_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $conn->query($sql);
    $table_created = true;
    
    echo '<div class="alert alert-success">
            <h4>Database Setup Complete!</h4>
            <p>Successfully created the task_notification_tracking table.</p>
          </div>';
} catch (Exception $e) {
    echo '<div class="alert alert-danger">
            <h4>Database Setup Error</h4>
            <p>Error creating the task_notification_tracking table: ' . $e->getMessage() . '</p>
          </div>';
}

// Step 2: Display cron job setup instructions
echo '
<div class="card mt-4">
    <div class="card-header">
        <h2 class="card-title">Cron Job Setup Instructions</h2>
    </div>
    <div class="card-body">
        <p>To enable automated task and habit notifications, you need to set up the following cron jobs on your server:</p>
        
        <h4>Task Notifications</h4>
        <p>This should run every 5-15 minutes to check for upcoming tasks and send notifications:</p>
        <pre>*/10 * * * * php ' . __DIR__ . '/emails/cron/task_notifications.php</pre>
        
        <h4>Habit Notifications</h4>
        <p>This should also run every 5-15 minutes to check for upcoming habits and send notifications:</p>
        <pre>*/10 * * * * php ' . __DIR__ . '/emails/cron/habit_notifications.php</pre>
        
        <div class="alert alert-info mt-3">
            <h5>To set up these cron jobs:</h5>
            <ol>
                <li>Access your server\'s crontab configuration with: <code>crontab -e</code></li>
                <li>Add the above lines to the crontab file</li>
                <li>Save and exit the editor</li>
            </ol>
            <p>This will run the notification scripts every 10 minutes. Adjust the timing as needed.</p>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h2 class="card-title">Email Configuration</h2>
    </div>
    <div class="card-body">
        <p>Make sure your email configuration is correctly set up in <code>config/email_config.php</code>.</p>
        <p>Current email settings:</p>
        <ul>';
        
// Display current email settings
$email_settings = [
    'SMTP Host' => defined('SMTP_HOST') ? SMTP_HOST : 'Not defined',
    'SMTP Port' => defined('SMTP_PORT') ? SMTP_PORT : 'Not defined',
    'SMTP Security' => defined('SMTP_SECURE') ? SMTP_SECURE : 'Not defined',
    'From Name' => defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Not defined',
    'Email Notifications Enabled' => defined('ENABLE_EMAIL_NOTIFICATIONS') ? (ENABLE_EMAIL_NOTIFICATIONS ? 'Yes' : 'No') : 'Not defined'
];

foreach ($email_settings as $setting => $value) {
    echo '<li><strong>' . $setting . ':</strong> ' . $value . '</li>';
}

echo '
        </ul>
        
        <div class="alert alert-warning mt-3">
            <h5>Testing Email Functionality</h5>
            <p>To test if your email configuration is working correctly, visit <a href="test_email.php">test_email.php</a>.</p>
        </div>
    </div>
</div>

<div class="mt-4 text-center">
    <a href="index.php" class="btn btn-primary">Return to Dashboard</a>
</div>

</div>
</body>
</html>';
?> 