<?php
require_once __DIR__ . '/../../includes/auth_check.php';

// Set page title
$page_title = "Exams";

// Set breadcrumbs
$breadcrumbs = [
    'Exams' => null
];

// Include database connection
require_once '../config/db_connect.php';

// Filter by subject if provided
$subject_filter = '';
$subject_id = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;

if ($subject_id > 0) {
    $subject_filter = "WHERE e.subject_id = $subject_id";
    
    // Get subject name for title
    $subject_query = "SELECT name FROM subjects WHERE id = $subject_id";
    $subject_result = $conn->query($subject_query);
    
    if ($subject_result && $subject_result->num_rows > 0) {
        $subject_name = $subject_result->fetch_assoc()['name'];
        $page_title = "$subject_name Exams";
        $breadcrumbs = [
            'Subjects' => '/GCSE/pages/subjects.php',
            $subject_name => "/GCSE/pages/subjects/" . strtolower($subject_name) . ".php",
            'Exams' => null
        ];
    }
}

// Get exams
$exams_query = "SELECT e.*, s.name as subject_name, s.color as subject_color 
                FROM exams e 
                JOIN subjects s ON e.subject_id = s.id 
                $subject_filter
                ORDER BY e.exam_date ASC";
$exams_result = $conn->query($exams_query);

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<style>
.exam-card {
    transition: transform 0.2s;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.exam-card:hover {
    transform: translateY(-2px);
}
.countdown-timer {
    font-size: 1.1rem;
    font-weight: 500;
}
.exam-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #666;
    font-size: 0.9rem;
}
.exam-info i {
    width: 16px;
}
</style>

<!-- Subject Filter -->
<div class="mb-4">
    <div class="btn-group">
        <a href="/GCSE/pages/exams.php" class="btn <?php echo !$subject_id ? 'btn-primary' : 'btn-outline-primary'; ?>">
            All Exams
        </a>
        <a href="/GCSE/pages/exams.php?subject=2" class="btn <?php echo $subject_id == 2 ? 'btn-primary' : 'btn-outline-primary'; ?>">
            Mathematics
        </a>
        <a href="/GCSE/pages/exams.php?subject=1" class="btn <?php echo $subject_id == 1 ? 'btn-primary' : 'btn-outline-primary'; ?>">
            English
        </a>
    </div>
</div>

<!-- Exams Grid -->
<div class="row">
    <?php 
    $now = new DateTime();
    while ($exam = $exams_result->fetch_assoc()): 
        $exam_date = new DateTime($exam['exam_date']);
        $interval = $now->diff($exam_date);
        $days_remaining = $interval->days;
        $is_past = $exam_date < $now;
    ?>
    <div class="col-md-6 mb-3">
        <div class="card exam-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge mb-2" style="background-color: <?php echo htmlspecialchars($exam['subject_color']); ?>">
                            <?php echo htmlspecialchars($exam['subject_name']); ?>
                        </span>
                        <h5 class="mb-0"><?php echo htmlspecialchars($exam['title']); ?></h5>
                    </div>
                    <?php if (!$is_past): ?>
                    <span class="badge bg-<?php echo $days_remaining <= 7 ? 'danger' : ($days_remaining <= 14 ? 'warning' : 'primary'); ?>">
                        <?php echo $days_remaining; ?> days
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="exam-info">
                    <div><i class="fas fa-calendar"></i> <?php echo $exam_date->format('D, j M Y'); ?></div>
                    <div><i class="fas fa-clock"></i> <?php echo $exam_date->format('g:i A'); ?></div>
                    <div><i class="fas fa-hourglass-half"></i> <?php echo floor($exam['duration'] / 60) . 'h ' . ($exam['duration'] % 60) . 'm'; ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<?php if ($exams_result->num_rows == 0): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    No exams found for the selected subject.
</div>
<?php endif; ?>

<!-- Exam Preparation Tips -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Exam Preparation Tips</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-check text-primary me-2"></i>Plan Your Revision</h5>
                        <p class="card-text">Create a realistic revision timetable that includes all subjects and topics. Allocate more time to challenging areas.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-file-alt text-primary me-2"></i>Practice Past Papers</h5>
                        <p class="card-text">Complete past exam papers under timed conditions to familiarize yourself with the format and improve time management.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-bed text-primary me-2"></i>Rest Well</h5>
                        <p class="card-text">Ensure you get enough sleep, especially the night before an exam. A well-rested mind performs better than an exhausted one.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';

// Close database connection
close_connection($conn);
?>