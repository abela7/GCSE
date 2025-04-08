<?php
// Email Configuration Settings

// SMTP Configuration
define('SMTP_HOST', 'abel.abuneteklehaymanot.org');
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl');
define('SMTP_AUTH', true);

// Your Domain Email credentials
define('SMTP_USERNAME', 'admin@abel.abuneteklehaymanot.org');
define('SMTP_PASSWORD', '2727@2121Abel'); // Add your email password here

// Email Settings
define('EMAIL_FROM_NAME', 'Amha-Silassie');
define('EMAIL_FROM_ADDRESS', 'admin@abel.abuneteklehaymanot.org');
define('EMAIL_REPLY_TO', 'admin@abel.abuneteklehaymanot.org');

// Notification Settings
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('DAILY_SUMMARY_TIME', '18:00'); // Time for daily summary emails (24-hour format)
define('MAX_RETRY_ATTEMPTS', 3);
define('RETRY_DELAY_MINUTES', 5); 