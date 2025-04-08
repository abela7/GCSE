<?php
// Set page title
$page_title = "Dashboard";

// Include database connection
require_once '../config/db_connect.php';

// Get subjects with their progress
$subjects_query = "SELECT s.*, 
    (SELECT COUNT(DISTINCT t.id) 
     FROM math_topics t 
     JOIN math_subsections sub ON t.subsection_id = sub.id 
     JOIN math_sections sec ON sub.section_id = sec.id 
     WHERE s.id = 2) + 
    (SELECT COUNT(DISTINCT t.id) 
     FROM eng_topics t 
     JOIN eng_subsections sub ON t.subsection_id = sub.id 
     JOIN eng_sections sec ON sub.section_id = sec.id 
     WHERE s.id = 1) as total_topics,
    (SELECT COUNT(DISTINCT t.id) 
     FROM math_topics t 
     JOIN math_subsections sub ON t.subsection_id = sub.id 
     JOIN math_sections sec ON sub.section_id = sec.id 
     JOIN topic_progress tp ON t.id = tp.topic_id 
     WHERE s.id = 2 AND tp.status = 'completed') +
    (SELECT COUNT(DISTINCT t.id) 
     FROM eng_topics t 
     JOIN eng_subsections sub ON t.subsection_id = sub.id 
     JOIN eng_sections sec ON sub.section_id = sec.id 
     JOIN eng_topic_progress tp ON t.id = tp.topic_id 
     WHERE s.id = 1 AND tp.status = 'completed') as completed_topics
FROM subjects s";

$subjects_result = $conn->query($subjects_query);

// Get upcoming exams (next 30 days)
$exams_query = "SELECT e.*, s.name as subject_name, s.color as subject_color 
                FROM exams e 
                JOIN subjects s ON e.subject_id = s.id 
                WHERE e.exam_date > NOW() AND e.exam_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                ORDER BY e.exam_date ASC 
                LIMIT 3";
$exams_result = $conn->query($exams_query);

// Get recent tasks
$tasks_query = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM tasks t 
                LEFT JOIN task_categories c ON t.category_id = c.id 
                WHERE t.status != 'completed'
                ORDER BY t.due_date ASC 
                LIMIT 5";
$tasks_result = $conn->query($tasks_query);

// Get habit completion stats
$habits_query = "SELECT COUNT(*) as total_habits, 
                SUM(CASE WHEN EXISTS (
                    SELECT 1 FROM habit_completions hc 
                    WHERE hc.habit_id = h.id 
                    AND hc.completion_date = CURDATE() 
                    AND hc.status = 'completed'
                ) THEN 1 ELSE 0 END) as completed_today
                FROM habits h
                WHERE h.is_active = 1";
$habits_result = $conn->query($habits_query);
$habits_stats = $habits_result->fetch_assoc();

// Include header
include '../includes/header.php';
?>

<style>
/* Card Styles */
.feature-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    height: 100%;
    overflow: hidden;
}
.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}
.feature-card .card-body {
    padding: 1.5rem;
}
.feature-card .icon-bg {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}
.feature-card .icon-bg i {
    font-size: 1.5rem;
    color: white;
}
.feature-card h5 {
    font-weight: 600;
    margin-bottom: 0.75rem;
}
.feature-card p {
    color: #6c757d;
    margin-bottom: 1rem;
}
.feature-card .card-footer {
    background: transparent;
    border-top: 1px solid rgba(0,0,0,0.05);
    padding: 0.75rem 1.5rem;
}

/* Progress Styles */
.progress {
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
}
.progress-label {
    font-size: 0.875rem;
    font-weight: 500;
}

/* Stats Card */
.stats-card {
    border: none;
    border-radius: 10px;
    background: #f8f9fa;
    padding: 1rem;
    height: 100%;
}
.stats-card .stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}
.stats-card .stat-label {
    color: #6c757d;
    font-size: 0.875rem;
}

/* Section Headings */
.section-heading {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}
.section-heading h4 {
    font-weight: 600;
    margin-bottom: 0;
}
.section-heading .line {
    flex-grow: 1;
    height: 1px;
    background-color: #e9ecef;
    margin-left: 1rem;
}

/* Responsive Adjustments */
@media (max-width: 767.98px) {
    .feature-card .icon-bg {
        width: 50px;
        height: 50px;
    }
    .feature-card .icon-bg i {
        font-size: 1.25rem;
    }
    .stats-card .stat-value {
        font-size: 1.5rem;
    }
}
</style>

