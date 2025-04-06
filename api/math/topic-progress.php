<?php
require_once '../../config/db_connect.php';

$topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;

if ($topic_id > 0) {
    // Get progress for this topic
    $progress_query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN mp.status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN mp.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            COALESCE(AVG(CASE WHEN mp.confidence > 0 THEN mp.confidence ELSE NULL END), 0) as avg_confidence
        FROM math_subtopics ms
        LEFT JOIN math_progress mp ON ms.id = mp.subtopic_id
        WHERE ms.topic_id = ?
    ";
    
    $stmt = $conn->prepare($progress_query);
    $stmt->bind_param('i', $topic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $progress = $result->fetch_assoc();
    
    $total = $progress['total'];
    $completed = $progress['completed'];
    $in_progress = $progress['in_progress'];
    $avg_confidence = $progress['avg_confidence'];
    
    $completion_percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
    ?>
    
    <div class="topic-progress-details">
        <div class="progress mb-2" style="height: 10px;">
            <div class="progress-bar bg-success" role="progressbar" 
                 style="width: <?php echo $completion_percentage; ?>%">
            </div>
            <?php if ($in_progress > 0): ?>
                <div class="progress-bar bg-warning" role="progressbar" 
                     style="width: <?php echo round(($in_progress / $total) * 100); ?>%">
                </div>
            <?php endif; ?>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                <?php echo $completed; ?>/<?php echo $total; ?> completed
            </small>
            <?php if ($avg_confidence > 0): ?>
                <small class="text-muted">
                    Avg. Confidence: <?php echo number_format($avg_confidence, 1); ?>/5
                </small>
            <?php endif; ?>
        </div>
    </div>
    <?php
} else {
    echo '<div class="alert alert-danger">Invalid topic ID</div>';
}
?> 