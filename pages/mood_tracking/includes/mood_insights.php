<?php
// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/db_connect.php';

/**
 * Get detailed mood insights for a specific date range
 * 
 * @param string $start_date Start date (YYYY-MM-DD)
 * @param string $end_date End date (YYYY-MM-DD)
 * @return array Detailed mood insights
 */
function getMoodInsights($start_date, $end_date) {
    global $conn;
    
    try {
        // Get all mood entries for the date range
        $query = "SELECT m.*, 
                 GROUP_CONCAT(DISTINCT t.name) as tags,
                 GROUP_CONCAT(DISTINCT f.name) as factors
                 FROM mood_entries m
                 LEFT JOIN mood_entry_tags met ON m.id = met.mood_entry_id
                 LEFT JOIN mood_tags t ON met.tag_id = t.id
                 LEFT JOIN mood_entry_factors mef ON m.id = mef.mood_entry_id
                 LEFT JOIN mood_factors f ON mef.mood_factor_id = f.id
                 WHERE DATE(m.date) BETWEEN ? AND ?
                 GROUP BY m.id
                 ORDER BY m.date ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $entries = [];
        while ($row = $result->fetch_assoc()) {
            $entries[] = $row;
        }
        
        if (empty($entries)) {
            return [
                'status' => 'no_data',
                'message' => 'No mood entries found for the selected period.'
            ];
        }
        
        // Calculate overall mood statistics
        $total_entries = count($entries);
        $total_mood = array_sum(array_column($entries, 'mood_level'));
        $avg_mood = round($total_mood / $total_entries, 1);
        
        // Group entries by day
        $entries_by_day = [];
        foreach ($entries as $entry) {
            $day = date('Y-m-d', strtotime($entry['date']));
            if (!isset($entries_by_day[$day])) {
                $entries_by_day[$day] = [
                    'entries' => [],
                    'total_mood' => 0,
                    'count' => 0
                ];
            }
            $entries_by_day[$day]['entries'][] = $entry;
            $entries_by_day[$day]['total_mood'] += $entry['mood_level'];
            $entries_by_day[$day]['count']++;
        }
        
        // Calculate daily averages
        foreach ($entries_by_day as &$day_data) {
            $day_data['avg_mood'] = round($day_data['total_mood'] / $day_data['count'], 1);
        }
        
        // Analyze mood patterns
        $mood_patterns = analyzeMoodPatterns($entries_by_day);
        
        // Analyze tag correlations
        $tag_analysis = analyzeTagCorrelations($entries);
        
        // Analyze time patterns
        $time_patterns = analyzeTimePatterns($entries);
        
        // Generate insights
        $insights = generateInsights($mood_patterns, $tag_analysis, $time_patterns);
        
        return [
            'status' => 'success',
            'period' => [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'total_entries' => $total_entries,
                'avg_mood' => $avg_mood
            ],
            'daily_data' => $entries_by_day,
            'patterns' => $mood_patterns,
            'tag_analysis' => $tag_analysis,
            'time_patterns' => $time_patterns,
            'insights' => $insights
        ];
        
    } catch (Exception $e) {
        error_log("Error getting mood insights: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Failed to analyze mood data.'
        ];
    }
}

/**
 * Analyze mood patterns from daily data
 * 
 * @param array $entries_by_day Daily mood entries
 * @return array Mood patterns analysis
 */
