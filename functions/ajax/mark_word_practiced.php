<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/vocabulary_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['word_id'])) {
    echo json_encode(['success' => false, 'message' => 'Word ID is required']);
    exit;
}

$word_id = intval($_POST['word_id']);
$user_id = $_SESSION['user_id'];

try {
    $result = mark_word_as_practiced($conn, $word_id, $user_id);
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Word marked as practiced successfully' : 'Error marking word as practiced'
    ]);
} catch (Exception $e) {
    error_log("Error in mark_word_practiced.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error marking word as practiced'
    ]);
} 