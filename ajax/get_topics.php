<?php
// AJAX endpoint to get topics based on subject ID
require_once '../config/db_connect.php';

// Validate input
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

if (!$subject_id) {
    echo json_encode([]);
    exit;
}

// Determine which table to query based on subject ID
$table_prefix = ($subject_id == 1) ? 'eng' : 'math';
$query = "SELECT t.id, t.name 
          FROM {$table_prefix}_topics t 
          JOIN {$table_prefix}_subsections s ON t.subsection_id = s.id 
          JOIN {$table_prefix}_sections sec ON s.section_id = sec.id 
          ORDER BY sec.name, s.name, t.name";

$result = $conn->query($query);

$topics = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $topics[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($topics);

// Close connection
close_connection($conn);
?>
