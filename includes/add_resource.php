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
$title = isset($_POST['title']) ? clean_input($conn, $_POST['title']) : '';
$subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
$type = isset($_POST['type']) ? clean_input($conn, $_POST['type']) : '';
$link = isset($_POST['link']) && !empty($_POST['link']) ? clean_input($conn, $_POST['link']) : null;
$notes = isset($_POST['notes']) ? clean_input($conn, $_POST['notes']) : '';

// Validate input
if (empty($title) || $subject_id <= 0 || empty($type)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

// Validate resource type
$valid_types = ['book', 'website', 'video', 'document', 'app', 'other'];
if (!in_array($type, $valid_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid resource type']);
    exit;
}

// Insert resource
if ($link === null) {
    $insert_query = "INSERT INTO resources (subject_id, title, type, notes) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('isss', $subject_id, $title, $type, $notes);
} else {
    $insert_query = "INSERT INTO resources (subject_id, title, type, link, notes) VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('issss', $subject_id, $title, $type, $link, $notes);
}

$success = $insert_stmt->execute();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Resource added successfully', 'resource_id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add resource: ' . $insert_stmt->error]);
}

// Close database connection
close_connection($conn);
?>