function analyzeMoodPatterns($entries_by_day) {
    $patterns = [
        'trend' => 'stable',
        'volatility' => 'low',
        'high_points' => [],
        'low_points' => [],
        'consistency' => 'moderate'
    ];
    
    $mood_values = array_column($entries_by_day, 'avg_mood');
    $total_days = count($mood_values);
    
    if ($total_days < 2) {
        return $patterns;
    }
    
    // Calculate trend
    $first_mood = $mood_values[0];
    $last_mood = end($mood_values);
    $mood_change = $last_mood - $first_mood;
    
    if (abs($mood_change) < 0.5) {
        $patterns['trend'] = 'stable';
    } elseif ($mood_change > 0) {
        $patterns['trend'] = 'improving';
    } else {
        $patterns['trend'] = 'declining';
    }
    
    // Calculate volatility
    $mood_range = max($mood_values) - min($mood_values);
    if ($mood_range > 2) {
        $patterns['volatility'] = 'high';
    } elseif ($mood_range > 1) {
        $patterns['volatility'] = 'moderate';
    } else {
        $patterns['volatility'] = 'low';
    }
    
    // Find high and low points
    foreach ($entries_by_day as $day => $data) {
        if ($data['avg_mood'] >= 4) {
            $patterns['high_points'][] = [
                'date' => $day,
                'mood' => $data['avg_mood'],
                'entries' => $data['entries']
            ];
        } elseif ($data['avg_mood'] <= 2) {
            $patterns['low_points'][] = [
                'date' => $day,
                'mood' => $data['avg_mood'],
                'entries' => $data['entries']
            ];
        }
    }
    
    // Calculate consistency
    $mood_std_dev = calculateStandardDeviation($mood_values);
    if ($mood_std_dev < 0.5) {
        $patterns['consistency'] = 'high';
    } elseif ($mood_std_dev < 1) {
        $patterns['consistency'] = 'moderate';
    } else {
        $patterns['consistency'] = 'low';
    }
    
    return $patterns;
}

/**
 * Analyze correlations between tags and mood levels
 * 
 * @param array $entries Mood entries with tags
 * @return array Tag correlation analysis
 */
function analyzeTagCorrelations($entries) {
    $tag_analysis = [];
    $tag_moods = [];
    
    // Group entries by tags
    foreach ($entries as $entry) {
        if (!empty($entry['tags'])) {
            $tags = explode(',', $entry['tags']);
            foreach ($tags as $tag) {
                if (!isset($tag_moods[$tag])) {
                    $tag_moods[$tag] = [
                        'total_mood' => 0,
                        'count' => 0,
                        'entries' => []
                    ];
                }
                $tag_moods[$tag]['total_mood'] += $entry['mood_level'];
                $tag_moods[$tag]['count']++;
                $tag_moods[$tag]['entries'][] = $entry;
            }
        }
    }
    
    // Calculate tag statistics
    foreach ($tag_moods as $tag => $data) {
        $avg_mood = round($data['total_mood'] / $data['count'], 1);
        
        $tag_analysis[$tag] = [
            'avg_mood' => $avg_mood,
            'entry_count' => $data['count'],
            'impact' => getMoodImpact($avg_mood),
            'common_factors' => analyzeCommonFactors($data['entries'])
        ];
    }
    
    // Sort by impact
    uasort($tag_analysis, function($a, $b) {
        return $b['avg_mood'] <=> $a['avg_mood'];
    });
    
    return $tag_analysis;
}

/**
 * Analyze time-based patterns in mood entries
 * 
 * @param array $entries Mood entries
 * @return array Time pattern analysis
 */
function analyzeTimePatterns($entries) {
    $time_patterns = [
        'morning' => ['total' => 0, 'count' => 0],
        'afternoon' => ['total' => 0, 'count' => 0],
        'evening' => ['total' => 0, 'count' => 0],
        'night' => ['total' => 0, 'count' => 0]
    ];
    
    foreach ($entries as $entry) {
        $hour = date('H', strtotime($entry['date']));
        
        if ($hour >= 5 && $hour < 12) {
            $time_patterns['morning']['total'] += $entry['mood_level'];
            $time_patterns['morning']['count']++;
        } elseif ($hour >= 12 && $hour < 17) {
            $time_patterns['afternoon']['total'] += $entry['mood_level'];
            $time_patterns['afternoon']['count']++;
        } elseif ($hour >= 17 && $hour < 21) {
            $time_patterns['evening']['total'] += $entry['mood_level'];
            $time_patterns['evening']['count']++;
        } else {
            $time_patterns['night']['total'] += $entry['mood_level'];
            $time_patterns['night']['count']++;
        }
    }
    
    // Calculate averages
    foreach ($time_patterns as &$period) {
        if ($period['count'] > 0) {
            $period['avg_mood'] = round($period['total'] / $period['count'], 1);
            $period['impact'] = getMoodImpact($period['avg_mood']);
        } else {
            $period['avg_mood'] = 0;
            $period['impact'] = 'neutral';
        }
    }
    
    return $time_patterns;
}

/**
 * Generate human-readable insights from analysis
 * 
 * @param array $mood_patterns Mood pattern analysis
 * @param array $tag_analysis Tag correlation analysis
 * @param array $time_patterns Time pattern analysis
 * @return array Generated insights
 */
