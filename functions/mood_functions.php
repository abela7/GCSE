<?php
// Include database connection
require_once '../config/database.php';

/**
 * Create a new mood entry
 * 
 * @param int $mood_level Mood level (1-5)
 * @param string $notes Optional notes about the mood
 * @param int $subject_id Optional associated subject ID
 * @param int $topic_id Optional associated topic ID
 * @param array $factor_ids Array of mood factor IDs
 * @return int|bool The ID of the inserted mood entry or false on failure
 */
function createMoodEntry($mood_level, $notes = null, $subject_id = null, $topic_id = null, $factor_ids = []) {
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
        
        // Insert mood factors if provided
        if (!empty($factor_ids) && $mood_entry_id) {
            $factor_stmt = $db->prepare("INSERT INTO mood_entry_factors (mood_entry_id, mood_factor_id) 
                                        VALUES (:mood_entry_id, :factor_id)");
            
            foreach ($factor_ids as $factor_id) {
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
 * Get mood entries with optional filtering
 * 
 * @param string $start_date Optional start date (YYYY-MM-DD)
 * @param string $end_date Optional end date (YYYY-MM-DD)
 * @param int $subject_id Optional subject ID filter
 * @param int $topic_id Optional topic ID filter
 * @return array|bool Array of mood entries or false on failure
 */
function getMoodEntries($start_date = null, $end_date = null, $subject_id = null, $topic_id = null) {
    try {
        $db = getDBConnection();
        
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
                  WHERE 1=1";
        
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
        
        $query .= " ORDER BY m.date DESC";
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $mood_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get factors for each mood entry
        foreach ($mood_entries as &$entry) {
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
 * Get mood factors
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

/**
 * Get mood statistics
 * 
 * @param string $start_date Optional start date (YYYY-MM-DD)
 * @param string $end_date Optional end date (YYYY-MM-DD)
 * @param int $subject_id Optional subject ID filter
 * @return array|bool Array of mood statistics or false on failure
 */
function getMoodStatistics($start_date = null, $end_date = null, $subject_id = null) {
    try {
        $db = getDBConnection();
        
        // Prepare date range
        $date_condition = "1=1";
        $params = [];
        
        if ($start_date) {
            $date_condition .= " AND DATE(date) >= :start_date";
            $params[':start_date'] = $start_date;
        }
        
        if ($end_date) {
            $date_condition .= " AND DATE(date) <= :end_date";
            $params[':end_date'] = $end_date;
        }
        
        if ($subject_id) {
            $date_condition .= " AND associated_subject_id = :subject_id";
            $params[':subject_id'] = $subject_id;
        }
        
        // Get average mood
        $avg_query = "SELECT AVG(mood_level) as average_mood FROM mood_entries WHERE $date_condition";
        $avg_stmt = $db->prepare($avg_query);
        
        foreach ($params as $key => $value) {
            $avg_stmt->bindValue($key, $value);
        }
        
        $avg_stmt->execute();
        $avg_result = $avg_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get mood distribution
        $dist_query = "SELECT mood_level, COUNT(*) as count FROM mood_entries WHERE $date_condition GROUP BY mood_level ORDER BY mood_level";
        $dist_stmt = $db->prepare($dist_query);
        
        foreach ($params as $key => $value) {
            $dist_stmt->bindValue($key, $value);
        }
        
        $dist_stmt->execute();
        $dist_result = $dist_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get most common factors
        $factor_query = "SELECT f.name, f.is_positive, COUNT(*) as count 
                        FROM mood_factors f
                        JOIN mood_entry_factors mef ON f.id = mef.mood_factor_id
                        JOIN mood_entries m ON mef.mood_entry_id = m.id
                        WHERE $date_condition
                        GROUP BY f.id
                        ORDER BY count DESC
                        LIMIT 5";
        
        $factor_stmt = $db->prepare($factor_query);
        
        foreach ($params as $key => $value) {
            $factor_stmt->bindValue($key, $value);
        }
        
        $factor_stmt->execute();
        $factor_result = $factor_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare statistics array
        $statistics = [
            'average_mood' => round($avg_result['average_mood'], 1),
            'mood_distribution' => $dist_result,
            'common_factors' => $factor_result
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
?>
