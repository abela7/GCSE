<?php
require_once '../../includes/db_connect.php';

// Get the category ID from the query string
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    // Invalid ID
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid category ID']);
    exit;
}

// Get the category data from the database
$query = "SELECT id, name, icon, color FROM task_categories WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Category not found
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Category not found']);
    exit;
}

// Return the category data as JSON
$category = $result->fetch_assoc();
header('Content-Type: application/json');
echo json_encode($category);