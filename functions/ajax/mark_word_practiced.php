<?php
require_once '../includes/db_connect.php';
require_once '../includes/vocabulary_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['word_id'])) {
    echo json_encode(['success' => false, 'message' => 'Word ID is required']);
    exit;
}

$word_id = intval($_POST['word_id']);
$user_id = 1; // Assuming user_id = 1 for now

$result = mark_word_as_practiced($conn, $word_id, $user_id);

echo json_encode([
    'success' => $result,
    'message' => $result ? 'Word marked as practiced successfully' : 'Error marking word as practiced'
]); 