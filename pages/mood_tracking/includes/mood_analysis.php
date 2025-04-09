<?php

class MoodAnalyzer {
    private $conn;
    private $mood_scale;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->mood_scale = [
            5 => ['label' => 'Very Happy', 'emoji' => '😊', 'color' => '#28a745'],
            4 => ['label' => 'Happy', 'emoji' => '🙂', 'color' => '#20c997'],
            3 => ['label' => 'Neutral', 'emoji' => '😐', 'color' => '#ffc107'],
            2 => ['label' => 'Down', 'emoji' => '😕', 'color' => '#fd7e14'],
            1 => ['label' => 'Very Down', 'emoji' => '😢', 'color' => '#dc3545']
        ];
    }

    public function analyzeMoodPatterns($start_date, $end_date) {
        try {
            // Get daily, weekly, and monthly mood data
            $daily_data = $this->analyzeDailyMood($start_date, $end_date);
            $weekly_data = $this->analyzeWeeklyMood($start_date, $end_date);
            $monthly_data = $this->analyzeMonthlyMood($start_date, $end_date);

            // Get time patterns and tag correlations
            $time_patterns = $this->analyzeTimeOfDayPatterns($start_date, $end_date);
            $tag_correlations = $this->analyzeTagCorrelations($start_date, $end_date);
            $consistency = $this->analyzeMoodConsistency($start_date, $end_date);
            $improvement_areas = $this->identifyImprovementAreas($start_date, $end_date);

            // Generate insights
            $insights = [
                'daily' => $this->generateDailyInsights($daily_data),
                'weekly' => $this->generateWeeklyInsights($weekly_data),
                'monthly' => $this->generateMonthlyInsights($monthly_data),
                'patterns' => [
                    'time' => $this->generateTimePatternInsights($time_patterns),
                    'tags' => $this->generateTagInsights($tag_correlations),
                    'consistency' => $consistency,
                    'improvement_areas' => [
                        'areas' => $improvement_areas,
                        'suggestions' => $this->generateImprovementSuggestions($improvement_areas)
                    ]
                ]
            ];

            return [
                'status' => 'success',
                'insights' => $insights,
                'data' => [
                    'daily' => $daily_data,
                    'weekly' => $weekly_data,
                    'monthly' => $monthly_data,
                    'time_patterns' => $time_patterns,
                    'tag_correlations' => $tag_correlations
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error analyzing mood patterns: ' . $e->getMessage()
            ];
        }
    }

    private function analyzeDailyMood($start_date, $end_date) {
        $query = "SELECT 
                    DATE(date) as entry_date,
                    AVG(mood_level) as avg_mood,
                    COUNT(*) as entry_count,
                    GROUP_CONCAT(notes) as notes
                  FROM mood_entries
                  WHERE date BETWEEN ? AND ?
                  GROUP BY DATE(date)
                  ORDER BY entry_date";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $daily_data = [];
        while ($row = $result->fetch_assoc()) {
            $daily_data[$row['entry_date']] = [
                'avg_mood' => round($row['avg_mood'], 1),
                'entry_count' => $row['entry_count'],
                'notes' => explode(',', $row['notes'])
            ];
        }

        return $daily_data;
    }

    private function generateDailyInsights($daily_data) {
        if (empty($daily_data)) {
            return null;
        }

        $today = date('Y-m-d');
        if (!isset($daily_data[$today])) {
            return null;
        }

        $mood_level = round($daily_data[$today]['avg_mood']);
        $mood_label = $this->mood_scale[$mood_level]['label'];

        return [
            'mood' => $mood_label,
            'mood_level' => $mood_level,
            'description' => $this->generateMoodDescription($mood_level, $daily_data[$today]),
            'suggestions' => $this->generateSuggestions($mood_level)
        ];
    }

    private function generateMoodDescription($mood_level, $data) {
        $descriptions = [
            5 => "You're having an excellent day! Your mood is very positive and uplifting.",
            4 => "You're having a good day! Your mood is stable and positive.",
            3 => "You're having an okay day. Your mood is balanced and neutral.",
            2 => "You're having a challenging day. Your mood is somewhat low.",
            1 => "You're having a difficult day. Your mood is significantly low."
        ];

        return $descriptions[$mood_level] ?? "No mood description available.";
    }

    private function generateSuggestions($mood_level) {
        $suggestions = [
            5 => "Consider using this positive energy to connect with others or pursue personal goals.",
            4 => "Build on this positive mood by engaging in activities you enjoy.",
            3 => "Try incorporating some mood-lifting activities into your day.",
            2 => "Consider reaching out to someone you trust or doing something that usually lifts your spirits.",
            1 => "It might be helpful to talk to someone or practice self-care activities."
        ];

        return $suggestions[$mood_level] ?? "No suggestions available.";
    }

    private function generateTimePatternInsights($time_patterns) {
        $insights = [];
        foreach ($time_patterns as $period => $data) {
            if ($data['entry_count'] > 0) {
                $insights[] = [
                    'title' => $period,
                    'description' => $this->generateTimeDescription($period, $data['avg_mood'])
                ];
            }
        }
        return $insights;
    }

    private function generateTimeDescription($period, $avg_mood) {
        $mood_level = round($avg_mood);
        $descriptions = [
            'Morning' => [
                5 => "Your mornings are typically very positive!",
                4 => "You tend to start your days well",
                3 => "Your mornings are generally balanced",
                2 => "Mornings can be challenging for you",
                1 => "You often struggle in the mornings"
            ],
            'Afternoon' => [
                5 => "Your afternoons are usually very energetic!",
                4 => "You maintain good energy in the afternoons",
                3 => "Your afternoons are typically steady",
                2 => "Afternoons can be difficult for you",
                1 => "You often experience low mood in the afternoons"
            ],
            'Evening' => [
                5 => "Your evenings are typically very enjoyable!",
                4 => "You usually end your days well",
                3 => "Your evenings are generally calm",
                2 => "Evenings can be challenging for you",
                1 => "You often feel down in the evenings"
            ],
            'Night' => [
                5 => "Your nights are usually very peaceful!",
                4 => "You typically feel good at night",
                3 => "Your nights are generally steady",
                2 => "Nights can be difficult for you",
                1 => "You often struggle at night"
            ]
        ];

        return $descriptions[$period][$mood_level] ?? "No pattern detected for this time period.";
    }

    public function getMoodScale() {
        return $this->mood_scale;
    }

    private function analyzeWeeklyMood($start_date, $end_date) {
        $query = "SELECT 
                    YEARWEEK(date) as week,
                    AVG(mood_level) as avg_mood,
                    COUNT(*) as entry_count,
                    GROUP_CONCAT(DISTINCT notes) as notes,
                    GROUP_CONCAT(DISTINCT tag_id) as tags
                  FROM mood_entries me
                  LEFT JOIN mood_entry_tags met ON me.id = met.mood_entry_id
                  WHERE date BETWEEN ? AND ?
                  GROUP BY YEARWEEK(date)
                  ORDER BY week DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $weekly_data = [];
        while ($row = $result->fetch_assoc()) {
            $weekly_data[$row['week']] = [
                'avg_mood' => round($row['avg_mood'], 1),
                'entry_count' => $row['entry_count'],
                'notes' => explode(',', $row['notes']),
                'tags' => explode(',', $row['tags']),
                'mood_label' => $this->getMoodLabel($row['avg_mood']),
                'trend' => $this->calculateMoodTrend($row['avg_mood'])
            ];
        }

        return $weekly_data;
    }

    private function analyzeMonthlyMood($start_date, $end_date) {
        $query = "SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    AVG(mood_level) as avg_mood,
                    COUNT(*) as entry_count,
                    GROUP_CONCAT(DISTINCT notes) as notes,
                    GROUP_CONCAT(DISTINCT tag_id) as tags
                  FROM mood_entries me
                  LEFT JOIN mood_entry_tags met ON me.id = met.mood_entry_id
                  WHERE date BETWEEN ? AND ?
                  GROUP BY DATE_FORMAT(date, '%Y-%m')
                  ORDER BY month DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $monthly_data = [];
        while ($row = $result->fetch_assoc()) {
            $monthly_data[$row['month']] = [
                'avg_mood' => round($row['avg_mood'], 1),
                'entry_count' => $row['entry_count'],
                'notes' => explode(',', $row['notes']),
                'tags' => explode(',', $row['tags']),
                'mood_label' => $this->getMoodLabel($row['avg_mood']),
                'trend' => $this->calculateMoodTrend($row['avg_mood'])
            ];
        }

        return $monthly_data;
    }

    private function analyzeTimeOfDayPatterns($start_date, $end_date) {
        $query = "SELECT 
                    CASE 
                        WHEN HOUR(date) BETWEEN 5 AND 11 THEN 'Morning'
                        WHEN HOUR(date) BETWEEN 12 AND 16 THEN 'Afternoon'
                        WHEN HOUR(date) BETWEEN 17 AND 20 THEN 'Evening'
                        ELSE 'Night'
                    END as time_period,
                    AVG(mood_level) as avg_mood,
                    COUNT(*) as entry_count
                  FROM mood_entries
                  WHERE date BETWEEN ? AND ?
                  GROUP BY time_period
                  ORDER BY FIELD(time_period, 'Morning', 'Afternoon', 'Evening', 'Night')";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $time_patterns = [];
        while ($row = $result->fetch_assoc()) {
            $time_patterns[$row['time_period']] = [
                'avg_mood' => round($row['avg_mood'], 1),
                'entry_count' => $row['entry_count']
            ];
        }

        // Ensure all time periods are included
        $all_periods = ['Morning', 'Afternoon', 'Evening', 'Night'];
        foreach ($all_periods as $period) {
            if (!isset($time_patterns[$period])) {
                $time_patterns[$period] = [
                    'avg_mood' => 0,
                    'entry_count' => 0
                ];
            }
        }

        return $time_patterns;
    }

    private function analyzeTagCorrelations($start_date, $end_date) {
        $query = "SELECT 
                    t.name as tag_name,
                    AVG(me.mood_level) as avg_mood,
                    COUNT(*) as entry_count,
                    GROUP_CONCAT(DISTINCT me.notes) as notes
                  FROM mood_entries me
                  JOIN mood_entry_tags met ON me.id = met.mood_entry_id
                  JOIN mood_tags t ON met.tag_id = t.id
                  WHERE me.date BETWEEN ? AND ?
                  GROUP BY t.name
                  ORDER BY avg_mood DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $correlations = [];
        while ($row = $result->fetch_assoc()) {
            $correlations[$row['tag_name']] = [
                'avg_mood' => round($row['avg_mood'], 1),
                'entry_count' => $row['entry_count'],
                'notes' => explode(',', $row['notes']),
                'impact' => $this->calculateTagImpact($row['avg_mood'])
            ];
        }

        return $correlations;
    }

    private function calculateTagImpact($avg_mood) {
        if ($avg_mood >= 4.5) return 'very_positive';
        if ($avg_mood >= 3.5) return 'positive';
        if ($avg_mood >= 2.5) return 'neutral';
        if ($avg_mood >= 1.5) return 'negative';
        return 'very_negative';
    }

    private function analyzeMoodConsistency($start_date, $end_date) {
        $query = "SELECT 
                    mood_level,
                    COUNT(*) as count,
                    DATE(date) as entry_date
                  FROM mood_entries
                  WHERE date BETWEEN ? AND ?
                  GROUP BY DATE(date), mood_level
                  ORDER BY entry_date";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $daily_moods = [];
        while ($row = $result->fetch_assoc()) {
            if (!isset($daily_moods[$row['entry_date']])) {
                $daily_moods[$row['entry_date']] = [];
            }
            $daily_moods[$row['entry_date']][$row['mood_level']] = $row['count'];
        }

        // Calculate consistency metrics
        $consistency = [
            'level' => 'stable',
            'description' => '',
            'metrics' => [
                'mood_swings' => 0,
                'stable_days' => 0,
                'total_days' => count($daily_moods)
            ]
        ];

        if (empty($daily_moods)) {
            return $consistency;
        }

        $previous_mood = null;
        foreach ($daily_moods as $date => $moods) {
            // Get the most frequent mood for the day
            arsort($moods);
            $current_mood = key($moods);

            if ($previous_mood !== null) {
                // Count mood swings (changes of 2 or more levels)
                if (abs($current_mood - $previous_mood) >= 2) {
                    $consistency['metrics']['mood_swings']++;
                }
            }

            // Count stable days (same mood level throughout the day)
            if (count($moods) === 1) {
                $consistency['metrics']['stable_days']++;
            }

            $previous_mood = $current_mood;
        }

        // Determine consistency level
        $swing_percentage = ($consistency['metrics']['mood_swings'] / $consistency['metrics']['total_days']) * 100;
        $stability_percentage = ($consistency['metrics']['stable_days'] / $consistency['metrics']['total_days']) * 100;

        if ($swing_percentage >= 50) {
            $consistency['level'] = 'volatile';
            $consistency['description'] = "Your mood shows significant fluctuations, with frequent changes in emotional state.";
        } elseif ($swing_percentage >= 30) {
            $consistency['level'] = 'moderate';
            $consistency['description'] = "Your mood shows moderate fluctuations, with some changes in emotional state.";
        } elseif ($stability_percentage >= 70) {
            $consistency['level'] = 'very_stable';
            $consistency['description'] = "Your mood is very stable, showing consistent emotional patterns.";
        } else {
            $consistency['level'] = 'stable';
            $consistency['description'] = "Your mood is generally stable, with occasional changes in emotional state.";
        }

        return $consistency;
    }

    private function identifyImprovementAreas($start_date, $end_date) {
        $query = "SELECT 
                    t.name as tag_name,
                    AVG(me.mood_level) as avg_mood,
                    COUNT(*) as entry_count
                  FROM mood_entries me
                  JOIN mood_entry_tags met ON me.id = met.mood_entry_id
                  JOIN mood_tags t ON met.tag_id = t.id
                  WHERE me.date BETWEEN ? AND ?
                  GROUP BY t.name
                  HAVING avg_mood < 3
                  ORDER BY avg_mood ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $improvement_areas = [];
        while ($row = $result->fetch_assoc()) {
            $improvement_areas[] = $row['tag_name'];
        }

        return $improvement_areas;
    }

    private function getMoodLabel($mood_level) {
        if ($mood_level >= 4.5) return 'Very Happy';
        if ($mood_level >= 3.5) return 'Happy';
        if ($mood_level >= 2.5) return 'Neutral';
        if ($mood_level >= 1.5) return 'Down';
        return 'Very Down';
    }

    private function calculateMoodTrend($mood_level) {
        if ($mood_level >= 4.5) return 'excellent';
        if ($mood_level >= 3.5) return 'positive';
        if ($mood_level >= 2.5) return 'stable';
        if ($mood_level >= 1.5) return 'challenging';
        return 'difficult';
    }
}
?> 