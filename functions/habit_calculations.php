<?php
require_once __DIR__ . '/../includes/db_connect.php';

/**
 * Update statistics for a specific habit
 * @param int $habit_id The ID of the habit
 * @return bool Whether the update was successful
 */
function updateHabitStats($habit_id) {
    global $conn;
    
    try {
        // Calculate totals and points directly from habit_completions
        $sql = "SELECT 
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as total_completions,
                COUNT(CASE WHEN status = 'procrastinated' THEN 1 END) as total_procrastinated,
                COUNT(CASE WHEN status = 'skipped' THEN 1 END) as total_skips,
                SUM(points_earned) as total_points,
                ROUND(
                    (COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0) / 
                    NULLIF(COUNT(*), 0),
                    2
                ) as success_rate
                FROM habit_completions
                WHERE habit_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $habit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        
        if (!$stats) {
            // If no completions exist, set all stats to 0
            $stats = [
                'total_completions' => 0,
                'total_procrastinated' => 0,
                'total_skips' => 0,
                'total_points' => 0,
                'success_rate' => 0
            ];
        }
        
        // Calculate current streak
        $streak_sql = "SELECT completion_date, status 
                      FROM habit_completions 
                      WHERE habit_id = ? 
                      AND completion_date <= CURDATE()
                      ORDER BY completion_date DESC";
        
        $stmt = $conn->prepare($streak_sql);
        $stmt->bind_param("i", $habit_id);
        $stmt->execute();
        $streak_result = $stmt->get_result();
        
        $current_streak = 0;
        $longest_streak = 0;
        $prev_date = null;
        
        while ($row = $streak_result->fetch_assoc()) {
            $curr_date = new DateTime($row['completion_date']);
            
            if ($row['status'] === 'completed') {
                if (!$prev_date || $curr_date->diff($prev_date)->days === 1) {
                    $current_streak++;
                    $longest_streak = max($longest_streak, $current_streak);
                } else {
                    $current_streak = 1;
                }
            } else {
                $current_streak = 0;
            }
            
            $prev_date = $curr_date;
        }
        
        // Update all statistics in habits table
        $update_sql = "UPDATE habits SET 
                      total_completions = ?,
                      total_procrastinated = ?,
                      total_skips = ?,
                      current_points = ?,
                      success_rate = ?,
                      current_streak = ?,
                      longest_streak = ?,
                      updated_at = CURRENT_TIMESTAMP
                      WHERE id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param(
            "iiiiiiis",
            $stats['total_completions'],
            $stats['total_procrastinated'],
            $stats['total_skips'],
            $stats['total_points'],
            $stats['success_rate'],
            $current_streak,
            $longest_streak,
            $habit_id
        );
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Error in updateHabitStats: " . $e->getMessage());
        return false;
    }
}

/**
 * Calculate points based on habit type and completion status
 * @param int $point_rule_id The ID of the point rule
 * @param string $status The completion status (completed/procrastinated/skipped)
 * @return int The points earned
 */
function calculatePoints($point_rule_id, $status) {
    global $conn;
    
    try {
        $sql = "SELECT completion_points, procrastinated_points, skip_points 
                FROM habit_point_rules 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $point_rule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rule = $result->fetch_assoc();
        
        if (!$rule) {
            error_log("No point rule found for ID: " . $point_rule_id);
            return 0;
        }
        
        switch ($status) {
            case 'completed':
                return (int)$rule['completion_points'];
            case 'procrastinated':
                return (int)$rule['procrastinated_points'];
            case 'skipped':
                return (int)$rule['skip_points'];
            default:
                error_log("Invalid status for point calculation: " . $status);
                return 0;
        }
    } catch (Exception $e) {
        error_log("Error calculating points: " . $e->getMessage());
        return 0;
    }
}

/**
 * Record a habit completion with status and optional reason
 * @param int $habit_id The ID of the habit
 * @param string $status The completion status (completed/procrastinated/skipped)
 * @param int|null $reason_id The ID of the reason from habit_completion_reasons
 * @param string|null $notes Additional notes about the completion
 * @return bool Whether the operation was successful
 */
function recordHabitCompletion($habit_id, $status, $reason_id = null, $notes = null) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Get habit point rule
        $sql = "SELECT point_rule_id FROM habits WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $habit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $habit = $result->fetch_assoc();
        
        // Calculate points
        $points = calculatePoints($habit['point_rule_id'], $status);
        
        // Insert completion record
        $sql = "INSERT INTO habit_completions (
                    habit_id, completion_date, completion_time, 
                    status, reason_id, points_earned, notes
                ) VALUES (?, CURDATE(), CURRENT_TIME(), ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issis", $habit_id, $status, $reason_id, $points, $notes);
        $stmt->execute();
        
        // Update habit statistics
        updateHabitStats($habit_id);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error recording habit completion: " . $e->getMessage());
        return false;
    }
}

/**
 * Get habit statistics for a date range
 * @param int $habit_id The ID of the habit
 * @param string $start_date Start date (YYYY-MM-DD)
 * @param string $end_date End date (YYYY-MM-DD)
 * @return array|false Statistics or false on error
 */