function generateInsights($mood_patterns, $tag_analysis, $time_patterns) {
    $insights = [];
    
    // Overall mood trend
    $insights['trend'] = generateTrendInsight($mood_patterns);
    
    // Tag-based insights
    $insights['tags'] = generateTagInsights($tag_analysis);
    
    // Time-based insights
    $insights['time'] = generateTimeInsights($time_patterns);
    
    // Consistency insights
    $insights['consistency'] = generateConsistencyInsight($mood_patterns);
    
    return $insights;
}

/**
 * Generate trend insight
 * 
 * @param array $mood_patterns Mood pattern analysis
 * @return string Trend insight
 */
function generateTrendInsight($mood_patterns) {
    $trend = $mood_patterns['trend'];
    $volatility = $mood_patterns['volatility'];
    
    if ($trend === 'improving') {
        return "Your mood has been improving over time, showing positive progress.";
    } elseif ($trend === 'declining') {
        return "Your mood has been declining, which might need attention.";
    } else {
        return "Your mood has been relatively stable.";
    }
}

/**
 * Generate tag-based insights
 * 
 * @param array $tag_analysis Tag correlation analysis
 * @return array Tag insights
 */
function generateTagInsights($tag_analysis) {
    $insights = [];
    
    foreach ($tag_analysis as $tag => $data) {
        if ($data['avg_mood'] >= 4) {
            $insights[] = "You feel great when engaging with {$tag} activities.";
        } elseif ($data['avg_mood'] <= 2) {
            $insights[] = "{$tag} activities tend to lower your mood.";
        }
    }
    
    return $insights;
}

/**
 * Generate time-based insights
 * 
 * @param array $time_patterns Time pattern analysis
 * @return array Time insights
 */
function generateTimeInsights($time_patterns) {
    $insights = [];
    
    foreach ($time_patterns as $period => $data) {
        if ($data['count'] > 0) {
            if ($data['avg_mood'] >= 4) {
                $insights[] = "You tend to feel best during the {$period}.";
            } elseif ($data['avg_mood'] <= 2) {
                $insights[] = "Your mood tends to be lower during the {$period}.";
            }
        }
    }
    
    return $insights;
}

/**
 * Generate consistency insight
 * 
 * @param array $mood_patterns Mood pattern analysis
 * @return string Consistency insight
 */
function generateConsistencyInsight($mood_patterns) {
    $consistency = $mood_patterns['consistency'];
    
    if ($consistency === 'high') {
        return "Your mood has been very consistent, showing stable emotional patterns.";
    } elseif ($consistency === 'moderate') {
        return "Your mood shows moderate variation, which is normal.";
    } else {
        return "Your mood shows significant variation, which might indicate external factors affecting your emotional state.";
    }
}

/**
 * Calculate standard deviation of mood values
 * 
 * @param array $values Mood values
 * @return float Standard deviation
 */
function calculateStandardDeviation($values) {
    $count = count($values);
    if ($count < 2) return 0;
    
    $mean = array_sum($values) / $count;
    $squared_diff_sum = 0;
    
    foreach ($values as $value) {
        $squared_diff_sum += pow($value - $mean, 2);
    }
    
    return sqrt($squared_diff_sum / ($count - 1));
}

/**
 * Get mood impact description
 * 
 * @param float $avg_mood Average mood value
 * @return string Impact description
 */
function getMoodImpact($avg_mood) {
    if ($avg_mood >= 4) {
        return 'very_positive';
    } elseif ($avg_mood >= 3) {
        return 'positive';
    } elseif ($avg_mood >= 2) {
        return 'negative';
    } else {
        return 'very_negative';
    }
}

/**
 * Analyze common factors in mood entries
 * 
 * @param array $entries Mood entries
 * @return array Common factors analysis
 */
function analyzeCommonFactors($entries) {
    $factors = [];
    
    foreach ($entries as $entry) {
        if (!empty($entry['factors'])) {
            $entry_factors = explode(',', $entry['factors']);
            foreach ($entry_factors as $factor) {
                if (!isset($factors[$factor])) {
                    $factors[$factor] = 0;
                }
                $factors[$factor]++;
            }
        }
    }
    
    arsort($factors);
    return array_slice($factors, 0, 3, true);
} 