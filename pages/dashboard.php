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

// Include header
include '../includes/header.php';
?>

<style>
.subject-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.subject-card:hover {
    transform: translateY(-2px);
}
.progress {
    height: 8px;
}
.stat-card {
    border: none;
    background: #f8f9fa;
}
.stat-value {
    font-size: 1.5rem;
    font-weight: 500;
}
</style>

<!-- Quick Stats -->
<div class="row mb-4">
    <?php while($subject = $subjects_result->fetch_assoc()): 
        $progress = $subject['total_topics'] > 0 ? 
            round(($subject['completed_topics'] / $subject['total_topics']) * 100) : 0;
        // Override Math color to blue
        $subject_color = $subject['name'] === 'Math' ? '#007bff' : $subject['color'];
    ?>
    <div class="col-md-6">
        <div class="card subject-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <span class="badge me-2" style="background-color: <?php echo $subject_color; ?>">
                            <?php echo $subject['name']; ?>
                        </span>
                    </h5>
                    <span class="text-muted"><?php echo $progress; ?>% Complete</span>
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
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Upcoming Exams -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-3"><i class="fas fa-calendar-alt me-2"></i>Upcoming Exams</h5>
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

<!-- Quick Links -->
<div class="row">
    <div class="col-md-3 mb-3">
        <a href="/pages/Today.php" class="card subject-card text-decoration-none text-dark">
            <div class="card-body">
                <h5><i class="fas fa-calendar-day text-info me-2"></i>Today</h5>
                <p class="text-muted mb-0">View today's tasks and progress</p>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="/pages/subjects/math.php" class="card subject-card text-decoration-none text-dark">
            <div class="card-body">
                <h5><i class="fas fa-calculator text-primary me-2"></i>Mathematics</h5>
                <p class="text-muted mb-0">View topics and track your progress</p>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="/pages/subjects/english.php" class="card subject-card text-decoration-none text-dark">
            <div class="card-body">
                <h5><i class="fas fa-book text-success me-2"></i>English</h5>
                <p class="text-muted mb-0">Practice language and literature</p>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="/pages/resources.php" class="card subject-card text-decoration-none text-dark">
            <div class="card-body">
                <h5><i class="fas fa-folder text-warning me-2"></i>Resources</h5>
                <p class="text-muted mb-0">Access study materials and guides</p>
            </div>
        </a>
    </div>
</div>

<?php
include '../includes/footer.php';
close_connection($conn);
?>