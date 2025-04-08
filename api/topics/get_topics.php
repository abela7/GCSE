<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

// Get subsection ID and subject ID
$subsection_id = isset($_GET['subsection_id']) ? intval($_GET['subsection_id']) : 0;
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

if (!$subsection_id || !$subject_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Subsection ID and Subject ID are required',
        'topics' => []
    ]);
    exit;
}

try {
    // Determine table based on subject_id
    $table = $subject_id == 1 ? 'eng_topics' : 'math_topics';
    $subsection_table = $subject_id == 1 ? 'eng_subsections' : 'math_subsections';
    
    // Verify the subsection exists in the correct table
    $check = $conn->prepare("SELECT 1 FROM $subsection_table WHERE id = ?");
    $check->bind_param('i', $subsection_id);
    $check->execute();
    
    if ($check->get_result()->num_rows === 0) {
        throw new Exception("Invalid subsection ID for the selected subject");
    }
    
    // Prepare and execute the query
    $query = "SELECT id, name FROM $table WHERE subsection_id = ? ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $subsection_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $topics = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode([
            'success' => true,
            'message' => 'Topics retrieved successfully',
            'topics' => $topics
        ]);
    } else {
        throw new Exception("Error fetching topics");
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch topics: ' . $e->getMessage(),
        'topics' => []
    ]);
}

$conn->close();
?> 