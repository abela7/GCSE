<?php
// Prevent any HTML error output
header('Content-Type: application/json');
error_reporting(0);

require_once '../../includes/config.php';

try {
    // Get the POST data
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['topic_id']) || !isset($data['confidence_level']) || !isset($data['subject'])) {
        throw new Exception('Missing required parameters');
    }

    $topic_id = intval($data['topic_id']);
    $confidence_level = intval($data['confidence_level']);
    $subject = strtolower($data['subject']); // Normalize subject to lowercase

    // Validate confidence level (1-5)
    if ($confidence_level < 1 || $confidence_level > 5) {
        throw new Exception('Invalid confidence level');
    }

    // Determine the correct table name
    $table_prefix = ($subject === 'math' || $subject === 'maths') ? 'topic' : 
                   (($subject === 'eng' || $subject === 'english') ? 'eng_topic' : '');
    
    if (empty($table_prefix)) {
        throw new Exception('Invalid subject');
    }

    // First try to update existing record
    $sql = "UPDATE {$table_prefix}_progress SET confidence_level = ? WHERE topic_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("ii", $confidence_level, $topic_id);
    $stmt->execute();

    // If no rows were updated, try to insert a new record
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        
        // Check if record exists
        $check_sql = "SELECT id FROM {$table_prefix}_progress WHERE topic_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $topic_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            // Record doesn't exist, insert new one
            $insert_sql = "INSERT INTO {$table_prefix}_progress (topic_id, confidence_level, status) VALUES (?, ?, 'in_progress')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ii", $topic_id, $confidence_level);
            $insert_stmt->execute();
            
            if ($insert_stmt->affected_rows === 0) {
                throw new Exception('Failed to insert new record');
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    } else {
        $stmt->close();
    }

    echo json_encode([
        'success' => true,
        'confidence_level' => $confidence_level,
        'message' => 'Confidence level updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 