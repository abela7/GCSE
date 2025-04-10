<?php
// Include database connection
require_once '../config/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if session ID is provided
if (!isset($_GET['session_id']) || empty($_GET['session_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Session ID is required']);
    exit;
}

// Get session ID
$session_id = (int)$_GET['session_id'];

// Validate session ID
if ($session_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
    exit;
}

try {
    // Get session details
    $query = "SELECT id, subject_id, date, duration, notes FROM sessions WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param('i', $session_id);
    $execution_result = $stmt->execute();
    
    if (!$execution_result) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
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
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} finally {
    // Close database connection
    close_connection($conn);
}
?> 