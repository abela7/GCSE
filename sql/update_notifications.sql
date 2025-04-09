-- Add notification tracking to tasks table
ALTER TABLE tasks
ADD COLUMN notification_sent TINYINT(1) DEFAULT 0 AFTER status;

-- Add notification tracking to habits table
ALTER TABLE habits 
ADD COLUMN notification_sent_date DATE DEFAULT NULL AFTER status;

-- Create logs directory if it doesn't exist
-- This would typically be done in a shell script, but including it here for reference
-- mkdir -p /path/to/website/logs
-- touch /path/to/website/logs/email_notifications.log
-- chmod 755 /path/to/website/logs
-- chmod 644 /path/to/website/logs/email_notifications.log 