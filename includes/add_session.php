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
$subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
$date = isset($_POST['date']) ? clean_input($conn, $_POST['date']) : '';
$duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
$notes = isset($_POST['notes']) ? clean_input($conn, $_POST['notes']) : '';

// Validate input
if ($subject_id <= 0 || empty($date) || $duration <= 0) {
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

// Insert session
$insert_query = "INSERT INTO sessions (subject_id, date, duration, notes) VALUES (?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_query);
$insert_stmt->bind_param('isis', $subject_id, $date, $duration, $notes);
$success = $insert_stmt->execute();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Study session added successfully', 'session_id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add study session: ' . $insert_stmt->error]);
}

// Close database connection
close_connection($conn);
?>