<?php
require_once '../../config/db_connect.php';

// Get subsection ID from URL
$subsection_id = isset($_GET['subsection_id']) ? intval($_GET['subsection_id']) : 0;

if (!$subsection_id) {
    header('Location: /pages/subjects/english.php');
    exit;
}

// Fetch subsection details and topics
$subsection_query = "
    SELECT 
        esub.*,
        es.name as section_name,
        COUNT(DISTINCT et.id) as total_topics,
        COUNT(DISTINCT CASE WHEN etp.status = 'completed' THEN et.id END) as completed_topics,
        COALESCE(ROUND(COUNT(DISTINCT CASE WHEN etp.status = 'completed' THEN et.id END) * 100.0 / 
            NULLIF(COUNT(DISTINCT et.id), 0), 1), 0) as progress_percentage
    FROM eng_subsections esub
    JOIN eng_sections es ON esub.section_id = es.id
    LEFT JOIN eng_topics et ON esub.id = et.subsection_id
    LEFT JOIN eng_topic_progress etp ON et.id = etp.topic_id
    WHERE esub.id = ?
    GROUP BY esub.id
";

$stmt = $conn->prepare($subsection_query);
$stmt->bind_param('i', $subsection_id);
$stmt->execute();
$subsection = $stmt->get_result()->fetch_assoc();

if (!$subsection) {
    header('Location: /pages/subjects/english.php');
    exit;
}

// Fetch topics with their progress
$topics_query = "
    SELECT 
        t.*,
        tp.status,
        tp.total_time_spent,
        tp.confidence_level,
        tp.last_studied,
        tp.completion_date,
        (SELECT COUNT(*) FROM topic_notes WHERE topic_id = t.id) as notes_count,
        (SELECT COUNT(*) FROM topic_questions WHERE topic_id = t.id) as questions_count,
        (SELECT COUNT(*) FROM topic_resources WHERE topic_id = t.id) as resources_count
    FROM eng_topics t
    LEFT JOIN eng_topic_progress tp ON t.id = tp.topic_id
    WHERE t.subsection_id = ?
    ORDER BY t.id
";

$stmt = $conn->prepare($topics_query);
$stmt->bind_param('i', $subsection_id);
$stmt->execute();
$topics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "English - " . htmlspecialchars($subsection['name']);
include '../../includes/header.php';

// Helper function to format study time
function formatStudyTime($seconds) {
    if ($seconds < 60) {
        return "Less than 1 minute";
    }
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    if ($hours > 0) {
        return "{$hours}h {$minutes}m";
    }
    return "{$minutes}m";
}

// Helper function to format date
function formatDate($date) {
    return $date ? date('j M Y', strtotime($date)) : 'Never';
}
?>

