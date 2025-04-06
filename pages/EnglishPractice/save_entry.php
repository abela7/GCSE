<?php
// GCSE/pages/EnglishPractice/save_entry.php
session_start();
require_once __DIR__ . '/../../config/db_connect.php';

// Validate form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_ep'] = "Invalid request method.";
    header('Location: daily_entry.php');
    exit;
}

// Get and validate practice_day_id
$practice_day_id = filter_input(INPUT_POST, 'practice_day_id', FILTER_VALIDATE_INT);
if (!$practice_day_id) {
    $_SESSION['error_ep'] = "Invalid practice day ID.";
    header('Location: daily_entry.php');
    exit;
}

// Get and validate practice date
$practice_date = filter_input(INPUT_POST, 'practice_date', FILTER_SANITIZE_STRING);
if (!$practice_date || !strtotime($practice_date)) {
    $_SESSION['error_ep'] = "Invalid practice date.";
    header('Location: daily_entry.php');
    exit;
}

// Get items array
$items = isset($_POST['items']) ? $_POST['items'] : [];
if (empty($items)) {
    $_SESSION['error_ep'] = "No items submitted.";
    header('Location: daily_entry.php?date=' . urlencode($practice_date));
    exit;
}

// Prepare insert statement
$stmt = $conn->prepare("INSERT INTO practice_items (practice_day_id, category_id, item_title, item_meaning, item_example) VALUES (?, ?, ?, ?, ?)");

if (!$stmt) {
    $_SESSION['error_ep'] = "Database error preparing statement: " . $conn->error;
    header('Location: daily_entry.php?date=' . urlencode($practice_date));
    exit;
}

// Counter for successful inserts
$inserted_count = 0;
$total_items = 0;

// Process each category's items
foreach ($items as $category_id => $category_items) {
    foreach ($category_items as $item) {
        $total_items++;
        
        // Skip empty items (all fields must be filled)
        if (empty($item['title']) || empty($item['meaning']) || empty($item['example'])) {
            continue;
        }
        
        // Bind parameters and execute
        $stmt->bind_param("iisss", 
            $practice_day_id,
            $category_id,
            $item['title'],
            $item['meaning'],
            $item['example']
        );
        
        if ($stmt->execute()) {
            $inserted_count++;
        } else {
            error_log("Error inserting item: " . $stmt->error);
        }
    }
}

$stmt->close();

// Set appropriate message based on results
if ($inserted_count === 0) {
    $_SESSION['error_ep'] = "No items were saved successfully.";
} elseif ($inserted_count < $total_items) {
    $_SESSION['warning_ep'] = "Saved {$inserted_count} out of {$total_items} items.";
} else {
    $_SESSION['success_ep'] = "Successfully saved all {$inserted_count} items.";
}

// Redirect back to the form
header('Location: daily_entry.php?date=' . urlencode($practice_date));
exit; 