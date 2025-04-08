<?php
// Include database connection
require_once '../../../config/database.php';

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
    try {
        $db = getDBConnection();
        
        // Begin transaction
        $db->beginTransaction();
        
        // Insert mood entry
        $stmt = $db->prepare("INSERT INTO mood_entries (mood_level, notes, associated_subject_id, associated_topic_id) 
                             VALUES (:mood_level, :notes, :subject_id, :topic_id)");
        
        $stmt->bindParam(':mood_level', $mood_level, PDO::PARAM_INT);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
        
        $stmt->execute();
        $mood_entry_id = $db->lastInsertId();
        
        // Insert mood tags if provided
        if (!empty($tag_ids) && $mood_entry_id) {
            $tag_stmt = $db->prepare("INSERT INTO mood_entry_tags (mood_entry_id, tag_id) 
                                     VALUES (:mood_entry_id, :tag_id)");
            
            foreach ($tag_ids as $tag_id) {
                $tag_stmt->bindParam(':mood_entry_id', $mood_entry_id, PDO::PARAM_INT);
                $tag_stmt->bindParam(':tag_id', $tag_id, PDO::PARAM_INT);
                $tag_stmt->execute();
            }
        }
        
        // Insert mood factors if provided (for backward compatibility)
        if (isset($_POST['factors']) && !empty($_POST['factors']) && $mood_entry_id) {
            $factor_stmt = $db->prepare("INSERT INTO mood_entry_factors (mood_entry_id, mood_factor_id) 
                                        VALUES (:mood_entry_id, :factor_id)");
            
            foreach ($_POST['factors'] as $factor_id) {
                $factor_stmt->bindParam(':mood_entry_id', $mood_entry_id, PDO::PARAM_INT);
                $factor_stmt->bindParam(':factor_id', $factor_id, PDO::PARAM_INT);
                $factor_stmt->execute();
            }
        }
        
        // Commit transaction
        $db->commit();
        
        return $mood_entry_id;
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
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
    try {
        $db = getDBConnection();
        
        // Begin transaction
        $db->beginTransaction();
        
        // Update mood entry
        $stmt = $db->prepare("UPDATE mood_entries 
                             SET mood_level = :mood_level, 
                                 notes = :notes, 
                                 associated_subject_id = :subject_id, 
                                 associated_topic_id = :topic_id,
                                 updated_at = CURRENT_TIMESTAMP
                             WHERE id = :entry_id");
        
        $stmt->bindParam(':mood_level', $mood_level, PDO::PARAM_INT);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
        $stmt->bindParam(':entry_id', $entry_id, PDO::PARAM_INT);
        
        $stmt->execute();
        
        // Delete existing tags for this entry
        $delete_stmt = $db->prepare("DELETE FROM mood_entry_tags WHERE mood_entry_id = :entry_id");
        $delete_stmt->bindParam(':entry_id', $entry_id, PDO::PARAM_INT);
        $delete_stmt->execute();
        
        // Insert new tags
        if (!empty($tag_ids)) {
            $tag_stmt = $db->prepare("INSERT INTO mood_entry_tags (mood_entry_id, tag_id) 
                                     VALUES (:mood_entry_id, :tag_id)");
            
            foreach ($tag_ids as $tag_id) {
                $tag_stmt->bindParam(':mood_entry_id', $entry_id, PDO::PARAM_INT);
                $tag_stmt->bindParam(':tag_id', $tag_id, PDO::PARAM_INT);
                $tag_stmt->execute();
            }
        }
        
        // Commit transaction
        $db->commit();
        
        return true;
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
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
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("SELECT m.*, 
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
                            WHERE m.id = :entry_id");
        
        $stmt->bindParam(':entry_id', $entry_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$entry) {
            return false;
        }
        
        // Get tags for this entry
        $tag_stmt = $db->prepare("SELECT t.* 
                                 FROM mood_tags t
                                 JOIN mood_entry_tags met ON t.id = met.tag_id
                                 WHERE met.mood_entry_id = :entry_id");
        $tag_stmt->bindParam(':entry_id', $entry_id, PDO::PARAM_INT);
        $tag_stmt->execute();
        $entry['tags'] = $tag_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get factors for this entry (for backward compatibility)
        $factor_stmt = $db->prepare("SELECT f.* 
                                    FROM mood_factors f
                                    JOIN mood_entry_factors mef ON f.id = mef.mood_factor_id
                                    WHERE mef.mood_entry_id = :entry_id");
        $factor_stmt->bindParam(':entry_id', $entry_id, PDO::PARAM_INT);
        $factor_stmt->execute();
        $entry['factors'] = $factor_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $entry;
    } catch (PDOException $e) {
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
    try {
        $db = getDBConnection();
        
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
        
        if ($start_date) {
            $query .= " AND DATE(m.date) >= :start_date";
            $params[':start_date'] = $start_date;
        }
        
        if ($end_date) {
            $query .= " AND DATE(m.date) <= :end_date";
            $params[':end_date'] = $end_date;
        }
        
        if ($subject_id) {
            $query .= " AND m.associated_subject_id = :subject_id";
            $params[':subject_id'] = $subject_id;
        }
        
        if ($topic_id) {
            $query .= " AND m.associated_topic_id = :topic_id";
            $params[':topic_id'] = $topic_id;
        }
        
        if ($mood_level) {
            $query .= " AND m.mood_level = :mood_level";
            $params[':mood_level'] = $mood_level;
        }
        
        if (!empty($tag_ids)) {
            $placeholders = implode(',', array_fill(0, count($tag_ids), '?'));
            $query .= " AND met.tag_id IN ($placeholders)";
            // We'll bind these parameters later
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
            $query .= " AND m.notes LIKE :search";
            $params[':search'] = "%$search%";
        }
        
        $query .= " ORDER BY m.date DESC";
        
        $stmt = $db->prepare($query);
        
        // Bind named parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Bind tag_id parameters
        if (!empty($tag_ids)) {
            foreach ($tag_ids as $index => $tag_id) {
                $stmt->bindValue($index + 1, $tag_id, PDO::PARAM_INT);
            }
        }
        
        $stmt->execute();
        $mood_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get tags and factors for each mood entry
        foreach ($mood_entries as &$entry) {
            // Get tags
            $tag_stmt = $db->prepare("SELECT t.* 
                                     FROM mood_tags t
                                     JOIN mood_entry_tags met ON t.id = met.tag_id
                                     WHERE met.mood_entry_id = :entry_id");
            $tag_stmt->bindParam(':entry_id', $entry['id'], PDO::PARAM_INT);
            $tag_stmt->execute();
            $entry['tags'] = $tag_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get factors (for backward compatibility)
            $factor_stmt = $db->prepare("SELECT f.* 
                                        FROM mood_factors f
                                        JOIN mood_entry_factors mef ON f.id = mef.mood_factor_id
                                        WHERE mef.mood_entry_id = :entry_id");
            $factor_stmt->bindParam(':entry_id', $entry['id'], PDO::PARAM_INT);
            $factor_stmt->execute();
            $entry['factors'] = $factor_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $mood_entries;
    } catch (PDOException $e) {
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
    try {
        $db = getDBConnection();
        
        $start_date = $year_month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $query = "SELECT 
                    DATE(date) as day,
                    COUNT(*) as entry_count,
                    AVG(mood_level) as avg_mood
                  FROM mood_entries
                  WHERE DATE(date) BETWEEN :start_date AND :end_date
                  GROUP BY DATE(date)
                  ORDER BY day";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $day = (int)substr($row['day'], 8, 2);
            $result[$day] = [
                'entry_count' => $row['entry_count'],
                'avg_mood' => round($row['avg_mood'], 1)
            ];
        }
        
        return $result;
    } catch (PDOException $e) {
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
    try {
        $db = getDBConnection();
        
        $query = "SELECT * FROM mood_tags WHERE 1=1";
        
        $params = [];
        
        if ($category) {
            $query .= " AND category = :category";
            $params[':category'] = $category;
        }
        
        $query .= " ORDER BY name ASC";
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
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
    try {
        $db = getDBConnection();
        
        $query = "SELECT DISTINCT category FROM mood_tags WHERE category IS NOT NULL ORDER BY category";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $categories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[] = $row['category'];
        }
        
        return $categories;
    } catch (PDOException $e) {
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
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("INSERT INTO mood_tags (name, category, color) VALUES (:name, :category, :color)");
        
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':color', $color, PDO::PARAM_STR);
        
        $stmt->execute();
        
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error creating mood tag: " . $e->getMessage());
        return false;
    }
}

/**
 * Update a mood tag
 * 
 * @param int $tag_id Tag ID
 * @param string $name Tag name
 * @param string $category Optional tag category
 * @param string $color Optional tag color (hex code)
 * @return bool True on success, false on failure
 */
function updateMoodTag($tag_id, $name, $category = null, $color = '#6c757d') {
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("UPDATE mood_tags SET name = :name, category = :category, color = :color WHERE id = :tag_id");
        
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':color', $color, PDO::PARAM_STR);
        $stmt->bindParam(':tag_id', $tag_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error updating mood tag: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a mood tag
 * 
 * @param int $tag_id Tag ID
 * @return bool True on success, false on failure
 */
function deleteMoodTag($tag_id) {
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("DELETE FROM mood_tags WHERE id = :tag_id");
        $stmt->bindParam(':tag_id', $tag_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error deleting mood tag: " . $e->getMessage());
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
    try {
        $db = getDBConnection();
        
        // Base query for filtering
        $base_condition = "1=1";
        $params = [];
        
        if ($start_date) {
            $base_condition .= " AND DATE(m.date) >= :start_date";
            $params[':start_date'] = $start_date;
        }
        
        if ($end_date) {
            $base_condition .= " AND DATE(m.date) <= :end_date";
            $params[':end_date'] = $end_date;
        }
        
        if ($subject_id) {
            $base_condition .= " AND m.associated_subject_id = :subject_id";
            $params[':subject_id'] = $subject_id;
        }
        
        // Tag filtering requires a different approach
        $tag_join = "";
        $tag_condition = "";
        if (!empty($tag_ids)) {
            $tag_join = " JOIN mood_entry_tags met ON m.id = met.mood_entry_id";
            $placeholders = implode(',', array_fill(0, count($tag_ids), '?'));
            $tag_condition = " AND met.tag_id IN ($placeholders)";
        }
        
        // Get average mood
        $avg_query = "SELECT AVG(m.mood_level) as average_mood 
                     FROM mood_entries m
                     $tag_join
                     WHERE $base_condition $tag_condition";
        
        $avg_stmt = $db->prepare($avg_query);
        
        // Bind named parameters
        foreach ($params as $key => $value) {
            $avg_stmt->bindValue($key, $value);
        }
        
        // Bind tag_id parameters if needed
        if (!empty($tag_ids)) {
            foreach ($tag_ids as $index => $tag_id) {
                $avg_stmt->bindValue($index + 1, $tag_id, PDO::PARAM_INT);
            }
        }
        
        $avg_stmt->execute();
        $avg_result = $avg_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get mood distribution
        $dist_query = "SELECT m.mood_level, COUNT(*) as count 
                      FROM mood_entries m
                      $tag_join
                      WHERE $base_condition $tag_condition
                      GROUP BY m.mood_level 
                      ORDER BY m.mood_level";
        
        $dist_stmt = $db->prepare($dist_query);
        
        // Bind named parameters
        foreach ($params as $key => $value) {
            $dist_stmt->bindValue($key, $value);
        }
        
        // Bind tag_id parameters if needed
        if (!empty($tag_ids)) {
            foreach ($tag_ids as $index => $tag_id) {
                $dist_stmt->bindValue($index + 1, $tag_id, PDO::PARAM_INT);
            }
        }
        
        $dist_stmt->execute();
        $dist_result = $dist_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get most common tags
        $tag_query = "SELECT t.name, t.category, t.color, COUNT(*) as count 
                     FROM mood_tags t
                     JOIN mood_entry_tags met ON t.id = met.tag_id
                     JOIN mood_entries m ON met.mood_entry_id = m.id
                     WHERE $base_condition
                     GROUP BY t.id
                     ORDER BY count DESC
                     LIMIT 5";
        
        $tag_stmt = $db->prepare($tag_query);
        
        // Bind named parameters
        foreach ($params as $key => $value) {
            $tag_stmt->bindValue($key, $value);
        }
        
        $tag_stmt->execute();
        $tag_result = $tag_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get mood by time of day
        $time_query = "SELECT 
                        CASE 
                            WHEN TIME(m.date) BETWEEN '05:00:00' AND '11:59:59' THEN 'Morning'
                            WHEN TIME(m.date) BETWEEN '12:00:00' AND '16:59:59' THEN 'Afternoon'
                            WHEN TIME(m.date) BETWEEN '17:00:00' AND '20:59:59' THEN 'Evening'
                            ELSE 'Night'
                        END as time_of_day,
                        AVG(m.mood_level) as average_mood,
                        COUNT(*) as count
                      FROM mood_entries m
                      $tag_join
                      WHERE $base_condition $tag_condition
                      GROUP BY time_of_day
                      ORDER BY 
                        CASE time_of_day
                            WHEN 'Morning' THEN 1
                            WHEN 'Afternoon' THEN 2
                            WHEN 'Evening' THEN 3
                            WHEN 'Night' THEN 4
                        END";
        
        $time_stmt = $db->prepare($time_query);
        
        // Bind named parameters
        foreach ($params as $key => $value) {
            $time_stmt->bindValue($key, $value);
        }
        
        // Bind tag_id parameters if needed
        if (!empty($tag_ids)) {
            foreach ($tag_ids as $index => $tag_id) {
                $time_stmt->bindValue($index + 1, $tag_id, PDO::PARAM_INT);
            }
        }
        
        $time_stmt->execute();
        $time_result = $time_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get daily mood averages for trend analysis
        $trend_query = "SELECT DATE(m.date) as day, AVG(m.mood_level) as average_mood
                       FROM mood_entries m
                       $tag_join
                       WHERE $base_condition $tag_condition
                       GROUP BY DATE(m.date)
                       ORDER BY day ASC
                       LIMIT 30";
        
        $trend_stmt = $db->prepare($trend_query);
        
        // Bind named parameters
        foreach ($params as $key => $value) {
            $trend_stmt->bindValue($key, $value);
        }
        
        // Bind tag_id parameters if needed
        if (!empty($tag_ids)) {
            foreach ($tag_ids as $index => $tag_id) {
                $trend_stmt->bindValue($index + 1, $tag_id, PDO::PARAM_INT);
            }
        }
        
        $trend_stmt->execute();
        $trend_result = $trend_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare statistics array
        $statistics = [
            'average_mood' => round($avg_result['average_mood'] ?? 0, 1),
            'mood_distribution' => $dist_result,
            'common_tags' => $tag_result,
            'mood_by_time' => $time_result,
            'mood_trend' => $trend_result
        ];
        
        return $statistics;
    } catch (PDOException $e) {
        error_log("Error getting mood statistics: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a mood entry
 * 
 * @param int $mood_entry_id The ID of the mood entry to delete
 * @return bool True on success, false on failure
 */
function deleteMoodEntry($mood_entry_id) {
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("DELETE FROM mood_entries WHERE id = :id");
        $stmt->bindParam(':id', $mood_entry_id, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error deleting mood entry: " . $e->getMessage());
        return false;
    }
}

/**
 * Get mood factors (for backward compatibility)
 * 
 * @param bool $positive_only Optional filter for positive factors only
 * @param bool $negative_only Optional filter for negative factors only
 * @return array|bool Array of mood factors or false on failure
 */
function getMoodFactors($positive_only = false, $negative_only = false) {
    try {
        $db = getDBConnection();
        
        $query = "SELECT * FROM mood_factors WHERE 1=1";
        
        if ($positive_only) {
            $query .= " AND is_positive = 1";
        } elseif ($negative_only) {
            $query .= " AND is_positive = 0";
        }
        
        $query .= " ORDER BY name ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting mood factors: " . $e->getMessage());
        return false;
    }
}
?>
