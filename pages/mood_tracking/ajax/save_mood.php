<?php
// Include required files
require_once __DIR__ . '/../includes/functions.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'entry_id' => null
];

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get form data
$entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : null;
$mood_level = isset($_POST['mood_level']) ? intval($_POST['mood_level']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$tags = isset($_POST['tags']) ? $_POST['tags'] : '';
$date_time = isset($_POST['date_time']) ? $_POST['date_time'] : date('Y-m-d H:i:s');

// Validate mood level
if (!$mood_level || $mood_level < 1 || $mood_level > 5) {
    $response['message'] = 'Invalid mood level';
    echo json_encode($response);
    exit;
}

// Convert tags to array if not empty
$tag_ids = [];
if (!empty($tags)) {
    $tag_ids = explode(',', $tags);
    $tag_ids = array_map('intval', $tag_ids);
    $tag_ids = array_filter($tag_ids);
}

// Save or update mood entry
try {
    if ($entry_id) {
        // Update existing entry
        $result = updateMoodEntry($entry_id, $mood_level, $notes, $date_time, $tag_ids);
        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Mood entry updated successfully';
            $response['entry_id'] = $entry_id;
        } else {
            $response['message'] = 'Failed to update mood entry';
        }
    } else {
        // Create new entry
        $entry_id = addMoodEntry($mood_level, $notes, $date_time, $tag_ids);
        if ($entry_id) {
            $response['success'] = true;
            $response['message'] = 'Mood entry saved successfully';
            $response['entry_id'] = $entry_id;
        } else {
            $response['message'] = 'Failed to save mood entry';
        }
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
