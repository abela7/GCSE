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

try {
    // Start transaction
    $conn->begin_transaction();

    // First, check if a record exists
    $check_query = "SELECT id FROM $progress_table WHERE topic_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('i', $topic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $update_query = "
            UPDATE $progress_table 
            SET status = ?,
                confidence_level = ?,
                notes = ?,
                last_studied = NOW(),
                completion_date = " . ($status === 'completed' ? 'NOW()' : 'NULL') . "
            WHERE topic_id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('sisi', $status, $confidence_level, $notes, $topic_id);
        $stmt->execute();
    } else {
        // Insert new record
        $insert_query = "
            INSERT INTO $progress_table (
                topic_id, 
                status, 
                confidence_level, 
                notes,
                last_studied,
                completion_date
            )
            VALUES (?, ?, ?, ?, NOW(), " . ($status === 'completed' ? 'NOW()' : 'NULL') . ")";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param('isis', $topic_id, $status, $confidence_level, $notes);
        $stmt->execute();
    }

    // Update section progress
    if ($subject !== 'english') {
        $update_section_progress = "
            UPDATE section_progress sp
            JOIN math_sections ms ON sp.section_id = ms.id
            JOIN math_subsections msub ON ms.id = msub.section_id
            JOIN math_topics mt ON msub.id = mt.subsection_id
            SET sp.completed_topics = (
                SELECT COUNT(*)
                FROM topic_progress tp2
                JOIN math_topics mt2 ON tp2.topic_id = mt2.id
                JOIN math_subsections msub2 ON mt2.subsection_id = msub2.id
                WHERE msub2.section_id = ms.id
                AND tp2.status = 'completed'
            )
            WHERE mt.id = ?";
        
        $stmt = $conn->prepare($update_section_progress);
        $stmt->bind_param('i', $topic_id);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Progress updated successfully',
        'data' => [
            'status' => $status,
            'confidence_level' => $confidence_level,
            'notes' => $notes
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error updating progress: ' . $e->getMessage()]);
}

$conn->close();
?> 