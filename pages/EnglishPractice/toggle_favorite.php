<?php
// GCSE/pages/EnglishPractice/toggle_favorite.php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';

// Ensure this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die('Direct access not permitted');
}

// Get the item ID from POST data
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
if (!$item_id) {
    die(json_encode(['success' => false, 'message' => 'Invalid item ID']));
}

// Check if the item exists
$check_sql = "SELECT id FROM practice_items WHERE id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param('i', $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    die(json_encode(['success' => false, 'message' => 'Item not found']));
}
$stmt->close();

// Check if item is already favorited
$check_favorite_sql = "SELECT id FROM favorite_practice_items WHERE practice_item_id = ?";
$stmt = $conn->prepare($check_favorite_sql);
$stmt->bind_param('i', $item_id);
$stmt->execute();
$result = $stmt->get_result();
$is_favorited = $result->num_rows > 0;
$stmt->close();

if ($is_favorited) {
    // Remove from favorites
    $sql = "DELETE FROM favorite_practice_items WHERE practice_item_id = ?";
    $action = 'unfavorited';
} else {
    // Add to favorites
    $sql = "INSERT INTO favorite_practice_items (practice_item_id) VALUES (?)";
    $action = 'favorited';
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $item_id);

if ($stmt->execute()) {
    $response = [
        'success' => true,
        'message' => 'Item ' . $action,
        'is_favorited' => !$is_favorited
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'Error updating favorite status'
    ];
}

$stmt->close();
echo json_encode($response); 