<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subtopic_id = isset($_POST['subtopic_id']) ? (int)$_POST['subtopic_id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'not_started';
    $confidence = isset($_POST['confidence']) ? (int)$_POST['confidence'] : 0;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    if ($subtopic_id > 0) {
        // Check if a record exists
        $check_query = "SELECT id FROM math_progress WHERE subtopic_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param('i', $subtopic_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing record
            $update_query = "
                UPDATE math_progress 
                SET status = ?, confidence = ?, notes = ?, last_studied = NOW()
                WHERE subtopic_id = ?
            ";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('sisi', $status, $confidence, $notes, $subtopic_id);
        } else {
            // Insert new record
            $insert_query = "
                INSERT INTO math_progress (subtopic_id, status, confidence, notes, last_studied)
                VALUES (?, ?, ?, ?, NOW())
            ";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param('isis', $subtopic_id, $status, $confidence, $notes);
        }
        
        if ($stmt->execute()) {
            // Get updated subtopic details for response
            $subtopic_query = "
                SELECT 
                    ms.*,
                    mp.status,
                    mp.confidence,
                    mp.last_studied,
                    mp.notes
                FROM math_subtopics ms 
                LEFT JOIN math_progress mp ON ms.id = mp.subtopic_id 
                WHERE ms.id = ?
            ";
            $subtopic_stmt = $conn->prepare($subtopic_query);
            $subtopic_stmt->bind_param('i', $subtopic_id);
            $subtopic_stmt->execute();
            $subtopic = $subtopic_stmt->get_result()->fetch_assoc();
            
            // Return updated HTML for the list item
            ?>
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
                                echo $status === 'completed' ? 'success' : 
                                    ($status === 'in_progress' ? 'warning' : 'secondary'); 
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                            </span>
                            <?php if ($confidence > 0): ?>
                                <span class="badge bg-info ms-2">
                                    Confidence: <?php echo $confidence; ?>/5
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <small class="text-muted">
                        Last studied: <?php echo date('M j, Y'); ?>
                    </small>
                </div>
                
                <!-- Update Form -->
                <div x-show="showUpdateForm" x-cloak
                     class="mt-3 p-3 border-top"
                     hx-target="closest .list-group-item"
                     hx-swap="outerHTML">
                    <form hx-post="/api/math/update-progress.php" class="needs-validation" novalidate>
                        <input type="hidden" name="subtopic_id" value="<?php echo $subtopic_id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="status" id="status_not_started_<?php echo $subtopic_id; ?>" 
                                       value="not_started" <?php echo $status === 'not_started' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-secondary" for="status_not_started_<?php echo $subtopic_id; ?>">Not Started</label>
                                
                                <input type="radio" class="btn-check" name="status" id="status_in_progress_<?php echo $subtopic_id; ?>"
                                       value="in_progress" <?php echo $status === 'in_progress' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-warning" for="status_in_progress_<?php echo $subtopic_id; ?>">In Progress</label>
                                
                                <input type="radio" class="btn-check" name="status" id="status_completed_<?php echo $subtopic_id; ?>"
                                       value="completed" <?php echo $status === 'completed' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-success" for="status_completed_<?php echo $subtopic_id; ?>">Completed</label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confidence Level</label>
                            <div class="range-container">
                                <input type="range" class="form-range" name="confidence" min="0" max="5" step="1" 
                                       value="<?php echo $confidence; ?>">
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
                            <textarea class="form-control" name="notes" rows="2"><?php echo htmlspecialchars($notes); ?></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-link" @click="showUpdateForm = false">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update progress']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid subtopic ID']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 