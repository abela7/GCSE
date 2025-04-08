<?php
// Include required files
require_once __DIR__ . '/../includes/functions.php';

// Get date parameter
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    exit('Invalid date format');
}

// Get entries for the specified date using the existing getMoodEntries function
$entries = getMoodEntries($date, $date, null, [], null, null);

if (empty($entries)) {
    echo '<div class="text-center py-4">
            <div class="mb-3">
                <i class="fas fa-calendar-day fa-3x text-muted"></i>
            </div>
            <p class="text-muted mb-0">No mood entries for this day</p>
          </div>';
} else {
    echo '<div class="list-group">';
    foreach ($entries as $entry) {
        // Get emoji based on mood level
        $emoji = '';
        if ($entry['mood_level'] == 5) $emoji = '😄';
        else if ($entry['mood_level'] == 4) $emoji = '🙂';
        else if ($entry['mood_level'] == 3) $emoji = '😐';
        else if ($entry['mood_level'] == 2) $emoji = '😕';
        else $emoji = '😢';
        
        echo '<div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-1">' . date('g:i A', strtotime($entry['date'])) . '</h6>
                    <span class="fs-4">' . $emoji . '</span>
                </div>';
        
        if (!empty($entry['notes'])) {
            echo '<p class="mb-1">' . nl2br(htmlspecialchars($entry['notes'])) . '</p>';
        }
        
        if (!empty($entry['tags'])) {
            echo '<div class="mt-2">';
            foreach ($entry['tags'] as $tag) {
                echo '<span class="mood-badge" style="background-color: ' . $tag['color'] . '">
                        ' . htmlspecialchars($tag['name']) . '
                      </span>';
            }
            echo '</div>';
        }
        
        echo '<div class="mt-2">
                <a href="entry.php?id=' . $entry['id'] . '" class="btn btn-sm btn-outline-accent me-1">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteEntry(' . $entry['id'] . ')">
                    <i class="fas fa-trash"></i> Delete
                </button>
              </div>
            </div>';
    }
    echo '</div>';
}
