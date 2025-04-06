<?php
// Set page title
$page_title = "Today";

// Include database connection
require_once '../config/db_connect.php';

// Get today's date in Y-m-d format
$today = date('Y-m-d');

// Get tasks for today (including recurring tasks)
$tasks_query = "SELECT t.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
                COALESCE(ti.status, t.status) as effective_status
                FROM tasks t 
                JOIN task_categories c ON t.category_id = c.id 
                LEFT JOIN task_instances ti ON t.id = ti.task_id AND ti.due_date = '$today'
                WHERE (t.task_type = 'one-time' AND t.due_date = '$today' AND t.status != 'completed')
                OR (t.task_type = 'recurring' AND ti.id IS NOT NULL AND ti.status != 'completed')
                ORDER BY t.priority DESC, t.due_date ASC";
$tasks_result = $conn->query($tasks_query);

// Get habits for today
$habits_query = "SELECT h.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
                 hp.status as today_status
                 FROM habits h
                 JOIN habit_categories c ON h.category_id = c.id
                 LEFT JOIN habit_progress hp ON h.id = hp.habit_id AND hp.date = '$today'
                 WHERE h.is_active = 1
                 AND (hp.id IS NULL OR hp.status != 'completed')
                 ORDER BY h.target_time ASC";
$habits_result = $conn->query($habits_query);

// Get upcoming exams (next 30 days)
$exams_query = "SELECT e.*, s.name as subject_name, s.color as subject_color,
                DATEDIFF(e.exam_date, CURRENT_DATE) as days_remaining
                FROM exams e 
                JOIN subjects s ON e.subject_id = s.id 
                WHERE e.exam_date > CURRENT_DATE 
                AND e.exam_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)
                ORDER BY e.exam_date ASC";
$exams_result = $conn->query($exams_query);

// Get upcoming assignments (next 7 days)
$assignments_query = "SELECT t.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
                     DATEDIFF(t.due_date, CURRENT_DATE) as days_remaining
                     FROM tasks t 
                     JOIN task_categories c ON t.category_id = c.id
                     WHERE t.due_date > CURRENT_DATE 
                     AND t.due_date <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
                     AND t.category_id IN (SELECT id FROM task_categories WHERE name LIKE '%Assignment%' OR name LIKE '%Homework%')
                     AND t.status != 'completed'
                     ORDER BY t.due_date ASC, t.priority DESC
                     LIMIT 5";
$assignments_result = $conn->query($assignments_query);

// Get exam reports for today
$exam_reports_query = "SELECT er.*, e.title as exam_title, s.name as subject_name, s.color as subject_color
                      FROM exam_reports er
                      JOIN exams e ON er.exam_id = e.id
                      JOIN subjects s ON e.subject_id = s.id
                      WHERE DATE(er.date) = '$today'
                      ORDER BY er.date DESC";
$exam_reports_result = $conn->query($exam_reports_query);

// Get study progress for today
$progress_query = "SELECT 
                    (SELECT COUNT(*) FROM topic_progress WHERE DATE(last_studied) = '$today') +
                    (SELECT COUNT(*) FROM eng_topic_progress WHERE DATE(last_studied) = '$today') as topics_studied,
                    (SELECT SUM(total_time_spent) FROM topic_progress WHERE DATE(last_studied) = '$today') +
                    (SELECT SUM(total_time_spent) FROM eng_topic_progress WHERE DATE(last_studied) = '$today') as total_study_time";
$progress_result = $conn->query($progress_query);
$progress = $progress_result->fetch_assoc();

// Include header
include '../includes/header.php';
?>

