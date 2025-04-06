<?php
// GCSE/pages/EnglishPractice/_functions.php

/**
 * Get practice items for a specific day, grouped by category.
 *
 * @param mysqli $conn Database connection object.
 * @param int $practice_day_id The ID of the practice day.
 * @return array An array grouped by category_id, or empty array if none found.
 */
function get_practice_items_by_day($conn, $practice_day_id) {
    $items = [];
    $practice_day_id = (int)$practice_day_id; // Sanitize

    if ($practice_day_id <= 0) {
        return $items; // Return empty if ID is invalid
    }

    $stmt = $conn->prepare("
        SELECT pi.id, pi.category_id, pi.item_title, pi.item_meaning, pi.item_example, pc.name as category_name
        FROM practice_items pi
        JOIN practice_categories pc ON pi.category_id = pc.id
        WHERE pi.practice_day_id = ?
        ORDER BY pi.category_id ASC, pi.id ASC
    ");

    if ($stmt) {
        $stmt->bind_param("i", $practice_day_id);
        if ($stmt->execute()) {
             $result = $stmt->get_result();
             while ($row = $result->fetch_assoc()) {
                $category_id = $row['category_id'];
                // Initialize category array if it doesn't exist
                if (!isset($items[$category_id])) {
                    $items[$category_id] = [
                        'name' => htmlspecialchars($row['category_name']), // Sanitize name here
                        'items' => []
                    ];
                }
                 // Sanitize item data before adding
                 $row['item_title'] = htmlspecialchars($row['item_title']);
                 $row['item_meaning'] = htmlspecialchars($row['item_meaning']);
                 $row['item_example'] = htmlspecialchars($row['item_example']);
                 $items[$category_id]['items'][] = $row;
            }
            $result->free();
        } else {
             error_log("Error executing get_practice_items_by_day query: " . $stmt->error);
        }
        $stmt->close();
    } else {
         error_log("Error preparing get_practice_items_by_day query: " . $conn->error);
    }

    return $items;
}

/**
 * Get practice day ID for a specific date (YYYY-MM-DD).
 * IMPORTANT: This version *only* gets the ID, it relies on days being pre-populated.
 *
 * @param mysqli $conn Database connection object.
 * @param string $date_str The date string ('Y-m-d').
 * @return int|null The practice day ID or null if not found or error.
 */
function get_practice_day_id($conn, $date_str) {
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
        error_log("get_practice_day_id: Invalid date format provided - " . $date_str);
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM practice_days WHERE practice_date = ?");
    if (!$stmt) {
        error_log("Prepare failed (get day id): " . $conn->error);
        return null;
    }

    $stmt->bind_param("s", $date_str);
    if (!$stmt->execute()) {
         error_log("Execute failed (get day id): " . $stmt->error);
         $stmt->close();
         return null;
    }

    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return (int)$row['id'];
    }

    $stmt->close();
    return null; // Return null if day not found
}

/**
 * Get or create a practice day ID for a specific date.
 *
 * @param mysqli $conn Database connection object.
 * @param string $date_str The date string ('Y-m-d').
 * @return int|null The practice day ID or null if error.
 */
function get_or_create_practice_day($conn, $date_str) {
    // First try to get existing day
    $existing_id = get_practice_day_id($conn, $date_str);
    if ($existing_id !== null) {
        return $existing_id;
    }

    // Calculate week number (assuming weeks start from a specific date)
    $start_date = new DateTime('2024-01-01'); // Adjust this to your actual start date
    $target_date = new DateTime($date_str);
    $week_diff = floor($start_date->diff($target_date)->days / 7) + 1;

    // Create new practice day
    $stmt = $conn->prepare("INSERT INTO practice_days (practice_date, week_number) VALUES (?, ?)");
    if (!$stmt) {
        error_log("Prepare failed (create day): " . $conn->error);
        return null;
    }

    $stmt->bind_param("si", $date_str, $week_diff);
    if (!$stmt->execute()) {
        error_log("Execute failed (create day): " . $stmt->error);
        $stmt->close();
        return null;
    }

    $new_id = $stmt->insert_id;
    $stmt->close();
    return $new_id;
}

