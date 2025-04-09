<?php
/**
 * Quick Mood Entry Script
 * This script receives parameters from email links and saves mood entries directly
 * 
 * Parameters:
 * - mood: 0-5 (Awful to Awesome)
 * - period: midday, evening, night
 * - time: HH:MM format (optional)
 */

session_start();
require_once '../../includes/db_connect.php';

// Set timezone
date_default_timezone_set('Europe/London');

// Default redirect location
$redirect_location = 'index.php?status=success';

// Check if parameters exist
if (!isset($_GET['mood']) || !isset($_GET['period'])) {
    header('Location: entry.php?error=missing_parameters');
    exit();
}

// Get parameters
$mood_value = intval($_GET['mood']);
$period = $_GET['period'];
$time = isset($_GET['time']) ? $_GET['time'] : date('H:i');
$date = date('Y-m-d');

// Validate mood value (0-5)
if ($mood_value < 0 || $mood_value > 5) {
    header('Location: entry.php?error=invalid_mood');
    exit();
}

// Map mood value to text
$mood_labels = [
    0 => 'Awful',
    1 => 'Bad',
    2 => 'Meh',
    3 => 'Okay',
    4 => 'Good',
    5 => 'Awesome'
];

$mood_text = $mood_labels[$mood_value];

// Automatically generate notes based on period and mood
$notes = "Quick entry from email: Feeling $mood_text during $period.";

// Get time within acceptable format
list($hours, $minutes) = explode(':', $time);
$formatted_time = sprintf('%02d:%02d:00', intval($hours), intval($minutes));

// Check if an entry already exists for this period and date
$check_query = "SELECT id FROM mood_entries WHERE entry_date = ? AND period = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ss", $date, $period);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Update existing entry
    $row = $check_result->fetch_assoc();
    $entry_id = $row['id'];
    
    $update_query = "UPDATE mood_entries SET 
                    mood_value = ?, 
                    mood_text = ?,
                    entry_time = ?,
                    notes = ?,
                    updated_at = NOW()
                    WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("isssi", $mood_value, $mood_text, $formatted_time, $notes, $entry_id);
    
    if ($update_stmt->execute()) {
        // Success
        $redirect_location = 'index.php?status=updated';
    } else {
        // Error
        $redirect_location = 'entry.php?error=update_failed';
    }
} else {
    // Insert new entry
    $insert_query = "INSERT INTO mood_entries 
                    (entry_date, period, mood_value, mood_text, entry_time, notes, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ssisis", $date, $period, $mood_value, $mood_text, $formatted_time, $notes);
    
    if ($insert_stmt->execute()) {
        // Success
        $redirect_location = 'index.php?status=saved';
    } else {
        // Error
        $redirect_location = 'entry.php?error=save_failed';
    }
}

// Close connection
$conn->close();

// Redirect to the mood tracking page
header("Location: $redirect_location");
exit();
?> 