<?php
session_start();
require_once 'config/db_connect.php';
require_once 'includes/functions.php';

// Page title
$page_title = "Status Dashboard";

// Get current date
$today = date('Y-m-d');

// First, check which tables exist in the database
$existing_tables = array();
$table_check = "SHOW TABLES";
$tables_result = $conn->query($table_check);
while ($table = $tables_result->fetch_array(MYSQLI_NUM)) {
    $existing_tables[] = $table[0];
}

// Get subject progress data from assignments table
$subjects = array(
    'Mathematics' => ['total' => 0, 'completed' => 0, 'confidence' => 0],
    'English' => ['total' => 0, 'completed' => 0, 'confidence' => 0],
    'Science' => ['total' => 0, 'completed' => 0, 'confidence' => 0]
);

if (in_array('assignments', $existing_tables)) {
    try {
        // Get math assignments
        $math_query = "
            SELECT 
                COUNT(*) as total_topics,
                SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed_topics,
                ROUND(AVG(confidence_level)) as avg_confidence
            FROM assignments
            WHERE subject = 'Mathematics'
        ";
        $math_result = $conn->query($math_query);
        if ($math_result && $math_result->num_rows > 0) {
            $math_data = $math_result->fetch_assoc();
            $subjects['Mathematics']['total'] = $math_data['total_topics'] ?: 0;
            $subjects['Mathematics']['completed'] = $math_data['completed_topics'] ?: 0;
            $subjects['Mathematics']['confidence'] = $math_data['avg_confidence'] ?: 0;
        }
        
        // Get English assignments
        $english_query = "
            SELECT 
                COUNT(*) as total_topics,
                SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed_topics,
                ROUND(AVG(confidence_level)) as avg_confidence
            FROM assignments
            WHERE subject = 'English'
        ";
        $english_result = $conn->query($english_query);
        if ($english_result && $english_result->num_rows > 0) {
            $english_data = $english_result->fetch_assoc();
            $subjects['English']['total'] = $english_data['total_topics'] ?: 0;
            $subjects['English']['completed'] = $english_data['completed_topics'] ?: 0;
            $subjects['English']['confidence'] = $english_data['avg_confidence'] ?: 0;
        }
        
        // Get Science assignments
        $science_query = "
            SELECT 
                COUNT(*) as total_topics,
                SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed_topics,
                ROUND(AVG(confidence_level)) as avg_confidence
            FROM assignments
            WHERE subject = 'Science'
        ";
        $science_result = $conn->query($science_query);
        if ($science_result && $science_result->num_rows > 0) {
            $science_data = $science_result->fetch_assoc();
            $subjects['Science']['total'] = $science_data['total_topics'] ?: 0;
            $subjects['Science']['completed'] = $science_data['completed_topics'] ?: 0;
            $subjects['Science']['confidence'] = $science_data['avg_confidence'] ?: 0;
        }
    } catch (Exception $e) {
        error_log("Error fetching subject data: " . $e->getMessage());
    }
}

// Assign math data
$math_total = $subjects['Mathematics']['total'];
$math_completed = $subjects['Mathematics']['completed'];
$math_confidence = $subjects['Mathematics']['confidence'];
$math_progress = $math_total > 0 ? round(($math_completed / $math_total) * 100) : 0;

// Assign english data
$english_total = $subjects['English']['total'];
$english_completed = $subjects['English']['completed'];
$english_confidence = $subjects['English']['confidence'];
$english_progress = $english_total > 0 ? round(($english_completed / $english_total) * 100) : 0;

// Calculate overall percentage
$total_topics = $math_total + $english_total;
$completed_topics = $math_completed + $english_completed;
$overall_percentage = $total_topics > 0 ? round(($completed_topics / $total_topics) * 100) : 0;

// TASK COMPLETION - Check if tasks table exists
$tasks_data = array(
    'total_tasks' => 0,
    'completed_tasks' => 0,
    'overdue_tasks' => 0,
    'today_tasks' => 0,
    'upcoming_tasks' => 0
);

if (in_array('tasks', $existing_tables)) {
    $tasks_query = "
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN status != 'completed' AND due_date < CURRENT_DATE THEN 1 ELSE 0 END) as overdue_tasks,
            SUM(CASE WHEN status != 'completed' AND due_date = CURRENT_DATE THEN 1 ELSE 0 END) as today_tasks,
            SUM(CASE WHEN status != 'completed' AND due_date > CURRENT_DATE THEN 1 ELSE 0 END) as upcoming_tasks
        FROM tasks
    ";
    try {
        $tasks_result = $conn->query($tasks_query);
        if ($tasks_result) {
            $tasks_data = $tasks_result->fetch_assoc();
        }
    } catch (Exception $e) {
        // Handle query error
        error_log("Error querying tasks table: " . $e->getMessage());
    }
}

// HABIT COMPLETION - Check if habits table exists
$habits_data = array(
    'total_habits' => 0,
    'completed_today' => 0,
    'pending_today' => 0
);

if (in_array('habits', $existing_tables) && in_array('habit_tracking', $existing_tables)) {
    $habits_query = "
        SELECT 
            COUNT(h.id) as total_habits,
            SUM(CASE WHEN EXISTS (
                SELECT 1 FROM habit_tracking ht 
                WHERE ht.habit_id = h.id AND DATE(ht.tracking_date) = CURRENT_DATE AND ht.status = 'completed'
            ) THEN 1 ELSE 0 END) as completed_today,
            SUM(CASE WHEN NOT EXISTS (
                SELECT 1 FROM habit_tracking ht 
                WHERE ht.habit_id = h.id AND DATE(ht.tracking_date) = CURRENT_DATE
            ) THEN 1 ELSE 0 END) as pending_today
        FROM habits h
        WHERE h.is_active = 1
    ";
    try {
        $habits_result = $conn->query($habits_query);
        if ($habits_result) {
            $habits_data = $habits_result->fetch_assoc();
        }
    } catch (Exception $e) {
        // Handle query error
        error_log("Error querying habits table: " . $e->getMessage());
    }
}

// EXAM COUNTDOWN - Check if exams table exists
$exams_result = null;
if (in_array('exams', $existing_tables)) {
    $exams_query = "
        SELECT 
            e.*, 
            DATEDIFF(e.exam_date, CURRENT_DATE) as days_remaining
        FROM exams e
        WHERE e.exam_date >= CURRENT_DATE
        ORDER BY e.exam_date ASC
        LIMIT 5
    ";
    try {
        $exams_result = $conn->query($exams_query);
    } catch (Exception $e) {
        // Handle query error
        error_log("Error querying exams table: " . $e->getMessage());
    }
}

// STUDY SESSIONS - Check if study_sessions table exists
$study_data = array(
    'total_duration' => 0,
    'avg_productivity' => 0,
    'total_sessions' => 0,
    'last_session_date' => null,
    'days_since_last' => 0
);

if (in_array('study_sessions', $existing_tables)) {
    $study_query = "
        SELECT 
            SUM(duration) as total_duration,
            AVG(productivity_rating) as avg_productivity,
            COUNT(*) as total_sessions,
            DATE_FORMAT(MAX(session_date), '%Y-%m-%d') as last_session_date,
            DATEDIFF(CURRENT_DATE, MAX(session_date)) as days_since_last
        FROM study_sessions
    ";
    try {
        $study_result = $conn->query($study_query);
        if ($study_result) {
            $study_data = $study_result->fetch_assoc();
        }
    } catch (Exception $e) {
        // Handle query error
        error_log("Error querying study_sessions table: " . $e->getMessage());
    }
}

$total_study_hours = $study_data['total_duration'] ? round($study_data['total_duration'] / 60, 1) : 0;
$avg_productivity = $study_data['avg_productivity'] ? round($study_data['avg_productivity'], 1) : 0;
$days_since_study = $study_data['days_since_last'] ?: 0;

// RECENT ACTIVITY - Using available tables
$activity_result = null;
$activity_query_parts = array();

