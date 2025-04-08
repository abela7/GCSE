<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

// Get subsection ID
$subsection_id = isset($_GET['subsection_id']) ? intval($_GET['subsection_id']) : 0;

if (!$subsection_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Subsection ID is required',
        'topics' => []
    ]);
    exit;
}

try {
    // First determine which subject this subsection belongs to
    $eng_check = $conn->prepare("SELECT 1 FROM eng_subsections WHERE id = ?");
    $eng_check->bind_param('i', $subsection_id);
    $eng_check->execute();
    $is_english = $eng_check->get_result()->num_rows > 0;
    
    $math_check = $conn->prepare("SELECT 1 FROM math_subsections WHERE id = ?");
    $math_check->bind_param('i', $subsection_id);
    $math_check->execute();
    $is_math = $math_check->get_result()->num_rows > 0;
    
    if (!$is_english && !$is_math) {
        throw new Exception("Invalid subsection ID");
    }
    
    $table = $is_english ? 'eng_topics' : 'math_topics';
    
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