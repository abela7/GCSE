<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

if (!$section_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Section ID is required']);
    exit;
}

try {
    // Get section progress
    $section_progress_query = "
        SELECT 
            COUNT(DISTINCT et.id) as total_topics,
            COUNT(DISTINCT CASE WHEN etp.status = 'completed' THEN et.id END) as completed_topics,
            COALESCE(ROUND(COUNT(DISTINCT CASE WHEN etp.status = 'completed' THEN et.id END) * 100.0 / 
                NULLIF(COUNT(DISTINCT et.id), 0), 1), 0) as progress_percentage
        FROM eng_sections es
        LEFT JOIN eng_subsections esub ON es.id = esub.section_id
        LEFT JOIN eng_topics et ON esub.id = et.subsection_id
        LEFT JOIN eng_topic_progress etp ON et.id = etp.topic_id
        WHERE es.id = ?
        GROUP BY es.id
    ";

    $stmt = $conn->prepare($section_progress_query);
    $stmt->bind_param('i', $section_id);
    $stmt->execute();
    $section_progress = $stmt->get_result()->fetch_assoc();

    // Get subsections with their progress
    $subsections_query = "
        SELECT 
            esub.*,
            COUNT(DISTINCT et.id) as total_topics,
            COUNT(DISTINCT CASE WHEN etp.status = 'completed' THEN et.id END) as completed_topics,
            COALESCE(ROUND(COUNT(DISTINCT CASE WHEN etp.status = 'completed' THEN et.id END) * 100.0 / 
                NULLIF(COUNT(DISTINCT et.id), 0), 1), 0) as progress_percentage
        FROM eng_subsections esub
        LEFT JOIN eng_topics et ON esub.id = et.subsection_id
        LEFT JOIN eng_topic_progress etp ON et.id = etp.topic_id
        WHERE esub.section_id = ?
        GROUP BY esub.id
        ORDER BY esub.subsection_number
    ";

    $stmt = $conn->prepare($subsections_query);
    $stmt->bind_param('i', $section_id);
    $stmt->execute();
    $subsections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'section_progress' => $section_progress,
        'subsections' => $subsections
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch subsections',
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?> 