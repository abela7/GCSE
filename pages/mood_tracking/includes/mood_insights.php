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
 * @return array Generated insights with categories and recommendations
 */
function generateInsights($mood_patterns, $tag_analysis, $time_patterns) {
    $insights = [
        'summary' => [],
        'patterns' => [],
        'recommendations' => [],
        'positive_factors' => [],
        'areas_for_improvement' => []
    ];
    
    // Overall mood summary
    $avg_mood = $mood_patterns['average'];
    $insights['summary'][] = generateMoodSummary($avg_mood, $mood_patterns['trend']);
    
    // Pattern recognition
    $insights['patterns'] = array_merge(
        generateTrendPatterns($mood_patterns),
        generateTimeBasedPatterns($time_patterns),
        generateTagPatterns($tag_analysis)
    );
    
    // Generate specific recommendations
    $insights['recommendations'] = generateRecommendations(
        $mood_patterns,
        $tag_analysis,
        $time_patterns
    );
    
    // Identify positive factors
    $insights['positive_factors'] = identifyPositiveFactors($tag_analysis, $time_patterns);
    
    // Areas for improvement
    $insights['areas_for_improvement'] = identifyAreasForImprovement(
        $mood_patterns,
        $tag_analysis,
        $time_patterns
    );
    
    return $insights;
}

/**
 * Generate mood summary based on average mood and trend
 * 
 * @param float $avg_mood Average mood value
 * @param string $trend Mood trend
 * @return string Detailed mood summary
 */
function generateMoodSummary($avg_mood, $trend) {
    $summary = "Your overall mood has been ";
    
    if ($avg_mood >= 4.5) {
        $summary .= "excellent! 🌟 ";
    } elseif ($avg_mood >= 3.5) {
        $summary .= "good! 😊 ";
    } elseif ($avg_mood >= 2.5) {
        $summary .= "moderate. 😐 ";
    } elseif ($avg_mood >= 1.5) {
        $summary .= "somewhat low. 😔 ";
    } else {
        $summary .= "quite low. 😢 ";
    }
    
    switch ($trend) {
        case 'improving':
            $summary .= "There's a positive trend in your mood, showing improvement over time.";
            break;
        case 'declining':
            $summary .= "There's been a slight decline in your mood recently.";
            break;
        case 'stable':
            $summary .= "Your mood has been relatively stable.";
            break;
        case 'variable':
            $summary .= "Your mood has shown some variability.";
            break;
    }
    
    return $summary;
}

/**
 * Generate detailed trend patterns
 * 
 * @param array $mood_patterns Mood pattern analysis
 * @return array Trend patterns
 */
function generateTrendPatterns($mood_patterns) {
    $patterns = [];
    
    // Analyze weekly patterns
    if (isset($mood_patterns['weekly_pattern'])) {
        $best_day = array_search(max($mood_patterns['weekly_pattern']), $mood_patterns['weekly_pattern']);
        $worst_day = array_search(min($mood_patterns['weekly_pattern']), $mood_patterns['weekly_pattern']);
        
        $patterns[] = "Your mood tends to be highest on {$best_day}s.";
        $patterns[] = "You might want to plan engaging activities for {$worst_day}s to boost your mood.";
    }
    
    // Analyze mood stability
    $volatility = $mood_patterns['volatility'];
    if ($volatility > 2) {
        $patterns[] = "Your mood shows significant variation. Consider tracking potential triggers for these changes.";
    } elseif ($volatility < 0.5) {
        $patterns[] = "Your mood is very stable, which can be positive but might also indicate emotional numbness if persistent.";
    }
    
    return $patterns;
}

/**
 * Generate time-based patterns
 * 
 * @param array $time_patterns Time pattern analysis
 * @return array Time-based patterns
 */
