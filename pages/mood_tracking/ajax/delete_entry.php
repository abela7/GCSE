<?php
// AJAX endpoint to delete a mood entry
require_once '../../../config/db_connect.php';
require_once '../includes/functions.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get entry ID
    $entry_id = isset($_POST['id']) ? intval($_POST['id']) : null;
    
    if ($entry_id) {
        // Delete the entry
        $result = deleteMoodEntry($entry_id);
        
        if ($result) {
            // Return success response
            echo json_encode(['success' => true]);
        } else {
            // Return error response
            echo json_encode(['success' => false, 'message' => 'Failed to delete mood entry']);
        }
    } else {
        // Return error response
        echo json_encode(['success' => false, 'message' => 'Invalid entry ID']);
    }
} else {
    // Return error response
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