if (in_array('tasks', $existing_tables)) {
    $activity_query_parts[] = "
        (SELECT 
            'task' as type,
            id,
            CONCAT('Task completed: ', title) as description,
            completion_date as activity_date
         FROM tasks
         WHERE status = 'completed' AND completion_date IS NOT NULL
         ORDER BY completion_date DESC
         LIMIT 5)
    ";
}

if (in_array('habit_tracking', $existing_tables) && in_array('habits', $existing_tables)) {
    $activity_query_parts[] = "
        (SELECT 
            'habit' as type,
            habit_id as id,
            CONCAT('Habit tracked: ', (SELECT name FROM habits WHERE id = habit_id)) as description,
            tracking_date as activity_date
         FROM habit_tracking
         WHERE status = 'completed'
         ORDER BY tracking_date DESC
         LIMIT 5)
    ";
}

if (in_array('study_sessions', $existing_tables)) {
    $activity_query_parts[] = "
        (SELECT 
            'session' as type,
            id,
            CONCAT('Study session: ', subject) as description,
            session_date as activity_date
         FROM study_sessions
         ORDER BY session_date DESC
         LIMIT 5)
    ";
}

if (!empty($activity_query_parts)) {
    $activity_query = implode(" UNION ", $activity_query_parts) . " ORDER BY activity_date DESC LIMIT 10";
    try {
        $activity_result = $conn->query($activity_query);
    } catch (Exception $e) {
        // Handle query error
        error_log("Error querying activity data: " . $e->getMessage());
    }
}

// ASSIGNMENTS PROGRESS - Check if assignments table exists
$assignments_data = array(
    'total' => 0,
    'completed' => 0,
    'in_progress' => 0,
    'not_started' => 0,
    'avg_progress' => 0
);

if (in_array('assignments', $existing_tables)) {
    $assignments_query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN progress > 0 AND progress < 100 THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN progress = 0 THEN 1 ELSE 0 END) as not_started,
            AVG(progress) as avg_progress
        FROM assignments
    ";
    try {
        $assignments_result = $conn->query($assignments_query);
        if ($assignments_result && $assignments_result->num_rows > 0) {
            $assignments_data = $assignments_result->fetch_assoc();
            
            // Ensure we have integer values
            $assignments_data['total'] = (int)$assignments_data['total'];
            $assignments_data['completed'] = (int)$assignments_data['completed'];
            $assignments_data['in_progress'] = (int)$assignments_data['in_progress'];
            $assignments_data['not_started'] = (int)$assignments_data['not_started'];
            $assignments_data['avg_progress'] = round((float)$assignments_data['avg_progress'], 1);
        }
    } catch (Exception $e) {
        // Handle query error
        error_log("Error querying assignments table: " . $e->getMessage());
    }
}

// Create a query to get section breakdown from actual section tables
$section_data = array();

