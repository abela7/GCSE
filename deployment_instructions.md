# Deployment Instructions for Enhanced Mood Tracking Feature

## Overview
This document provides step-by-step instructions for deploying the enhanced mood tracking feature to your live website at abel.abuneteklehaymanot.org.

## Prerequisites
- Access to your web server via FTP or file manager
- Access to your MySQL database via phpMyAdmin or similar tool
- Backup of your current website files and database (recommended)

## Deployment Steps

### 1. Database Updates
First, you need to update your database structure to support the enhanced mood tracking feature:

1. Log in to your phpMyAdmin
2. Select your database (`abunetdg_web_app`)
3. Go to the "SQL" tab
4. Copy and paste the following SQL code:

```sql
-- Add tags table
CREATE TABLE IF NOT EXISTS `mood_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#6c757d',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tag_name` (`name`)
);

-- Add junction table for mood entries and tags
CREATE TABLE IF NOT EXISTS `mood_entry_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mood_entry_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entry_tag` (`mood_entry_id`,`tag_id`),
  KEY `fk_tag_entry` (`tag_id`),
  CONSTRAINT `fk_mood_entry_tag` FOREIGN KEY (`mood_entry_id`) REFERENCES `mood_entries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tag_entry` FOREIGN KEY (`tag_id`) REFERENCES `mood_tags` (`id`) ON DELETE CASCADE
);

-- Insert default tags
INSERT INTO `mood_tags` (`name`, `category`, `color`) VALUES
('Health', 'Personal', '#28a745'),
('Family', 'Personal', '#17a2b8'),
('Relationship', 'Personal', '#e83e8c'),
('School', 'Academic', '#007bff'),
('Work', 'Professional', '#fd7e14');
```

5. Click "Go" to execute the SQL code

### 2. File Structure Setup
Create the necessary directory structure on your server:

1. Connect to your server via FTP or file manager
2. Navigate to your website root directory
3. Create the following directory structure if it doesn't exist:
   ```
   pages/
   └── mood_tracking/
       ├── ajax/
       └── includes/
   ```

### 3. File Deployment
Upload the enhanced mood tracking files to your server:

#### Core Files
1. Upload `pages/mood_tracking/index.php`
2. Upload `pages/mood_tracking/entry.php`
3. Upload `pages/mood_tracking/history.php`
4. Upload `pages/mood_tracking/analytics.php`
5. Upload `pages/mood_tracking/settings.php`

#### AJAX Endpoints
1. Upload `pages/mood_tracking/ajax/get_recent_entries.php`
2. Upload `pages/mood_tracking/ajax/delete_entry.php`
3. Upload `pages/mood_tracking/ajax/get_topics.php`

#### Backend Functions
1. Upload `pages/mood_tracking/includes/functions.php`

### 4. Update Navigation
Update your header file to include the new mood tracking navigation:

1. Open your `includes/header.php` file
2. Find the navigation section (usually inside a `<ul>` or `<nav>` element)
3. Add the following menu item:
   ```html
   <li class="nav-item">
     <a class="nav-link" href="<?php echo $base_url; ?>pages/mood_tracking/index.php">
       <i class="fas fa-smile"></i> Mood Tracker
     </a>
   </li>
   ```

### 5. Verify Installation
After deploying all files, verify that the enhanced mood tracking feature is working correctly:

1. Visit your website at abel.abuneteklehaymanot.org
2. Navigate to the Mood Tracker section
3. Test the following functionality:
   - View the mood tracking dashboard
   - Add a new mood entry
   - View mood history with filters
   - Check analytics visualizations
   - Manage tags in settings

### 6. Troubleshooting
If you encounter any issues during deployment:

1. **Database Connection Issues**
   - Verify that the database connection details in `config/db_connect.php` are correct
   - Check that the new tables were created successfully in phpMyAdmin

2. **File Path Issues**
   - Ensure that all file paths in the include statements are correct for your server
   - The paths should be relative to your website root directory

3. **Permission Issues**
   - Make sure all uploaded files have the correct permissions (typically 644 for files and 755 for directories)

4. **JavaScript or CSS Issues**
   - Clear your browser cache
   - Check the browser console for any JavaScript errors

### 7. Backup and Rollback Plan
In case you need to revert to the previous version:

1. Keep a backup of your original files before making changes
2. If needed, restore the original files from your backup
3. You can safely drop the new tables (`mood_tags` and `mood_entry_tags`) if you need to roll back

## Additional Notes

### Mobile Responsiveness
The enhanced mood tracking feature is fully responsive and optimized for mobile devices. Test the interface on different screen sizes to ensure it displays correctly.

### Browser Compatibility
The feature has been tested on modern browsers (Chrome, Firefox, Safari, Edge). If you notice any compatibility issues with specific browsers, please let me know.

### Documentation
Refer to the `mood_tracking_documentation.md` file for detailed information about the enhanced mood tracking feature, including its functionality, technical implementation, and user guide.

## Support
If you need any assistance with the deployment or encounter any issues, please don't hesitate to reach out for help.
