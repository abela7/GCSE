<?php
// Include database connection
require_once '../../../config/db_connect.php';

/**
 * Create a new mood entry
 * 
 * @param int $mood_level Mood level (1-5)
 * @param string $notes Optional notes about the mood
 * @param int $subject_id Optional associated subject ID
 * @param int $topic_id Optional associated topic ID
 * @param array $tag_ids Array of tag IDs
 * @return int|bool The ID of the inserted mood entry or false on failure
 */
function createMoodEntry($mood_level, $notes = null, $subject_id = null, $topic_id = null, $tag_ids = []) {
    global $conn;
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert mood entry
        $stmt = $conn->prepare("INSERT INTO mood_entries (mood_level, notes, associated_subject_id, associated_topic_id) 
                             VALUES (?, ?, ?, ?)");
        
        $stmt->bind_param("isii", $mood_level, $notes, $subject_id, $topic_id);
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
        
        // Insert mood factors if provided (for backward compatibility)
        if (isset($_POST['factors']) && !empty($_POST['factors']) && $mood_entry_id) {
            $factor_stmt = $conn->prepare("INSERT INTO mood_entry_factors (mood_entry_id, mood_factor_id) 
                                        VALUES (?, ?)");
            
            foreach ($_POST['factors'] as $factor_id) {
                $factor_stmt->bind_param("ii", $mood_entry_id, $factor_id);
                $factor_stmt->execute();
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
 * @param int $subject_id Optional associated subject ID
 * @param int $topic_id Optional associated topic ID
 * @param array $tag_ids Array of tag IDs
 * @return bool True on success, false on failure
 */
function updateMoodEntry($entry_id, $mood_level, $notes = null, $subject_id = null, $topic_id = null, $tag_ids = []) {
    global $conn;
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Update mood entry
        $stmt = $conn->prepare("UPDATE mood_entries 
                             SET mood_level = ?, 
                                 notes = ?, 
                                 associated_subject_id = ?, 
                                 associated_topic_id = ?,
                                 updated_at = CURRENT_TIMESTAMP
                             WHERE id = ?");
        
        $stmt->bind_param("isiii", $mood_level, $notes, $subject_id, $topic_id, $entry_id);
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
                WHERE m.id = ?";
        
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
        
        // Get factors for this entry (for backward compatibility)
        $factor_query = "SELECT f.* 
                        FROM mood_factors f
                        JOIN mood_entry_factors mef ON f.id = mef.mood_factor_id
                        WHERE mef.mood_entry_id = ?";
        $factor_stmt = $conn->prepare($factor_query);
        $factor_stmt->bind_param("i", $entry_id);
        $factor_stmt->execute();
        $factor_result = $factor_stmt->get_result();
        $entry['factors'] = [];
        while ($factor = $factor_result->fetch_assoc()) {
            $entry['factors'][] = $factor;
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
 * @param int $subject_id Optional subject ID filter
 * @param int $topic_id Optional topic ID filter
 * @param int $mood_level Optional mood level filter
 * @param array $tag_ids Optional array of tag IDs to filter by
 * @param string $time_of_day Optional time of day filter (morning, afternoon, evening, night)
 * @param string $search Optional search term for notes
 * @return array|bool Array of mood entries or false on failure
 */
function getMoodEntries($start_date = null, $end_date = null, $subject_id = null, $topic_id = null, $mood_level = null, $tag_ids = [], $time_of_day = null, $search = null) {
    global $conn;
    
    try {
        $query = "SELECT DISTINCT m.*, 
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
                  LEFT JOIN math_topics mt ON m.associated_topic_id = mt.id";
        
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
        
        if ($subject_id) {
            $query .= " AND m.associated_subject_id = ?";
            $params[] = $subject_id;
            $types .= "i";
        }
        
        if ($topic_id) {
            $query .= " AND m.associated_topic_id = ?";
            $params[] = $topic_id;
            $types .= "i";
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
            
            // Get factors for this entry (for backward compatibility)
            $factor_query = "SELECT f.* 
                            FROM mood_factors f
                            JOIN mood_entry_factors mef ON f.id = mef.mood_factor_id
                            WHERE mef.mood_entry_id = ?";
            $factor_stmt = $conn->prepare($factor_query);
            $factor_stmt->bind_param("i", $entry['id']);
            $factor_stmt->execute();
            $factor_result = $factor_stmt->get_result();
            $entry['factors'] = [];
            while ($factor = $factor_result->fetch_assoc()) {
                $entry['factors'][] = $factor;
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
        $stmt = $conn->prepare("DELETE FROM mood_tags WHERE id = ?");
        $stmt->bind_param("i", $tag_id);
        $stmt->execute();
        
        return $stmt->affected_rows > 0;
    } catch (Exception $e) {
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
        
        // Delete associated tags
        $tag_stmt = $conn->prepare("DELETE FROM mood_entry_tags WHERE mood_entry_id = ?");
        $tag_stmt->bind_param("i", $entry_id);
        $tag_stmt->execute();
        
        // Delete associated factors
        $factor_stmt = $conn->prepare("DELETE FROM mood_entry_factors WHERE mood_entry_id = ?");
        $factor_stmt->bind_param("i", $entry_id);
        $factor_stmt->execute();
        
        // Delete the entry
        $entry_stmt = $conn->prepare("DELETE FROM mood_entries WHERE id = ?");
        $entry_stmt->bind_param("i", $entry_id);
        $entry_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return $entry_stmt->affected_rows > 0;
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
 * @param int $subject_id Optional subject ID filter
 * @param array $tag_ids Optional array of tag IDs to filter by
 * @return array|bool Array of mood statistics or false on failure
 */
function getMoodStatistics($start_date = null, $end_date = null, $subject_id = null, $tag_ids = []) {
    global $conn;
    
    try {
        // Base query for all statistics
        $base_query = "FROM mood_entries m";
        
        // Join with tags table if tag filtering is requested
        if (!empty($tag_ids)) {
            $base_query .= " LEFT JOIN mood_entry_tags met ON m.id = met.mood_entry_id";
        }
        
        $base_query .= " WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if ($start_date) {
            $base_query .= " AND DATE(m.date) >= ?";
            $params[] = $start_date;
            $types .= "s";
        }
        
        if ($end_date) {
            $base_query .= " AND DATE(m.date) <= ?";
            $params[] = $end_date;
            $types .= "s";
        }
        
        if ($subject_id) {
            $base_query .= " AND m.associated_subject_id = ?";
            $params[] = $subject_id;
            $types .= "i";
        }
        
        if (!empty($tag_ids)) {
            $placeholders = implode(',', array_fill(0, count($tag_ids), '?'));
            $base_query .= " AND met.tag_id IN ($placeholders)";
            foreach ($tag_ids as $tag_id) {
                $params[] = $tag_id;
                $types .= "i";
            }
        }
        
        // Get average mood
        $avg_query = "SELECT AVG(mood_level) as avg_mood " . $base_query;
        $avg_stmt = $conn->prepare($avg_query);
        
        if (!empty($params)) {
            $avg_stmt->bind_param($types, ...$params);
        }
        
        $avg_stmt->execute();
        $avg_result = $avg_stmt->get_result();
        $avg_row = $avg_result->fetch_assoc();
        $avg_mood = $avg_row['avg_mood'] ? round($avg_row['avg_mood'], 1) : 0;
        
        // Get mood distribution
        $dist_query = "SELECT mood_level, COUNT(*) as count " . $base_query . " GROUP BY mood_level ORDER BY mood_level";
        $dist_stmt = $conn->prepare($dist_query);
        
        if (!empty($params)) {
            $dist_stmt->bind_param($types, ...$params);
        }
        
        $dist_stmt->execute();
        $dist_result = $dist_stmt->get_result();
        
        $mood_distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        while ($row = $dist_result->fetch_assoc()) {
            $mood_distribution[$row['mood_level']] = (int)$row['count'];
        }
        
        // Get mood by time of day
        $time_query = "SELECT 
                        CASE 
                            WHEN TIME(date) BETWEEN '05:00:00' AND '11:59:59' THEN 'morning'
                            WHEN TIME(date) BETWEEN '12:00:00' AND '16:59:59' THEN 'afternoon'
                            WHEN TIME(date) BETWEEN '17:00:00' AND '20:59:59' THEN 'evening'
                            ELSE 'night'
                        END as time_of_day,
                        AVG(mood_level) as avg_mood,
                        COUNT(*) as count
                      " . $base_query . "
                      GROUP BY time_of_day
                      ORDER BY FIELD(time_of_day, 'morning', 'afternoon', 'evening', 'night')";
        
        $time_stmt = $conn->prepare($time_query);
        
        if (!empty($params)) {
            $time_stmt->bind_param($types, ...$params);
        }
        
        $time_stmt->execute();
        $time_result = $time_stmt->get_result();
        
        $mood_by_time = [
            'morning' => ['avg_mood' => 0, 'count' => 0],
            'afternoon' => ['avg_mood' => 0, 'count' => 0],
            'evening' => ['avg_mood' => 0, 'count' => 0],
            'night' => ['avg_mood' => 0, 'count' => 0]
        ];
        
        while ($row = $time_result->fetch_assoc()) {
            $mood_by_time[$row['time_of_day']] = [
                'avg_mood' => round($row['avg_mood'], 1),
                'count' => (int)$row['count']
            ];
        }
        
        // Get mood trend by day
        $trend_query = "SELECT 
                        DATE(date) as day,
                        AVG(mood_level) as avg_mood
                      " . $base_query . "
                      GROUP BY day
                      ORDER BY day
                      LIMIT 30";
        
        $trend_stmt = $conn->prepare($trend_query);
        
        if (!empty($params)) {
            $trend_stmt->bind_param($types, ...$params);
        }
        
        $trend_stmt->execute();
        $trend_result = $trend_stmt->get_result();
        
        $mood_trend = [];
        while ($row = $trend_result->fetch_assoc()) {
            $mood_trend[] = [
                'day' => $row['day'],
                'avg_mood' => round($row['avg_mood'], 1)
            ];
        }
        
        // Get tag statistics
        $tag_query = "SELECT 
                      t.id,
                      t.name,
                      t.color,
                      COUNT(*) as count,
                      AVG(m.mood_level) as avg_mood
                    FROM mood_tags t
                    JOIN mood_entry_tags met ON t.id = met.tag_id
                    JOIN mood_entries m ON met.mood_entry_id = m.id
                    WHERE 1=1";
        
        $tag_params = [];
        $tag_types = "";
        
        if ($start_date) {
            $tag_query .= " AND DATE(m.date) >= ?";
            $tag_params[] = $start_date;
            $tag_types .= "s";
        }
        
        if ($end_date) {
            $tag_query .= " AND DATE(m.date) <= ?";
            $tag_params[] = $end_date;
            $tag_types .= "s";
        }
        
        if ($subject_id) {
            $tag_query .= " AND m.associated_subject_id = ?";
            $tag_params[] = $subject_id;
            $tag_types .= "i";
        }
        
        $tag_query .= " GROUP BY t.id
                      ORDER BY count DESC
                      LIMIT 10";
        
        $tag_stmt = $conn->prepare($tag_query);
        
        if (!empty($tag_params)) {
            $tag_stmt->bind_param($tag_types, ...$tag_params);
        }
        
        $tag_stmt->execute();
        $tag_result = $tag_stmt->get_result();
        
        $tag_stats = [];
        while ($row = $tag_result->fetch_assoc()) {
            $tag_stats[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'color' => $row['color'],
                'count' => (int)$row['count'],
                'avg_mood' => round($row['avg_mood'], 1)
            ];
        }
        
        // Return all statistics
        return [
            'avg_mood' => $avg_mood,
            'mood_distribution' => $mood_distribution,
            'mood_by_time' => $mood_by_time,
            'mood_trend' => $mood_trend,
            'tag_stats' => $tag_stats
        ];
    } catch (Exception $e) {
        error_log("Error getting mood statistics: " . $e->getMessage());
        return false;
    }
}
