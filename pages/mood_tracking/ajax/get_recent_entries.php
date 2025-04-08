<?php
// AJAX endpoint to get recent mood entries
require_once '../../../config/db_connect.php';
require_once '../includes/functions.php';

// Get the 5 most recent mood entries
$recent_entries = getMoodEntries(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'), null, null, null, [], null, null);

// Limit to 5 entries
$recent_entries = array_slice($recent_entries, 0, 5);

// Return as JSON
header('Content-Type: application/json');
echo json_encode($recent_entries);
?>
