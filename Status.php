<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database connection with error handling
try {
    require_once __DIR__ . '/config/db_connect.php';
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

try {
    require_once __DIR__ . '/includes/functions.php';
} catch (Exception $e) {
    die("Functions file error: " . $e->getMessage());
}

// Page title
$page_title = "Status Dashboard";

// Get current date
$today = date('Y-m-d');

// SUBJECT PROGRESS
// Only fetch English progress since math tables don't exist
$english_query = "
    SELECT 
        COUNT(DISTINCT t.id) as total_topics,
        SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed_topics,
        ROUND(AVG(p.confidence_level)) as avg_confidence
    FROM eng_topics t
    LEFT JOIN eng_topic_progress p ON t.id = p.topic_id
";
$english_result = $conn->query($english_query);
$english_data = $english_result ? $english_result->fetch_assoc() : null;

$english_total = $english_data ? ($english_data['total_topics'] ?: 0) : 0;
$english_completed = $english_data ? ($english_data['completed_topics'] ?: 0) : 0;
$english_confidence = $english_data ? ($english_data['avg_confidence'] ?: 0) : 0;
$english_progress = $english_total > 0 ? round(($english_completed / $english_total) * 100) : 0;

// Set math values to 0 since we don't have math tables
$math_total = 0;
$math_completed = 0;
$math_confidence = 0;
$math_progress = 0;

// TASK COMPLETION
$tasks_query = "
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status != 'completed' AND due_date < CURRENT_DATE THEN 1 ELSE 0 END) as overdue_tasks,
        SUM(CASE WHEN status != 'completed' AND due_date = CURRENT_DATE THEN 1 ELSE 0 END) as today_tasks,
        SUM(CASE WHEN status != 'completed' AND due_date > CURRENT_DATE THEN 1 ELSE 0 END) as upcoming_tasks
    FROM tasks
";
$tasks_result = $conn->query($tasks_query);
$tasks_data = $tasks_result->fetch_assoc();

// HABIT COMPLETION
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
$habits_result = $conn->query($habits_query);
$habits_data = $habits_result->fetch_assoc();

// EXAM COUNTDOWN
$exams_query = "
    SELECT 
        e.*, 
        DATEDIFF(e.exam_date, CURRENT_DATE) as days_remaining
    FROM exams e
    WHERE e.exam_date >= CURRENT_DATE
    ORDER BY e.exam_date ASC
    LIMIT 5
";
$exams_result = $conn->query($exams_query);

// STUDY SESSIONS
$study_query = "
    SELECT 
        SUM(duration) as total_duration,
        AVG(productivity_rating) as avg_productivity,
        COUNT(*) as total_sessions,
        DATE_FORMAT(MAX(session_date), '%Y-%m-%d') as last_session_date,
        DATEDIFF(CURRENT_DATE, MAX(session_date)) as days_since_last
    FROM study_sessions
";
$study_result = $conn->query($study_query);
$study_data = $study_result->fetch_assoc();

$total_study_hours = $study_data['total_duration'] ? round($study_data['total_duration'] / 60, 1) : 0;
$avg_productivity = $study_data['avg_productivity'] ? round($study_data['avg_productivity'], 1) : 0;
$days_since_study = $study_data['days_since_last'] ?: 0;

// RECENT ACTIVITY
$activity_query = "
    (SELECT 
        'task' as type,
        id,
        'Task completed: ' || title as description,
        completion_date as activity_date
     FROM tasks
     WHERE status = 'completed'
     ORDER BY completion_date DESC
     LIMIT 5)
    UNION
    (SELECT 
        'habit' as type,
        habit_id as id,
        'Habit tracked: ' || (SELECT name FROM habits WHERE id = habit_id) as description,
        tracking_date as activity_date
     FROM habit_tracking
     WHERE status = 'completed'
     ORDER BY tracking_date DESC
     LIMIT 5)
    UNION
    (SELECT 
        'session' as type,
        id,
        'Study session: ' || subject as description,
        session_date as activity_date
     FROM study_sessions
     ORDER BY session_date DESC
     LIMIT 5)
    ORDER BY activity_date DESC
    LIMIT 10
";

// Modified query that should work with MySQL
$activity_query = "
    (SELECT 
        'task' as type,
        id,
        CONCAT('Task completed: ', title) as description,
        completion_date as activity_date
     FROM tasks
     WHERE status = 'completed' AND completion_date IS NOT NULL
     ORDER BY completion_date DESC
     LIMIT 5)
    UNION
    (SELECT 
        'habit' as type,
        habit_id as id,
        CONCAT('Habit tracked: ', (SELECT name FROM habits WHERE id = habit_id)) as description,
        tracking_date as activity_date
     FROM habit_tracking
     WHERE status = 'completed'
     ORDER BY tracking_date DESC
     LIMIT 5)
    UNION
    (SELECT 
        'session' as type,
        id,
        CONCAT('Study session: ', subject) as description,
        session_date as activity_date
     FROM study_sessions
     ORDER BY session_date DESC
     LIMIT 5)
    ORDER BY activity_date DESC
    LIMIT 10
