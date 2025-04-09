<?php
require_once '../../includes/header.php';
require_once '../../includes/db_connect.php';  // Database connection

// Get form data
$name = $_POST['name'] ?? '';
$category_id = $_POST['category_id'] ?? null;
$point_rule_id = $_POST['point_rule_id'] ?? null;
$target_time = $_POST['target_time'] ?? null;
$description = $_POST['description'] ?? '';

// Validate required fields
if (!$name || !$category_id || !$point_rule_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Insert new habit
$insert_query = "INSERT INTO habits 
                (category_id, point_rule_id, name, description, target_time, 
                 current_points, total_completions, total_procrastinated, total_skips, 
                 current_streak, longest_streak, success_rate, is_active) 
                VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0.00, 1)";

$stmt = $conn->prepare($insert_query);
$stmt->bind_param("iisss", $category_id, $point_rule_id, $name, $description, $target_time);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
} 