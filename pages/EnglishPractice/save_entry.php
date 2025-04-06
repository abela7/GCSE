<?php
// GCSE/pages/EnglishPractice/save_entry.php
session_start();
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/_functions.php'; // Include functions if needed

// 1. Validate Request Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_ep'] = "Invalid request method.";
    header('Location: daily_entry.php'); // Redirect back to entry form
    exit;
}

// 2. Get and Validate practice_day_id and practice_date
$practice_day_id = filter_input(INPUT_POST, 'practice_day_id', FILTER_VALIDATE_INT);
$practice_date = isset($_POST['practice_date']) ? $_POST['practice_date'] : null;

// Validate date format rigorously
$date_valid = false;
if ($practice_date) {
    $d = DateTime::createFromFormat('Y-m-d', $practice_date);
    $date_valid = $d && $d->format('Y-m-d') === $practice_date;
}

// Redirect back to specific date if validation fails
$redirect_url = 'daily_entry.php' . ($date_valid ? '?date=' . urlencode($practice_date) : '');

if (!$practice_day_id || $practice_day_id <= 0) {
    $_SESSION['error_ep'] = "Invalid or missing practice day ID.";
     error_log("save_entry error: Invalid practice_day_id ($practice_day_id) for date $practice_date");
    header('Location: ' . $redirect_url);
    exit;
}
if (!$date_valid) {
     $_SESSION['error_ep'] = "Invalid practice date submitted.";
      error_log("save_entry error: Invalid practice_date ($practice_date)");
    header('Location: daily_entry.php'); // Go to today if date is bad
    exit;
}


// 3. Get items array
$items = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : [];

if (empty($items)) {
    // It's possible user submitted an empty form, treat as success? Or warning?
    $_SESSION['warning_ep'] = "No items were submitted to save.";
    header('Location: ' . $redirect_url);
    exit;
}

// 4. Prepare Insert Statement (Prepare once, execute multiple times)
// Using INSERT ... ON DUPLICATE KEY UPDATE might be better if users might edit via this form
// But for simple additive entry, INSERT IGNORE or simple INSERT is fine. Let's use simple INSERT for now.
$sql = "INSERT INTO practice_items (practice_day_id, category_id, item_title, item_meaning, item_example, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['error_ep'] = "Database error preparing statement: " . htmlspecialchars($conn->error); // Sanitize DB errors
     error_log("save_entry prepare error: " . $conn->error);
    header('Location: ' . $redirect_url);
    exit;
}

// 5. Process Items & Execute Inserts (within a transaction)
$inserted_count = 0;
$submitted_item_count = 0; // Count actual items with data submitted
$error_occurred = false;

try {
    if (!$conn->begin_transaction()) { throw new Exception("Could not start transaction."); }

    foreach ($items as $category_id => $category_items) {
        if (!is_array($category_items)) continue; // Skip if category data is not an array
        $cat_id_int = (int)$category_id; // Ensure category ID is integer

        foreach ($category_items as $index => $item) {
            if (!is_array($item)) continue; // Skip malformed item data

            // Trim whitespace
            $title   = isset($item['title']) ? trim($item['title']) : '';
            $meaning = isset($item['meaning']) ? trim($item['meaning']) : '';
            $example = isset($item['example']) ? trim($item['example']) : '';

            // Only count as a submitted item if at least title is present
            if (!empty($title)) {
                 $submitted_item_count++;

                // Validate that all required fields for *this item* are present
                if (empty($title) || empty($meaning) || empty($example)) {
                     error_log("Skipping incomplete item for CatID: $cat_id_int, Index: $index, Date: $practice_date");
                     // Optionally add a specific warning message
                    continue; // Skip this specific item
                }

                // Bind parameters and execute
                $stmt->bind_param("iisss", $practice_day_id, $cat_id_int, $title, $meaning, $example );

                if ($stmt->execute()) {
                    $inserted_count++;
                } else {
                    // Log specific item error but continue trying others
                     error_log("Error inserting item (CatID: $cat_id_int, Index: $index, Date: $practice_date): " . $stmt->error);
                     $error_occurred = true; // Flag that at least one error happened
                }
            } // end if !empty title
        } // end loop items in category
    } // end loop categories

    $stmt->close(); // Close statement AFTER loops

    // Commit or Rollback
    if ($error_occurred) {
         $conn->rollback();
         $_SESSION['error_ep'] = "An error occurred while saving some items. Please check and try again.";
         error_log("Save entries rolled back due to insertion errors for date $practice_date");
    } else {
         $conn->commit();
          // Set success/warning message
          if ($inserted_count === 0 && $submitted_item_count > 0) {
                $_SESSION['error_ep'] = "No valid items were saved (maybe fields were empty?).";
          } elseif ($inserted_count < $submitted_item_count) {
                $_SESSION['warning_ep'] = "Successfully saved {$inserted_count} out of {$submitted_item_count} items submitted with data.";
          } else {
                $_SESSION['success_ep'] = "Successfully saved {$inserted_count} practice items!";
          }
    }

} catch (Exception $e) {
     if ($conn->ping() && $conn->inTransaction) { $conn->rollback(); }
     $_SESSION['error_ep'] = "An unexpected error occurred: " . $e->getMessage();
     error_log("save_entry Transaction Exception: " . $e->getMessage());
}


// 6. Redirect back
header('Location: ' . $redirect_url);
exit;
?>