<?php
// Include database connection
require_once '../config/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if session_id is provided
if (!isset($_GET['session_id']) || empty($_GET['session_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session ID is required']);
    exit;
}

// Get the session ID
$session_id = (int)$_GET['session_id'];

// Validate session ID
if ($session_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

// Get session details
$query = "SELECT * FROM sessions WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $session_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Session not found']);
    exit;
}

// Get session data
$session = $result->fetch_assoc();

// Return session data
echo json_encode(['success' => true, 'session' => $session]);

// Close database connection
close_connection($conn);
?> 