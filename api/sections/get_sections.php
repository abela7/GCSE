<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

// Get subject ID from request
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

if (!$subject_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Subject ID is required',
        'sections' => []
    ]);
    exit;
}

try {
    // Determine which table to query based on subject_id
    $table = $subject_id == 1 ? 'eng_sections' : 'math_sections';
    
    // Prepare and execute the query
    $query = "SELECT id, name, section_number FROM $table ORDER BY section_number";
    $result = $conn->query($query);
    
    if ($result) {
        $sections = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode([
            'success' => true,
            'message' => 'Sections retrieved successfully',
            'sections' => $sections
        ]);
    } else {
        throw new Exception("Error fetching sections");
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch sections: ' . $e->getMessage(),
        'sections' => []
    ]);
}

$conn->close();
?> 