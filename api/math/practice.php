<?php
require_once '../../config/db_connect.php';

// Get topics with low confidence or in progress
$topics_query = "
    SELECT 
        mt.*,
        COUNT(ms.id) as total_subtopics,
        SUM(CASE WHEN mp.status = 'completed' THEN 1 ELSE 0 END) as completed_subtopics,
        AVG(CASE WHEN mp.confidence > 0 THEN mp.confidence ELSE 0 END) as avg_confidence
    FROM math_topics mt
    JOIN math_subtopics ms ON mt.id = ms.topic_id
    LEFT JOIN math_progress mp ON ms.id = mp.subtopic_id
    GROUP BY mt.id
    HAVING avg_confidence < 3 OR completed_subtopics < total_subtopics
    ORDER BY avg_confidence ASC, completed_subtopics/total_subtopics ASC
    LIMIT 3
";

$topics_result = $conn->query($topics_query);

// Get recently studied topics
$recent_query = "
    SELECT 
        mt.*,
        MAX(mp.last_studied) as last_studied,
        COUNT(DISTINCT ms.id) as subtopic_count
    FROM math_topics mt
    JOIN math_subtopics ms ON mt.id = ms.topic_id
    JOIN math_progress mp ON ms.id = mp.subtopic_id
    WHERE mp.last_studied IS NOT NULL
    GROUP BY mt.id
    ORDER BY last_studied DESC
    LIMIT 3
";

$recent_result = $conn->query($recent_query);
?>

<div class="practice-content">
    <!-- Topics Needing Practice -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-4">
                <i class="fas fa-exclamation-circle text-warning me-2"></i>
                Topics Needing Practice
            </h5>
            
            <?php if ($topics_result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($topic = $topics_result->fetch_assoc()): ?>
                        <div class="col-md-4">
                            <div class="practice-card h-100 p-3 border rounded">
                                <h6 class="mb-2"><?php echo htmlspecialchars($topic['name']); ?></h6>
                                <p class="small text-muted mb-3"><?php echo htmlspecialchars($topic['description']); ?></p>
                                
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo round(($topic['completed_subtopics'] / $topic['total_subtopics']) * 100); ?>%">
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center small text-muted">
                                    <span>Progress: <?php echo $topic['completed_subtopics']; ?>/<?php echo $topic['total_subtopics']; ?></span>
                                    <span>Confidence: <?php echo number_format($topic['avg_confidence'], 1); ?>/5</span>
                                </div>
                                
                                <button class="btn btn-primary btn-sm w-100 mt-3" 
                                        @click="selectedTopic = <?php echo json_encode($topic); ?>; showTopicModal = true">
                                    Practice Now
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <p>Great job! All topics are progressing well.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recently Studied -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-4">
                <i class="fas fa-history me-2"></i>
                Recently Studied
            </h5>
            
            <?php if ($recent_result->num_rows > 0): ?>
                <div class="list-group list-group-flush">
                    <?php while ($topic = $recent_result->fetch_assoc()): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($topic['name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo $topic['subtopic_count']; ?> subtopics
                                    </small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">
                                        Last studied: <?php echo date('M j, Y', strtotime($topic['last_studied'])); ?>
                                    </small>
                                    <button class="btn btn-outline-primary btn-sm mt-2"
                                            @click="selectedTopic = <?php echo json_encode($topic); ?>; showTopicModal = true">
                                        Continue
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted">
                    <p>No recently studied topics found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.practice-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.practice-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style> 