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
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    $date = isset($_POST['date']) ? clean_input($conn, $_POST['date']) : '';
    $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
    $notes = isset($_POST['notes']) ? clean_input($conn, $_POST['notes']) : '';

    // Validate input
    $errors = [];
    
    if ($session_id <= 0) {
        $errors[] = "Invalid session ID";
    }
    
    if ($subject_id <= 0) {
        $errors[] = "Invalid subject ID";
    }
    
    if (empty($date)) {
        $errors[] = "Date is required";
    }
    
    if ($duration <= 0) {
        $errors[] = "Invalid duration";
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
        exit;
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }

    // Validate duration (5 minutes to 8 hours)
    if ($duration < 5 || $duration > 480) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Duration must be between 5 and 480 minutes']);
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
    
    // Verify subject exists
    $subj_query = "SELECT id FROM subjects WHERE id = ?";
    $subj_stmt = $conn->prepare($subj_query);
    
    if (!$subj_stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $subj_stmt->bind_param('i', $subject_id);
    $subj_execution = $subj_stmt->execute();
    
    if (!$subj_execution) {
        throw new Exception("Query execution failed: " . $subj_stmt->error);
    }
    
    $subj_result = $subj_stmt->get_result();
    
    if ($subj_result->num_rows === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Subject not found']);
        exit;
    }

    // Update session
    $update_query = "UPDATE sessions SET subject_id = ?, date = ?, duration = ?, notes = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    
    if (!$update_stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $update_stmt->bind_param('isisi', $subject_id, $date, $duration, $notes, $session_id);
    $success = $update_stmt->execute();

    if (!$success) {
        throw new Exception("Failed to update study session: " . $update_stmt->error);
    }
    
    // Check if any rows were affected
    if ($update_stmt->affected_rows === 0) {
        // No rows were updated - could mean the data is the same or the session doesn't exist
        // Since we checked for existence earlier, it's likely the data is the same
        echo json_encode(['success' => true, 'message' => 'No changes were made to the study session']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Study session updated successfully']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} finally {
    // Close database connection
    close_connection($conn);
}
?> 