<style>
.task-card, .habit-card, .overview-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    margin-bottom: 1rem;
    border-radius: 12px;
}
.task-card:hover, .habit-card:hover, .overview-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.priority-indicator {
    width: 4px;
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    border-radius: 4px 0 0 4px;
}
.priority-high { background-color: #dc3545; }
.priority-medium { background-color: #ffc107; }
.priority-low { background-color: #28a745; }
.exam-countdown {
    font-size: 1.1rem;
    font-weight: 500;
    color: #1a1a1a;
}
.countdown-urgent {
    color: #dc3545;
}
.countdown-warning {
    color: #ffc107;
}
.countdown-safe {
    color: #28a745;
}
.progress-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: conic-gradient(var(--progress-color) var(--progress), #eee 0deg);
    position: relative;
}
.progress-circle::before {
    content: '';
    position: absolute;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: white;
}
.progress-circle-content {
    position: relative;
    z-index: 1;
    text-align: center;
}
.accordion-button:not(.collapsed) {
    background-color: rgba(205, 175, 86, 0.1);
    color: var(--primary-color);
}
.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(205, 175, 86, 0.25);
}
.overview-icon {
    font-size: 2rem;
    color: #cdaf56;
    margin-bottom: 1rem;
}
.overview-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.overview-subtitle {
    font-size: 0.9rem;
    color: #6c757d;
}
.study-stat {
    text-align: center;
    padding: 1rem;
    background: rgba(205, 175, 86, 0.1);
    border-radius: 12px;
}
.stat-number {
    font-size: 2rem;
    font-weight: 600;
    color: #cdaf56;
}
.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <!-- Study Overview Accordion -->
            <div class="accordion mb-4" id="studyOverviewAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#studyOverviewCollapse">
                            <i class="fas fa-graduation-cap me-2"></i>Study Overview
                        </button>
                    </h2>
                    <div id="studyOverviewCollapse" class="accordion-collapse collapse show" data-bs-parent="#studyOverviewAccordion">
                        <div class="accordion-body">
                            <div class="row g-4">
                                <!-- Upcoming Assignments -->
                                <div class="col-md-6">
                                    <div class="overview-card">
                                        <div class="card-body">
                                            <div class="text-center mb-3">
                                                <i class="fas fa-book-reader overview-icon"></i>
                                                <h5 class="overview-title">Upcoming Assignments</h5>
                                            </div>
                                            <?php if ($assignments_result->num_rows > 0): ?>
                                                <?php while ($assignment = $assignments_result->fetch_assoc()): ?>
                                                    <div class="d-flex align-items-center mb-3">
                                                        <div class="flex-grow-1">
                                                            <div class="fw-medium"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                                            <small class="text-muted">Due in <?php echo $assignment['days_remaining']; ?> days</small>
                                                        </div>
                                                        <span class="badge" style="background-color: <?php echo htmlspecialchars($assignment['category_color']); ?>">
                                                            <?php echo htmlspecialchars($assignment['category_name']); ?>
                                                        </span>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p class="text-muted text-center mb-0">No upcoming assignments</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Upcoming Exams -->
                                <div class="col-md-6">
                                    <div class="overview-card">
                                        <div class="card-body">
                                            <div class="text-center mb-3">
                                                <i class="fas fa-file-alt overview-icon"></i>
                                                <h5 class="overview-title">Exam Countdown</h5>
                                            </div>
                                            <?php if ($exams_result->num_rows > 0): ?>
                                                <?php while ($exam = $exams_result->fetch_assoc()): ?>
                                                    <div class="d-flex align-items-center mb-3">
                                                        <div class="flex-grow-1">
                                                            <div class="fw-medium"><?php echo htmlspecialchars($exam['title']); ?></div>
                                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($exam['exam_date'])); ?></small>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="exam-countdown <?php 
                                                                echo $exam['days_remaining'] <= 7 ? 'countdown-urgent' : 
                                                                    ($exam['days_remaining'] <= 14 ? 'countdown-warning' : 'countdown-safe'); 
                                                            ?>">
                                                                <?php echo $exam['days_remaining']; ?> days
                                                            </div>
                                                            <span class="badge" style="background-color: <?php echo htmlspecialchars($exam['subject_color']); ?>">
                                                                <?php echo htmlspecialchars($exam['subject_name']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p class="text-muted text-center mb-0">No upcoming exams</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Today's Study Stats -->
                                <div class="col-12">
                                    <div class="overview-card">
                                        <div class="card-body">
                                            <div class="text-center mb-3">
                                                <i class="fas fa-chart-line overview-icon"></i>
                                                <h5 class="overview-title">Today's Progress</h5>
                                            </div>
                                            <div class="row g-4">
                                                <div class="col-md-6">
                                                    <div class="study-stat">
                                                        <div class="stat-number"><?php echo $progress['topics_studied'] ?: 0; ?></div>
                                                        <div class="stat-label">Topics Studied</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="study-stat">
                                                        <div class="stat-number">
                                                            <?php 
                                                            $hours = floor(($progress['total_study_time'] ?: 0) / 3600);
                                                            $minutes = floor((($progress['total_study_time'] ?: 0) % 3600) / 60);
                                                            echo $hours > 0 ? $hours . 'h ' : '';
                                                            echo $minutes . 'm';
                                                            ?>
                                                        </div>
                                                        <div class="stat-label">Study Time</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks Accordion -->
            <div class="accordion mb-4" id="tasksAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#tasksCollapse">
                            <i class="fas fa-tasks me-2"></i>Today's Tasks
                        </button>
                    </h2>
                    <div id="tasksCollapse" class="accordion-collapse collapse show" data-bs-parent="#tasksAccordion">
                        <div class="accordion-body">
                            <?php if ($tasks_result->num_rows > 0): ?>
                                <?php while ($task = $tasks_result->fetch_assoc()): ?>
                                    <div class="card task-card">
                                        <div class="priority-indicator priority-<?php echo strtolower($task['priority']); ?>"></div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                                    <?php if ($task['category_name']): ?>
                                                        <span class="badge" style="background-color: <?php echo htmlspecialchars($task['category_color']); ?>">
                                                            <i class="<?php echo htmlspecialchars($task['category_icon']); ?> me-1"></i>
                                                            <?php echo htmlspecialchars($task['category_name']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo date('g:i A', strtotime($task['due_date'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <?php if ($task['description']): ?>
                                                <p class="card-text mt-2 mb-0"><?php echo htmlspecialchars($task['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">No tasks scheduled for today.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Habits Accordion -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#habitsCollapse">
                            <i class="fas fa-repeat me-2"></i>Daily Habits
                        </button>
                    </h2>
                    <div id="habitsCollapse" class="accordion-collapse collapse" data-bs-parent="#tasksAccordion">
                        <div class="accordion-body">
                            <?php if ($habits_result && $habits_result->num_rows > 0): ?>
                                <?php while ($habit = $habits_result->fetch_assoc()): ?>
                                    <div class="card habit-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($habit['name']); ?></h6>
                                                    <?php if ($habit['category_name']): ?>
                                                        <span class="badge" style="background-color: <?php echo htmlspecialchars($habit['category_color']); ?>">
                                                            <i class="<?php echo htmlspecialchars($habit['category_icon']); ?> me-1"></i>
                                                            <?php echo htmlspecialchars($habit['category_name']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted me-2">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo date('g:i A', strtotime($habit['target_time'])); ?>
                                                    </small>
                                                    <?php if ($habit['today_status'] == 'completed'): ?>
                                                        <span class="badge bg-success">Completed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if ($habit['description']): ?>
                                                <p class="card-text mt-2 mb-0"><?php echo htmlspecialchars($habit['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">No habits scheduled for today.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Exam Reports Accordion -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#examReportsCollapse">
                            <i class="fas fa-file-alt me-2"></i>Exam Reports
                        </button>
                    </h2>
                    <div id="examReportsCollapse" class="accordion-collapse collapse" data-bs-parent="#tasksAccordion">
                        <div class="accordion-body">
                            <?php if ($exam_reports_result && $exam_reports_result->num_rows > 0): ?>
                                <?php while ($report = $exam_reports_result->fetch_assoc()): ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($report['exam_title']); ?></h6>
                                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($report['subject_color']); ?>">
                                                        <?php echo htmlspecialchars($report['subject_name']); ?>
                                                    </span>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-<?php echo $report['score'] >= 70 ? 'success' : ($report['score'] >= 50 ? 'warning' : 'danger'); ?>">
                                                        Score: <?php echo $report['score']; ?>%
                                                    </span>
                                                </div>
                                            </div>
                                            <?php if ($report['notes']): ?>
                                                <p class="card-text mt-2 mb-0"><?php echo htmlspecialchars($report['notes']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">No exam reports for today.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Exams and Quick Links -->
        <div class="col-md-4">
            <!-- Quick Links -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i>Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="/GCSE/pages/subjects.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-book me-2"></i>Subjects
                        </a>
                        <a href="/GCSE/pages/exams.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-check me-2"></i>Exam Schedule
                        </a>
                        <a href="/GCSE/pages/tasks.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tasks me-2"></i>Tasks
                        </a>
                        <a href="/GCSE/pages/progress.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-bar me-2"></i>Progress Overview
                        </a>
                        <a href="/GCSE/pages/resources.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-folder me-2"></i>Study Resources
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all accordions
    var accordions = document.querySelectorAll('.accordion');
    accordions.forEach(function(accordion) {
        new bootstrap.Collapse(accordion, {
            toggle: false
        });
    });
});
</script>

<?php
// Include footer
include '../includes/footer.php';

// Close database connection
close_connection($conn);
?> 