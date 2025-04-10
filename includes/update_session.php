<?php
// Include database connection
require_once '../config/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate input
$session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
$subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
$date = isset($_POST['date']) ? $_POST['date'] : '';
$duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

// Validate input
if ($session_id <= 0 || $subject_id <= 0 || empty($date) || $duration <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// Validate duration (5 minutes to 8 hours)
if ($duration < 5 || $duration > 480) {
    echo json_encode(['success' => false, 'message' => 'Duration must be between 5 and 480 minutes']);
    exit;
}

// Clean inputs
$date = $conn->real_escape_string($date);
$notes = $conn->real_escape_string($notes);

// Update session
$update_query = "UPDATE sessions SET subject_id = ?, date = ?, duration = ?, notes = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param('isisi', $subject_id, $date, $duration, $notes, $session_id);
$success = $update_stmt->execute();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Study session updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update study session: ' . $update_stmt->error]);
}

// Close database connection
close_connection($conn);
?> 