function getHabitStats($habit_id, $start_date, $end_date) {
    global $conn;
    
    try {
        $sql = "SELECT 
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completions,
                COUNT(CASE WHEN status = 'procrastinated' THEN 1 END) as procrastinated,
                COUNT(CASE WHEN status = 'skipped' THEN 1 END) as skips,
                SUM(points_earned) as points,
                GROUP_CONCAT(DISTINCT CASE 
                    WHEN status IN ('procrastinated', 'skipped') 
                    THEN reason 
                END) as reasons
                FROM habit_completions
                WHERE habit_id = ?
                AND completion_date BETWEEN ? AND ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $habit_id, $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error getting habit stats: " . $e->getMessage());
        return false;
    }
}

/**
 * Get points trend for a habit
 * @param int $habit_id The ID of the habit
 * @param int $days Number of days to look back
 * @return array|false Daily points or false on error
 */
function getPointsTrend($habit_id, $days = 30) {
    global $conn;
    
    try {
        $sql = "SELECT 
                completion_date,
                SUM(points_earned) as daily_points,
                GROUP_CONCAT(status) as statuses
                FROM habit_completions
                WHERE habit_id = ?
                AND completion_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY completion_date
                ORDER BY completion_date";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $habit_id, $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting points trend: " . $e->getMessage());
        return false;
    }
}

/**
 * Calculate and update streak information for a habit
 * @param int $habit_id The ID of the habit
 * @return bool True if successful, false otherwise
 */
function updateStreaks($habit_id) {
    global $conn;
    
    try {
        // Get completion history ordered by date
        $sql = "SELECT completion_date, status
                FROM habit_completions
                WHERE habit_id = ?
                ORDER BY completion_date DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $habit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $current_streak = 0;
        $longest_streak = 0;
        $temp_streak = 0;
        $last_date = null;
        
        while ($row = $result->fetch_assoc()) {
            $current_date = new DateTime($row['completion_date']);
            
            if ($last_date) {
                $diff = $last_date->diff($current_date);
                if ($diff->days > 1) {
                    // Break in streak
                    $temp_streak = 0;
                }
            }
            
            if ($row['status'] === 'completed') {
                $temp_streak++;
                if ($temp_streak > $longest_streak) {
                    $longest_streak = $temp_streak;
                }
                if (!$last_date || $diff->days <= 1) {
                    $current_streak = $temp_streak;
                }
            } else {
                $temp_streak = 0;
                if (!$last_date) {
                    $current_streak = 0;
                }
            }
            
            $last_date = $current_date;
        }
        
        // Update streaks in habits table
        $update_sql = "UPDATE habits 
                      SET current_streak = ?,
                          longest_streak = ?
                      WHERE id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("iii", $current_streak, $longest_streak, $habit_id);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error updating streaks: " . $e->getMessage());
        return false;
    }
}

/**
 * Record a habit completion with structured reason
 * @param int $habit_id The ID of the habit
 * @param string $status The completion status
 * @param int|null $reason_id The ID of the predefined reason
 * @param string|null $custom_reason Custom reason text
 * @param string|null $notes Additional notes
 * @return bool True if successful, false otherwise
 */
function recordHabitCompletionWithReason($habit_id, $status, $reason_id = null, $custom_reason = null, $notes = null) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Record basic completion first
        if (!recordHabitCompletion($habit_id, $status, null, $notes)) {
            throw new Exception("Failed to record completion");
        }
        
        // If reason provided, record it
        if ($reason_id || $custom_reason) {
            $completion_id = $conn->insert_id;
            $reason_sql = "INSERT INTO habit_completion_reasons 
                          (completion_id, reason_id, custom_reason) 
                          VALUES (?, ?, ?)";
            
            $stmt = $conn->prepare($reason_sql);
            $stmt->bind_param("iis", $completion_id, $reason_id, $custom_reason);
            if (!$stmt->execute()) {
                throw new Exception("Failed to record reason");
            }
        }
        
        // Update streaks
        if (!updateStreaks($habit_id)) {
            throw new Exception("Failed to update streaks");
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error recording habit completion with reason: " . $e->getMessage());
        return false;
    }
}

/**
 * Get the most common reasons for procrastination/skipping for a habit
 * @param int $habit_id The ID of the habit
 * @param int $days Number of days to look back
 * @return array|false Array of reasons and their counts or false on error
 */
function getCommonReasons($habit_id, $days = 30) {
    global $conn;
    
    try {
        $sql = "SELECT 
                hcr.reason,
                COUNT(*) as count,
                hc.status
                FROM habit_completions hc
                JOIN habit_completion_reasons hcr ON hc.reason_id = hcr.id
                WHERE hc.habit_id = ?
                AND hc.completion_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND hc.status IN ('procrastinated', 'skipped')
                GROUP BY hcr.reason, hc.status
                ORDER BY count DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $habit_id, $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting common reasons: " . $e->getMessage());
        return false;
    }
} 