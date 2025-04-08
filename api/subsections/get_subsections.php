<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

// Get section ID and determine subject from the section's table
$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

if (!$section_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Section ID is required',
        'subsections' => []
    ]);
    exit;
}

try {
    // First determine which subject this section belongs to
    $eng_check = $conn->prepare("SELECT 1 FROM eng_sections WHERE id = ?");
    $eng_check->bind_param('i', $section_id);
    $eng_check->execute();
    $is_english = $eng_check->get_result()->num_rows > 0;
    
    $math_check = $conn->prepare("SELECT 1 FROM math_sections WHERE id = ?");
    $math_check->bind_param('i', $section_id);
    $math_check->execute();
    $is_math = $math_check->get_result()->num_rows > 0;
    
    if (!$is_english && !$is_math) {
        throw new Exception("Invalid section ID");
    }
    
    $table = $is_english ? 'eng_subsections' : 'math_subsections';
    
    // Prepare and execute the query
    $query = "SELECT id, name, subsection_number FROM $table WHERE section_id = ? ORDER BY subsection_number";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $section_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $subsections = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode([
            'success' => true,
            'message' => 'Subsections retrieved successfully',
            'subsections' => $subsections
        ]);
    } else {
        throw new Exception("Error fetching subsections");
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch subsections: ' . $e->getMessage(),
        'subsections' => []
    ]);
}

$conn->close();
?> 