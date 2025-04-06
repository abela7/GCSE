<?php
require_once '../../config/db_connect.php';

$topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;

if ($topic_id > 0) {
    // Get subtopics for this topic
    $subtopics_query = "
        SELECT 
            ms.*,
            mp.status,
            mp.confidence,
            mp.last_studied,
            mp.notes
        FROM math_subtopics ms 
        LEFT JOIN math_progress mp ON ms.id = mp.subtopic_id 
        WHERE ms.topic_id = ?
        ORDER BY ms.name ASC
    ";
    
    $stmt = $conn->prepare($subtopics_query);
    $stmt->bind_param('i', $topic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0):
    ?>
        <div class="subtopics-list">
            <div class="list-group">
                <?php while ($subtopic = $result->fetch_assoc()): ?>
                    <div class="list-group-item list-group-item-action" 
                         x-data="{ showUpdateForm: false }"
                         :class="{ 'active': showUpdateForm }">
                        <div class="d-flex w-100 justify-content-between align-items-start"
                             @click="showUpdateForm = !showUpdateForm">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($subtopic['name']); ?></h6>
                                <?php if ($subtopic['description']): ?>
                                    <p class="mb-1 small"><?php echo htmlspecialchars($subtopic['description']); ?></p>
                                <?php endif; ?>
                                <div>
                                    <span class="badge bg-<?php 
                                        echo ($subtopic['status'] ?? 'not_started') === 'completed' ? 'success' : 
                                            (($subtopic['status'] ?? 'not_started') === 'in_progress' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $subtopic['status'] ?? 'not_started')); ?>
                                    </span>
                                    <?php if (($subtopic['confidence'] ?? 0) > 0): ?>
                                        <span class="badge bg-info ms-2">
                                            Confidence: <?php echo $subtopic['confidence']; ?>/5
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($subtopic['last_studied']): ?>
                                <small class="text-muted">
                                    Last studied: <?php echo date('M j, Y', strtotime($subtopic['last_studied'])); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Update Form -->
                        <div x-show="showUpdateForm" x-cloak
                             class="mt-3 p-3 border-top"
                             hx-target="closest .list-group-item"
                             hx-swap="outerHTML">
                            <form hx-post="/GCSE/api/math/update-progress.php" class="needs-validation" novalidate>
                                <input type="hidden" name="subtopic_id" value="<?php echo $subtopic['id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="status" id="status_not_started_<?php echo $subtopic['id']; ?>" 
                                               value="not_started" <?php echo ($subtopic['status'] ?? 'not_started') === 'not_started' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-secondary" for="status_not_started_<?php echo $subtopic['id']; ?>">Not Started</label>
                                        
                                        <input type="radio" class="btn-check" name="status" id="status_in_progress_<?php echo $subtopic['id']; ?>"
                                               value="in_progress" <?php echo ($subtopic['status'] ?? '') === 'in_progress' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-warning" for="status_in_progress_<?php echo $subtopic['id']; ?>">In Progress</label>
                                        
                                        <input type="radio" class="btn-check" name="status" id="status_completed_<?php echo $subtopic['id']; ?>"
                                               value="completed" <?php echo ($subtopic['status'] ?? '') === 'completed' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-success" for="status_completed_<?php echo $subtopic['id']; ?>">Completed</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Confidence Level</label>
                                    <div class="range-container">
                                        <input type="range" class="form-range" name="confidence" min="0" max="5" step="1" 
                                               value="<?php echo $subtopic['confidence'] ?? 0; ?>">
                                        <div class="d-flex justify-content-between px-2">
                                            <span>0</span>
                                            <span>1</span>
                                            <span>2</span>
                                            <span>3</span>
                                            <span>4</span>
                                            <span>5</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" name="notes" rows="2"><?php echo htmlspecialchars($subtopic['notes'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="text-end">
                                    <button type="button" class="btn btn-link" @click="showUpdateForm = false">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No subtopics found for this topic.
        </div>
    <?php endif;
} else {
    echo '<div class="alert alert-danger">Invalid topic ID</div>';
}
?> 