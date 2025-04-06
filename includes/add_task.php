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
$description = isset($_POST['description']) ? clean_input($conn, $_POST['description']) : '';
$subject_id = isset($_POST['subject_id']) && !empty($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;
$due_date = isset($_POST['due_date']) && !empty($_POST['due_date']) ? clean_input($conn, $_POST['due_date']) : null;
$priority = isset($_POST['priority']) ? clean_input($conn, $_POST['priority']) : 'medium';

// Validate title
if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Task title is required']);
    exit;
}

// Validate priority
if (!in_array($priority, ['low', 'medium', 'high'])) {
    $priority = 'medium';
}

// Insert task
if ($subject_id === null && $due_date === null) {
    $insert_query = "INSERT INTO tasks (title, description, priority) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('sss', $title, $description, $priority);
} else if ($subject_id === null) {
    $insert_query = "INSERT INTO tasks (title, description, due_date, priority) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('ssss', $title, $description, $due_date, $priority);
} else if ($due_date === null) {
    $insert_query = "INSERT INTO tasks (title, description, subject_id, priority) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('ssis', $title, $description, $subject_id, $priority);
} else {
    $insert_query = "INSERT INTO tasks (title, description, subject_id, due_date, priority) VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('ssiss', $title, $description, $subject_id, $due_date, $priority);
}

$success = $insert_stmt->execute();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Task added successfully', 'task_id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add task: ' . $insert_stmt->error]);
}

// Close database connection
close_connection($conn);
?>