<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

// Get section ID and subject ID
$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

if (!$section_id || !$subject_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Section ID and Subject ID are required',
        'subsections' => []
    ]);
    exit;
}

try {
    // Determine table based on subject_id
    $table = $subject_id == 1 ? 'eng_subsections' : 'math_subsections';
    
    // Verify the section exists in the correct table
    $section_table = $subject_id == 1 ? 'eng_sections' : 'math_sections';
    $check = $conn->prepare("SELECT 1 FROM $section_table WHERE id = ?");
    $check->bind_param('i', $section_id);
    $check->execute();
    
    if ($check->get_result()->num_rows === 0) {
        throw new Exception("Invalid section ID for the selected subject");
    }
    
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