// First check for math sections
if (in_array('math_sections', $existing_tables) && in_array('math_subsections', $existing_tables) && in_array('math_topics', $existing_tables) && in_array('topic_progress', $existing_tables)) {
    $math_sections_query = "
        SELECT 
            'Mathematics' as subject,
            ms.name as section,
            COUNT(mt.id) as total_topics,
            SUM(CASE WHEN tp.status = 'completed' THEN 1 ELSE 0 END) as completed_topics,
            ROUND(SUM(CASE WHEN tp.status = 'completed' THEN 1 ELSE 0 END) * 100 / COUNT(mt.id)) as progress
        FROM math_sections ms
        JOIN math_subsections mss ON ms.id = mss.section_id
        JOIN math_topics mt ON mss.id = mt.subsection_id
        LEFT JOIN topic_progress tp ON mt.id = tp.topic_id
        GROUP BY ms.id, ms.name
        ORDER BY ms.section_number
        LIMIT 5
    ";
    
    try {
        $math_sections_result = $conn->query($math_sections_query);
        if ($math_sections_result && $math_sections_result->num_rows > 0) {
            while ($section = $math_sections_result->fetch_assoc()) {
                $section_data[] = array(
                    'subject' => $section['subject'],
                    'section' => $section['section'],
                    'total_topics' => (int)$section['total_topics'],
                    'completed_topics' => (int)$section['completed_topics'],
                    'progress' => (int)$section['progress']
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching math section data: " . $e->getMessage());
    }
}

// Then check for English sections
if (in_array('eng_sections', $existing_tables) && in_array('eng_subsections', $existing_tables) && in_array('eng_topics', $existing_tables) && in_array('eng_topic_progress', $existing_tables)) {
    $eng_sections_query = "
        SELECT 
            'English' as subject,
            es.name as section,
            COUNT(et.id) as total_topics,
            SUM(CASE WHEN etp.status = 'completed' THEN 1 ELSE 0 END) as completed_topics,
            ROUND(SUM(CASE WHEN etp.status = 'completed' THEN 1 ELSE 0 END) * 100 / COUNT(et.id)) as progress
        FROM eng_sections es
        JOIN eng_subsections ess ON es.id = ess.section_id
        JOIN eng_topics et ON ess.id = et.subsection_id
        LEFT JOIN eng_topic_progress etp ON et.id = etp.topic_id
        GROUP BY es.id, es.name
        ORDER BY es.section_number
        LIMIT 5
    ";
    
    try {
        $eng_sections_result = $conn->query($eng_sections_query);
        if ($eng_sections_result && $eng_sections_result->num_rows > 0) {
            while ($section = $eng_sections_result->fetch_assoc()) {
                $section_data[] = array(
                    'subject' => $section['subject'],
                    'section' => $section['section'],
                    'total_topics' => (int)$section['total_topics'],
                    'completed_topics' => (int)$section['completed_topics'],
                    'progress' => (int)$section['progress']
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching English section data: " . $e->getMessage());
    }
}

// Fallback to the assignments table if no sections are found yet
if (empty($section_data) && in_array('assignments', $existing_tables)) {
    $sections_query = "
        SELECT 
            subject,
            topic as section,
            COUNT(*) as total_topics,
            SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed_topics,
            ROUND(SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) * 100 / COUNT(*)) as progress
        FROM assignments
        GROUP BY subject, topic
        ORDER BY subject, topic
        LIMIT 5
    ";
    
    try {
        $sections_result = $conn->query($sections_query);
        if ($sections_result && $sections_result->num_rows > 0) {
            while ($section = $sections_result->fetch_assoc()) {
                $section_data[] = array(
                    'subject' => $section['subject'],
                    'section' => $section['section'],
                    'total_topics' => (int)$section['total_topics'],
                    'completed_topics' => (int)$section['completed_topics'],
                    'progress' => (int)$section['progress']
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching section data: " . $e->getMessage());
    }
}

// If no sections found, use the temporary data
if (empty($section_data)) {
    $section_data = array(
        array('subject' => 'Mathematics', 'section' => 'Algebra', 'total_topics' => 8, 'completed_topics' => 6, 'progress' => 75),
        array('subject' => 'Mathematics', 'section' => 'Geometry', 'total_topics' => 7, 'completed_topics' => 2, 'progress' => 29),
        array('subject' => 'English', 'section' => 'Literature', 'total_topics' => 6, 'completed_topics' => 5, 'progress' => 83),
        array('subject' => 'English', 'section' => 'Language', 'total_topics' => 8, 'completed_topics' => 4, 'progress' => 50),
        array('subject' => 'Science', 'section' => 'Biology', 'total_topics' => 9, 'completed_topics' => 6, 'progress' => 67)
    );
}

// Get habit streak data - Fixed query to accurately count completions
$habit_streak_data = array();
if (in_array('habits', $existing_tables) && in_array('habit_completions', $existing_tables)) {
    $streak_query = "
        SELECT 
            h.id,
            h.name,
            COUNT(hc.id) as completion_count
        FROM habits h
        LEFT JOIN habit_completions hc ON h.id = hc.habit_id AND hc.status = 'completed'
        WHERE h.is_active = 1
        GROUP BY h.id, h.name
        ORDER BY completion_count DESC
        LIMIT 3
    ";
    
    try {
        $streak_result = $conn->query($streak_query);
        if ($streak_result && $streak_result->num_rows > 0) {
            while ($habit = $streak_result->fetch_assoc()) {
                $habit_streak_data[] = array(
                    'name' => $habit['name'],
                    'completion_count' => (int)$habit['completion_count']
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching habit streak data: " . $e->getMessage());
    }
}

// If no habit streak data, try alternative table
if (empty($habit_streak_data) && in_array('habits', $existing_tables) && in_array('habit_progress', $existing_tables)) {
    $streak_query_alt = "
        SELECT 
            h.id,
            h.name,
            COUNT(hp.id) as completion_count
        FROM habits h
        LEFT JOIN habit_progress hp ON h.id = hp.habit_id AND hp.status = 'completed'
        WHERE h.is_active = 1
        GROUP BY h.id, h.name
        ORDER BY completion_count DESC
        LIMIT 3
    ";
    
    try {
        $streak_result_alt = $conn->query($streak_query_alt);
        if ($streak_result_alt && $streak_result_alt->num_rows > 0) {
            while ($habit = $streak_result_alt->fetch_assoc()) {
                $habit_streak_data[] = array(
                    'name' => $habit['name'],
                    'completion_count' => (int)$habit['completion_count']
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching alternative habit streak data: " . $e->getMessage());
    }
}

// If still no habit streak data, use the habits table total_completions field
if (empty($habit_streak_data) && in_array('habits', $existing_tables)) {
    $streak_query_backup = "
        SELECT 
            id,
            name,
            total_completions as completion_count
        FROM habits
        WHERE is_active = 1
        ORDER BY total_completions DESC
        LIMIT 3
    ";
    
    try {
        $streak_result_backup = $conn->query($streak_query_backup);
        if ($streak_result_backup && $streak_result_backup->num_rows > 0) {
            while ($habit = $streak_result_backup->fetch_assoc()) {
                $habit_streak_data[] = array(
                    'name' => $habit['name'],
                    'completion_count' => (int)$habit['completion_count']
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching backup habit streak data: " . $e->getMessage());
    }
}

// If still no habit streak data, use temporary data
if (empty($habit_streak_data)) {
    $habit_streak_data = array(
        array('name' => 'Math Practice', 'completion_count' => 15),
        array('name' => 'Reading', 'completion_count' => 8),
        array('name' => 'Flashcards', 'completion_count' => 12)
    );
}

// Include headeractice data
include 'includes/header.php';
?> (in_array('practice_items', $existing_tables) && in_array('practice_days', $existing_tables)) {
    // Get recent practice items
<div class="container-fluid py-4">
    <div class="row mb-4">tem_title, pi.item_meaning, pi.item_example, pc.name as category_name,
        <div class="col">e_date, pd.day_number
            <h1 class="h3 mb-0 text-gray-800">Status Dashboard</h1>
            <p class="text-muted">Comprehensive overview of your GCSE preparation</p>
        </div>ractice_days pd ON pi.practice_day_id = pd.id
        <div class="col-auto">ate DESC, pi.id DESC
            <div class="btn-group">
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <button type="button" class="btn btn-primary" id="refreshDashboard">
                    <i class="fas fa-sync-alt"></i> Refresh> 0) {
                </button>= $practice_result->fetch_assoc()) {
            </div>nglish_practice_data[] = $item;
        </div>
    </div>
    } catch (Exception $e) {
    <!-- Key Metrics --> fetching English practice data: " . $e->getMessage());
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">ms', $existing_tables)) {
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Overall Progressractice_items) as favorite_items,
                            </div>INCT practice_date) FROM practice_days) as practice_days,
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $overall_percentage; ?>%te >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY))
                            </div>ek
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $overall_percentage; ?>%" 
                                     aria-valuenow="<?php echo $overall_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>>query($practice_stats_query);
                        </div>&& $stats_result->num_rows > 0) {
                        <div class="col-auto">t->fetch_assoc();
                            <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                        </div>ice_data['stats'] = $practice_stats;
                    </div>
                </div>tion $e) {
            </div>log("Error fetching English practice stats: " . $e->getMessage());
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Study Time
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_study_hours; ?> hours</div>
                            <div class="small text-muted mt-2">
                                <?php if ($days_since_study > 0): ?>
                                    Last study: <?php echo $days_since_study; ?> days ago
                                <?php else: ?>
                                    Studied today
                                <?php endif; ?>s.id
                            </div>NT_DATE
                        </div>SC
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>ult = $conn->query($next_exam_query);
            </div>exam_result && $next_exam_result->num_rows > 0) {
        </div>pcoming_exam = $next_exam_result->fetch_assoc();
        }
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">age());
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tasks Completed
                            </div>s,
                            <div class="h5 mb-0 font-weight-bold text-gray-800">ams,
                                <?php
                                    // Display task completion percentage
                                    $task_completion = $tasks_data['total_tasks'] > 0 
                                        ? round(($tasks_data['completed_tasks'] / $tasks_data['total_tasks']) * 100) 
                                        : 0;
                                    echo $task_completion . '%';
                                ?>
                            </div>->query($exam_stats_query);
                            <div class="progress progress-sm mt-2"> {
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $task_completion; ?>%" 
                                     aria-valuenow="<?php echo $task_completion; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>ing exam statistics: " . $e->getMessage());
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>and deadlines
                </div>array();
            </div>ss_assignments', $existing_tables)) {
        </div>ts_detail_query = "
        SELECT 
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Habits Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                    $habits_total = $habits_data['total_habits'] ?: 0;
                                    $habits_completed = $habits_data['completed_today'] ?: 0;
                                    echo $habits_completed . '/' . $habits_total; 
                                ?>l[] = $assignment;
                            </div>
                            <?php if ($habits_total > 0): ?>
                                <div class="progress progress-sm mt-2">
                                    <div class="progress-bar bg-warning" role="progressbar" 
                                         style="width: <?php echo ($habits_completed / $habits_total) * 100; ?>%" 
                                         aria-valuenow="<?php echo ($habits_completed / $habits_total) * 100; ?>" 
                                         aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>$existing_tables)) {
            </div>_query = "
        </div> 
    </div>  priority,
            COUNT(*) as count
    <!-- Subject Progress and Exam Countdown -->
    <div class="row mb-4">ompleted' AND due_date >= CURRENT_DATE
        <!-- Subject Progress -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Subject Progress</h6>
                    <div class="dropdown no-arrow">um_rows > 0) {
                        <a class="dropdown-toggle" href="#" role="button" id="subjectDropdown" 
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="subjectDropdown">
                            <div class="dropdown-header">View Options:</div>
                            <a class="dropdown-item" href="#">Math Detail</a>
                            <a class="dropdown-item" href="#">English Detail</a>
                            <a class="dropdown-item" href="#">Science Detail</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#">All Subjects</a>
                        </div>ue_date, t.due_time, 
                    </div>estimated_duration,
                </div> category_name,
                <div class="card-body">
                    <div class="row mb-4">
                        <!-- Math Progress -->
                        <div class="col-lg-4 mb-3">id = tc.id
                            <h6 class="font-weight-bold">Mathematics</h6>
                            <div class="progress mb-2" style="height: 25px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $math_progress; ?>%">
                                    <?php echo $math_progress; ?>%
                                </div>
                            </div>
                            <div class="small">
                                <span class="font-weight-bold"><?php echo $math_completed; ?>/<?php echo $math_total; ?></span> topics completed
                                <span class="float-right">Confidence: <?php echo $math_confidence; ?>/10</span>
                            </div>y();
                        </div>coming_tasks_result->fetch_assoc()) {
                        g_tasks[] = $task;
                        <!-- English Progress -->
                        <div class="col-lg-4 mb-3">g_tasks;
                            <h6 class="font-weight-bold">English</h6>
                            <div class="progress mb-2" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $english_progress; ?>%">
                                    <?php echo $english_progress; ?>%
                                </div>
                            </div>
                            <div class="small">
                                <span class="font-weight-bold"><?php echo $english_completed; ?>/<?php echo $english_total; ?></span> topics completed
                                <span class="float-right">Confidence: <?php echo $english_confidence; ?>/10</span>
                            </div>
                        </div>
                        me, hc.color, hc.icon,
                        <!-- Science Progress -->
                        <div class="col-lg-4 mb-3">
                            <h6 class="font-weight-bold">Science</h6>
                            <div class="progress mb-2" style="height: 25px;"> hp.status = 'completed'
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: <?php echo $subjects['Science']['total'] > 0 ? round(($subjects['Science']['completed'] / $subjects['Science']['total']) * 100) : 0; ?>%">
                                    <?php echo $subjects['Science']['total'] > 0 ? round(($subjects['Science']['completed'] / $subjects['Science']['total']) * 100) : 0; ?>%
                                </div>lor, hc.icon
                            </div>
                            <div class="small">
                                <span class="font-weight-bold"><?php echo $subjects['Science']['completed']; ?>/<?php echo $subjects['Science']['total']; ?></span> topics completed
                                <span class="float-right">Confidence: <?php echo $subjects['Science']['confidence']; ?>/10</span>
                            </div>
                        </div>nn->query($habit_category_query);
                    </div>lt && $category_result->num_rows > 0) {
            while ($category = $category_result->fetch_assoc()) {
                    <h6 class="font-weight-bold mb-3">Section Breakdown</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>bit category data: " . $e->getMessage());
                                    <th>Subject</th>
                                    <th>Section</th>
                                    <th>Progress</th>
                                    <th>Topics</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($section_data as $section): ?>
                                <tr>
                                    <td><?php echo $section['subject']; ?></td>
                                    <td><?php echo $section['section']; ?></td>
                                    <td>hensive overview of your GCSE preparation</p>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?php echo $section['progress']; ?>%" 
                                                 aria-valuenow="<?php echo $section['progress']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </td>ss="btn btn-primary" id="refreshDashboard">
                                    <td><?php echo $section['completed_topics']; ?>/<?php echo $section['total_topics']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>->
            </div>w mb-4">
        </div>lass="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
        <!-- Exam Countdown -->d-body">
        <div class="col-lg-4 mb-4"> no-gutters align-items-center">
            <div class="card shadow h-100">2">
                <div class="card-header py-3">s font-weight-bold text-primary text-uppercase mb-1">
                    <h6 class="m-0 font-weight-bold">Upcoming Exams</h6>
                </div>      </div>
                <div class="card-body">"h5 mb-0 font-weight-bold text-gray-800">
                    <?php if ($exams_result && $exams_result->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                        <?php while ($exam = $exams_result->fetch_assoc()): ?>
                            <li class="list-group-item px-0">e="progressbar" style="width: <?php echo $overall_percentage; ?>%" 
                                <div class="d-flex justify-content-between align-items-center">luemin="0" aria-valuemax="100"></div>
                                    <div>
                                        <h6 class="font-weight-bold mb-0"><?php echo $exam['title']; ?></h6>
                                        <div class="small text-muted">
                                            <?php echo date('j M Y, g:i a', strtotime($exam['exam_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <div class="h4 mb-0 font-weight-bold 
                                            <?php 
                                                if ($exam['days_remaining'] <= 7) {
                                                    echo 'text-danger';
                                                } elseif ($exam['days_remaining'] <= 30) {
                                                    echo 'text-warning';
                                                } else {ms-center">
                                                    echo 'text-info';
                                                }ont-weight-bold text-success text-uppercase mb-1">
                                            ?>">
                                            <?php echo $exam['days_remaining']; ?>
                                        </div>0 font-weight-bold text-gray-800"><?php echo $total_study_hours; ?> hours</div>
                                        <div class="small text-muted">days left</div>
                                    </div>$days_since_study > 0): ?>
                                </div>st study: <?php echo $days_since_study; ?> days ago
                            </li>?php else: ?>
                        <?php endwhile; ?>d today
                        </ul>   <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-check fa-3x text-gray-300 mb-3"></i>
                            <p class="mb-0">No upcoming exams scheduled</p>>
                            <a href="#" class="btn btn-sm btn-primary mt-3">Add Exam</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>iv class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
    <!-- Tasks and Assignments -->ody">
    <div class="row">div class="row no-gutters align-items-center">
        <!-- Tasks -->  <div class="col mr-2">
        <div class="col-lg-6 mb-4">ass="text-xs font-weight-bold text-info text-uppercase mb-1">
            <div class="card shadow h-100">eted
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Tasks Overview</h6>ay-800">
                    <a href="tasks.php" class="btn btn-sm btn-primary">Manage Tasks</a>
                </div>              $task_completion = $tasks_data['total_tasks'] > 0 
                <div class="card-body"> ? round(($tasks_data['completed_tasks'] / $tasks_data['total_tasks']) * 100) 
                    <div class="row mb-4">0;
                        <div class="col-6">ask_completion . '%';
                            <div class="small text-muted">Completion Rate</div>
                            <div class="h4 font-weight-bold">
                                <?phps="progress progress-sm mt-2">
                                    echo $tasks_data['total_tasks'] > 0 progressbar" style="width: <?php echo $task_completion; ?>%" 
                                        ? round(($tasks_data['completed_tasks'] / $tasks_data['total_tasks']) * 100) . '%' </div>
                                        : '0%';
                                ?>
                            </div>="col-auto">
                        </div> class="fas fa-tasks fa-2x text-gray-300"></i>
                        <div class="col-6">
                            <div class="small text-muted">Total Tasks</div>
                            <div class="h4 font-weight-bold">
                                <?php echo $tasks_data['total_tasks']; ?>
                            </div>
                        </div>
                    </div>-3 col-md-6 mb-4">
                    <div class="row">eft-warning shadow h-100 py-2">
                        <div class="col-4 text-center">
                            <div class="border rounded py-2">nter">
                                <div class="h4 mb-0 text-danger font-weight-bold">
                                    <?php echo $tasks_data['overdue_tasks']; ?>ext-uppercase mb-1">
                                </div> Today
                                <div class="small text-muted">Overdue</div>
                            </div>lass="h5 mb-0 font-weight-bold text-gray-800">
                        </div>  <?php 
                        <div class="col-4 text-center">bits_data['total_habits'] ?: 0;
                            <div class="border rounded py-2">ts_data['completed_today'] ?: 0;
                                <div class="h4 mb-0 text-warning font-weight-bold">
                                    <?php echo $tasks_data['today_tasks']; ?>
                                </div>
                                <div class="small text-muted">Due Today</div>
                            </div>iv class="progress progress-sm mt-2">
                        </div>      <div class="progress-bar bg-warning" role="progressbar" 
                        <div class="col-4 text-center"><?php echo ($habits_completed / $habits_total) * 100; ?>%" 
                            <div class="border rounded py-2"> echo ($habits_completed / $habits_total) * 100; ?>" 
                                <div class="h4 mb-0 text-info font-weight-bold">/div>
                                    <?php echo $tasks_data['upcoming_tasks']; ?>
                                </div>f; ?>
                                <div class="small text-muted">Upcoming</div>
                            </div>="col-auto">
                        </div> class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>div>
                    div>
                    <?php if (!empty($tasks_data['upcoming_list'])): ?>div>
                    <div class="mt-4">div>
                        <h6 class="font-weight-bold mb-3">Upcoming Tasks</h6></div>
                        <div class="list-group">
                            <?php foreach ($tasks_data['upcoming_list'] as $task): ?>
                            <div class="list-group-item list-group-item-action flex-column align-items-start p-2">n -->
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($task['category_icon'])): ?>
                                        <div class="mr-2" style="width: 24px; height: 24px; border-radius: 50%; background-color: <?php echo $task['category_color']; ?> !important; display: flex; align-items: center; justify-content: center">="card shadow">
                                            <i class="<?php echo $task['category_icon']; ?> text-white small"></i> py-3 d-flex flex-row align-items-center justify-content-between">
                                        </div>ss</h6>
                                        <?php endif; ?>
                                        <h6 class="mb-0 text-truncate" style="max-width: 180px;"><?php echo htmlspecialchars($task['title']); ?></h6>"dropdown-toggle" href="#" role="button" id="subjectDropdown" 
                                    </div>
                                    <small class="text-muted">ss="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        <?php 
                                            $due_date = new DateTime($task['due_date']);dby="subjectDropdown">
                                            $today = new DateTime('today');
                                            $diff = $today->diff($due_date);dropdown-item" href="#">Math Detail</a>
                                            
                                            if ($diff->days == 0) {ss="dropdown-item" href="#">Science Detail</a>
                                                echo 'Today';iv class="dropdown-divider"></div>
                                                if ($task['due_time']) {s</a>
                                                    echo ' ' . date('g:i A', strtotime($task['due_time']));
                                                }
                                            } elseif ($diff->days == 1) {
                                                echo 'Tomorrow';lass="card-body">
                                            } else {  <div class="row mb-4">
                                                echo date('j M', strtotime($task['due_date']));          <!-- Math Progress -->
                                            }              <div class="col-lg-4 mb-3">
                                        ?>                      <h6 class="font-weight-bold">Mathematics</h6>
                                    </small>                            <div class="progress mb-2" style="height: 25px;">
                                </div>  <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $math_progress; ?>%">
                                <div class="d-flex justify-content-between align-items-center mt-1">; ?>%
                                    <div>                        </div>
                                        <span class="badge badge-<?php 
                                            echo $task['priority'] == 'high' ? 'danger' :   <div class="small">
                                                ($task['priority'] == 'medium' ? 'warning' : 'info'); mpleted; ?>/<?php echo $math_total; ?></span> topics completed
                                        ?>"><?php echo ucfirst($task['priority']); ?></span>-right">Confidence: <?php echo $math_confidence; ?>/10</span>
                                           </div>
                                        <?php if ($task['estimated_duration']): ?>         </div>
                                        <span class="badge badge-light">
                                            <i class="far fa-clock"></i> <?php echo $task['estimated_duration']; ?> min<!-- English Progress -->
                                        </span> <div class="col-lg-4 mb-3">
                                        <?php endif; ?>6>
                                    </div>ght: 25px;">
                                    <small class="text-muted"><?php echo $task['category_name'] ?: 'Uncategorized'; ?></small>cess" role="progressbar" style="width: <?php echo $english_progress; ?>%">
                                </div>; ?>%
                            </div>              </div>
                            <?php endforeach; ?>
                        </div>mall">
                    </div>ass="font-weight-bold"><?php echo $english_completed; ?>/<?php echo $english_total; ?></span> topics completed
                    <?php endif; ?>ss="float-right">Confidence: <?php echo $english_confidence; ?>/10</span>
                </div>
            </div>      </div>
        </div>
        cience Progress -->
        <!-- Habit Categories -->lass="col-lg-4 mb-3">
        <div class="col-lg-6 mb-4">6 class="font-weight-bold">Science</h6>
            <div class="card shadow h-100">div class="progress mb-2" style="height: 25px;">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">              <div class="progress-bar bg-info" role="progressbar" 
                    <h6 class="m-0 font-weight-bold">Habits by Category</h6>       style="width: <?php echo $subjects['Science']['total'] > 0 ? round(($subjects['Science']['completed'] / $subjects['Science']['total']) * 100) : 0; ?>%">
                    <a href="habits.php" class="btn btn-sm btn-primary">Manage Habits</a>                      <?php echo $subjects['Science']['total'] > 0 ? round(($subjects['Science']['completed'] / $subjects['Science']['total']) * 100) : 0; ?>%
                </div>                      </div>
                <div class="card-body">          </div>
                    <?php if (!empty($habit_categories)): ?>"small">
                        <div class="row">          <span class="font-weight-bold"><?php echo $subjects['Science']['completed']; ?>/<?php echo $subjects['Science']['total']; ?></span> topics completed
                            <?php foreach($habit_categories as $category): ?>       <span class="float-right">Confidence: <?php echo $subjects['Science']['confidence']; ?>/10</span>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="mr-2" style="width: 30px; height: 30px; border-radius: 50%; background-color: <?php echo $category['color']; ?>; display: flex; align-items: center; justify-content: center">h6 class="font-weight-bold mb-3">Section Breakdown</h6>
                                                <i class="<?php echo $category['icon']; ?> text-white"></i>   <div class="table-responsive">
                                            </div>          <table class="table table-sm">
                                            <h6 class="mb-0 font-weight-bold"><?php echo $category['name']; ?></h6>   <thead>
                                        </div>                       <tr>
                                    </div>                             <th>Subject</th>
                                    <div class="d-flex justify-content-between align-items-center">                                    <th>Section</th>
                                        <div class="small">h>Progress</th>
                                            <span class="font-weight-bold"><?php echo $category['completed_today']; ?>/<?php echo $category['total_habits']; ?></span> completed today
                                        </div>
                                        <div>        </thead>
                                            <?php              <tbody>
                                                $completion_percent = $category['total_habits'] > 0 ? $section): ?>
                                                    round(($category['completed_today'] / $category['total_habits']) * 100) : 0;        <tr>
                                            ?>td><?php echo $section['subject']; ?></td>
                                            <span class="badge badge-<?php              <td><?php echo $section['section']; ?></td>
                                                echo $completion_percent == 100 ? 'success' : 
                                                     ($completion_percent >= 50 ? 'primary' : 'secondary');"height: 10px;">
                                            ?>"><?php echo $completion_percent; ?>%</span>r bg-primary" role="progressbar" 
                                        </div>                               style="width: <?php echo $section['progress']; ?>%" 
                                    </div>               aria-valuenow="<?php echo $section['progress']; ?>" 
                                    <div class="progress mt-2" style="height: 6px;">        aria-valuemin="0" aria-valuemax="100"></div>
                                        <div class="progress-bar" style="width: <?php echo $completion_percent; ?>%; background-color: <?php echo $category['color']; ?>"></div>/div>
                                    </div>>
                                </div>                  <td><?php echo $section['completed_topics']; ?>/<?php echo $section['total_topics']; ?></td>
                            </div>  </tr>
                            <?php endforeach; ?>                  <?php endforeach; ?>
                        </div>                  </tbody>
                    <?php else: ?>      </table>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-gray-300 mb-3"></i>>
                            <p class="mb-0">No habit categories found</p>
                            <a href="habit_categories.php" class="btn btn-sm btn-primary mt-3">Create Categories</a>
                        </div>
                    <?php endif; ?>
                </div>ol-lg-4 mb-4">
            </div>class="card shadow h-100">
        </div>  <div class="card-header py-3">
    </div>6 class="m-0 font-weight-bold">Upcoming Exams</h6>
    
    <!-- English Practice and Assignments -->ody">
    <div class="row mb-4">   <?php if ($exams_result && $exams_result->num_rows > 0): ?>
        <!-- English Practice -->           <ul class="list-group list-group-flush">
        <div class="col-lg-6 mb-4">               <?php while ($exam = $exams_result->fetch_assoc()): ?>
            <div class="card shadow h-100">                     <li class="list-group-item px-0">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">                                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">English Practice</h6>
                    <a href="english_practice.php" class="btn btn-sm btn-primary">View All</a>            <h6 class="font-weight-bold mb-0"><?php echo $exam['title']; ?></h6>
                </div> class="small text-muted">
                <div class="card-body">php echo date('j M Y, g:i a', strtotime($exam['exam_date'])); ?>
                    <?php if (!empty($english_practice_data)): ?>             </div>
                        <?php if (isset($english_practice_data['stats'])): ?></div>
                        <div class="row mb-4">   <div class="text-center">
                            <div class="col-3 text-center">t-weight-bold 
                                <div class="h4 mb-0 font-weight-bold"><?php echo $english_practice_data['stats']['total_items']; ?></div>
                                <div class="small text-muted">Total Items</div>f ($exam['days_remaining'] <= 7) {
                            </div>     echo 'text-danger';
                            <div class="col-3 text-center"> elseif ($exam['days_remaining'] <= 30) {
                                <div class="h4 mb-0 font-weight-bold"><?php echo $english_practice_data['stats']['favorite_items']; ?></div>                              echo 'text-warning';
                                <div class="small text-muted">Favorites</div> } else {
                            </div>                                   echo 'text-info';
                            <div class="col-3 text-center">                                }
                                <div class="h4 mb-0 font-weight-bold"><?php echo $english_practice_data['stats']['practice_days']; ?></div>  ?>">
                                <div class="small text-muted">Days</div>     <?php echo $exam['days_remaining']; ?>
                            </div>
                            <div class="col-3 text-center">"small text-muted">days left</div>
                                <div class="h4 mb-0 font-weight-bold"><?php echo $english_practice_data['stats']['items_last_week']; ?></div>
                                <div class="small text-muted">Last Week</div>
                            </div>      </li>
                        </div>
                        <?php endif; ?>    </ul>
                        
                        <h6 class="font-weight-bold mb-3">Recent Practice Items</h6>
                        <div class="list-group">
                            <?php 
                            $counter = 0;           <a href="#" class="btn btn-sm btn-primary mt-3">Add Exam</a>
                            foreach($english_practice_data as $item):           </div>
                                if (!isset($item['id'])) continue; // Skip stats array
                                $counter++;           </div>
                                if ($counter > 5) break;        </div>
                            ?>
                                <div class="list-group-item p-3 mb-2">
                                    <div class="d-flex w-100 justify-content-between">    
                                        <h6 class="mb-1 font-weight-bold"><?php echo htmlspecialchars($item['item_title']); ?></h6>-->
                                        <small class="text-muted"><?php echo date('j M', strtotime($item['practice_date'])); ?></small>
                                    </div>
                                    <p class="mb-1"><small><?php echo htmlspecialchars($item['item_meaning']); ?></small></p>
                                    <div class="d-flex justify-content-between align-items-center mt-2">shadow h-100">
                                        <span class="badge badge-light"><?php echo htmlspecialchars($item['category_name']); ?></span>        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                        <span class="small text-muted">Day <?php echo $item['day_number']; ?></span> class="m-0 font-weight-bold">Tasks Overview</h6>
                                    </div> href="tasks.php" class="btn btn-sm btn-primary">Manage Tasks</a>
                                </div>        </div>
                            <?php endforeach; ?>">
                        </div>lass="row mb-4">
                    <?php else: ?>lass="col-6">
                        <div class="text-center py-4">           <div class="small text-muted">Completion Rate</div>
                            <i class="fas fa-book fa-3x text-gray-300 mb-3"></i>                     <div class="h4 font-weight-bold">
                            <p class="mb-0">No practice items found</p>                             <?php
                            <a href="english_practice.php" class="btn btn-sm btn-primary mt-3">Start Practice</a>                           echo $tasks_data['total_tasks'] > 0 
                        </div>                                        ? round(($tasks_data['completed_tasks'] / $tasks_data['total_tasks']) * 100) . '%' 
                    <?php endif; ?>                                 : '0%';
                </div>               ?>
            </div>                     </div>
        </div> </div>
         class="col-6">
        <!-- Assignments -->div class="small text-muted">Total Tasks</div>
        <div class="col-lg-6 mb-4">   <div class="h4 font-weight-bold">
            <div class="card shadow h-100">         <?php echo $tasks_data['total_tasks']; ?>
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">        </div>
                    <h6 class="m-0 font-weight-bold">Assignment Deadlines</h6> </div>
                    <a href="assignments.php" class="btn btn-sm btn-primary">View All</a>div>
                </div>div class="row">
                <div class="card-body">    <div class="col-4 text-center">
                    <?php if (!empty($assignments_detail)): ?>      <div class="border rounded py-2">
                        <div class="table-responsive">                               <div class="h4 mb-0 text-danger font-weight-bold">
                            <table class="table table-sm">                                    <?php echo $tasks_data['overdue_tasks']; ?>
                                <thead>
                                    <tr>ext-muted">Overdue</div>
                                        <th>Assignment</th>
                                        <th>Due Date</th>
                                        <th>Progress</th>nter">
                                        <th>Priority</th>                            <div class="border rounded py-2">
                                    </tr>ning font-weight-bold">
                                </thead>today_tasks']; ?>
                                <tbody>
                                    <?php foreach($assignments_detail as $assignment): ?>">Due Today</div>
                                    <tr>
                                        <td>
                                            <span class="font-weight-bold"><?php echo htmlspecialchars($assignment['title']); ?></span>>
                                            <?php if ($assignment['credits']): ?>                            <div class="border rounded py-2">
                                                <br><small class="text-muted"><?php echo $assignment['credits']; ?> credits</small>ont-weight-bold">
                                            <?php endif; ?>oming_tasks']; ?>
                                        </td>
                                        <td>pcoming</div>
                                            <?php 
                                                $due_date = new DateTime($assignment['due_date']);                        </div>
                                                $today = new DateTime('today');             </div>
                                                $diff = $today->diff($due_date);
                                                
                                                if ($diff->days == 0) {
                                                    echo '<span class="text-warning">Today</span>';       
                                                } elseif ($diff->days == 1) {        <!-- Habit Streaks -->
                                                    echo '<span class="text-warning">Tomorrow</span>';class="col-lg-6 mb-4">
                                                } else {dow h-100">
                                                    echo date('j M', strtotime($assignment['due_date']));               <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                    echo '<br><small class="text-muted">' . $diff->days . ' days left</small>';                    <h6 class="m-0 font-weight-bold">Top Habit Streaks</h6>
                                                }           <a href="habits.php" class="btn btn-sm btn-primary">Manage Habits</a>
                                            ?>
                                        </td>               <div class="card-body">
                                        <td>                    <?php foreach($habit_streak_data as $habit): ?>
                                            <div class="progress" style="height: 8px; width: 80px;">          <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div class="progress-bar <?php iv>
                                                    echo $assignment['progress_percentage'] == 100 ? 'bg-success' : mb-0 font-weight-bold"><?php echo $habit['name']; ?></h6>
                                                        ($assignment['progress_percentage'] >= 50 ? 'bg-info' : 'bg-warning');                            </div>
                                                ?>" role="progressbar" style="width: <?php echo $assignment['progress_percentage']; ?>%"></div>                            <div class="d-flex align-items-center">
                                            </div>             <div class="h5 mb-0 mr-2 font-weight-bold text-primary">
                                            <small class="text-muted"><?php echo $assignment['progress_percentage']; ?>%</small>    <?php echo $habit['completion_count']; ?>
                                        </td>                               </div>
                                        <td>                                <div class="text-xs text-uppercase text-muted">completions</div>
                                            <span class="badge badge-<?php                  </div>
                                                echo $assignment['priority'] == 'high' ? 'danger' : </div>
                                                    ($assignment['priority'] == 'medium' ? 'warning' : 'info');      <div class="progress mb-4" style="height: 10px;">
                                            ?>"><?php echo ucfirst($assignment['priority']); ?></span>iv class="progress-bar" role="progressbar" style="width: <?php echo min($habit['completion_count'] * 5, 100); ?>%"></div>
                                        </td>                       </div>
                                    </tr>                    <?php endforeach; ?>
                                    <?php endforeach; ?> </div>
                                </tbody>
                            </table>       </div>
                        </div>    </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-gray-300 mb-3"></i>!-- JavaScript for Charts -->
                            <p class="mb-0">No upcoming assignments found</p><script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                            <a href="assignments.php" class="btn btn-sm btn-primary mt-3">Add Assignment</a>
                        </div>ContentLoaded', function() {
                    <?php endif; ?>   // Task Progress Chart
                </div>    const taskCtx = document.getElementById('taskProgressChart').getContext('2d');
            </div>ssChart = new Chart(taskCtx, {
        </div>ughnut',
    </div>
    'Completed', 'Overdue', 'Today', 'Upcoming'],
    <!-- Exam Countdown Detail -->
    <?php if ($upcoming_exam): ?>
    <div class="row mb-4">                   <?php echo $tasks_data['completed_tasks']; ?>, 
        <div class="col-12">                    <?php echo $tasks_data['overdue_tasks']; ?>, 
            <div class="card shadow">  <?php echo $tasks_data['today_tasks']; ?>, 
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">       <?php echo $tasks_data['upcoming_tasks']; ?>
                    <h6 class="m-0 font-weight-bold">Next Exam Countdown</h6>
                    <a href="exams.php" class="btn btn-sm btn-primary">All Exams</a>ndColor: [
                </div>', // success
                <div class="card-body">    '#e74a3b', // danger
                    <div class="row align-items-center">   '#f6c23e', // warning
                        <div class="col-md-4 text-center">cc'  // info
                            <div class="h1 font-weight-bold <?php ,
                                echo $upcoming_exam['days_remaining'] <= 7 ? 'text-danger' :                hoverBackgroundColor: [
                                    ($upcoming_exam['days_remaining'] <= 30 ? 'text-warning' : 'text-info');                     '#17a673',
                            ?>"><?php echo $upcoming_exam['days_remaining']; ?></div>       '#c63825',
                            <div class="h5 text-muted">Days Remaining</div>
                            <div class="mt-3">                   '#2c9faf'
                                <div class="countdown-date">                ],
                                    <?php echo date('l, j F Y', strtotime($upcoming_exam['exam_date'])); ?>derWidth: 0
                                </div>
                                <div class="countdown-time font-weight-bold">       },
                                    <?php echo date('g:i A', strtotime($upcoming_exam['exam_date'])); ?>        options: {
                                </div>ectRatio: false,
                            </div> {
                        </div> {
                        <div class="col-md-8">ition: 'bottom',
                            <h4 class="font-weight-bold" style="color: <?php echo $upcoming_exam['subject_color'] ?: '#333'; ?>">                   labels: {
                                <?php echo htmlspecialchars($upcoming_exam['title']); ?>                        padding: 20,
                            </h4>ointStyle: true
                            <p class="lead"><?php echo htmlspecialchars($upcoming_exam['subject_name']); ?></p>    }
                            
                            <div class="row mt-4">
                                <div class="col-md-6"> cutout: '70%'
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fas fa-clock text-primary mr-2"></i>
                                        <div>
                                            <div class="small text-muted">Duration</div>   // Assignment Chart with real data
                                            <div class="font-weight-bold">    const assignmentCtx = document.getElementById('assignmentChart').getContext('2d');
                                                <?php echo floor($upcoming_exam['duration'] / 60); ?> hr mentChart = new Chart(assignmentCtx, {
                                                <?php echo $upcoming_exam['duration'] % 60; ?> min
                                            </div>
                                        </div>mpleted', 'In Progress', 'Not Started'],
                                    </div>           datasets: [{
                                                    label: 'Assignments',
                                    <div class="d-flex align-items-center mb-3">data: [
                                        <i class="fas fa-map-marker-alt text-danger mr-2"></i>hp echo $assignments_data['completed']; ?>,
                                        <div>    <?php echo $assignments_data['in_progress']; ?>,
                                            <div class="small text-muted">Location</div>    <?php echo $assignments_data['not_started']; ?>
                                            <div class="font-weight-bold"><?php echo htmlspecialchars($upcoming_exam['location']); ?></div>,
                                        </div>undColor: [
                                    </div>cess
                                </div>   '#f6c23e', // warning
                                  '#e74a3b'  // danger
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-3"> 0
                                        <i class="fas fa-clipboard-list text-success mr-2"></i>
                                        <div>       },
                                            <div class="small text-muted">Exam Board</div>        options: {
                                            <div class="font-weight-bold"><?php echo htmlspecialchars($upcoming_exam['exam_board']); ?></div>tainAspectRatio: false,
                                        </div>
                                    </div>
                                                       beginAtZero: true,
                                    <div class="d-flex align-items-center mb-3">                    ticks: {
                                        <i class="fas fa-calculator text-info mr-2"></i>        precision: 0
                                        <div>
                                            <div class="small text-muted">Equipment</div>
                                            <div class="font-weight-bold">           },
                                                <?php echo $upcoming_exam['calculator_allowed'] ? 'Calculator Allowed' : 'No Calculator'; ?>            plugins: {
                                            </div>: {
                                        </div>: false
                                    </div>}
                                </div>
                            </div>   }
                            });
                            <div class="mt-3 text-right">
                                <a href="exam_detail.php?id=<?php echo $upcoming_exam['id']; ?>" class="btn btn-primary">0 seconds (30000 ms)
                                    <i class="fas fa-info-circle"></i> Exam Detailsunction refreshData() {
                                </a>    fetch('ajax/get_dashboard_data.php')
                                <a href="revision_plan.php?exam_id=<?php echo $upcoming_exam['id']; ?>" class="btn btn-success ml-2">sponse.json())
                                    <i class="fas fa-tasks"></i> Revision Planata => {
                                </a>           // Update task chart
                            </div>            if (data.tasks) {
                        </div>taskProgressChart.data.datasets[0].data = [
                    </div>    data.tasks.completed_tasks,
                </div>    data.tasks.overdue_tasks,
            </div>   data.tasks.today_tasks,
        </div>                   data.tasks.upcoming_tasks
    </div>                   ];
    <?php endif; ?>                    taskProgressChart.update();
</div>
  
<!-- JavaScript for Charts -->     // Update assignment chart
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>data.datasets[0].data = [
document.addEventListener('DOMContentLoaded', function() {                   data.assignments.completed,
    // Task Progress Chart                    data.assignments.in_progress,
    const taskCtx = document.getElementById('taskProgressChart').getContext('2d');   data.assignments.not_started
    const taskProgressChart = new Chart(taskCtx, {
        type: 'doughnut',               assignmentChart.update();
        data: {                   
            labels: ['Completed', 'Overdue', 'Today', 'Upcoming'],            // Update the numbers in the cards
            datasets: [{                    document.querySelector('.text-danger + .h5').textContent = data.assignments.not_started;
                data: [               document.querySelector('.text-warning + .h5').textContent = data.assignments.in_progress;
                    <?php echo $tasks_data['completed_tasks']; ?>,    document.querySelector('.text-success + .h5').textContent = data.assignments.completed;
                    <?php echo $tasks_data['overdue_tasks']; ?>, 
                    <?php echo $tasks_data['today_tasks']; ?>,             })
                    <?php echo $tasks_data['upcoming_tasks']; ?>console.error('Error fetching dashboard data:', error));
                ],
                backgroundColor: [  
                    '#1cc88a', // success    // Refresh every 30 seconds

























































































































































































































































































































































?>close_connection($conn);// Close database connectioninclude 'includes/footer.php';// Include footer<?php</style>}    }        font-size: 0.9rem;    .table-responsive {        }        font-size: 1rem;    .countdown-date, .countdown-time {@media (max-width: 767px) {}    transform: translateY(-3px);.list-group-item:hover {}    border-radius: 0.5rem !important;    transition: transform 0.15s ease-in-out;.list-group-item {}    font-size: 1.2rem;.countdown-time {}    color: #6c757d;    font-size: 1.1rem;.countdown-date {/* Custom styles for the detailed sections */<style><!-- Additional CSS for the new sections --></style>}    }        display: none !important;    .btn, .dropdown {        }        box-shadow: none !important;        border: 1px solid #ddd !important;    .card {@media print {/* Print styles */}    }        height: 30px;        width: 30px;        left: -30px;    .activity-icon {        }        left: 15px;    .activity-timeline::before {        }        padding-left: 30px;    .activity-timeline {        }        padding: 1rem;    .card-body {@media (max-width: 768px) {/* Responsive fixes */}    color: #858796;    font-size: 0.85rem;.activity-date {}    line-height: 1.4;    font-weight: 600;.activity-text {}    z-index: 1;    justify-content: center;    align-items: center;    display: flex;    color: white;    background-color: var(--primary);    border-radius: 50%;    height: 40px;    width: 40px;    left: -40px;    position: absolute;.activity-icon {}    margin-bottom: 20px;    padding-bottom: 20px;    position: relative;.activity-item {}    background-color: #e3e6f0;    height: 100%;    width: 2px;    top: 0;    left: 20px;    position: absolute;    content: '';.activity-timeline::before {}    padding-left: 40px;    position: relative;.activity-timeline {/* Activity Timeline */}    background-color: #f8f9fc;.habit-item:hover {}    transition: background-color 0.2s;.habit-item {}    color: white;    border-radius: 0.5rem;    height: 35px;    width: 35px;    justify-content: center;    align-items: center;    display: flex;.habit-icon {/* Habit Styles */}    border: 1px solid var(--border);    background-color: #f8f9fc;    padding: 0.75rem;    border-radius: 0.5rem;.exam-countdown {/* Exam Countdown */}    color: #5a5c69 !important;.text-gray-800 {}    color: #dddfeb !important;.text-gray-300 {}    border-radius: 1rem;.progress-bar {}    background-color: #eaecf4;    height: 0.5rem;    border-radius: 1rem;.progress {}    font-weight: 700 !important;.font-weight-bold {}    border-bottom: 1px solid var(--border);    background-color: #f8f9fc;.card-header {}    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1) !important;.shadow {}    transform: translateY(-5px);.card:hover {}    transition: transform 0.2s ease-in-out;    border: 1px solid var(--border);    border-radius: 0.75rem;.card {.border-left-danger { border-left: 4px solid var(--danger); }.border-left-warning { border-left: 4px solid var(--warning); }.border-left-info { border-left: 4px solid var(--info); }.border-left-success { border-left: 4px solid var(--success); }.border-left-primary { border-left: 4px solid var(--primary); }.bg-dark { background-color: var(--dark) !important; }.bg-light { background-color: var(--light) !important; }.bg-danger { background-color: var(--danger) !important; }.bg-warning { background-color: var(--warning) !important; }.bg-info { background-color: var(--info) !important; }.bg-success { background-color: var(--success) !important; }.bg-primary { background-color: var(--primary) !important; }.text-danger { color: var(--danger) !important; }.text-warning { color: var(--warning) !important; }.text-info { color: var(--info) !important; }.text-success { color: var(--success) !important; }.text-primary { color: var(--primary) !important; }}    --border: #e3e6f0;    --dark: #5a5c69;    --light: #f8f9fc;    --danger: #e74a3b;    --warning: #f6c23e;    --info: #36b9cc;    --success: #1cc88a;    --secondary: #2c3e50;    --primary-light: #e6d5a7;    --primary-dark: #b69a45;    --primary: #cdaf56;:root {/* Main Styles */<style></script>});    });        }, 1000);            location.reload();        setTimeout(() => {        // Reload page after 1 second                refreshData();        // Refresh data                this.disabled = true;        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';        // Show loading indicator    document.getElementById('refreshDashboard').addEventListener('click', function() {    // Refresh dashboard button    setInterval(refreshData, 30000);    // Refresh every 30 seconds        }            .catch(error => console.error('Error fetching dashboard data:', error));            })                }                    document.querySelector('.text-success + .h5').textContent = data.assignments.completed;                    document.querySelector('.text-warning + .h5').textContent = data.assignments.in_progress;                    document.querySelector('.text-danger + .h5').textContent = data.assignments.not_started;                    // Update the numbers in the cards                                        assignmentChart.update();                    ];                        data.assignments.not_started                        data.assignments.in_progress,                        data.assignments.completed,                    assignmentChart.data.datasets[0].data = [                if (data.assignments) {                // Update assignment chart                                }                    taskProgressChart.update();                    ];                        data.tasks.upcoming_tasks                        data.tasks.today_tasks,                        data.tasks.overdue_tasks,                        data.tasks.completed_tasks,                    taskProgressChart.data.datasets[0].data = [                if (data.tasks) {                // Update task chart            .then(data => {            .then(response => response.json())        fetch('ajax/get_dashboard_data.php')    function refreshData() {    // Refresh data every 30 seconds (30000 ms)    });        }            }                }                    display: false                legend: {            plugins: {            },                }                    }                        precision: 0                    ticks: {                    beginAtZero: true,                y: {            scales: {            maintainAspectRatio: false,        options: {        },            }]                borderWidth: 0                ],                    '#e74a3b'  // danger                    '#f6c23e', // warning                    '#1cc88a', // success                backgroundColor: [                ],                    <?php echo $assignments_data['not_started']; ?>                    <?php echo $assignments_data['in_progress']; ?>,                    <?php echo $assignments_data['completed']; ?>,                data: [                label: 'Assignments',            datasets: [{            labels: ['Completed', 'In Progress', 'Not Started'],        data: {        type: 'bar',    const assignmentChart = new Chart(assignmentCtx, {    const assignmentCtx = document.getElementById('assignmentChart').getContext('2d');    // Assignment Chart with real data    });        }            cutout: '70%'            },                }                    }                        usePointStyle: true                        padding: 20,                    labels: {                    position: 'bottom',                legend: {            plugins: {            maintainAspectRatio: false,        options: {        },            }]                borderWidth: 0                ],                    '#2c9faf'                    '#dda20a',                    '#c63825',                    '#17a673',                hoverBackgroundColor: [                ],                    '#36b9cc'  // info                    '#f6c23e', // warning                    '#e74a3b', // danger    setInterval(refreshData, 30000);

    // Refresh dashboard button
    document.getElementById('refreshDashboard').addEventListener('click', function() {
        // Show loading indicator
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
        this.disabled = true;
        
        // Refresh data
        refreshData();
        
        // Reload page after 1 second
        setTimeout(() => {
            location.reload();
        }, 1000);
    });
});
</script>

<style>
/* Main Styles */
:root {
    --primary: #cdaf56;
    --primary-dark: #b69a45;
    --primary-light: #e6d5a7;
    --secondary: #2c3e50;
    --success: #1cc88a;
    --info: #36b9cc;
    --warning: #f6c23e;
    --danger: #e74a3b;
    --light: #f8f9fc;
    --dark: #5a5c69;
    --border: #e3e6f0;
}

.text-primary { color: var(--primary) !important; }
.text-success { color: var(--success) !important; }
.text-info { color: var(--info) !important; }
.text-warning { color: var(--warning) !important; }
.text-danger { color: var(--danger) !important; }

.bg-primary { background-color: var(--primary) !important; }
.bg-success { background-color: var(--success) !important; }
.bg-info { background-color: var(--info) !important; }
.bg-warning { background-color: var(--warning) !important; }
.bg-danger { background-color: var(--danger) !important; }
.bg-light { background-color: var(--light) !important; }
.bg-dark { background-color: var(--dark) !important; }

.border-left-primary { border-left: 4px solid var(--primary); }
.border-left-success { border-left: 4px solid var(--success); }
.border-left-info { border-left: 4px solid var(--info); }
.border-left-warning { border-left: 4px solid var(--warning); }
.border-left-danger { border-left: 4px solid var(--danger); }

.card {
    border-radius: 0.75rem;
    border: 1px solid var(--border);
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-5px);
}

.shadow {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1) !important;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid var(--border);
}

.font-weight-bold {
    font-weight: 700 !important;
}

.progress {
    border-radius: 1rem;
    height: 0.5rem;
    background-color: #eaecf4;
}

.progress-bar {
    border-radius: 1rem;
}

.text-gray-300 {
    color: #dddfeb !important;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

/* Exam Countdown */
.exam-countdown {
    border-radius: 0.5rem;
    padding: 0.75rem;
    background-color: #f8f9fc;
    border: 1px solid var(--border);
}

/* Habit Styles */
.habit-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 0.5rem;
    color: white;
}

.habit-item {
    transition: background-color 0.2s;
}

.habit-item:hover {
    background-color: #f8f9fc;
}

/* Activity Timeline */
.activity-timeline {
    position: relative;
    padding-left: 40px;
}

.activity-timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    width: 2px;
    height: 100%;
    background-color: #e3e6f0;
}

.activity-item {
    position: relative;
    padding-bottom: 20px;
    margin-bottom: 20px;
}

.activity-icon {
    position: absolute;
    left: -40px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.activity-text {
    font-weight: 600;
    line-height: 1.4;
}

.activity-date {
    font-size: 0.85rem;
    color: #858796;
}

/* Responsive fixes */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .activity-timeline {
        padding-left: 30px;
    }
    
    .activity-timeline::before {
        left: 15px;
    }
    
    .activity-icon {
        left: -30px;
        width: 30px;
        height: 30px;
    }
}

/* Print styles */
@media print {
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    
    .btn, .dropdown {
        display: none !important;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';

// Close database connection
close_connection($conn);
?>
