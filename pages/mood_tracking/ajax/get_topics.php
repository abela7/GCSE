<?php
// Include database connection
require_once '../../../config/db_connect.php';

// Get topics for a subject
if (isset($_GET['subject_id'])) {
    $subject_id = intval($_GET['subject_id']);
    $topics = [];
    
    if ($subject_id == 1) { // English
        $query = "SELECT t.id, t.name 
                 FROM eng_topics t
                 JOIN eng_subsections sub ON t.subsection_id = sub.id
                 JOIN eng_sections sec ON sub.section_id = sec.id
                 ORDER BY sec.name, sub.name, t.name";
    } elseif ($subject_id == 2) { // Math
        $query = "SELECT t.id, t.name 
                 FROM math_topics t
                 JOIN math_subsections sub ON t.subsection_id = sub.id
                 JOIN math_sections sec ON sub.section_id = sec.id
                 ORDER BY sec.name, sub.name, t.name";
    }
    
    if (isset($query)) {
        $result = $conn->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $topics[] = [
                    'id' => $row['id'],
                    'name' => $row['name']
                ];
            }
        }
    }
    
    // Return topics as JSON
    header('Content-Type: application/json');
    echo json_encode($topics);
    exit;
}

// Return error if no subject_id provided
header('Content-Type: application/json');
echo json_encode([]);
exit;
