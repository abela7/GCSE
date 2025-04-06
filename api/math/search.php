<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['term'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Search term is required']);
    exit;
}

$term = '%' . $_GET['term'] . '%';

// Search in sections, subsections, and topics
$query = "
    SELECT DISTINCT 
        ms.id as section_id,
        ms.name as section_name,
        ms.description as section_description,
        msub.id as subsection_id,
        msub.name as subsection_name,
        mt.id as topic_id,
        mt.name as topic_name
    FROM math_sections ms
    LEFT JOIN math_subsections msub ON ms.id = msub.section_id
    LEFT JOIN math_topics mt ON msub.id = mt.subsection_id
    WHERE 
        ms.name LIKE ? OR 
        ms.description LIKE ? OR
        msub.name LIKE ? OR 
        msub.description LIKE ? OR
        mt.name LIKE ? OR
        mt.description LIKE ?
    ORDER BY ms.section_number, msub.subsection_number
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ssssss', $term, $term, $term, $term, $term, $term);
$stmt->execute();
$result = $stmt->get_result();

$sections = [];
$matches = [];

while ($row = $result->fetch_assoc()) {
    $sections[] = $row['section_id'];
    
    // Group matches by type for detailed results
    if (stripos($row['section_name'], $_GET['term']) !== false || 
        stripos($row['section_description'], $_GET['term']) !== false) {
        $matches['sections'][] = [
            'id' => $row['section_id'],
            'name' => $row['section_name']
        ];
    }
    
    if ($row['subsection_id'] && (
        stripos($row['subsection_name'], $_GET['term']) !== false)) {
        $matches['subsections'][] = [
            'id' => $row['subsection_id'],
            'name' => $row['subsection_name'],
            'section_id' => $row['section_id']
        ];
    }
    
    if ($row['topic_id'] && (
        stripos($row['topic_name'], $_GET['term']) !== false)) {
        $matches['topics'][] = [
            'id' => $row['topic_id'],
            'name' => $row['topic_name'],
            'section_id' => $row['section_id'],
            'subsection_id' => $row['subsection_id']
        ];
    }
}

echo json_encode([
    'sections' => array_unique($sections),
    'matches' => $matches
]); 