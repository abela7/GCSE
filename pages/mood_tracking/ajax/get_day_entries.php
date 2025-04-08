<?php
// Include required files
require_once __DIR__ . '/../includes/functions.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'entries' => []
];

// Check if date parameter is provided
if (!isset($_GET['date']) || empty($_GET['date'])) {
    $response['message'] = 'Date parameter is required';
    echo json_encode($response);
    exit;
}

$date = $_GET['date'];

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $response['message'] = 'Invalid date format. Use YYYY-MM-DD';
    echo json_encode($response);
    exit;
}

// Get entries for the specified date
try {
    $entries = getMoodEntriesByDate($date);
    
    if (!empty($entries)) {
        $response['success'] = true;
        $response['entries'] = $entries;
        
        // Generate HTML for entries
        $html = '';
        foreach ($entries as $entry) {
            $mood = $entry['mood_level'];
            $emoji = '';
            if ($mood == 5) $emoji = '😄';
            else if ($mood == 4) $emoji = '🙂';
            else if ($mood == 3) $emoji = '😐';
            else if ($mood == 2) $emoji = '😕';
            else $emoji = '😢';
            
            $html .= '<div class="card mb-3 entry-card mood-' . $mood . '">';
            $html .= '<div class="card-body">';
            $html .= '<div class="d-flex justify-content-between align-items-center mb-2">';
            $html .= '<h5 class="card-title mb-0">' . $emoji . ' ' . getMoodLevelText($mood) . '</h5>';
            $html .= '<div class="text-muted small">' . date('g:i A', strtotime($entry['date'])) . '</div>';
            $html .= '</div>';
            
            if (!empty($entry['tags'])) {
                $html .= '<div class="mb-2">';
                foreach ($entry['tags'] as $tag) {
                    $html .= '<span class="tag-badge" style="background-color: ' . $tag['color'] . '">';
                    $html .= htmlspecialchars($tag['name']) . '</span>';
                }
                $html .= '</div>';
            }
            
            if (!empty($entry['notes'])) {
                $html .= '<div class="card-text mb-3">' . nl2br(htmlspecialchars($entry['notes'])) . '</div>';
            }
            
            $html .= '<div class="d-flex justify-content-end">';
            $html .= '<a href="entry.php?id=' . $entry['id'] . '" class="btn btn-sm btn-outline-accent me-2">';
            $html .= '<i class="fas fa-edit me-1"></i>Edit</a>';
            $html .= '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteEntry(' . $entry['id'] . ')">';
            $html .= '<i class="fas fa-trash me-1"></i>Delete</button>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        echo $html;
        exit;
    } else {
        echo '<div class="text-center py-4">';
        echo '<p class="text-muted mb-3">No mood entries for this day</p>';
        echo '<a href="entry.php?date=' . $date . '" class="btn btn-accent">';
        echo '<i class="fas fa-plus me-1"></i>Add Entry for This Day</a>';
        echo '</div>';
        exit;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    echo json_encode($response);
    exit;
}

// Helper function to get mood level text
function getMoodLevelText($level) {
    switch (intval($level)) {
        case 1: return 'Very Bad';
        case 2: return 'Bad';
        case 3: return 'Neutral';
        case 4: return 'Good';
        case 5: return 'Very Good';
        default: return '';
    }
}
