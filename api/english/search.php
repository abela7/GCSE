<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$search_term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (empty($search_term)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Search term is required']);
    exit;
}

try {
    // Search in sections and subsections
    $search_query = "
        SELECT DISTINCT
            'section' as type,
            es.id,
            es.name,
            es.description,
            es.section_number as number
        FROM eng_sections es
        WHERE LOWER(es.name) LIKE LOWER(?) 
           OR LOWER(es.description) LIKE LOWER(?)
        
        UNION ALL
        
        SELECT DISTINCT
            'subsection' as type,
            esub.section_id as id,
            esub.name,
            esub.description,
            esub.subsection_number as number
        FROM eng_subsections esub
        WHERE LOWER(esub.name) LIKE LOWER(?) 
           OR LOWER(esub.description) LIKE LOWER(?)
        ORDER BY type DESC, number
    ";

    $search_param = "%{$search_term}%";
    $stmt = $conn->prepare($search_query);
    $stmt->bind_param('ssss', $search_param, $search_param, $search_param, $search_param);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Extract unique section IDs from the results
    $section_ids = array_unique(array_map(function($item) {
        return $item['id'];
    }, array_filter($results, function($item) {
        return $item['type'] === 'section';
    })));

    echo json_encode([
        'success' => true,
        'results' => [
            'sections' => array_values($section_ids),
            'matches' => $results
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to perform search',
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?> 