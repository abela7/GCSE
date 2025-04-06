<?php
// Set page title
$page_title = "Math Topics";

// Set breadcrumbs
$breadcrumbs = [
    'Home' => '/',
    'Subjects' => '/pages/subjects.php',
    'Mathematics' => '/pages/subjects/math.php',
    'Topics' => null
];

// Include database connection
require_once '../../config/db_connect.php';

// Get subsection ID from URL
$subsection_id = isset($_GET['subsection']) ? intval($_GET['subsection']) : 0;

if (!$subsection_id) {
    header('Location: /pages/subjects/math.php');
    exit;
}

// Fetch subsection details
$subsection_query = "
    SELECT 
        ms.name as section_name,
        ms.id as section_id,
        msub.name as subsection_name,
        msub.description as subsection_description
    FROM math_subsections msub
    JOIN math_sections ms ON msub.section_id = ms.id
    WHERE msub.id = ?
";

$stmt = $conn->prepare($subsection_query);
$stmt->bind_param('i', $subsection_id);
$stmt->execute();
$subsection_result = $stmt->get_result();
$subsection = $subsection_result->fetch_assoc();

if (!$subsection) {
    header('Location: /pages/subjects/math.php');
    exit;
}

// Update breadcrumbs with section name
$breadcrumbs[$subsection['section_name']] = "/pages/subjects/math.php#section-" . $subsection['section_id'];
$breadcrumbs[$subsection['subsection_name']] = null;

// Fetch topics for this subsection
$topics_query = "
    SELECT 
        mt.*,
        tp.status,
        tp.confidence_level,
        tp.last_studied,
        COALESCE(
            tp.total_time_spent + 
            COALESCE((
                SELECT SUM(accumulated_seconds)
                FROM study_time_tracking stt
                WHERE stt.topic_id = mt.id
                AND stt.status IN ('active', 'paused')
            ), 0)
        , 0) as total_time_spent,
        COALESCE(
            (SELECT status FROM study_time_tracking 
             WHERE topic_id = mt.id 
             AND status IN ('active', 'paused')
             ORDER BY start_time DESC 
             LIMIT 1
            ), 'not_started'
        ) as timer_status
    FROM math_topics mt
    LEFT JOIN topic_progress tp ON mt.id = tp.topic_id
    WHERE mt.subsection_id = ?
    ORDER BY mt.id;
";

$stmt = $conn->prepare($topics_query);
$stmt->bind_param('i', $subsection_id);
$stmt->execute();
$topics_result = $stmt->get_result();

// Include header
include '../../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-1"><?php echo htmlspecialchars($subsection['subsection_name']); ?></h4>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($subsection['subsection_description']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php while ($topic = $topics_result->fetch_assoc()): 
            $status_class = 'bg-secondary';
            $status_text = 'Not Started';
            
            if ($topic['status'] === 'completed') {
                $status_class = 'bg-success';
                $status_text = 'Completed';
            } elseif ($topic['status'] === 'in_progress' || $topic['timer_status'] === 'active' || $topic['timer_status'] === 'paused') {
                $status_class = 'bg-warning';
                $status_text = 'In Progress';
            }
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 topic-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title mb-0">
                            <?php echo htmlspecialchars($topic['name']); ?>
                        </h5>
                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </div>
                    
                    <p class="card-text text-muted mb-3">
                        <?php echo htmlspecialchars($topic['description']); ?>
                    </p>

                    <?php if ($topic['status'] === 'completed' || $topic['status'] === 'in_progress'): ?>
                    <div class="topic-stats small text-muted mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Confidence Level:</span>
                            <span class="fw-medium">
                                <?php
                                $confidence = intval($topic['confidence_level']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo '<i class="fas fa-star' . ($i <= $confidence ? ' text-warning' : ' text-muted') . '"></i>';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Time Spent:</span>
                            <span class="fw-medium">
                                <?php 
                                $total_seconds = $topic['total_time_spent'];
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

                    <div class="d-flex gap-2">
                        <a href="/pages/topic.php?id=<?php echo $topic['id']; ?>&subject=math" 
                           class="btn btn-primary flex-grow-1">
                            <?php if ($topic['timer_status'] === 'active'): ?>
                                Continue Studying
                            <?php elseif ($topic['timer_status'] === 'paused'): ?>
                                Resume Study
                            <?php else: ?>
                                Start Learning
                            <?php endif; ?>
                        </a>
                        <?php if ($topic['status'] === 'completed'): ?>
                        <button type="button" 
                                class="btn btn-outline-primary" 
                                onclick="resetTopic(<?php echo $topic['id']; ?>)">
                            <i class="fas fa-redo"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<style>
.topic-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid rgba(0,0,0,0.1);
}

.topic-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}
</style>

<script>
async function resetTopic(topicId) {
    if (!confirm('Are you sure you want to reset this topic? This will clear all progress.')) {
        return;
    }

    try {
        const response = await fetch('/api/math/reset_topic.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ topic_id: topicId })
        });

        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Error resetting topic: ' + data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error resetting topic. Please try again.');
    }
}
</script>

<?php include '../../includes/footer.php'; ?> 