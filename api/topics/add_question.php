<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['topic_id']) || !isset($data['question'])) {
        throw new Exception('Missing required fields');
    }

    $db = getDBConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Insert new question
    $stmt = $db->prepare("
        INSERT INTO topic_questions (topic_id, question, answer, created_at)
        VALUES (:topic_id, :question, :answer, NOW())
    ");
    
    $stmt->execute([
        ':topic_id' => $data['topic_id'],
        ':question' => $data['question'],
        ':answer' => $data['answer'] ?? null
    ]);
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Question added successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }
    
    error_log('Error adding question: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add question'
    ]);
}
?> 