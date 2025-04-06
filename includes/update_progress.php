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
$subtopic_id = isset($_POST['subtopic_id']) ? (int)$_POST['subtopic_id'] : 0;
$subject = isset($_POST['subject']) ? clean_input($conn, $_POST['subject']) : '';
$status = isset($_POST['status']) ? clean_input($conn, $_POST['status']) : 'not_started';
$confidence = isset($_POST['confidence']) ? (int)$_POST['confidence'] : 0;
$notes = isset($_POST['notes']) ? clean_input($conn, $_POST['notes']) : '';

// Validate input
if ($subtopic_id <= 0 || !in_array($subject, ['english', 'math'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
    exit;
}

// Validate status
if (!in_array($status, ['not_started', 'in_progress', 'completed'])) {
    $status = 'not_started';
}

// Validate confidence (0-5)
if ($confidence < 0 || $confidence > 5) {
    $confidence = 0;
}

// Set the current date for last_studied if status is in_progress or completed
$last_studied = ($status == 'not_started') ? null : date('Y-m-d');

// Determine which table to update based on subject
$table_prefix = ($subject == 'english') ? 'eng' : 'math';
$progress_table = $table_prefix . '_progress';

// Check if a record already exists
$check_query = "SELECT id FROM $progress_table WHERE subtopic_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('i', $subtopic_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Update existing record
    $progress_id = $check_result->fetch_assoc()['id'];
    
    if ($last_studied === null) {
        $update_query = "UPDATE $progress_table SET status = ?, confidence = ?, notes = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param('sisi', $status, $confidence, $notes, $progress_id);
    } else {
        $update_query = "UPDATE $progress_table SET status = ?, confidence = ?, last_studied = ?, notes = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param('sissi', $status, $confidence, $last_studied, $notes, $progress_id);
    }
    
    $success = $update_stmt->execute();
    
    if (!$success) {
        echo json_encode(['success' => false, 'message' => 'Failed to update progress: ' . $update_stmt->error]);
        exit;
    }
} else {
    // Insert new record
    if ($last_studied === null) {
        $insert_query = "INSERT INTO $progress_table (subtopic_id, status, confidence, notes) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('isis', $subtopic_id, $status, $confidence, $notes);
    } else {
        $insert_query = "INSERT INTO $progress_table (subtopic_id, status, confidence, last_studied, notes) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('isiss', $subtopic_id, $status, $confidence, $last_studied, $notes);
    }
    
    $success = $insert_stmt->execute();
    
    if (!$success) {
        echo json_encode(['success' => false, 'message' => 'Failed to insert progress: ' . $insert_stmt->error]);
        exit;
    }
}

// Map status to display text
$status_text = ucfirst(str_replace('_', ' ', $status));

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Progress updated successfully',
    'status' => $status,
    'status_text' => $status_text,
    'confidence' => $confidence,
    'last_studied' => $last_studied ? date('M j, Y', strtotime($last_studied)) : 'Never'
]);

// Close database connection
close_connection($conn);
?>