<div class="container py-4">
    <!-- Breadcrumb and Title -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/pages/subjects.php">Subjects</a></li>
            <li class="breadcrumb-item"><a href="/pages/subjects/english.php">English</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($subsection['name']); ?></li>
        </ol>
    </nav>

    <!-- Subsection Header -->
    <div class="section-header mb-4">
        <h1 class="mb-3"><?php echo htmlspecialchars($subsection['name']); ?></h1>
        <p class="lead"><?php echo htmlspecialchars($subsection['description']); ?></p>
        <div class="progress mb-3">
            <div class="progress-bar" role="progressbar" 
                 style="width: <?php echo $subsection['progress_percentage']; ?>%">
            </div>
        </div>
        <div class="d-flex justify-content-between text-muted">
            <span><?php echo $subsection['completed_topics']; ?>/<?php echo $subsection['total_topics']; ?> topics completed</span>
            <span><?php echo $subsection['progress_percentage']; ?>% complete</span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-1"><?php echo htmlspecialchars($subsection['subsection_name']); ?></h4>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($subsection['subsection_description']); ?></p>
                        </div>
                        <button onclick="cleanupDuplicates()" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-broom me-1"></i>Clean Up Duplicates
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Topics Grid -->
    <div class="row g-4">
        <?php foreach ($topics as $topic): ?>
            <div class="col-md-6 col-lg-4">
                <div class="topic-card card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title mb-0">
                                <?php echo htmlspecialchars($topic['name']); ?>
                            </h5>
                            <span class="badge"><?php echo $topic['id']; ?></span>
                        </div>
                        
                        <p class="card-text text-muted mb-3">
                            <?php echo htmlspecialchars($topic['description']); ?>
                        </p>

                        <div class="topic-stats mb-3">
                            <div class="stat-item">
                                <i class="fas fa-book-open"></i>
                                <span><?php echo $topic['notes_count']; ?> notes</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-question-circle"></i>
                                <span><?php echo $topic['questions_count']; ?> questions</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-file-alt"></i>
                                <span><?php echo $topic['resources_count']; ?> resources</span>
                            </div>
                        </div>

                        <div class="topic-progress mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="status-badge <?php echo $topic['status'] ?? 'not-started'; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $topic['status'] ?? 'not started')); ?>
                                </span>
                                <span class="confidence-level">
                                    <?php
                                    $confidence = intval($topic['confidence_level'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo '<i class="fas fa-star' . ($i <= $confidence ? ' active' : '') . '"></i>';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="study-info">
                                <span class="study-time">
                                    <i class="fas fa-clock"></i>
                                    <?php echo formatStudyTime($topic['total_time_spent'] ?? 0); ?>
                                </span>
                                <span class="last-studied">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo formatDate($topic['last_studied']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="topic-actions">
                            <a href="/pages/topic.php?id=<?php echo $topic['id']; ?>&subject=english" 
                               class="btn btn-primary btn-sm">
                                Study Topic
                            </a>
                            <button class="btn btn-outline-danger btn-sm reset-topic" 
                                    data-topic-id="<?php echo $topic['id']; ?>">
                                Reset Progress
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.section-header {
    background: #f8fafc;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.topic-card {
    transition: all 0.3s ease;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 1rem;
    background: #ffffff;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.topic-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    border-color: #3b82f6;
}

.topic-card .card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin-right: 3rem;
}

.topic-card .badge {
    padding: 0.5rem 1rem;
    font-weight: 600;
    font-size: 0.9rem;
    border-radius: 0.75rem;
    background: #e0e7ff;
    color: #4338ca;
    min-width: 2.5rem;
    text-align: center;
}

.topic-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.9rem;
    color: #64748b;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.topic-progress {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 0.75rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.completed {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.in_progress {
    background: #e0e7ff;
    color: #4338ca;
}

.status-badge.not-started {
    background: #f1f5f9;
    color: #64748b;
}

.confidence-level {
    color: #cbd5e1;
}

.confidence-level .fa-star.active {
    color: #eab308;
}

.study-info {
    display: flex;
    justify-content: space-between;
    margin-top: 0.75rem;
    font-size: 0.875rem;
    color: #64748b;
}

.study-info i {
    margin-right: 0.5rem;
}

.topic-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
}

.topic-actions .btn {
    flex: 1;
    border-radius: 0.75rem;
    padding: 0.5rem 1rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.progress {
    height: 0.5rem;
    border-radius: 1rem;
    background-color: #f1f5f9;
    overflow: hidden;
}

.progress-bar {
    background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
    border-radius: 1rem;
    transition: width 0.5s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle reset topic buttons
    document.querySelectorAll('.reset-topic').forEach(button => {
        button.addEventListener('click', async function() {
            const topicId = this.dataset.topicId;
            
            if (!confirm('Are you sure you want to reset progress for this topic? This will reset all progress data but keep your notes, questions, and resources.')) {
                return;
            }

            try {
                const response = await fetch('/api/english/reset_topic.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ topic_id: topicId })
                });

                const data = await response.json();

                if (data.success) {
                    // Reload the page to show updated progress
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Failed to reset topic');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error resetting topic: ' + error.message);
            }
        });
    });
});

async function cleanupDuplicates() {
    if (!confirm('This will remove duplicate topic records. Continue?')) {
        return;
    }

    try {
        const response = await fetch('/api/english/cleanup_duplicates.php');
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error cleaning up duplicates: ' + data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error cleaning up duplicates. Please try again.');
    }
}
</script>

<?php include '../../includes/footer.php'; ?> 