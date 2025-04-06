<?php
// Set page title
$page_title = "Topics";

// Include database connection
require_once '../config/db_connect.php';

// Check if subsection ID and subject are provided
if (!isset($_GET['subsection']) || !isset($_GET['subject'])) {
    header('Location: /GCSE/pages/subjects.php');
    exit;
}

$subsection_id = intval($_GET['subsection']);
$subject = $_GET['subject'];

// Redirect math topics to the dedicated math_topics.php page
if ($subject === 'math') {
    header('Location: /GCSE/pages/subjects/math_topics.php?subsection=' . $subsection_id);
    exit;
}

// Set table names based on subject
if ($subject === 'english') {
    $sections_table = 'eng_sections';
    $subsections_table = 'eng_subsections';
    $topics_table = 'eng_topics';
    $progress_table = 'eng_topic_progress';
    $subject_name = 'English';
    $subject_page = '/GCSE/pages/subjects/english.php';
    $gradient_colors = ['#28a745', '#20c997'];
} else {
    $sections_table = 'math_sections';
    $subsections_table = 'math_subsections';
    $topics_table = 'math_topics';
    $progress_table = 'topic_progress';
    $subject_name = 'Mathematics';
    $subject_page = '/GCSE/pages/subjects/math.php';
    $gradient_colors = ['#0066ff', '#00ccff'];
}

// Fetch subsection details with section info
$subsection_query = "
    SELECT 
        s.name as section_name,
        sub.*,
        COUNT(DISTINCT t.id) as total_topics,
        COALESCE(SUM(CASE WHEN tp.status = 'completed' THEN 1 ELSE 0 END), 0) as completed_topics,
        COALESCE(ROUND(SUM(CASE WHEN tp.status = 'completed' THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT t.id), 2), 0) as progress_percentage,
        COALESCE(
            (SELECT SUM(accumulated_seconds)
             FROM eng_study_time_tracking stt
             WHERE stt.topic_id = t.id
             AND stt.status IN ('active', 'paused')
            ), 0) as total_study_time
    FROM $sections_table s
    INNER JOIN $subsections_table sub ON s.id = sub.section_id
    LEFT JOIN $topics_table t ON sub.id = t.subsection_id
    LEFT JOIN $progress_table tp ON t.id = tp.topic_id
    WHERE sub.id = ?
    GROUP BY sub.id;
";

$stmt = $conn->prepare($subsection_query);
$stmt->bind_param('i', $subsection_id);
$stmt->execute();
$subsection = $stmt->get_result()->fetch_assoc();

if (!$subsection) {
    header("Location: $subject_page");
    exit;
}

// Set breadcrumbs
$breadcrumbs = [
    'Home' => '/GCSE/',
    'Subjects' => '/GCSE/pages/subjects.php',
    $subject_name => $subject_page,
    $subsection['name'] => null
];

// Fetch topics for this subsection
$topics_query = "
    SELECT 
        t.*,
        tp.status,
        tp.confidence_level,
        tp.completion_date,
        COALESCE(
            tp.total_time_spent + 
            COALESCE((
                SELECT SUM(accumulated_seconds)
                FROM eng_study_time_tracking stt
                WHERE stt.topic_id = t.id
                AND stt.status IN ('active', 'paused')
            ), 0)
        , 0) as total_time_spent,
        COALESCE(COUNT(DISTINCT tq.id), 0) as question_count,
        COALESCE(COUNT(DISTINCT tn.id), 0) as note_count,
        COALESCE(COUNT(DISTINCT tr.id), 0) as resource_count,
        COALESCE(
            (SELECT status FROM eng_study_time_tracking 
             WHERE topic_id = t.id 
             AND status IN ('active', 'paused')
             ORDER BY start_time DESC 
             LIMIT 1
            ), 'not_started'
        ) as timer_status
    FROM $topics_table t
    LEFT JOIN $progress_table tp ON t.id = tp.topic_id
    LEFT JOIN topic_questions tq ON t.id = tq.topic_id
    LEFT JOIN topic_notes tn ON t.id = tn.topic_id
    LEFT JOIN topic_resources tr ON t.id = tr.topic_id
    WHERE t.subsection_id = ?
    GROUP BY t.id
    ORDER BY t.id;