/**
 * Get practice items for review/practice based on filters.
 *
 * @param mysqli $conn Database connection object.
 * @param int $limit Max number of items.
 * @param int|null $category_id Filter by specific category ID.
 * @param string|null $date_filter 'today', 'week', specific date 'Y-m-d', or null for all time.
 * @return array Array of practice items.
 */
function get_practice_items_for_flashcards($conn, $limit = 20, $category_id = null, $date_filter = null) {
    $items = [];
    $params = [];
    $types = '';

    $sql = "SELECT pi.id, pi.item_title, pi.item_meaning, pi.item_example, pc.name as category_name, pd.practice_date
            FROM practice_items pi
            JOIN practice_categories pc ON pi.category_id = pc.id
            JOIN practice_days pd ON pi.practice_day_id = pd.id
            WHERE 1=1"; // Start WHERE clause

    // Apply Category Filter
    if ($category_id !== null && $category_id > 0) {
        $sql .= " AND pi.category_id = ?";
        $params[] = (int)$category_id;
        $types .= 'i';
    }

    // Apply Date Filter
    $today = date('Y-m-d');
    if ($date_filter === 'today') {
        $sql .= " AND pd.practice_date = ?";
        $params[] = $today;
        $types .= 's';
    } elseif ($date_filter === 'week') {
        // Calculate start of the current week (e.g., Monday)
        $startOfWeek = date('Y-m-d', strtotime('monday this week', strtotime($today)));
        // Adjust if today IS Monday, some interpretations might differ
        if (date('N', strtotime($today)) == 1) { // 1 (for Monday) through 7 (for Sunday)
            $startOfWeek = $today;
        }
        // Generate dates for the entire week ending today or Sunday
        $endOfWeek = date('Y-m-d', strtotime('sunday this week', strtotime($today)));

        $sql .= " AND pd.practice_date BETWEEN ? AND ?";
        $params[] = $startOfWeek;
        $params[] = $endOfWeek; // Or use $today if you only want up to today
        $types .= 'ss';
    } elseif ($date_filter && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter)) {
         // Specific Date Filter
         $sql .= " AND pd.practice_date = ?";
         $params[] = $date_filter;
         $types .= 's';
    }
    // If $date_filter is null or invalid, no date filter is applied (all time)


    $sql .= " ORDER BY RAND() LIMIT ?"; // Randomize for practice
    $params[] = (int)$limit;
    $types .= 'i';

    // Prepare and execute
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($types)) { // Bind params only if there are any
            $stmt->bind_param($types, ...$params); // Splat operator requires PHP 5.6+
        }

        if ($stmt->execute()) {
             $result = $stmt->get_result();
             while ($row = $result->fetch_assoc()) {
                 // Sanitize output
                 $row['item_title'] = htmlspecialchars($row['item_title']);
                 $row['item_meaning'] = htmlspecialchars($row['item_meaning']);
                 $row['item_example'] = htmlspecialchars($row['item_example']);
                 $row['category_name'] = htmlspecialchars($row['category_name']);
                 $items[] = $row;
            }
             $result->free();
        } else {
            error_log("Error executing get_practice_items_for_flashcards query: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Error preparing get_practice_items_for_flashcards query: " . $conn->error . " SQL: " . $sql);
    }

    return $items;
}


