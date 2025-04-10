<?php
// Include database connection
require_once '../config/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if session ID is provided
if (!isset($_GET['session_id']) || empty($_GET['session_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session ID is required']);
    exit;
}

// Get session ID
$session_id = (int)$_GET['session_id'];

// Validate session ID
if ($session_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

// Get session details
$query = "SELECT id, subject_id, date, duration, notes FROM sessions WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $session_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Session not found']);
    exit;
}

// Fetch session data
$session = $result->fetch_assoc();

// Return session data
echo json_encode([
    'success' => true,
    'session' => [
        'id' => $session['id'],
        'subject_id' => $session['subject_id'],
        'date' => $session['date'],
        'duration' => $session['duration'],
        'notes' => $session['notes']
    ]
]);

// Close database connection
close_connection($conn);
?> 