";

$stmt = $conn->prepare($topics_query);
$stmt->bind_param('i', $subsection_id);
$stmt->execute();
$topics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include header
include '../includes/header.php';
?>

<div class="topics-page">
    <!-- Hero Section -->
    <div class="hero-section py-4 mb-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h6 class="text-white-50 mb-2"><?php echo htmlspecialchars($subsection['section_name']); ?></h6>
                    <h1 class="display-5 fw-bold text-white mb-3"><?php echo htmlspecialchars($subsection['name']); ?></h1>
                    <p class="lead text-white-75 mb-0"><?php echo htmlspecialchars($subsection['description']); ?></p>
                </div>
                <div class="col-lg-4">
                    <div class="stats-card bg-white p-4 rounded-4 shadow-sm">
                        <div class="stat-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="stat-label">Progress</span>
                                <span class="stat-value"><?php echo round($subsection['progress_percentage']); ?>%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $subsection['progress_percentage']; ?>%">
                                </div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="stat-label">Topics Completed</span>
                                <span class="stat-value">
                                    <?php echo $subsection['completed_topics']; ?>/<?php echo $subsection['total_topics']; ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($subsection['total_study_time'] > 0): ?>
                        <div class="stat-item mt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="stat-label">Time Spent</span>
                                <span class="stat-value">
                                    <?php 
                                    $total_seconds = $subsection['total_study_time'];
                                    $hours = floor($total_seconds / 3600);
                                    $minutes = floor(($total_seconds % 3600) / 60);
                                    
                                    if ($hours > 0) {
                                        echo $hours . 'h ' . $minutes . 'm';
                                    } else {
                                        echo $minutes . 'm';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Topics List -->
        <div class="topics-list">
            <?php foreach ($topics as $topic): ?>
            <div class="topic-card card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="card-title mb-1">
                                <?php echo htmlspecialchars($topic['name']); ?>
                            </h5>
                            <p class="text-muted mb-0">
                                <?php echo htmlspecialchars($topic['description']); ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <?php if ($topic['status'] === 'completed'): ?>
                                    <span class="badge bg-success me-2">Completed</span>
                                <?php elseif ($topic['status'] === 'in_progress'): ?>
                                    <span class="badge bg-warning me-2">In Progress</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary me-2">Not Started</span>
                                <?php endif; ?>
                                
                                <?php if ($topic['confidence_level'] > 0): ?>
                                <div class="confidence-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $topic['confidence_level'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($topic['total_time_spent'] > 0): ?>
                            <div class="text-muted small mt-2">
                                <i class="fas fa-clock me-1"></i>
                                <?php 
                                $total_seconds = $topic['total_time_spent'];
                                $hours = floor($total_seconds / 3600);
                                $minutes = floor(($total_seconds % 3600) / 60);
                                
                                if ($hours > 0) {
                                    echo $hours . 'h ' . $minutes . 'm spent';
                                } else {
                                    echo $minutes . 'm spent';
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2 text-end">
                            <a href="/GCSE/pages/topic.php?id=<?php echo $topic['id']; ?>&subject=<?php echo $subject; ?>" 
                               class="btn btn-primary">
                                Study Topic
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.hero-section {
    background: linear-gradient(135deg, <?php echo $gradient_colors[0]; ?> 0%, <?php echo $gradient_colors[1]; ?> 100%);
    color: white;
}

.stats-card {
    border-radius: 1rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.stat-value {
    font-weight: 600;
    font-size: 1.1rem;
}

.topic-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid rgba(0,0,0,0.1);
}

.topic-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.progress {
    background-color: #e9ecef;
    border-radius: 50px;
    overflow: hidden;
}

.progress-bar {
    border-radius: 50px;
}

.confidence-stars {
    font-size: 0.9rem;
}

.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}

.text-white-75 {
    color: rgba(255, 255, 255, 0.75);
}
</style>

<?php include '../includes/footer.php'; ?> 