function generateTimeBasedPatterns($time_patterns) {
    $patterns = [];
    $best_time = '';
    $worst_time = '';
    $max_mood = 0;
    $min_mood = 5;
    
    foreach ($time_patterns as $period => $data) {
        if ($data['count'] > 0) {
            if ($data['avg_mood'] > $max_mood) {
                $max_mood = $data['avg_mood'];
                $best_time = $period;
            }
            if ($data['avg_mood'] < $min_mood) {
                $min_mood = $data['avg_mood'];
                $worst_time = $period;
            }
        }
    }
    
    if ($best_time && $worst_time) {
        $patterns[] = "Your peak mood typically occurs during the {$best_time}.";
        $patterns[] = "You might benefit from extra self-care during the {$worst_time}.";
    }
    
    return $patterns;
}

/**
 * Generate tag-based patterns
 * 
 * @param array $tag_analysis Tag correlation analysis
 * @return array Tag-based patterns
 */
function generateTagPatterns($tag_analysis) {
    $patterns = [];
    $positive_tags = [];
    $negative_tags = [];
    
    foreach ($tag_analysis as $tag => $data) {
        if ($data['count'] >= 3) { // Only consider tags with sufficient data
            if ($data['avg_mood'] >= 4) {
                $positive_tags[] = $tag;
            } elseif ($data['avg_mood'] <= 2) {
                $negative_tags[] = $tag;
            }
        }
    }
    
    if (!empty($positive_tags)) {
        $patterns[] = "Activities involving " . implode(", ", $positive_tags) . " consistently boost your mood.";
    }
    if (!empty($negative_tags)) {
        $patterns[] = "You might want to review your approach to " . implode(", ", $negative_tags) . " as these tend to lower your mood.";
    }
    
    return $patterns;
}

/**
 * Generate specific recommendations based on analysis
 * 
 * @param array $mood_patterns Mood pattern analysis
 * @param array $tag_analysis Tag correlation analysis
 * @param array $time_patterns Time pattern analysis
 * @return array Specific recommendations
 */
function generateRecommendations($mood_patterns, $tag_analysis, $time_patterns) {
    $recommendations = [];
    
    // Time-based recommendations
    foreach ($time_patterns as $period => $data) {
        if ($data['count'] > 0 && $data['avg_mood'] <= 2.5) {
            $recommendations[] = "Consider scheduling uplifting activities during the {$period} to improve your mood during this time.";
        }
    }
    
    // Activity recommendations
    $positive_activities = [];
    foreach ($tag_analysis as $tag => $data) {
        if ($data['count'] >= 3 && $data['avg_mood'] >= 4) {
            $positive_activities[] = $tag;
        }
    }
    
    if (!empty($positive_activities)) {
        $recommendations[] = "Try to incorporate more " . implode(", ", $positive_activities) . " into your routine, as these activities positively impact your mood.";
    }
    
    // Trend-based recommendations
    if ($mood_patterns['trend'] === 'declining') {
        $recommendations[] = "Your mood has been declining. Consider reaching out to a friend or professional for support.";
    }
    
    return $recommendations;
}

/**
 * Identify positive factors affecting mood
 * 
 * @param array $tag_analysis Tag correlation analysis
 * @param array $time_patterns Time pattern analysis
 * @return array Positive factors
 */
function identifyPositiveFactors($tag_analysis, $time_patterns) {
    $factors = [];
    
    // Identify positive activities
    foreach ($tag_analysis as $tag => $data) {
        if ($data['count'] >= 3 && $data['avg_mood'] >= 3.5) {
            $factors[] = [
                'type' => 'activity',
                'name' => $tag,
                'impact' => round($data['avg_mood'], 1),
                'frequency' => $data['count']
            ];
        }
    }
    
    // Identify positive time periods
    foreach ($time_patterns as $period => $data) {
        if ($data['count'] >= 3 && $data['avg_mood'] >= 3.5) {
            $factors[] = [
                'type' => 'time_period',
                'name' => $period,
                'impact' => round($data['avg_mood'], 1),
                'frequency' => $data['count']
            ];
        }
    }
    
    return $factors;
}

/**
 * Identify areas for improvement
 * 
 * @param array $mood_patterns Mood pattern analysis
 * @param array $tag_analysis Tag correlation analysis
 * @param array $time_patterns Time pattern analysis
 * @return array Areas for improvement
 */
