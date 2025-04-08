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
    // First determine if this is a math or english section
    $is_english = $conn->query("SELECT 1 FROM eng_sections WHERE id = $section_id")->num_rows > 0;
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