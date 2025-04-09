<?php
require_once __DIR__ . '/../../includes/auth_check.php';

$page_title = "Assignment Details";
include_once __DIR__ . '/../../includes/header.php';
include '../config/db_connect.php';

if (!isset($_GET['id'])) {
    header('Location: assignments.php');
    exit();
}

$assignment_id = (int)$_GET['id'];

// Fetch assignment details
$sql = "SELECT * FROM access_assignments WHERE id = $assignment_id";
$result = mysqli_query($conn, $sql);
$assignment = mysqli_fetch_assoc($result);

if (!$assignment) {
    header('Location: assignments.php');
    exit();
}

// Fetch assessment criteria
$sql = "SELECT * FROM assessment_criteria WHERE assignment_id = $assignment_id ORDER BY id ASC";
$criteria_result = mysqli_query($conn, $sql);

// Calculate progress
$total_criteria = mysqli_num_rows($criteria_result);
$completed_criteria = 0;
$in_progress_criteria = 0;

mysqli_data_seek($criteria_result, 0);
while ($criterion = mysqli_fetch_assoc($criteria_result)) {
    if ($criterion['status'] === 'completed') {
        $completed_criteria++;
    } elseif ($criterion['status'] === 'in_progress') {
        $in_progress_criteria++;
    }
}

$progress = $total_criteria > 0 ? ($completed_criteria / $total_criteria) * 100 : 0;
$days_remaining = ceil((strtotime($assignment['due_date']) - time()) / (60 * 60 * 24));
$status_class = $days_remaining < 7 ? 'danger' : ($days_remaining < 14 ? 'warning' : 'success');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?php echo htmlspecialchars($assignment['title']); ?></h1>
        <div>
            <button class="btn btn-outline-primary me-2" onclick="window.location.href='assignments.php'">
                <i class="fas fa-arrow-left"></i> Back to Assignments
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateProgressModal">
                <i class="fas fa-edit"></i> Update Progress
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Assignment Details -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Assignment Details</h5>
                    <div class="mb-3">
                        <label class="text-muted">Credits</label>
                        <p><?php echo $assignment['credits']; ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Due Date</label>
                        <p>
                            <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                            <span class="badge bg-<?php echo $status_class; ?> ms-2">
                                <?php echo $days_remaining; ?> days left
                            </span>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Priority</label>
                        <p><?php echo ucfirst($assignment['priority']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Estimated Hours</label>
                        <p><?php echo $assignment['estimated_hours']; ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Description</label>
                        <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Overview -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Progress Overview</h5>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Overall Progress</span>
                            <span><?php echo round($progress); ?>%</span>
                        </div>
                        <div class="progress mb-3">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <div class="row text-center">
                            <div class="col">
                                <h6 class="mb-0"><?php echo $total_criteria; ?></h6>
                                <small class="text-muted">Total Criteria</small>
                            </div>
                            <div class="col">
                                <h6 class="mb-0"><?php echo $completed_criteria; ?></h6>
                                <small class="text-muted">Completed</small>
                            </div>
                            <div class="col">
                                <h6 class="mb-0"><?php echo $in_progress_criteria; ?></h6>
                                <small class="text-muted">In Progress</small>
                            </div>
                        </div>
                    </div>

                    <h5 class="card-title">Assessment Criteria</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Criterion</th>
                                    <th>Grade Required</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($criteria_result, 0);
                                while ($criterion = mysqli_fetch_assoc($criteria_result)):
                                    $status_class = match($criterion['status']) {
                                        'completed' => 'success',
                                        'in_progress' => 'warning',
                                        default => 'secondary'
                                    };
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($criterion['criteria_text']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo ucfirst($criterion['grade_required']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $criterion['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($criterion['notes'] ?? ''); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Progress Modal -->
<div class="modal fade" id="updateProgressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Progress</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="assignments.php">
                <input type="hidden" name="action" value="update_progress">
                <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Criterion</label>
                        <select class="form-select" name="criteria_id" required>
                            <?php
                            mysqli_data_seek($criteria_result, 0);
                            while ($criterion = mysqli_fetch_assoc($criteria_result)):
                            ?>
                            <option value="<?php echo $criterion['id']; ?>">
                                <?php echo htmlspecialchars($criterion['criteria_text']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="not_started">Not Started</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Progress</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.progress {
    height: 8px;
    border-radius: 4px;
}
.progress-bar {
    background-color: #007bff;
}
</style>

<?php include '../includes/footer.php'; ?> 