<?php
// Include database connection
require_once '../config/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get and validate input
    $session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;

    // Validate input
    if ($session_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
        exit;
    }
    
    // Verify session exists
    $check_query = "SELECT id FROM sessions WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    
    if (!$check_stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $check_stmt->bind_param('i', $session_id);
    $execution_result = $check_stmt->execute();
    
    if (!$execution_result) {
        throw new Exception("Query execution failed: " . $check_stmt->error);
    }
    
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }

    // Delete session
    $delete_query = "DELETE FROM sessions WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    
    if (!$delete_stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $delete_stmt->bind_param('i', $session_id);
    $success = $delete_stmt->execute();

    if (!$success) {
        throw new Exception("Failed to delete study session: " . $delete_stmt->error);
    }
    
    // Check if any rows were affected
    if ($delete_stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Session not found or already deleted']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Study session deleted successfully']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} finally {
    // Close database connection
    close_connection($conn);
}
?>