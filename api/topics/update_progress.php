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
$status = isset($data['status']) ? $data['status'] : 'not_started';
$confidence_level = isset($data['confidence_level']) ? intval($data['confidence_level']) : 0;
$notes = isset($data['notes']) ? $data['notes'] : '';
$subject = isset($data['subject']) ? $data['subject'] : '';

if ($topic_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid topic ID']);
    exit;
}

// Validate status
if (!in_array($status, ['not_started', 'in_progress', 'completed'])) {
    $status = 'not_started';
}

// Validate confidence level
if ($confidence_level < 0 || $confidence_level > 5) {
    $confidence_level = 0;
}

// Determine which progress table to use
$progress_table = ($subject === 'english') ? 'eng_topic_progress' : 'topic_progress';

// Update or insert progress record
$query = "
    INSERT INTO $progress_table (
        topic_id, 
        status, 
        confidence_level, 
        notes,
        last_studied,
        completion_date
    )
    VALUES (?, ?, ?, ?, NOW(), " . ($status === 'completed' ? 'NOW()' : 'NULL') . ")
    ON DUPLICATE KEY UPDATE
        status = ?,
        confidence_level = ?,
        notes = ?,
        last_studied = NOW(),
        completion_date = " . ($status === 'completed' ? 'NOW()' : 'NULL');

$stmt = $conn->prepare($query);
$stmt->bind_param('isissis', 
    $topic_id, $status, $confidence_level, $notes,
    $status, $confidence_level, $notes
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Progress updated successfully',
        'data' => [
            'status' => $status,
            'confidence_level' => $confidence_level,
            'notes' => $notes
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating progress']);
}
?> 