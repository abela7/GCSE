# Enhanced Mood Tracking Feature Documentation

## Overview
This document provides comprehensive documentation for the enhanced mood tracking feature implemented for the GCSE website. The enhancements include a reorganized file structure, improved UI/UX, advanced filtering capabilities, comprehensive visualization tools, and a tag management system.

## Key Enhancements

### 1. Reorganized Structure
- Created a dedicated `mood_tracking` folder in the pages directory
- Organized files into a logical structure:
  - `index.php` - Main dashboard with calendar view and statistics
  - `entry.php` - Add/edit mood entries with tag selection
  - `history.php` - View and filter mood history
  - `analytics.php` - Detailed mood analysis and visualizations
  - `settings.php` - Tag management interface
  - `ajax/` - AJAX endpoints for dynamic functionality
  - `includes/functions.php` - Backend functionality

### 2. Enhanced Mood Entry System
- Improved mood entry form with emoji-based rating system
- Added tag selection with predefined and custom tags
- Implemented edit functionality for existing entries
- Added subject and topic association (maintained from original implementation)
- Improved mobile responsiveness for better usability on all devices

### 3. Comprehensive Visualization
- Added calendar view with color-coded mood indicators
- Implemented mood distribution charts
- Created mood trend analysis over time
- Added time-of-day mood analysis
- Provided mood insights based on collected data

### 4. Advanced Filtering System
- Filter by date range
- Filter by mood level
- Filter by tags
- Filter by time of day
- Search functionality for notes
- Combination filtering for detailed analysis

### 5. Tag Management System
- Implemented the requested predefined tags:
  - Health
  - Family
  - Relationship
  - School
  - Work
- Added ability to create custom tags
- Implemented tag categories for organization
- Added color customization for visual identification

### 6. Mobile Responsiveness
- Optimized all interfaces for mobile devices
- Improved touch targets for better usability
- Responsive layouts that adapt to different screen sizes
- Mobile-friendly charts and visualizations

## Technical Implementation

### Database Structure
Added two new tables to the database:
```sql
CREATE TABLE IF NOT EXISTS `mood_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#6c757d',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tag_name` (`name`)
);

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
```

### Backend Functions
Enhanced the backend functionality with new functions:
- `createMoodEntry()` - Create new mood entries with tags
- `updateMoodEntry()` - Update existing mood entries
- `getMoodEntry()` - Get a single mood entry by ID
- `getMoodEntries()` - Get mood entries with advanced filtering
- `getMoodEntriesByDay()` - Get mood entries grouped by day for calendar view
- `getMoodTags()` - Get all mood tags
- `getMoodTagCategories()` - Get unique tag categories
- `createMoodTag()` - Create a new mood tag
- `updateMoodTag()` - Update an existing mood tag
- `deleteMoodTag()` - Delete a mood tag
- `getMoodStatistics()` - Get comprehensive mood statistics with filtering

### AJAX Endpoints
Created AJAX endpoints for dynamic functionality:
- `get_recent_entries.php` - Get recent mood entries for dashboard
- `delete_entry.php` - Delete a mood entry
- `get_topics.php` - Get topics for a selected subject

## User Guide

### Dashboard (index.php)
The dashboard provides an overview of your mood tracking:
- Calendar view with color-coded days based on mood
- Average mood statistics
- Mood distribution chart
- Mood by time of day chart
- Quick access to recent entries

### Adding/Editing Entries (entry.php)
To add a new mood entry:
1. Click "Add Mood Entry" from any page
2. Select your mood level (1-5)
3. Choose relevant tags
4. Optionally select a subject and topic
5. Add notes about your mood
6. Click "Save Mood Entry"

To edit an existing entry:
1. Click the edit icon on any entry
2. Modify the details as needed
3. Click "Update Mood Entry"

### Viewing History (history.php)
The history page allows you to view and filter your mood entries:
- Filter by date range
- Filter by mood level
- Filter by tags
- Filter by time of day
- Search in notes
- View detailed entry information

### Analytics (analytics.php)
The analytics page provides detailed insights into your mood patterns:
- Mood distribution chart
- Mood trend over time
- Mood by time of day analysis
- Common tags analysis
- Automated mood insights

### Managing Tags (settings.php)
The settings page allows you to manage your mood tags:
1. Click "Add New Tag" to create a custom tag
2. Enter a name, optional category, and select a color
3. Use the edit icon to modify existing tags
4. Use the delete icon to remove tags you no longer need

## Mobile Usage
The enhanced mood tracking feature is fully responsive and optimized for mobile devices:
- Simplified layouts on smaller screens
- Touch-friendly controls
- Optimized charts for mobile viewing
- Easy navigation between pages

## Backward Compatibility
The enhanced mood tracking feature maintains compatibility with the original implementation:
- Existing mood entries are preserved
- Original mood factors are still supported
- Subject and topic associations are maintained

## Future Enhancement Opportunities
Potential future enhancements could include:
1. **Advanced Analytics**
   - Correlation between mood and academic performance
   - Predictive mood analysis based on patterns
   - Weekly and monthly mood reports

2. **Integration with Study Sessions**
   - Automatic mood prompts after study sessions
   - Mood-based study recommendations

3. **Notification System**
   - Reminders to track mood
   - Alerts for mood pattern changes

4. **Data Export**
   - Export mood data to CSV or PDF
   - Generate printable mood reports
