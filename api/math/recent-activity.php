<?php
require_once '../../config/db_connect.php';

// Get recent activity
$activity_query = "
    SELECT 
        mp.*, 
        ms.name as subtopic_name,
        mt.name as topic_name
    FROM math_progress mp 
    JOIN math_subtopics ms ON mp.subtopic_id = ms.id 
    JOIN math_topics mt ON ms.topic_id = mt.id
    WHERE mp.last_studied IS NOT NULL 
    ORDER BY mp.last_studied DESC 
    LIMIT 5
";
$activity_result = $conn->query($activity_query);

if ($activity_result->num_rows > 0): ?>
    <div class="list-group list-group-flush">
        <?php while ($activity = $activity_result->fetch_assoc()): ?>
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1"><?php echo htmlspecialchars($activity['subtopic_name']); ?></h6>
                        <p class="mb-1 text-muted">
                            <small>
                                <i class="fas fa-book me-1"></i>
                                <?php echo htmlspecialchars($activity['topic_name']); ?>
                            </small>
                        </p>
                        <div>
                            <span class="badge bg-<?php 
                                echo $activity['status'] === 'completed' ? 'success' : 
                                    ($activity['status'] === 'in_progress' ? 'warning' : 'secondary'); 
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $activity['status'])); ?>
                            </span>
                            <?php if ($activity['confidence'] > 0): ?>
                                <span class="badge bg-info ms-2">
                                    Confidence: <?php echo $activity['confidence']; ?>/5
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <small class="text-muted">
                        <?php 
                        $date = new DateTime($activity['last_studied']);
                        echo $date->format('M j, Y'); 
                        ?>
                    </small>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="text-center text-muted">
        <i class="fas fa-info-circle mb-2"></i>
        <p>No recent activity found.</p>
    </div>
<?php endif; ?> 