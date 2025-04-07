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

// Create a query to get section breakdown
$section_data = array();
if (in_array('assignments', $existing_tables)) {
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

// Include header
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0 text-gray-800">Status Dashboard</h1>
            <p class="text-muted">Comprehensive overview of your GCSE preparation</p>
        </div>
        <div class="col-auto">
            <div class="btn-group">
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <button type="button" class="btn btn-primary" id="refreshDashboard">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Overall Progress
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $overall_percentage; ?>%
                            </div>
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $overall_percentage; ?>%" 
                                     aria-valuenow="<?php echo $overall_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
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
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tasks Completed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                    $task_completion = $tasks_data['total_tasks'] > 0 
                                        ? round(($tasks_data['completed_tasks'] / $tasks_data['total_tasks']) * 100) 
                                        : 0;
                                    echo $task_completion . '%';
                                ?>
                            </div>
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $task_completion; ?>%" 
                                     aria-valuenow="<?php echo $task_completion; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
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
                                ?>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Subject Progress and Exam Countdown -->
    <div class="row mb-4">
        <!-- Subject Progress -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Subject Progress</h6>
                    <div class="dropdown no-arrow">
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
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <!-- Math Progress -->
                        <div class="col-lg-4 mb-3">
                            <h6 class="font-weight-bold">Mathematics</h6>
                            <div class="progress mb-2" style="height: 25px;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $math_progress; ?>%">
                                    <?php echo $math_progress; ?>%
                                </div>
                            </div>
                            <div class="small">
                                <span class="font-weight-bold"><?php echo $math_completed; ?>/<?php echo $math_total; ?></span> topics completed
                                <span class="float-right">Confidence: <?php echo $math_confidence; ?>/10</span>
                            </div>
                        </div>
                        
                        <!-- English Progress -->
                        <div class="col-lg-4 mb-3">
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
                        
                        <!-- Science Progress -->
                        <div class="col-lg-4 mb-3">
                            <h6 class="font-weight-bold">Science</h6>
                            <div class="progress mb-2" style="height: 25px;">
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: <?php echo $subjects['Science']['total'] > 0 ? round(($subjects['Science']['completed'] / $subjects['Science']['total']) * 100) : 0; ?>%">
                                    <?php echo $subjects['Science']['total'] > 0 ? round(($subjects['Science']['completed'] / $subjects['Science']['total']) * 100) : 0; ?>%
                                </div>
                            </div>
                            <div class="small">
                                <span class="font-weight-bold"><?php echo $subjects['Science']['completed']; ?>/<?php echo $subjects['Science']['total']; ?></span> topics completed
                                <span class="float-right">Confidence: <?php echo $subjects['Science']['confidence']; ?>/10</span>
                            </div>
                        </div>
                    </div>

                    <h6 class="font-weight-bold mb-3">Section Breakdown</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
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
                                    <td>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?php echo $section['progress']; ?>%" 
                                                 aria-valuenow="<?php echo $section['progress']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </td>
                                    <td><?php echo $section['completed_topics']; ?>/<?php echo $section['total_topics']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Exam Countdown -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Upcoming Exams</h6>
                </div>
                <div class="card-body">
                    <?php if ($exams_result && $exams_result->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                        <?php while ($exam = $exams_result->fetch_assoc()): ?>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
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
                                                } else {
                                                    echo 'text-info';
                                                }
                                            ?>">
                                            <?php echo $exam['days_remaining']; ?>
                                        </div>
                                        <div class="small text-muted">days left</div>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-check fa-3x text-gray-300 mb-3"></i>
                            <p class="mb-0">No upcoming exams scheduled</p>
                            <a href="#" class="btn btn-sm btn-primary mt-3">Add Exam</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tasks and Assignments -->
    <div class="row">
        <!-- Tasks -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Tasks Overview</h6>
                    <a href="tasks.php" class="btn btn-sm btn-primary">Manage Tasks</a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="small text-muted">Completion Rate</div>
                            <div class="h4 font-weight-bold">
                                <?php
                                    echo $tasks_data['total_tasks'] > 0 
                                        ? round(($tasks_data['completed_tasks'] / $tasks_data['total_tasks']) * 100) . '%' 
                                        : '0%';
                                ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Total Tasks</div>
                            <div class="h4 font-weight-bold">
                                <?php echo $tasks_data['total_tasks']; ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-center">
                            <div class="border rounded py-2">
                                <div class="h4 mb-0 text-danger font-weight-bold">
                                    <?php echo $tasks_data['overdue_tasks']; ?>
                                </div>
                                <div class="small text-muted">Overdue</div>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="border rounded py-2">
                                <div class="h4 mb-0 text-warning font-weight-bold">
                                    <?php echo $tasks_data['today_tasks']; ?>
                                </div>
                                <div class="small text-muted">Due Today</div>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="border rounded py-2">
                                <div class="h4 mb-0 text-info font-weight-bold">
                                    <?php echo $tasks_data['upcoming_tasks']; ?>
                                </div>
                                <div class="small text-muted">Upcoming</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Habit Streaks -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Top Habit Streaks</h6>
                    <a href="habits.php" class="btn btn-sm btn-primary">Manage Habits</a>
                </div>
                <div class="card-body">
                    <?php foreach($habit_streak_data as $habit): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0 font-weight-bold"><?php echo $habit['name']; ?></h6>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="h5 mb-0 mr-2 font-weight-bold text-primary">
                                    <?php echo $habit['completion_count']; ?>
                                </div>
                                <div class="text-xs text-uppercase text-muted">completions</div>
                            </div>
                        </div>
                        <div class="progress mb-4" style="height: 10px;">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo min($habit['completion_count'] * 5, 100); ?>%"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Task Progress Chart
    const taskCtx = document.getElementById('taskProgressChart').getContext('2d');
    const taskProgressChart = new Chart(taskCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Overdue', 'Today', 'Upcoming'],
            datasets: [{
                data: [
                    <?php echo $tasks_data['completed_tasks']; ?>, 
                    <?php echo $tasks_data['overdue_tasks']; ?>, 
                    <?php echo $tasks_data['today_tasks']; ?>, 
                    <?php echo $tasks_data['upcoming_tasks']; ?>
                ],
                backgroundColor: [
                    '#1cc88a', // success
                    '#e74a3b', // danger
                    '#f6c23e', // warning
                    '#36b9cc'  // info
                ],
                hoverBackgroundColor: [
                    '#17a673',
                    '#c63825',
                    '#dda20a',
                    '#2c9faf'
                ],
                borderWidth: 0
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            },
            cutout: '70%'
        }
    });

    // Assignment Chart with real data
    const assignmentCtx = document.getElementById('assignmentChart').getContext('2d');
    const assignmentChart = new Chart(assignmentCtx, {
        type: 'bar',
        data: {
            labels: ['Completed', 'In Progress', 'Not Started'],
            datasets: [{
                label: 'Assignments',
                data: [
                    <?php echo $assignments_data['completed']; ?>,
                    <?php echo $assignments_data['in_progress']; ?>,
                    <?php echo $assignments_data['not_started']; ?>
                ],
                backgroundColor: [
                    '#1cc88a', // success
                    '#f6c23e', // warning
                    '#e74a3b'  // danger
                ],
                borderWidth: 0
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Refresh data every 30 seconds (30000 ms)
    function refreshData() {
        fetch('ajax/get_dashboard_data.php')
            .then(response => response.json())
            .then(data => {
                // Update task chart
                if (data.tasks) {
                    taskProgressChart.data.datasets[0].data = [
                        data.tasks.completed_tasks,
                        data.tasks.overdue_tasks,
                        data.tasks.today_tasks,
                        data.tasks.upcoming_tasks
                    ];
                    taskProgressChart.update();
                }
                
                // Update assignment chart
                if (data.assignments) {
                    assignmentChart.data.datasets[0].data = [
                        data.assignments.completed,
                        data.assignments.in_progress,
                        data.assignments.not_started
                    ];
                    assignmentChart.update();
                    
                    // Update the numbers in the cards
                    document.querySelector('.text-danger + .h5').textContent = data.assignments.not_started;
                    document.querySelector('.text-warning + .h5').textContent = data.assignments.in_progress;
                    document.querySelector('.text-success + .h5').textContent = data.assignments.completed;
                }
            })
            .catch(error => console.error('Error fetching dashboard data:', error));
    }
    
    // Refresh every 30 seconds
    setInterval(refreshData, 30000);

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