/**
 * Get start and end dates for a given week number relative to the practice period start.
 *
 * @param mysqli $conn Database connection object.
 * @param int $week_num The week number (1, 2, 3...).
 * @return array|null Associative array with 'start_date' and 'end_date' (Y-m-d) or null.
 */
 function get_week_dates($conn, $week_num) {
    $week_num = (int)$week_num;
    if ($week_num <= 0) return null;

    // Find the earliest start date for the week number
    $stmt_start = $conn->prepare("SELECT MIN(practice_date) as start_date FROM practice_days WHERE week_number = ?");
    if(!$stmt_start) return null;
    $stmt_start->bind_param("i", $week_num);
    $stmt_start->execute();
    $start_res = $stmt_start->get_result();
    $start_row = $start_res->fetch_assoc();
    $stmt_start->close();
    $start_date = $start_row['start_date'];

    if(!$start_date) return null; // Week not found

    // Find the latest end date for the week number
    $stmt_end = $conn->prepare("SELECT MAX(practice_date) as end_date FROM practice_days WHERE week_number = ?");
     if(!$stmt_end) return null;
    $stmt_end->bind_param("i", $week_num);
    $stmt_end->execute();
    $end_res = $stmt_end->get_result();
    $end_row = $end_res->fetch_assoc();
    $stmt_end->close();
    $end_date = $end_row['end_date'];

     return ['start_date' => $start_date, 'end_date' => $end_date];
 }

 /**
 * Get practice items grouped by day within a specific week.
 *
 * @param mysqli $conn Database connection object.
 * @param int $week_num The week number.
 * @return array An array grouped by date, then category_id.
 */
function get_practice_items_by_week($conn, $week_num) {
    $items_by_day = [];
    $week_dates = get_week_dates($conn, $week_num);

    if (!$week_dates) {
        return $items_by_day; // Return empty if week not found
    }

    $stmt = $conn->prepare("
        SELECT pd.practice_date, pi.id, pi.category_id, pi.item_title, pi.item_meaning, pi.item_example, pc.name as category_name
        FROM practice_items pi
        JOIN practice_days pd ON pi.practice_day_id = pd.id
        JOIN practice_categories pc ON pi.category_id = pc.id
        WHERE pd.week_number = ?
        ORDER BY pd.practice_date ASC, pi.category_id ASC, pi.id ASC
    ");

    if ($stmt) {
        $stmt->bind_param("i", $week_num);
        if ($stmt->execute()) {
             $result = $stmt->get_result();
             while ($row = $result->fetch_assoc()) {
                 $date = $row['practice_date'];
                 $category_id = $row['category_id'];

                 // Initialize date array if it doesn't exist
                 if (!isset($items_by_day[$date])) {
                     $items_by_day[$date] = [];
                 }
                 // Initialize category array within the date if it doesn't exist
                 if (!isset($items_by_day[$date][$category_id])) {
                     $items_by_day[$date][$category_id] = [
                         'name' => htmlspecialchars($row['category_name']),
                         'items' => []
                     ];
                 }
                 // Sanitize item data
                 $row['item_title'] = htmlspecialchars($row['item_title']);
                 $row['item_meaning'] = htmlspecialchars($row['item_meaning']);
                 $row['item_example'] = htmlspecialchars($row['item_example']);
                 $items_by_day[$date][$category_id]['items'][] = $row;
            }
            $result->free();
        } else {
             error_log("Error executing get_practice_items_by_week query: " . $stmt->error);
        }
        $stmt->close();
    } else {
         error_log("Error preparing get_practice_items_by_week query: " . $conn->error);
    }
    return $items_by_day;
}

/**
 * Format date for display - Use standard PHP formatting.
 */
function format_practice_date($date_str) {
    if (!$date_str) return 'N/A';
    try {
        $date = new DateTimeImmutable($date_str); // Use immutable
        return $date->format('D, M j, Y'); // e.g., Sat, Apr 05, 2025
    } catch (Exception $e) {
        error_log("format_practice_date Error formatting date '$date_str': " . $e->getMessage());
        return $date_str; // Return original string on error
    }
}

/**
 * Calculate days remaining until a target date.
 */
 function days_until($target_date_str) {
    try {
        $target_date = new DateTimeImmutable($target_date_str);
        $today = new DateTimeImmutable('today'); // Ensure we compare date parts only

        if ($today > $target_date) {
            return 0; // Or return a message like 'Past'
        }

        $interval = $today->diff($target_date);
        // Add 1 because diff doesn't count the end date itself usually when calculating "days remaining"
        return $interval->days + ($interval->invert ? 0 : 1);
    } catch (Exception $e) {
        error_log("days_until Error parsing date '$target_date_str': " . $e->getMessage());
        return 'N/A'; // Return 'N/A' or similar on error
    }
 }


?>