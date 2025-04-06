<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Parse JSON input
$data = json_decode(file_get_contents('php://input'), true);

$topic_id = isset($data['topic_id']) ? intval($data['topic_id']) : 0;
$content = isset($data['content']) ? $data['content'] : '';
$subject = isset($data['subject']) ? $data['subject'] : '';

if ($topic_id <= 0 || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$query = "
    INSERT INTO topic_notes (topic_id, content, created_at)
    VALUES (?, ?, NOW())
";

$stmt = $conn->prepare($query);
$stmt->bind_param('is', $topic_id, $content);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Note added successfully',
        'data' => [
            'id' => $conn->insert_id,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error adding note']);
}
?> 