<?php
// Include required files
require_once __DIR__ . '/../includes/functions.php';

// Set header to return JSON
header('Content-Type: application/json');

try {
    // Get POST data
    $mood_level = isset($_POST['mood_level']) ? intval($_POST['mood_level']) : null;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
    $tags = isset($_POST['tags']) && !empty($_POST['tags']) ? explode(',', $_POST['tags']) : [];
    $date_time = isset($_POST['date_time']) ? $_POST['date_time'] : date('Y-m-d H:i:s');
    $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : null;

    // Validate mood level
    if (!$mood_level || $mood_level < 1 || $mood_level > 5) {
        throw new Exception('Invalid mood level');
    }

    // Create or update mood entry
    if ($entry_id) {
        // Update existing entry
        $result = updateMoodEntry($entry_id, $mood_level, $notes, $tags, $date_time);
        $message = 'Mood entry updated successfully';
    } else {
        // Create new entry
        $result = createMoodEntry($mood_level, $notes, $tags, $date_time);
        $message = 'Mood entry saved successfully';
    }

    if ($result === false) {
        throw new Exception('Failed to save mood entry');
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => $message,
        'entry_id' => $result
    ]);

} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 