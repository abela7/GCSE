<?php
// Include database connection
require_once __DIR__ . '/../../config/db_connect.php';

/**
 * Create a new mood entry
 * 
 * @param int $mood_level Mood level (1-5)
 * @param string $notes Optional notes about the mood
 * @param array $tag_ids Array of tag IDs
 * @return int|bool The ID of the inserted mood entry or false on failure
 */
function createMoodEntry($mood_level, $notes = null, $tag_ids = []) {
    global $conn;
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert mood entry
        $stmt = $conn->prepare("INSERT INTO mood_entries (mood_level, notes) 
                             VALUES (?, ?)");
        
        $stmt->bind_param("is", $mood_level, $notes);
        $stmt->execute();
        
        $mood_entry_id = $conn->insert_id;
        
        // Insert mood tags if provided
        if (!empty($tag_ids) && $mood_entry_id) {
            $tag_stmt = $conn->prepare("INSERT INTO mood_entry_tags (mood_entry_id, tag_id) 
                                     VALUES (?, ?)");
            
            foreach ($tag_ids as $tag_id) {
                $tag_stmt->bind_param("ii", $mood_entry_id, $tag_id);
                $tag_stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        return $mood_entry_id;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error creating mood entry: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing mood entry
 * 
 * @param int $entry_id The ID of the mood entry to update
 * @param int $mood_level Mood level (1-5)
 * @param string $notes Optional notes about the mood
 * @param array $tag_ids Array of tag IDs
 * @return bool True on success, false on failure
 */
function updateMoodEntry($entry_id, $mood_level, $notes = null, $tag_ids = []) {
    global $conn;
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Update mood entry
        $stmt = $conn->prepare("UPDATE mood_entries 
                             SET mood_level = ?, 
                                 notes = ?,
                                 updated_at = CURRENT_TIMESTAMP
                             WHERE id = ?");
        
        $stmt->bind_param("isi", $mood_level, $notes, $entry_id);
        $stmt->execute();
        
        // Delete existing tags for this entry
        $delete_stmt = $conn->prepare("DELETE FROM mood_entry_tags WHERE mood_entry_id = ?");
        $delete_stmt->bind_param("i", $entry_id);
        $delete_stmt->execute();
        
        // Insert new tags
        if (!empty($tag_ids)) {
            $tag_stmt = $conn->prepare("INSERT INTO mood_entry_tags (mood_entry_id, tag_id) 
                                     VALUES (?, ?)");
            
            foreach ($tag_ids as $tag_id) {
                $tag_stmt->bind_param("ii", $entry_id, $tag_id);
                $tag_stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error updating mood entry: " . $e->getMessage());
        return false;
    }
}

/**
 * Get a single mood entry by ID
 * 
 * @param int $entry_id The ID of the mood entry to retrieve
 * @return array|bool The mood entry or false on failure
 */
function getMoodEntry($entry_id) {
    global $conn;
    
    try {
        $query = "SELECT m.* FROM mood_entries m WHERE m.id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $entry_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $entry = $result->fetch_assoc();
        
        if (!$entry) {
            return false;
        }
        
        // Get tags for this entry
        $tag_query = "SELECT t.* 
                     FROM mood_tags t
                     JOIN mood_entry_tags met ON t.id = met.tag_id
                     WHERE met.mood_entry_id = ?";
        $tag_stmt = $conn->prepare($tag_query);
        $tag_stmt->bind_param("i", $entry_id);
        $tag_stmt->execute();
        $tag_result = $tag_stmt->get_result();
        $entry['tags'] = [];
        while ($tag = $tag_result->fetch_assoc()) {
            $entry['tags'][] = $tag;
        }
        
        return $entry;
    } catch (Exception $e) {
        error_log("Error getting mood entry: " . $e->getMessage());
        return false;
    }
}

/**
 * Get mood entries with optional filtering
 * 
 * @param string $start_date Optional start date (YYYY-MM-DD)
 * @param string $end_date Optional end date (YYYY-MM-DD)
 * @param int $mood_level Optional mood level filter
 * @param array $tag_ids Optional array of tag IDs to filter by
 * @param string $time_of_day Optional time of day filter (morning, afternoon, evening, night)
 * @param string $search Optional search term for notes
 * @return array|bool Array of mood entries or false on failure
 */
function getMoodEntries($start_date = null, $end_date = null, $mood_level = null, $tag_ids = [], $time_of_day = null, $search = null) {
    global $conn;
    
    try {
        $query = "SELECT DISTINCT m.* FROM mood_entries m";
        
        // Join with tags table if tag filtering is requested
        if (!empty($tag_ids)) {
            $query .= " LEFT JOIN mood_entry_tags met ON m.id = met.mood_entry_id";
        }
        
        $query .= " WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if ($start_date) {
            $query .= " AND DATE(m.date) >= ?";
            $params[] = $start_date;
            $types .= "s";
        }
        
        if ($end_date) {
            $query .= " AND DATE(m.date) <= ?";
            $params[] = $end_date;
            $types .= "s";
        }
        
        if ($mood_level) {
            $query .= " AND m.mood_level = ?";
            $params[] = $mood_level;
            $types .= "i";
        }
        
        if (!empty($tag_ids)) {
            $placeholders = implode(',', array_fill(0, count($tag_ids), '?'));
            $query .= " AND met.tag_id IN ($placeholders)";
            foreach ($tag_ids as $tag_id) {
                $params[] = $tag_id;
                $types .= "i";
            }
        }
        
        if ($time_of_day) {
            switch ($time_of_day) {
                case 'morning':
                    $query .= " AND TIME(m.date) BETWEEN '05:00:00' AND '11:59:59'";
                    break;
                case 'afternoon':
                    $query .= " AND TIME(m.date) BETWEEN '12:00:00' AND '16:59:59'";
                    break;
                case 'evening':
                    $query .= " AND TIME(m.date) BETWEEN '17:00:00' AND '20:59:59'";
                    break;
                case 'night':
                    $query .= " AND (TIME(m.date) >= '21:00:00' OR TIME(m.date) < '05:00:00')";
                    break;
            }
        }
        
        if ($search) {
            $query .= " AND m.notes LIKE ?";
            $params[] = "%$search%";
            $types .= "s";
        }
        
        $query .= " ORDER BY m.date DESC";
        
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $mood_entries = [];
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
            
            $mood_entries[] = $entry;
        }
        
        return $mood_entries;
    } catch (Exception $e) {
        error_log("Error getting mood entries: " . $e->getMessage());
        return false;
    }
}

/**
 * Get mood entries grouped by day for calendar view
 * 
 * @param string $year_month Year and month in format YYYY-MM
 * @return array|bool Array of mood entries grouped by day or false on failure
 */
function getMoodEntriesByDay($year_month) {
    global $conn;
    
    try {
        $start_date = $year_month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $query = "SELECT 
                    DATE(date) as day,
                    COUNT(*) as entry_count,
                    AVG(mood_level) as avg_mood
                  FROM mood_entries
                  WHERE DATE(date) BETWEEN ? AND ?
                  GROUP BY DATE(date)
                  ORDER BY day";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $entries_by_day = [];
        while ($row = $result->fetch_assoc()) {
            $day = (int)substr($row['day'], 8, 2);
            $entries_by_day[$day] = [
                'entry_count' => $row['entry_count'],
                'avg_mood' => round($row['avg_mood'], 1)
            ];
        }
        
        return $entries_by_day;
    } catch (Exception $e) {
        error_log("Error getting mood entries by day: " . $e->getMessage());
        return false;
    }
}

/**
 * Get mood tags
 * 
 * @param string $category Optional category filter
 * @return array|bool Array of mood tags or false on failure
 */
function getMoodTags($category = null) {
    global $conn;
    
    try {
        $query = "SELECT * FROM mood_tags WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($category) {
            $query .= " AND category = ?";
            $params[] = $category;
            $types .= "s";
        }
        
        $query .= " ORDER BY name ASC";
        
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tags = [];
        while ($tag = $result->fetch_assoc()) {
            $tags[] = $tag;
        }
        
        return $tags;
    } catch (Exception $e) {
        error_log("Error getting mood tags: " . $e->getMessage());
        return false;
    }
}

/**
 * Get mood tag categories
 * 
 * @return array|bool Array of unique tag categories or false on failure
 */
function getMoodTagCategories() {
    global $conn;
    
    try {
        $query = "SELECT DISTINCT category FROM mood_tags WHERE category IS NOT NULL ORDER BY category";
        $result = $conn->query($query);
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        
        return $categories;
    } catch (Exception $e) {
        error_log("Error getting mood tag categories: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a new mood tag
 * 
 * @param string $name Tag name
 * @param string $category Optional tag category
 * @param string $color Optional tag color (hex code)
 * @return int|bool The ID of the inserted tag or false on failure
 */
function createMoodTag($name, $category = null, $color = '#6c757d') {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO mood_tags (name, category, color) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $category, $color);
        $stmt->execute();
        
        return $conn->insert_id;
    } catch (Exception $e) {
        error_log("Error creating mood tag: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing mood tag
 * 
 * @param int $tag_id The ID of the tag to update
 * @param string $name Tag name
 * @param string $category Optional tag category
 * @param string $color Optional tag color (hex code)
 * @return bool True on success, false on failure
 */
function updateMoodTag($tag_id, $name, $category = null, $color = '#6c757d') {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE mood_tags SET name = ?, category = ?, color = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $category, $color, $tag_id);
        $stmt->execute();
        
        return $stmt->affected_rows > 0;
    } catch (Exception $e) {
        error_log("Error updating mood tag: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a mood tag
 * 
 * @param int $tag_id The ID of the tag to delete
 * @return bool True on success, false on failure
 */
function deleteMoodTag($tag_id) {
    global $conn;
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Delete tag associations
        $assoc_stmt = $conn->prepare("DELETE FROM mood_entry_tags WHERE tag_id = ?");
        $assoc_stmt->bind_param("i", $tag_id);
        $assoc_stmt->execute();
        
        // Delete tag
        $stmt = $conn->prepare("DELETE FROM mood_tags WHERE id = ?");
        $stmt->bind_param("i", $tag_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return $stmt->affected_rows > 0;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error deleting mood tag: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a mood entry
 * 
 * @param int $entry_id The ID of the entry to delete
 * @return bool True on success, false on failure
 */
function deleteMoodEntry($entry_id) {
    global $conn;
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Delete tag associations
        $tag_stmt = $conn->prepare("DELETE FROM mood_entry_tags WHERE mood_entry_id = ?");
        $tag_stmt->bind_param("i", $entry_id);
        $tag_stmt->execute();
        
        // Delete entry
        $stmt = $conn->prepare("DELETE FROM mood_entries WHERE id = ?");
        $stmt->bind_param("i", $entry_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return $stmt->affected_rows > 0;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error deleting mood entry: " . $e->getMessage());
        return false;
    }
}

/**
 * Get mood statistics
 * 
 * @param string $start_date Optional start date (YYYY-MM-DD)
 * @param string $end_date Optional end date (YYYY-MM-DD)
 * @param array $tag_ids Optional array of tag IDs to filter by
 * @return array|bool Array of mood statistics or false on failure
 */
function getMoodStatistics($start_date = null, $end_date = null, $tag_ids = []) {
    global $conn;
    
    try {
        // Base query for mood entries
        $query = "SELECT m.* FROM mood_entries m";
        
        // Join with tags table if tag filtering is requested
        if (!empty($tag_ids)) {
            $query .= " LEFT JOIN mood_entry_tags met ON m.id = met.mood_entry_id";
        }
        
        $query .= " WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if ($start_date) {
            $query .= " AND DATE(m.date) >= ?";
            $params[] = $start_date;
            $types .= "s";
        }
        
        if ($end_date) {
            $query .= " AND DATE(m.date) <= ?";
            $params[] = $end_date;
            $types .= "s";
        }
        
        if (!empty($tag_ids)) {
            $placeholders = implode(',', array_fill(0, count($tag_ids), '?'));
            $query .= " AND met.tag_id IN ($placeholders)";
            foreach ($tag_ids as $tag_id) {
                $params[] = $tag_id;
                $types .= "i";
            }
        }
        
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Initialize statistics
        $stats = [
            'total_entries' => 0,
            'avg_mood' => 0,
            'mood_distribution' => [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0
            ],
            'time_of_day' => [
                'morning' => 0,
                'afternoon' => 0,
                'evening' => 0,
                'night' => 0
            ],
            'mood_by_day' => [],
            'mood_by_tag' => []
        ];
        
        $total_mood = 0;
        $entries = [];
        
        // Process entries
        while ($entry = $result->fetch_assoc()) {
            $stats['total_entries']++;
            $total_mood += $entry['mood_level'];
            $stats['mood_distribution'][$entry['mood_level']]++;
            
            // Determine time of day
            $hour = (int)date('H', strtotime($entry['date']));
            if ($hour >= 5 && $hour < 12) {
                $stats['time_of_day']['morning']++;
            } elseif ($hour >= 12 && $hour < 17) {
                $stats['time_of_day']['afternoon']++;
            } elseif ($hour >= 17 && $hour < 21) {
                $stats['time_of_day']['evening']++;
            } else {
                $stats['time_of_day']['night']++;
            }
            
            // Store entry for further processing
            $entries[] = $entry;
        }
        
        // Calculate average mood
        $stats['avg_mood'] = $stats['total_entries'] > 0 ? round($total_mood / $stats['total_entries'], 1) : 0;
        
        // Calculate mood by day
        $mood_by_day = [];
        foreach ($entries as $entry) {
            $day = date('Y-m-d', strtotime($entry['date']));
            if (!isset($mood_by_day[$day])) {
                $mood_by_day[$day] = [
                    'total' => 0,
                    'count' => 0
                ];
            }
            $mood_by_day[$day]['total'] += $entry['mood_level'];
            $mood_by_day[$day]['count']++;
        }
        
        foreach ($mood_by_day as $day => $data) {
            $stats['mood_by_day'][$day] = round($data['total'] / $data['count'], 1);
        }
        
        // Calculate mood by tag
        if (!empty($entries)) {
            $entry_ids = array_column($entries, 'id');
            $id_list = implode(',', $entry_ids);
            
            $tag_query = "SELECT t.id, t.name, t.color, AVG(m.mood_level) as avg_mood, COUNT(m.id) as entry_count
                         FROM mood_tags t
                         JOIN mood_entry_tags met ON t.id = met.tag_id
                         JOIN mood_entries m ON met.mood_entry_id = m.id
                         WHERE m.id IN ($id_list)
                         GROUP BY t.id
                         ORDER BY entry_count DESC";
            
            $tag_result = $conn->query($tag_query);
            
            while ($tag = $tag_result->fetch_assoc()) {
                $stats['mood_by_tag'][] = [
                    'id' => $tag['id'],
                    'name' => $tag['name'],
                    'color' => $tag['color'],
                    'avg_mood' => round($tag['avg_mood'], 1),
                    'entry_count' => $tag['entry_count']
                ];
            }
        }
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting mood statistics: " . $e->getMessage());
        return false;
    }
}
