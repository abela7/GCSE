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
    
    if (!isset($data['question_id'])) {
        throw new Exception('Missing question ID');
    }

    $db = getDBConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Delete the question
    $stmt = $db->prepare("DELETE FROM topic_questions WHERE id = :question_id");
    $stmt->execute([':question_id' => $data['question_id']]);
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Question deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }
    
    error_log('Error deleting question: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete question'
    ]);
} 