<?php
// Include required files
require_once __DIR__ . '/../includes/functions.php';

// Get recent mood entries (last 5)
$query = "SELECT m.*, 
          s.name as subject_name, 
          CASE 
              WHEN m.associated_subject_id = 1 THEN CONCAT(es.name, ' - ', et.name) 
              WHEN m.associated_subject_id = 2 THEN CONCAT(ms.name, ' - ', mt.name)
              ELSE NULL
          END as topic_name
        FROM mood_entries m
        LEFT JOIN subjects s ON m.associated_subject_id = s.id
        LEFT JOIN eng_sections es ON m.associated_subject_id = 1
        LEFT JOIN eng_topics et ON m.associated_topic_id = et.id
        LEFT JOIN math_sections ms ON m.associated_subject_id = 2
        LEFT JOIN math_topics mt ON m.associated_topic_id = mt.id
        ORDER BY m.date DESC
        LIMIT 5";

$result = $conn->query($query);

$entries = [];
if ($result && $result->num_rows > 0) {
    while ($entry = $result->fetch_assoc()) {
        // Get tags for this entry
        $tag_query = "SELECT t.* 
                     FROM mood_tags t
                     JOIN mood_entry_tags met ON t.id = met.tag_id
                     WHERE met.mood_entry_id = ?";
        $tag_stmt = $conn->prepare($tag_query);
        $tag_stmt->bind_param("i", $entry['id']);
        $tag_stmt->execute();
        $tag_result = $tag_stmt->get_result();
        
        $entry['tags'] = [];
        while ($tag = $tag_result->fetch_assoc()) {
            $entry['tags'][] = $tag;
        }
        
        $entries[] = $entry;
    }
}

// Set header to return JSON
header('Content-Type: application/json');
echo json_encode($entries);
exit;
