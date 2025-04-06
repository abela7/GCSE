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
$duration = isset($data['duration']) ? intval($data['duration']) : 0;
$subject = isset($data['subject']) ? $data['subject'] : '';

if ($topic_id <= 0 || $duration <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Determine which progress table to use
$progress_table = ($subject === 'english') ? 'eng_topic_progress' : 'topic_progress';

// Update or insert progress record
$query = "
    INSERT INTO $progress_table (topic_id, total_time_spent, last_studied)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE
    total_time_spent = total_time_spent + ?,
    last_studied = NOW()
";

$stmt = $conn->prepare($query);
$stmt->bind_param('iii', $topic_id, $duration, $duration);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Study time saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error saving study time']);
}
?> 