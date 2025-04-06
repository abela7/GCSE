<?php
// GCSE/pages/EnglishPractice/_functions.php

/**
 * Ensure required tables exist
 */
function ensure_tables_exist($conn) {
    // Create practice_days table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS practice_days (
        id INT AUTO_INCREMENT PRIMARY KEY,
        practice_date DATE NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create practice_categories table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS practice_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create practice_items table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS practice_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        practice_day_id INT NOT NULL,
        category_id INT NOT NULL,
        item_title VARCHAR(255) NOT NULL,
        item_meaning TEXT NOT NULL,
        item_example TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (practice_day_id) REFERENCES practice_days(id),
        FOREIGN KEY (category_id) REFERENCES practice_categories(id)
    )");

    // Insert default categories if none exist
    $result = $conn->query("SELECT COUNT(*) as count FROM practice_categories");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $default_categories = [
            'Vocabulary Words',
            'Literary Devices',
            'Grammar Rules',
            'Writing Techniques',
            'Common Phrases'
        ];
        
        $stmt = $conn->prepare("INSERT INTO practice_categories (name) VALUES (?)");
        foreach ($default_categories as $category) {
            $stmt->bind_param("s", $category);
            $stmt->execute();
        }
        $stmt->close();
    }
}

/**
 * Get practice items for a specific day
 */
function get_practice_items_by_day($conn, $practice_day_id) {
    $items = [];
    
    $stmt = $conn->prepare("
        SELECT pi.*, pc.name as category_name 
        FROM practice_items pi
        JOIN practice_categories pc ON pi.category_id = pc.id
        WHERE pi.practice_day_id = ?
        ORDER BY pi.category_id ASC, pi.id ASC
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $practice_day_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $category_id = $row['category_id'];
            if (!isset($items[$category_id])) {
                $items[$category_id] = [
                    'name' => $row['category_name'],
                    'items' => []
                ];
            }
            $items[$category_id]['items'][] = $row;
        }
        
        $stmt->close();
    }
    
    return $items;
}

/**
 * Get practice day ID for a specific date, create if it doesn't exist
 */
function get_or_create_practice_day($conn, $date) {
    // Ensure tables exist first
    ensure_tables_exist($conn);
    
    // First try to get existing day
    $stmt = $conn->prepare("SELECT id FROM practice_days WHERE practice_date = ?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['id'];
    }
    
    // If not found, create new day
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO practice_days (practice_date) VALUES (?)");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("s", $date);
    if ($stmt->execute()) {
        $day_id = $conn->insert_id;
        $stmt->close();
        return $day_id;
    }
    
    $stmt->close();
    return null;
}

/**
 * Get random practice items for review/practice
 */
function get_random_practice_items($conn, $limit = 10, $category_id = null) {
    $sql = "
        SELECT pi.*, pc.name as category_name 
        FROM practice_items pi
        JOIN practice_categories pc ON pi.category_id = pc.id
        WHERE 1=1
    ";
    
    if ($category_id) {
        $sql .= " AND pi.category_id = " . intval($category_id);
    }
    
    $sql .= " ORDER BY RAND() LIMIT " . intval($limit);
    
    $items = [];
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $result->free();
    }
    
    return $items;
}

/**
 * Format date for display
 */
function format_practice_date($date_str) {
    try {
        $date = new DateTime($date_str);
        return $date->format('l, F j, Y');
    } catch (Exception $e) {
        return $date_str;
    }
}

/**
 * Get practice statistics for a date range
 */
function get_practice_stats($conn, $start_date, $end_date) {
    $stats = [
        'total_days' => 0,
        'total_items' => 0,
        'items_per_category' => []
    ];
    
    $sql = "
        SELECT 
            COUNT(DISTINCT pd.id) as total_days,
            COUNT(pi.id) as total_items,
            pc.id as category_id,
            pc.name as category_name,
            COUNT(pi.id) as category_count
        FROM practice_days pd
        LEFT JOIN practice_items pi ON pd.id = pi.practice_day_id
        LEFT JOIN practice_categories pc ON pi.category_id = pc.id
        WHERE pd.practice_date BETWEEN ? AND ?
        GROUP BY pc.id, pc.name
    ";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if ($row['category_id']) {
                $stats['items_per_category'][$row['category_id']] = [
                    'name' => $row['category_name'],
                    'count' => $row['category_count']
                ];
            }
            $stats['total_days'] = $row['total_days'];
            $stats['total_items'] += $row['category_count'];
        }
        
        $stmt->close();
    }
    
    return $stats;
} 