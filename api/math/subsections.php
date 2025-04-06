<?php
// Include database connection
require_once '../../config/db_connect.php';

// Check if section_id is provided
if (!isset($_GET['section_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Section ID is required']);
    exit;
}

$section_id = intval($_GET['section_id']);

// Get section progress
$section_progress_query = "
    SELECT 
        COUNT(DISTINCT mt.id) as total_topics,
        COALESCE(SUM(CASE WHEN tp.status = 'completed' THEN 1 ELSE 0 END), 0) as completed_topics,
        COALESCE(ROUND(
            SUM(CASE WHEN tp.status = 'completed' THEN 1 ELSE 0 END) * 100.0 / 
            NULLIF(COUNT(DISTINCT mt.id), 0)
        , 1), 0) as progress_percentage
    FROM math_sections ms
    LEFT JOIN math_subsections msub ON ms.id = msub.section_id
    LEFT JOIN math_topics mt ON msub.id = mt.subsection_id
    LEFT JOIN topic_progress tp ON mt.id = tp.topic_id
    WHERE ms.id = ?
    GROUP BY ms.id
";

$stmt = $conn->prepare($section_progress_query);
$stmt->bind_param('i', $section_id);
$stmt->execute();
$section_progress = $stmt->get_result()->fetch_assoc();

// Get subsections with their progress
$subsections_query = "
    SELECT 
        msub.*,
        COUNT(DISTINCT mt.id) as total_topics,
        COALESCE(SUM(CASE WHEN tp.status = 'completed' THEN 1 ELSE 0 END), 0) as completed_topics,
        COALESCE(ROUND(
            SUM(CASE WHEN tp.status = 'completed' THEN 1 ELSE 0 END) * 100.0 / 
            NULLIF(COUNT(DISTINCT mt.id), 0)
        , 1), 0) as progress_percentage
    FROM math_subsections msub
    LEFT JOIN math_topics mt ON msub.id = mt.subsection_id
    LEFT JOIN topic_progress tp ON mt.id = tp.topic_id
    WHERE msub.section_id = ?
    GROUP BY msub.id
    ORDER BY msub.subsection_number
";

$stmt = $conn->prepare($subsections_query);
$stmt->bind_param('i', $section_id);
$stmt->execute();
$subsections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'section_progress' => $section_progress,
    'subsections' => $subsections
]); 