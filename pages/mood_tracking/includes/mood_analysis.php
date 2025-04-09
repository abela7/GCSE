<?php

class MoodAnalyzer {
    private $conn;
    private $mood_scale = [
        1 => ['emoji' => '😢', 'label' => 'Very Bad', 'color' => '#dc3545'],
        2 => ['emoji' => '😕', 'label' => 'Bad', 'color' => '#fd7e14'],
        3 => ['emoji' => '😐', 'label' => 'Neutral', 'color' => '#ffc107'],
        4 => ['emoji' => '🙂', 'label' => 'Good', 'color' => '#28a745'],
        5 => ['emoji' => '😊', 'label' => 'Very Good', 'color' => '#17a2b8']
    ];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function analyzeMoodPatterns($start_date, $end_date) {
        $analysis = [
            'daily' => $this->analyzeDailyMood($start_date, $end_date),
            'weekly' => $this->analyzeWeeklyMood($start_date, $end_date),
            'monthly' => $this->analyzeMonthlyMood($start_date, $end_date),
            'patterns' => $this->identifyMoodPatterns($start_date, $end_date),
            'correlations' => $this->findMoodCorrelations($start_date, $end_date),
            'insights' => []
        ];

        // Generate human-readable insights
        $analysis['insights'] = $this->generateInsights($analysis);
        
        return $analysis;
    }