";

$activity_result = $conn->query($activity_query);

// ASSIGNMENTS PROGRESS
$assignments_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN progress > 0 AND progress < 100 THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN progress = 0 THEN 1 ELSE 0 END) as not_started,
        AVG(progress) as avg_progress
    FROM assignments
";
$assignments_result = $conn->query($assignments_query);
$assignments_data = $assignments_result->fetch_assoc();

// Include header
include __DIR__ . '/includes/header.php';
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
                                <?php 
                                    $overall_topics = $math_total + $english_total;
                                    $overall_completed = $math_completed + $english_completed;
                                    $overall_percentage = $overall_topics > 0 ? round(($overall_completed / $overall_topics) * 100) : 0;
                                    echo $overall_percentage . '%';
                                ?>
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
                                    $task_percentage = $tasks_data['total_tasks'] > 0 ? 
                                        round(($tasks_data['completed_tasks'] / $tasks_data['total_tasks']) * 100) : 0;
                                    echo $task_percentage . '%';
                                ?>
                            </div>
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $task_percentage; ?>%"
                                     aria-valuenow="<?php echo $task_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
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
                                    $habit_percentage = $habits_data['total_habits'] > 0 ? 
                                        round(($habits_data['completed_today'] / $habits_data['total_habits']) * 100) : 0;
                                    echo $habit_percentage . '%';
                                ?>
                            </div>
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $habit_percentage; ?>%"
                                     aria-valuenow="<?php echo $habit_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
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
                           data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="subjectDropdown">
                            <a class="dropdown-item" href="pages/subjects/math.php">View Mathematics</a>
                            <a class="dropdown-item" href="pages/subjects/english.php">View English</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="pages/subjects.php">All Subjects</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Mathematics</h6>
                            <div class="progress mb-1" style="height: 20px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $math_progress; ?>%"
                                     aria-valuenow="<?php echo $math_progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $math_progress; ?>%
                                </div>
                            </div>
                            <div class="row mt-2 px-1">
                                <div class="col">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-book"></i> <?php echo $math_completed; ?>/<?php echo $math_total; ?> topics completed
                                    </span>
                                </div>
                                <div class="col text-end">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-star"></i> Avg. confidence: <?php echo $math_confidence; ?>/5
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">English</h6>
                            <div class="progress mb-1" style="height: 20px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $english_progress; ?>%"
                                     aria-valuenow="<?php echo $english_progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $english_progress; ?>%
                                </div>
                            </div>
                            <div class="row mt-2 px-1">
                                <div class="col">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-book"></i> <?php echo $english_completed; ?>/<?php echo $english_total; ?> topics completed
                                    </span>
                                </div>
                                <div class="col text-end">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-star"></i> Avg. confidence: <?php echo $english_confidence; ?>/5
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <h6 class="font-weight-bold">Section Breakdown</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Section</th>
                                            <th>Progress</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Section Breakdown query - only for English
                                        $sections_query = "
                                            SELECT 
                                                'English' as subject,
                                                s.name as section, 
                                                COUNT(t.id) as total_topics,
                                                SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed_topics,
                                                ROUND((SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100) as progress
                                            FROM eng_sections s
                                            JOIN eng_subsections sub ON s.id = sub.section_id
                                            JOIN eng_topics t ON sub.id = t.subsection_id
                                            LEFT JOIN eng_topic_progress p ON t.id = p.topic_id
                                            GROUP BY s.name
                                            ORDER BY progress DESC
                                            LIMIT 5
                                        ";
                                        $sections_result = $conn->query($sections_query);
                                        
                                        while ($section = $sections_result->fetch_assoc()):
                                            $progress_class = 'bg-danger';
                                            if ($section['progress'] >= 75) {
                                                $progress_class = 'bg-success';
                                            } elseif ($section['progress'] >= 50) {
                                                $progress_class = 'bg-info';
                                            } elseif ($section['progress'] >= 25) {
                                                $progress_class = 'bg-warning';
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo $section['subject']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $section['section']; ?></td>
                                            <td width="40%">
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar <?php echo $progress_class; ?>" role="progressbar"
                                                         style="width: <?php echo $section['progress']; ?>%" 
                                                         aria-valuenow="<?php echo $section['progress']; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo $section['completed_topics']; ?>/<?php echo $section['total_topics']; ?> 
                                                (<?php echo $section['progress']; ?>%)
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Exam Countdown -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Upcoming Exams</h6>
                    <a href="pages/exam_countdown.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-calendar-alt"></i> View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($exams_result->num_rows > 0): ?>
                        <?php while ($exam = $exams_result->fetch_assoc()): ?>
                            <div class="exam-countdown mb-3">
                                <h6 class="font-weight-bold mb-1"><?php echo htmlspecialchars($exam['exam_name']); ?></h6>
                                <div class="progress mb-2" style="height: 5px;">
                                    <?php 
                                        // Calculate percentage of time left
                                        $total_prep_time = (strtotime($exam['exam_date']) - strtotime($exam['created_at'])) / (60 * 60 * 24);
                                        $time_left_percent = $total_prep_time > 0 ? 
                                            min(100, round(($exam['days_remaining'] / $total_prep_time) * 100)) : 0;
                                        $progress_class = $exam['days_remaining'] <= 7 ? 'bg-danger' : 
                                                        ($exam['days_remaining'] <= 30 ? 'bg-warning' : 'bg-info');
                                    ?>
                                    <div class="progress-bar <?php echo $progress_class; ?>" role="progressbar" 
                                         style="width: <?php echo $time_left_percent; ?>%" 
                                         aria-valuenow="<?php echo $time_left_percent; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="small text-muted">
                                        <i class="far fa-calendar-alt"></i> 
                                        <?php echo date('j M Y, g:i A', strtotime($exam['exam_date'])); ?>
                                    </div>
                                    <div>
                                        <span class="badge <?php echo $exam['days_remaining'] <= 7 ? 'bg-danger' : 
                                            ($exam['days_remaining'] <= 30 ? 'bg-warning' : 'bg-primary'); ?>">
                                            <?php echo $exam['days_remaining']; ?> days left
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="far fa-calendar-check fa-3x mb-3 text-muted"></i>
                            <p>No upcoming exams</p>
                            <a href="pages/exams.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Add Exam
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tasks and Assignments -->
    <div class="row mb-4">
        <!-- Task Overview -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Task Overview</h6>
                    <a href="pages/tasks/" class="btn btn-sm btn-primary">
                        <i class="fas fa-tasks"></i> Manage Tasks
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="card border-left-danger h-100 py-2">
                                <div class="card-body py-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Overdue
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $tasks_data['overdue_tasks']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-times fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="card border-left-warning h-100 py-2">
                                <div class="card-body py-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Today
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $tasks_data['today_tasks']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-info h-100 py-2">
                                <div class="card-body py-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Upcoming
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $tasks_data['upcoming_tasks']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="taskProgressChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Assignment Progress -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Assignment Progress</h6>
                    <a href="pages/assignments.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-file-alt"></i> View Assignments
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="card border-left-danger h-100 py-2">
                                <div class="card-body py-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Not Started
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $assignments_data['not_started']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="card border-left-warning h-100 py-2">
                                <div class="card-body py-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                In Progress
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $assignments_data['in_progress']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-spinner fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-success h-100 py-2">
                                <div class="card-body py-2">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Completed
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $assignments_data['completed']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="assignmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Habits and Recent Activity -->
    <div class="row mb-4">
        <!-- Habit Tracker -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Habit Tracking</h6>
                    <a href="pages/habits/" class="btn btn-sm btn-primary">
                        <i class="fas fa-check"></i> View Habits
                    </a>
                </div>
                <div class="card-body">
                    <!-- Habits Today -->
                    <h6 class="font-weight-bold mb-3">Today's Habits</h6>
                    <?php 
                    // Fetch habits for today
                    $todays_habits_query = "
                        SELECT 
                            h.id, 
                            h.name,
                            h.description,
                            c.name as category,
                            c.color as category_color,
                            c.icon as category_icon,
                            COALESCE(ht.status, 'pending') as status
                        FROM habits h
                        LEFT JOIN habit_categories c ON h.category_id = c.id
                        LEFT JOIN habit_tracking ht ON h.id = ht.habit_id AND DATE(ht.tracking_date) = CURRENT_DATE
                        WHERE h.is_active = 1
                        ORDER BY h.name ASC
                        LIMIT 5
                    ";
                    $todays_habits_result = $conn->query($todays_habits_query);
                    ?>
                    
                    <?php if ($todays_habits_result->num_rows > 0): ?>
                        <div class="habit-list">
                            <?php while ($habit = $todays_habits_result->fetch_assoc()): ?>
                                <div class="habit-item d-flex align-items-center p-2 mb-2 border rounded">
                                    <div class="me-3">
                                        <span class="habit-icon" style="background-color: <?php echo $habit['category_color']; ?>">
                                            <i class="<?php echo $habit['category_icon']; ?>"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($habit['name']); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo htmlspecialchars(substr($habit['description'], 0, 50)); ?></p>
                                    </div>
                                    <div>
                                        <?php if ($habit['status'] == 'completed'): ?>
                                            <span class="badge bg-success rounded-pill">Completed</span>
                                        <?php elseif ($habit['status'] == 'skipped'): ?>
                                            <span class="badge bg-secondary rounded-pill">Skipped</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning rounded-pill">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="far fa-calendar-alt fa-3x mb-3 text-muted"></i>
                            <p>No habits scheduled</p>
                            <a href="pages/habits/manage_habits.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Add Habit
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Habit Streak -->
                    <h6 class="font-weight-bold mb-3 mt-4">Streak Analysis</h6>
                    <?php 
                    // Fetch top 3 habit streaks
                    $streak_query = "
                        SELECT 
                            h.name,
                            (
                                SELECT COUNT(*)
                                FROM (
                                    SELECT 
                                        t1.habit_id,
                                        t1.tracking_date,
                                        DATE_SUB(t1.tracking_date, INTERVAL ROW_NUMBER() OVER (PARTITION BY t1.habit_id ORDER BY t1.tracking_date) DAY) as grp
                                    FROM habit_tracking t1
                                    WHERE t1.status = 'completed'
                                    ORDER BY t1.habit_id, t1.tracking_date
                                ) as t2
                                WHERE t2.habit_id = h.id
                                GROUP BY t2.habit_id, t2.grp
                                ORDER BY COUNT(*) DESC
                                LIMIT 1
                            ) as max_streak
                        FROM habits h
                        WHERE h.is_active = 1
                        ORDER BY max_streak DESC
                        LIMIT 3
                    ";
                    
                    // Simplified query that works in MySQL
                    $streak_query = "
                        SELECT 
                            h.id,
                            h.name,
                            COUNT(ht.id) as completion_count
                        FROM habits h
                        LEFT JOIN habit_tracking ht ON h.id = ht.habit_id AND ht.status = 'completed'
                        WHERE h.is_active = 1
                        GROUP BY h.id, h.name
                        ORDER BY completion_count DESC
                        LIMIT 3
                    ";
                    
                    $streak_result = $conn->query($streak_query);
                    ?>
                    
                    <div class="row">
                        <?php while ($habit = $streak_result->fetch_assoc()): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card border">
                                    <div class="card-body py-2 text-center">
                                        <h6 class="text-truncate"><?php echo htmlspecialchars($habit['name']); ?></h6>
                                        <div class="streak-count font-weight-bold h4">
                                            <?php echo $habit['completion_count']; ?>
                                        </div>
                                        <div class="small text-muted">completions</div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold">Recent Activity</h6>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        <?php if ($activity_result && $activity_result->num_rows > 0): ?>
                            <?php while ($activity = $activity_result->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="activity-icon 
                                        <?php echo $activity['type'] == 'task' ? 'bg-info' : 
                                            ($activity['type'] == 'habit' ? 'bg-success' : 'bg-primary'); ?>">
                                        <i class="fas 
                                            <?php echo $activity['type'] == 'task' ? 'fa-tasks' : 
                                                ($activity['type'] == 'habit' ? 'fa-check' : 'fa-book'); ?>">
                                        </i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-text"><?php echo htmlspecialchars($activity['description']); ?></div>
                                        <div class="activity-date">
                                            <?php 
                                                $activity_date = new DateTime($activity['activity_date']);
                                                $now = new DateTime();
                                                $diff = $activity_date->diff($now);
                                                
                                                if ($diff->d == 0) {
                                                    if ($diff->h == 0) {
                                                        if ($diff->i == 0) {
                                                            echo 'Just now';
                                                        } else {
                                                            echo $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
                                                        }
                                                    } else {
                                                        echo $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                                                    }
                                                } elseif ($diff->d < 7) {
                                                    echo $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
                                                } else {
                                                    echo $activity_date->format('j M Y');
                                                }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="far fa-clock fa-3x mb-3 text-muted"></i>
                                <p>No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>
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

    // Assignment Chart
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

    // Refresh dashboard button
    document.getElementById('refreshDashboard').addEventListener('click', function() {
        location.reload();
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
include __DIR__ . '/includes/footer.php';

// Close database connection
close_connection($conn);
?>
