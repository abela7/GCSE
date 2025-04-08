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
    // First determine if this is a math or english subsection
    $is_english = $conn->query("SELECT 1 FROM eng_subsections WHERE id = $subsection_id")->num_rows > 0;
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