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

// Validate input
if ($session_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

// Delete session
$delete_query = "DELETE FROM sessions WHERE id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param('i', $session_id);
$success = $delete_stmt->execute();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Study session deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete study session: ' . $delete_stmt->error]);
}

// Close database connection
close_connection($conn);
?>