    private function analyzeDailyMood($start_date, $end_date) {
        $query = "SELECT 
                    DATE(date) as day,
                    AVG(mood_level) as avg_mood,
                    COUNT(*) as entry_count,
                    GROUP_CONCAT(DISTINCT notes) as notes,
                    GROUP_CONCAT(DISTINCT tag_id) as tags
                  FROM mood_entries me
                  LEFT JOIN mood_entry_tags met ON me.id = met.mood_entry_id
                  WHERE date BETWEEN ? AND ?
                  GROUP BY DATE(date)
                  ORDER BY day DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $daily_data = [];
        while ($row = $result->fetch_assoc()) {
            $daily_data[$row['day']] = [
                'avg_mood' => round($row['avg_mood'], 1),
                'entry_count' => $row['entry_count'],
                'notes' => explode(',', $row['notes']),
                'tags' => explode(',', $row['tags']),
                'mood_label' => $this->getMoodLabel($row['avg_mood']),
                'trend' => $this->calculateMoodTrend($row['avg_mood'])
            ];
        }

        return $daily_data;
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

    private function identifyMoodPatterns($start_date, $end_date) {
        $patterns = [
            'time_of_day' => $this->analyzeTimeOfDayPatterns($start_date, $end_date),
            'tag_correlations' => $this->analyzeTagCorrelations($start_date, $end_date),
            'mood_consistency' => $this->analyzeMoodConsistency($start_date, $end_date),
            'improvement_areas' => $this->identifyImprovementAreas($start_date, $end_date)
        ];

        return $patterns;
    }

    private function findMoodCorrelations($start_date, $end_date) {
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

    private function generateInsights($analysis) {
        $insights = [];

        // Daily insights
        if (!empty($analysis['daily'])) {
            $latest_day = array_key_first($analysis['daily']);
            $daily_data = $analysis['daily'][$latest_day];
            
            $insights['daily'] = [
                'mood' => $daily_data['mood_label'],
                'description' => $this->generateDailyDescription($daily_data),
                'suggestions' => $this->generateDailySuggestions($daily_data)
            ];
        }

        // Weekly insights
        if (!empty($analysis['weekly'])) {
            $latest_week = array_key_first($analysis['weekly']);
            $weekly_data = $analysis['weekly'][$latest_week];
            
            $insights['weekly'] = [
                'mood' => $weekly_data['mood_label'],
                'description' => $this->generateWeeklyDescription($weekly_data),
                'suggestions' => $this->generateWeeklySuggestions($weekly_data)
            ];
        }

        // Monthly insights
        if (!empty($analysis['monthly'])) {
            $latest_month = array_key_first($analysis['monthly']);
            $monthly_data = $analysis['monthly'][$latest_month];
            
            $insights['monthly'] = [
                'mood' => $monthly_data['mood_label'],
                'description' => $this->generateMonthlyDescription($monthly_data),
                'suggestions' => $this->generateMonthlySuggestions($monthly_data)
            ];
        }

        // Pattern insights
        $insights['patterns'] = $this->generatePatternInsights($analysis['patterns']);

        return $insights;
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

    private function generateDailyDescription($data) {
        $mood = $data['mood_label'];
        $trend = $data['trend'];
        
        $descriptions = [
            'Very Happy' => [
                'excellent' => "You're having an amazing day! Your mood is consistently high, which suggests you're feeling great about your current situation.",
                'positive' => "You're having a really good day! Your positive mood indicates you're feeling content and satisfied.",
                'stable' => "You're having a good day overall, with your mood staying in a positive range.",
                'challenging' => "Despite some challenges, you're maintaining a positive outlook today.",
                'difficult' => "You're showing remarkable resilience by staying positive despite difficulties."
            ],
            'Happy' => [
                'excellent' => "You're having a great day! Your mood is consistently good, showing you're in a positive state of mind.",
                'positive' => "You're having a good day! Your mood is stable and positive, indicating you're feeling content.",
                'stable' => "You're having a balanced day with your mood staying in a comfortable range.",
                'challenging' => "You're managing to stay positive despite some challenges today.",
                'difficult' => "You're showing strength by maintaining a positive attitude through difficulties."
            ],
            'Neutral' => [
                'excellent' => "You're having a stable day with your mood in a balanced state.",
                'positive' => "You're having a calm day with your mood staying in a neutral range.",
                'stable' => "You're having a typical day with your mood remaining steady.",
                'challenging' => "You're having a challenging day, but managing to stay balanced.",
                'difficult' => "You're having a tough day, but showing resilience by maintaining stability."
            ],
            'Down' => [
                'excellent' => "You're having a challenging day, but shown signs of improvement.",
                'positive' => "You're having a difficult day, but maintaining hope for better times.",
                'stable' => "You're having a tough day, but managing to stay composed.",
                'challenging' => "You're having a particularly challenging day with your mood being affected.",
                'difficult' => "You're having a very difficult day with your mood being significantly impacted."
            ],
            'Very Down' => [
                'excellent' => "You're having an extremely challenging day, but shown incredible strength.",
                'positive' => "You're having a very difficult day, but maintaining hope for improvement.",
                'stable' => "You're having a tough day, but shown resilience in the face of challenges.",
                'challenging' => "You're having an extremely difficult day with your mood being severely affected.",
                'difficult' => "You're having an exceptionally challenging day with your mood being deeply impacted."
            ]
        ];

        return $descriptions[$mood][$trend];
    }

    private function generateDailySuggestions($data) {
        $mood = $data['mood_label'];
        $trend = $data['trend'];
        
        $suggestions = [
            'Very Happy' => [
                'excellent' => "Consider documenting what's making you feel so good - these insights can help during tougher times.",
                'positive' => "Take a moment to appreciate and celebrate your positive mood.",
                'stable' => "Use this positive energy to tackle any pending tasks or goals.",
                'challenging' => "Reflect on what's helping you maintain positivity despite challenges.",
                'difficult' => "Your resilience is impressive - consider sharing your coping strategies with others."
            ],
            'Happy' => [
                'excellent' => "This is a great time to engage in activities you enjoy and build positive momentum.",
                'positive' => "Consider using this positive energy to connect with others or pursue personal goals.",
                'stable' => "Take advantage of your balanced mood to reflect on your well-being strategies.",
                'challenging' => "Your ability to stay positive is commendable - consider journaling about your coping mechanisms.",
                'difficult' => "Your strength in maintaining positivity is admirable - consider sharing your experience with others."
            ],
            'Neutral' => [
                'excellent' => "Use this stable period to reflect on your emotional patterns and set new goals.",
                'positive' => "Consider engaging in activities that might boost your mood further.",
                'stable' => "This is a good time to practice mindfulness and self-reflection.",
                'challenging' => "Consider reaching out to supportive friends or engaging in comforting activities.",
                'difficult' => "Your ability to maintain balance is impressive - consider what's helping you stay centered."
            ],
            'Down' => [
                'excellent' => "Consider engaging in self-care activities that usually help improve your mood.",
                'positive' => "Reach out to supportive friends or family members for connection.",
                'stable' => "Practice self-compassion and consider what might help lift your mood.",
                'challenging' => "Your strength in facing challenges is admirable - consider seeking additional support.",
                'difficult' => "This might be a good time to practice self-care and reach out for support."
            ],
            'Very Down' => [
                'excellent' => "Your resilience is remarkable - consider seeking additional support to help maintain it.",
                'positive' => "Reach out to trusted friends, family, or professionals for support.",
                'stable' => "Practice self-compassion and consider what support systems might help.",
                'challenging' => "Consider reaching out for professional support to help navigate this difficult time.",
                'difficult' => "Your strength is evident - please consider seeking additional support to help you through this."
            ]
        ];

        return $suggestions[$mood][$trend];
    }

    private function generateWeeklyDescription($data) {
        $mood = $data['mood_label'];
        $trend = $data['trend'];
        
        $descriptions = [
            'Very Happy' => [
                'excellent' => "You've had an amazing week! Your consistently high mood suggests you're thriving in your current situation.",
                'positive' => "You've had a really good week! Your positive mood indicates you're feeling content and satisfied.",
                'stable' => "You've had a good week overall, with your mood staying in a positive range.",
                'challenging' => "Despite some challenges, you've maintained a positive outlook this week.",
                'difficult' => "You've shown remarkable resilience by staying positive despite difficulties."
            ],
            'Happy' => [
                'excellent' => "You've had a great week! Your mood has been consistently good, showing you're in a positive state of mind.",
                'positive' => "You've had a good week! Your mood has been stable and positive, indicating you're feeling content.",
                'stable' => "You've had a balanced week with your mood staying in a comfortable range.",
                'challenging' => "You've managed to stay positive despite some challenges this week.",
                'difficult' => "You've shown strength by maintaining a positive attitude through difficulties."
            ],
            'Neutral' => [
                'excellent' => "You've had a stable week with your mood in a balanced state.",
                'positive' => "You've had a calm week with your mood staying in a neutral range.",
                'stable' => "You've had a typical week with your mood remaining steady.",
                'challenging' => "You've had a challenging week, but managed to stay balanced.",
                'difficult' => "You've had a tough week, but shown resilience by maintaining stability."
            ],
            'Down' => [
                'excellent' => "You've had a challenging week, but shown signs of improvement.",
                'positive' => "You've had a difficult week, but maintained hope for better times.",
                'stable' => "You've had a tough week, but managed to stay composed.",
                'challenging' => "You've had a particularly challenging week with your mood being affected.",
                'difficult' => "You've had a very difficult week with your mood being significantly impacted."
            ],
            'Very Down' => [
                'excellent' => "You've had an extremely challenging week, but shown incredible strength.",
                'positive' => "You've had a very difficult week, but maintained hope for improvement.",
                'stable' => "You've had a tough week, but shown resilience in the face of challenges.",
                'challenging' => "You've had an extremely difficult week with your mood being severely affected.",
                'difficult' => "You've had an exceptionally challenging week with your mood being deeply impacted."
            ]
        ];

        return $descriptions[$mood][$trend];
    }

    private function generateWeeklySuggestions($data) {
        $mood = $data['mood_label'];
        $trend = $data['trend'];
        
        $suggestions = [
            'Very Happy' => [
                'excellent' => "Consider documenting what's been making you feel so good - these insights can help during tougher times.",
                'positive' => "Take time to appreciate and celebrate your positive week.",
                'stable' => "Use this positive momentum to set new goals or tackle challenging projects.",
                'challenging' => "Reflect on what's helped you maintain positivity despite challenges.",
                'difficult' => "Your resilience is impressive - consider sharing your coping strategies with others."
            ],
            'Happy' => [
                'excellent' => "This is a great time to build on your positive momentum and set new goals.",
                'positive' => "Consider using this positive energy to strengthen relationships or pursue personal growth.",
                'stable' => "Take advantage of your balanced mood to reflect on your well-being strategies.",
                'challenging' => "Your ability to stay positive is commendable - consider journaling about your coping mechanisms.",
                'difficult' => "Your strength in maintaining positivity is admirable - consider sharing your experience with others."
            ],
            'Neutral' => [
                'excellent' => "Use this stable period to reflect on your emotional patterns and set new goals.",
                'positive' => "Consider engaging in activities that might boost your mood further.",
                'stable' => "This is a good time to practice mindfulness and self-reflection.",
                'challenging' => "Consider reaching out to supportive friends or engaging in comforting activities.",
                'difficult' => "Your ability to maintain balance is impressive - consider what's helping you stay centered."
            ],
            'Down' => [
                'excellent' => "Consider engaging in self-care activities that usually help improve your mood.",
                'positive' => "Reach out to supportive friends or family members for connection.",
                'stable' => "Practice self-compassion and consider what might help lift your mood.",
                'challenging' => "Your strength in facing challenges is admirable - consider seeking additional support.",
                'difficult' => "This might be a good time to practice self-care and reach out for support."
            ],
            'Very Down' => [
                'excellent' => "Your resilience is remarkable - consider seeking additional support to help maintain it.",
                'positive' => "Reach out to trusted friends, family, or professionals for support.",
                'stable' => "Practice self-compassion and consider what support systems might help.",
                'challenging' => "Consider reaching out for professional support to help navigate this difficult time.",
                'difficult' => "Your strength is evident - please consider seeking additional support to help you through this."
            ]
        ];

        return $suggestions[$mood][$trend];
    }

    private function generateMonthlyDescription($data) {
        $mood = $data['mood_label'];
        $trend = $data['trend'];
        
        $descriptions = [
            'Very Happy' => [
                'excellent' => "You've had an amazing month! Your consistently high mood suggests you're thriving in your current situation.",
                'positive' => "You've had a really good month! Your positive mood indicates you're feeling content and satisfied.",
                'stable' => "You've had a good month overall, with your mood staying in a positive range.",
                'challenging' => "Despite some challenges, you've maintained a positive outlook this month.",
                'difficult' => "You've shown remarkable resilience by staying positive despite difficulties."
            ],
            'Happy' => [
                'excellent' => "You've had a great month! Your mood has been consistently good, showing you're in a positive state of mind.",
                'positive' => "You've had a good month! Your mood has been stable and positive, indicating you're feeling content.",
                'stable' => "You've had a balanced month with your mood staying in a comfortable range.",
                'challenging' => "You've managed to stay positive despite some challenges this month.",
                'difficult' => "You've shown strength by maintaining a positive attitude through difficulties."
            ],
            'Neutral' => [
                'excellent' => "You've had a stable month with your mood in a balanced state.",
                'positive' => "You've had a calm month with your mood staying in a neutral range.",
                'stable' => "You've had a typical month with your mood remaining steady.",
                'challenging' => "You've had a challenging month, but managed to stay balanced.",
                'difficult' => "You've had a tough month, but shown resilience by maintaining stability."
            ],
            'Down' => [
                'excellent' => "You've had a challenging month, but shown signs of improvement.",
                'positive' => "You've had a difficult month, but maintained hope for better times.",
                'stable' => "You've had a tough month, but managed to stay composed.",
                'challenging' => "You've had a particularly challenging month with your mood being affected.",
                'difficult' => "You've had a very difficult month with your mood being significantly impacted."
            ],
            'Very Down' => [
                'excellent' => "You've had an extremely challenging month, but shown incredible strength.",
                'positive' => "You've had a very difficult month, but maintained hope for improvement.",
                'stable' => "You've had a tough month, but shown resilience in the face of challenges.",
                'challenging' => "You've had an extremely difficult month with your mood being severely affected.",
                'difficult' => "You've had an exceptionally challenging month with your mood being deeply impacted."
            ]
        ];

        return $descriptions[$mood][$trend];
    }

    private function generateMonthlySuggestions($data) {
        $mood = $data['mood_label'];
        $trend = $data['trend'];
        
        $suggestions = [
            'Very Happy' => [
                'excellent' => "Consider documenting what's been making you feel so good - these insights can help during tougher times.",
                'positive' => "Take time to appreciate and celebrate your positive month.",
                'stable' => "Use this positive momentum to set new goals or tackle challenging projects.",
                'challenging' => "Reflect on what's helped you maintain positivity despite challenges.",
                'difficult' => "Your resilience is impressive - consider sharing your coping strategies with others."
            ],
            'Happy' => [
                'excellent' => "This is a great time to build on your positive momentum and set new goals.",
                'positive' => "Consider using this positive energy to strengthen relationships or pursue personal growth.",
                'stable' => "Take advantage of your balanced mood to reflect on your well-being strategies.",
                'challenging' => "Your ability to stay positive is commendable - consider journaling about your coping mechanisms.",
                'difficult' => "Your strength in maintaining positivity is admirable - consider sharing your experience with others."
            ],
            'Neutral' => [
                'excellent' => "Use this stable period to reflect on your emotional patterns and set new goals.",
                'positive' => "Consider engaging in activities that might boost your mood further.",
                'stable' => "This is a good time to practice mindfulness and self-reflection.",
                'challenging' => "Consider reaching out to supportive friends or engaging in comforting activities.",
                'difficult' => "Your ability to maintain balance is impressive - consider what's helping you stay centered."
            ],
            'Down' => [
                'excellent' => "Consider engaging in self-care activities that usually help improve your mood.",
                'positive' => "Reach out to supportive friends or family members for connection.",
                'stable' => "Practice self-compassion and consider what might help lift your mood.",
                'challenging' => "Your strength in facing challenges is admirable - consider seeking additional support.",
                'difficult' => "This might be a good time to practice self-care and reach out for support."
            ],
            'Very Down' => [
                'excellent' => "Your resilience is remarkable - consider seeking additional support to help maintain it.",
                'positive' => "Reach out to trusted friends, family, or professionals for support.",
                'stable' => "Practice self-compassion and consider what support systems might help.",
                'challenging' => "Consider reaching out for professional support to help navigate this difficult time.",
                'difficult' => "Your strength is evident - please consider seeking additional support to help you through this."
            ]
        ];

        return $suggestions[$mood][$trend];
    }

    private function generatePatternInsights($patterns) {
        $insights = [];

        // Time of day patterns
        if (!empty($patterns['time_of_day'])) {
            $best_time = array_search(max($patterns['time_of_day']), $patterns['time_of_day']);
            $worst_time = array_search(min($patterns['time_of_day']), $patterns['time_of_day']);
            
            $insights['time_patterns'] = [
                'best_time' => $best_time,
                'worst_time' => $worst_time,
                'description' => "You tend to feel best during {$best_time} and find {$worst_time} more challenging."
            ];
        }

        // Tag correlations
        if (!empty($patterns['tag_correlations'])) {
            $positive_tags = array_filter($patterns['tag_correlations'], function($tag) {
                return $tag['impact'] === 'very_positive' || $tag['impact'] === 'positive';
            });
            
            $negative_tags = array_filter($patterns['tag_correlations'], function($tag) {
                return $tag['impact'] === 'very_negative' || $tag['impact'] === 'negative';
            });

            if (!empty($positive_tags)) {
                $insights['positive_activities'] = [
                    'tags' => array_keys($positive_tags),
                    'description' => "These activities consistently improve your mood: " . implode(', ', array_keys($positive_tags))
                ];
            }

            if (!empty($negative_tags)) {
                $insights['negative_activities'] = [
                    'tags' => array_keys($negative_tags),
                    'description' => "These activities tend to lower your mood: " . implode(', ', array_keys($negative_tags))
                ];
            }
        }

        // Mood consistency
        if (!empty($patterns['mood_consistency'])) {
            $consistency = $patterns['mood_consistency'];
            $insights['consistency'] = [
                'level' => $consistency['level'],
                'description' => $consistency['description']
            ];
        }

        // Improvement areas
        if (!empty($patterns['improvement_areas'])) {
            $insights['improvement_areas'] = [
                'areas' => $patterns['improvement_areas'],
                'description' => "Consider focusing on these areas to improve your overall well-being: " . 
                               implode(', ', $patterns['improvement_areas'])
            ];
        }

        return $insights;
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
}
?> 