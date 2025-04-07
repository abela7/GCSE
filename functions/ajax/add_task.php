<?php
require_once '../includes/db_connect.php';
require_once '../includes/task_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate required fields
$required_fields = ['title', 'category_id', 'priority'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
        exit;
    }
}

// Prepare task data
$task_data = [
    'title' => trim($_POST['title']),
    'category_id' => intval($_POST['category_id']),
    'priority' => $_POST['priority'],
    'due_time' => !empty($_POST['due_time']) ? $_POST['due_time'] : null
];

$result = add_task($conn, $task_data);

echo json_encode([
    'success' => $result !== false,
    'message' => $result !== false ? 'Task added successfully' : 'Error adding task',
    'task_id' => $result
]); 