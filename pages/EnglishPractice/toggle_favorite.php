<?php
// GCSE/pages/EnglishPractice/toggle_favorite.php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';

// Check if it's an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Get the item ID from POST data
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

if ($item_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

// Check if the item exists
$stmt = $conn->prepare("SELECT id FROM practice_items WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

// Check if the item is already favorited
$stmt = $conn->prepare("SELECT id FROM favorite_practice_items WHERE practice_item_id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Remove from favorites
    $stmt = $conn->prepare("DELETE FROM favorite_practice_items WHERE practice_item_id = ?");
    $stmt->bind_param("i", $item_id);
    $success = $stmt->execute();
    $message = "Removed from favorites";
    $is_favorited = false;
} else {
    // Add to favorites
    $stmt = $conn->prepare("INSERT INTO favorite_practice_items (practice_item_id) VALUES (?)");
    $stmt->bind_param("i", $item_id);
    $success = $stmt->execute();
    $message = "Added to favorites";
    $is_favorited = true;
}

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'is_favorited' => $is_favorited
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
} 