<div class="container-fluid py-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card feature-card bg-primary text-white">
                <div class="card-body">
                    <h3 class="mb-2">Welcome to Your GCSE Dashboard</h3>
                    <p class="mb-0">Track your progress, manage your studies, and stay organized with all your GCSE preparation tools in one place.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="section-heading">
        <h4><i class="fas fa-chart-line me-2"></i>Your Progress</h4>
        <div class="line"></div>
    </div>
    
    <div class="row mb-4">
        <?php 
        // Reset the result pointer
        $subjects_result->data_seek(0);
        while($subject = $subjects_result->fetch_assoc()): 
            $progress = $subject['total_topics'] > 0 ? 
                round(($subject['completed_topics'] / $subject['total_topics']) * 100) : 0;
            // Override Math color to blue
            $subject_color = $subject['name'] === 'Math' ? '#007bff' : $subject['color'];
        ?>
        <div class="col-md-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <span class="badge me-2" style="background-color: <?php echo $subject_color; ?>">
                                <?php echo $subject['name']; ?>
                            </span>
                            Progress
                        </h5>
                        <span class="progress-label"><?php echo $progress; ?>% Complete</span>
                    </div>
                    <div class="progress mb-3">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?php echo $progress; ?>%; background-color: <?php echo $subject_color; ?>" 
                             aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span><?php echo $subject['completed_topics']; ?> topics completed</span>
                        <span><?php echo $subject['total_topics']; ?> total topics</span>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="/pages/subjects/<?php echo strtolower($subject['name']); ?>.php" class="btn btn-sm btn-outline-primary">View Details</a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Main Features Navigation -->
    <div class="section-heading">
        <h4><i class="fas fa-th-large me-2"></i>Main Features</h4>
        <div class="line"></div>
    </div>
    
    <div class="row mb-4">
        <!-- Study Planner -->
        <div class="col-md-4 col-sm-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg bg-primary">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h5>Study Planner</h5>
                    <p>Plan your study sessions, track your progress, and manage your time effectively.</p>
                </div>
                <div class="card-footer">
                    <a href="/pages/planner.php" class="btn btn-sm btn-outline-primary">Open Planner</a>
                </div>
            </div>
        </div>
        
        <!-- Task Manager -->
        <div class="col-md-4 col-sm-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg bg-success">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h5>Task Manager</h5>
                    <p>Create, organize, and complete tasks to stay on top of your studies and assignments.</p>
                </div>
                <div class="card-footer">
                    <a href="/pages/tasks.php" class="btn btn-sm btn-outline-success">Manage Tasks</a>
                </div>
            </div>
        </div>
        
        <!-- Habit Tracker -->
        <div class="col-md-4 col-sm-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg bg-info">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h5>Habit Tracker</h5>
                    <p>Build and maintain productive study habits with daily tracking and streaks.</p>
                </div>
                <div class="card-footer">
                    <a href="/pages/habits.php" class="btn btn-sm btn-outline-info">Track Habits</a>
                </div>
            </div>
        </div>
        
        <!-- Mood Tracker -->
        <div class="col-md-4 col-sm-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg bg-warning">
                        <i class="fas fa-smile"></i>
                    </div>
                    <h5>Mood Tracker</h5>
                    <p>Monitor your emotional well-being and identify patterns to optimize your study performance.</p>
                </div>
                <div class="card-footer">
                    <a href="/pages/mood_tracking/index.php" class="btn btn-sm btn-outline-warning">Track Mood</a>
                </div>
            </div>
        </div>
        
        <!-- English Practice -->
        <div class="col-md-4 col-sm-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg bg-danger">
                        <i class="fas fa-book"></i>
                    </div>
                    <h5>English Practice</h5>
                    <p>Improve your language skills with comprehensive English practice materials and exercises.</p>
                </div>
                <div class="card-footer">
                    <a href="/pages/subjects/english.php" class="btn btn-sm btn-outline-danger">Practice English</a>
                </div>
            </div>
        </div>
        
        <!-- Math Practice -->
        <div class="col-md-4 col-sm-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg bg-primary">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h5>Math Practice</h5>
                    <p>Master mathematical concepts with interactive practice problems and step-by-step solutions.</p>
                </div>
                <div class="card-footer">
                    <a href="/pages/subjects/math.php" class="btn btn-sm btn-outline-primary">Practice Math</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Features -->
    <div class="section-heading">
        <h4><i class="fas fa-tools me-2"></i>Additional Tools</h4>
        <div class="line"></div>
    </div>
    
    <div class="row mb-4">
        <!-- Resources -->
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg bg-secondary">
                        <i class="fas fa-folder"></i>
                    </div>
                    <h5>Resources</h5>
                    <p>Access study materials, guides, and references.</p>
                </div>
                <div class="card-footer">
                    <a href="/pages/resources.php" class="btn btn-sm btn-outline-secondary">View Resources</a>
                </div>
            </div>
        </div>
        
        <!-- Exams -->
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg bg-danger">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h5>Exams</h5>
                    <p>Manage exam schedules and preparation plans.</p>
                </div>
                <div class="card-footer">
                    <a href="/pages/exams.php" class="btn btn-sm btn-outline-danger">View Exams</a>
                </div>
            </div>
        </div>
        
        <!-- Today's Plan -->
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg bg-success">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <h5>Today</h5>
                    <p>See your schedule and tasks for today.</p>
                </div>
                <div class="card-footer">
                    <a href="/pages/Today.php" class="btn btn-sm btn-outline-success">View Today</a>
                </div>
            </div>
        </div>
        
        <!-- Notes -->
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg bg-info">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <h5>Notes</h5>
                    <p>Create and organize your study notes.</p>
                </div>
                <div class="card-footer">
                    <a href="/pages/notes.php" class="btn btn-sm btn-outline-info">View Notes</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Exams and Tasks -->
    <div class="row mb-4">
        <!-- Upcoming Exams -->
        <div class="col-md-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-calendar-alt me-2 text-danger"></i>Upcoming Exams</h5>
                    <?php if ($exams_result->num_rows > 0): ?>
                        <?php 
                        $now = new DateTime();
                        while ($exam = $exams_result->fetch_assoc()): 
                            $exam_date = new DateTime($exam['exam_date']);
                            $interval = $now->diff($exam_date);
                            $days_remaining = $interval->days;
                        ?>
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="badge me-2" style="background-color: <?php echo htmlspecialchars($exam['subject_color']); ?>">
                                        <?php echo htmlspecialchars($exam['subject_name']); ?>
                                    </span>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($exam['title']); ?></h6>
                                </div>
                                <div class="text-muted small">
                                    <i class="fas fa-calendar me-1"></i> <?php echo $exam_date->format('D, j M Y'); ?> at <?php echo $exam_date->format('g:i A'); ?>
                                </div>
                            </div>
                            <span class="badge bg-<?php echo $days_remaining <= 7 ? 'danger' : ($days_remaining <= 14 ? 'warning' : 'primary'); ?>">
                                <?php echo $days_remaining; ?> days
                            </span>
                        </div>
                        <?php endwhile; ?>
                        <div class="text-end">
                            <a href="/pages/exams.php" class="btn btn-sm btn-outline-primary">View All Exams</a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No upcoming exams in the next 30 days.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Tasks -->
        <div class="col-md-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-tasks me-2 text-success"></i>Pending Tasks</h5>
                    <?php if ($tasks_result && $tasks_result->num_rows > 0): ?>
                        <?php 
                        while ($task = $tasks_result->fetch_assoc()): 
                            $due_date = new DateTime($task['due_date']);
                            $now = new DateTime();
                            $is_overdue = $due_date < $now;
                        ?>
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-1">
                                    <?php if (!empty($task['category_name'])): ?>
                                    <span class="badge me-2" style="background-color: <?php echo htmlspecialchars($task['category_color']); ?>">
                                        <?php echo htmlspecialchars($task['category_name']); ?>
                                    </span>
                                    <?php endif; ?>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($task['title']); ?></h6>
                                </div>
                                <div class="text-muted small">
                                    <i class="fas fa-calendar me-1"></i> Due: <?php echo $due_date->format('D, j M Y'); ?>
                                </div>
                            </div>
                            <span class="badge <?php echo $is_overdue ? 'bg-danger' : 'bg-warning'; ?>">
                                <?php echo $is_overdue ? 'Overdue' : 'Pending'; ?>
                            </span>
                        </div>
                        <?php endwhile; ?>
                        <div class="text-end">
                            <a href="/pages/tasks.php" class="btn btn-sm btn-outline-primary">View All Tasks</a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No pending tasks. Great job!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Mood Tracking Widget -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card feature-card">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-smile me-2 text-warning"></i>Mood Tracking</h5>
                    <?php include '../includes/mood_widget.php'; ?>
                    <div class="text-end mt-3">
                        <a href="/pages/mood_tracking/index.php" class="btn btn-sm btn-outline-warning">Open Mood Tracker</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
close_connection($conn);
?>