function identifyAreasForImprovement($mood_patterns, $tag_analysis, $time_patterns) {
    $areas = [];
    
    // Check for concerning patterns
    if ($mood_patterns['volatility'] > 2) {
        $areas[] = [
            'type' => 'pattern',
            'concern' => 'High mood volatility',
            'suggestion' => 'Consider tracking potential triggers and establishing a more consistent routine'
        ];
    }
    
    // Identify challenging activities
    foreach ($tag_analysis as $tag => $data) {
        if ($data['count'] >= 3 && $data['avg_mood'] <= 2.5) {
            $areas[] = [
                'type' => 'activity',
                'concern' => $tag,
                'impact' => round($data['avg_mood'], 1),
                'suggestion' => "Consider seeking support or developing new strategies for handling {$tag}-related situations"
            ];
        }
    }
    
    // Identify challenging time periods
    foreach ($time_patterns as $period => $data) {
        if ($data['count'] >= 3 && $data['avg_mood'] <= 2.5) {
            $areas[] = [
                'type' => 'time_period',
                'concern' => $period,
                'impact' => round($data['avg_mood'], 1),
                'suggestion' => "Try to implement mood-boosting activities during the {$period}"
            ];
        }
    }
    
    return $areas;
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

// This include file displays the pattern analysis sections
// It's used across different time periods (daily, weekly, monthly)
// to maintain consistency and reduce code duplication

// Tag Patterns
if (!empty($analysis['insights']['patterns']['tags'])): ?>
    <div class="mb-4">
        <h4 class="h5 mb-3">Activity Impact</h4>
        <?php foreach ($analysis['insights']['patterns']['tags'] as $pattern): ?>
            <div class="pattern-card">
                <div class="pattern-title">
                    <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($pattern['title'] ?? 'Unknown Tag'); ?>
                </div>
                <div class="pattern-description"><?php echo htmlspecialchars($pattern['description'] ?? 'No description available.'); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Mood Consistency -->
<?php if (!empty($analysis['insights']['patterns']['consistency'])): ?>
    <div class="mb-4">
        <h4 class="h5 mb-3">Mood Consistency</h4>
        <div class="pattern-card">
            <?php 
            $consistency = $analysis['insights']['patterns']['consistency'];
            $icon = '';
            $level = $consistency['level'] ?? 'unknown';
            switch($level) {
                case 'very_stable': $icon = '🎯'; break;
                case 'stable': $icon = '⚖️'; break;
                case 'moderate': $icon = '🔄'; break;
                case 'volatile': $icon = '📊'; break;
                default: $icon = '📈';
            }
            ?>
            <div class="pattern-title"><?php echo $icon . ' ' . ucfirst(str_replace('_', ' ', $level)); ?></div>
            <div class="pattern-description"><?php echo htmlspecialchars($consistency['description'] ?? 'No description available.'); ?></div>
            <?php if (!empty($consistency['metrics'])): ?>
                <div class="mt-2 text-white-50">
                    <small>
                        <?php 
                        echo "Stable days: {$consistency['metrics']['stable_days']}/{$consistency['metrics']['total_days']}";
                        if (isset($consistency['metrics']['mood_swings']) && $consistency['metrics']['mood_swings'] > 0) {
                            echo " • Significant mood changes: {$consistency['metrics']['mood_swings']}";
                        }
                        ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Improvement Areas -->
<?php if (!empty($analysis['insights']['patterns']['improvement_areas']['areas'])): ?>
    <div class="mb-4">
        <h4 class="h5 mb-3">Areas for Improvement</h4>
        <div class="pattern-card">
            <div class="pattern-title"><i class="fas fa-bullseye me-2"></i>Focus Areas</div>
            <div class="pattern-description">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($analysis['insights']['patterns']['improvement_areas']['suggestions'] as $suggestion): ?>
                        <li class="mb-2">
                            <i class="fas fa-arrow-right me-2"></i><?php echo htmlspecialchars($suggestion); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?> 