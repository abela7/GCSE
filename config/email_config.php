<?php
// Email Configuration Settings

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);

// Your Gmail credentials
define('SMTP_USERNAME', 'abelgoytom77@gmail.com'); // Add your Gmail address here
define('SMTP_PASSWORD', 'wmfm sgyn ucuz mlbu'); // Add your App Password here

// Email Settings
define('EMAIL_FROM_NAME', 'GCSE Study App');
define('EMAIL_FROM_ADDRESS', 'abelgoytom77@gmail.com'); // Add your Gmail address here
define('EMAIL_REPLY_TO', 'abelgoytom77@gmail.com'); // Add your Gmail address here

// Notification Settings
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('DAILY_SUMMARY_TIME', '18:00'); // Time for daily summary emails (24-hour format)
define('MAX_RETRY_ATTEMPTS', 3);
define('RETRY_DELAY_MINUTES', 5); 