<?php
require_once '../config/db_connect.php';
include '../includes/header.php';

// Function to calculate days remaining
function getDaysRemaining($due_date) {
    $now = new DateTime();
    $due = new DateTime($due_date);
    $interval = $now->diff($due);
    $days = $interval->days;
    
    if ($now > $due) {
        return ['days' => -$days, 'class' => 'text-danger fw-bold', 'bg' => 'bg-danger bg-opacity-10'];
    }
    
    return ['days' => $days, 'class' => 'text-primary fw-bold', 'bg' => 'bg-light'];
}

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch($status) {
        case 'completed':
            return 'text-success fw-bold';
        case 'in_progress':
            return 'text-warning fw-bold';
        case 'not_started':
            return 'text-secondary fw-bold';
        default:
            return 'text-secondary fw-bold';
    }
}

// Function to get priority badge class
function getPriorityBadgeClass($priority) {
    switch($priority) {
        case 'high':
            return 'bg-danger bg-opacity-10 text-danger';
        default:
            return 'bg-primary bg-opacity-10 text-primary';
    }
}

// Fetch assignments with unit information and criteria count
$sql = "SELECT a.*, u.unit_code, u.unit_name,
        (SELECT COUNT(*) FROM assessment_criteria WHERE assignment_id = a.id) as criteria_count
        FROM access_assignments a 
        LEFT JOIN access_course_units u ON a.unit_id = u.id 
        ORDER BY a.due_date ASC";
$result = $conn->query($sql);
?>

<div class="container-fluid mt-4">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <h2><i class="fas fa-tasks"></i> Assignments</h2>
            <div class="btn-group" role="group" aria-label="View toggle">
                <button type="button" class="btn btn-outline-primary btn-sm active" id="gridView">
                    <i class="fas fa-th-large"></i>
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="listView">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
        <a href="add_assignment.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Assignment
        </a>
    </div>

    <div id="assignmentsContainer" class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <?php while($assignment = $result->fetch_assoc()): 
            $days_info = getDaysRemaining($assignment['due_date']);
            $status_class = getStatusBadgeClass($assignment['status']);
            $priority_class = getPriorityBadgeClass($assignment['priority']);
        ?>
            <div class="col assignment-item">
                <div class="card h-100 shadow-sm hover-shadow">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <span class="badge bg-primary">
                            <i class="fas fa-book me-1"></i>
                            <?php echo htmlspecialchars($assignment['unit_code']); ?>
                        </span>
                        <div>
                            <span class="badge <?php echo $priority_class; ?>">
                                <i class="fas fa-flag"></i> <?php echo ucfirst($assignment['priority']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-truncate mb-3" title="<?php echo htmlspecialchars($assignment['title']); ?>">
                            <?php echo htmlspecialchars($assignment['title']); ?>
                        </h5>
                        <div class="progress mb-3">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo $assignment['progress_percentage']; ?>%"
                                 aria-valuenow="<?php echo $assignment['progress_percentage']; ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-opacity-25 <?php echo $status_class; ?>">
                                <i class="fas fa-clock me-1"></i>
                                <?php 
                                $status_text = str_replace('_', ' ', $assignment['status']);
                                echo ucwords($status_text); 
                                ?>
                            </span>
                            <span class="text-muted">
                                <strong><?php echo round($assignment['progress_percentage']); ?>%</strong> Complete
                            </span>
                        </div>
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="p-2 border rounded-3 h-100 bg-light">
                                    <small class="d-block text-muted">Credits</small>
                                    <strong class="text-primary"><?php echo $assignment['credits']; ?></strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 border rounded-3 h-100 bg-light">
                                    <small class="d-block text-muted">Criteria</small>
                                    <strong class="text-primary"><?php echo $assignment['criteria_count']; ?></strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 border rounded-3 h-100 days-container <?php echo $days_info['bg']; ?>">
                                    <small class="d-block text-muted mb-1">Days Left</small>
                                    <strong class="<?php echo $days_info['class']; ?> d-block">
                                        <?php 
                                        if ($days_info['days'] < 0) {
                                            echo '<i class="fas fa-exclamation-circle"></i> Overdue';
                                        } elseif ($days_info['days'] == 0) {
                                            echo '<i class="fas fa-exclamation-circle"></i> Today';
                                        } else {
                                            echo $days_info['days'];
                                        }
                                        ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="far fa-calendar-alt me-1"></i>
                                Due: <?php echo date('d M Y', strtotime($assignment['due_date'])); ?>
                            </small>
                            <div class="d-flex gap-2">
                                <a href="edit_assignment.php?id=<?php echo $assignment['id']; ?>" 
                                   class="btn btn-sm btn-dark">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="view_assignment.php?id=<?php echo $assignment['id']; ?>" 
                                   class="btn btn-sm btn-dark">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="javascript:void(0)" 
                                   onclick="confirmDelete(<?php echo $assignment['id']; ?>, '<?php echo addslashes($assignment['title']); ?>')" 
                                   class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<style>
.hover-shadow {
    transition: all 0.2s ease-in-out;
}
.hover-shadow:hover {
    box-shadow: 0 .25rem .5rem rgba(0,0,0,.05)!important;
    transform: translateY(-1px);
}
.card {
    border: 1px solid #e6e6e6;
    border-radius: 12px;
    overflow: hidden;
    background: #ffffff;
}
.card:hover {
    border-color: #e6e6e6;
    box-shadow: 0 .25rem .5rem rgba(0,0,0,.05);
}
.card-header {
    background: #f8f9fa;
    padding: 0.75rem;
    border-bottom: 1px solid #e6e6e6;
}
.progress {
    height: 8px;
    border-radius: 4px;
    background-color: #e9ecef;
    margin-bottom: 1rem;
}
.progress-bar {
    background-color: #ffb300;
    border-radius: 4px;
}
.btn-group {
    gap: 8px;
    display: flex;
}
.btn-group .btn {
    border-radius: 6px !important;
    padding: 0.4rem 0.75rem;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
}
.btn-group .btn i {
    font-size: 0.9rem;
}
.btn-dark, 
.btn-outline-primary,
.btn-primary {
    background-color: #2c3e50;
    border-color: #2c3e50;
    color: #fff;
}
.btn-dark:hover, 
.btn-outline-primary:hover,
.btn-primary:hover {
    background-color: #34495e;
    border-color: #34495e;
    color: #fff;
}
.btn-outline-primary {
    background-color: transparent;
    color: #2c3e50;
}
.btn-danger {
    background-color: #ff4444;
    border-color: #ff4444;
}
.btn-danger:hover {
    background-color: #cc0000;
    border-color: #cc0000;
}
.card-footer {
    background: #ffffff;
    padding: 1rem;
    border-top: 1px solid #e6e6e6;
}
.badge {
    padding: 0.4em 0.6em;
    font-weight: 500;
    font-size: 0.875rem;
}
.badge.bg-primary {
    background-color: #ffb300 !important;
    color: #000;
}
.card-title {
    color: #2c3e50;
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 1rem;
}
.text-primary {
    color: #2c3e50 !important;
}
.text-danger {
    color: #dc3545 !important;
}
.bg-primary {
    background-color: #0d6efd !important;
}
.border {
    border-color: #e6e6e6 !important;
}
.text-muted {
    color: #6c757d !important;
}
.fw-bold {
    font-weight: 600 !important;
}
.days-number {
    color: #ff4444 !important;
}
.days-number.text-danger {
    color: #dc3545 !important;
}
.text-success {
    color: #28a745 !important;
}
.text-warning {
    color: #ffc107 !important;
}
.text-secondary {
    color: #6c757d !important;
}

/* List view styles */
#assignmentsContainer.list-view {
    display: block !important;
}

#assignmentsContainer.list-view .assignment-item {
    width: 100% !important;
    margin-bottom: 0.5rem;
}

#assignmentsContainer.list-view .card {
    flex-direction: row;
    align-items: center;
    padding: 0.75rem;
    border: none;
    background: #f8f9fa;
    border-radius: 8px;
}

#assignmentsContainer.list-view .card-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: auto;
    border: none;
    padding: 0;
    margin: 0;
    background: none;
}

#assignmentsContainer.list-view .card-body {
    display: flex;
    align-items: center;
    padding: 0;
    margin-left: 1rem;
    gap: 1.5rem;
    flex: 1;
}

#assignmentsContainer.list-view .title-section {
    min-width: 300px;
    max-width: 300px;
}

#assignmentsContainer.list-view .card-title {
    font-size: 0.95rem;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

#assignmentsContainer.list-view .status-badge {
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

#assignmentsContainer.list-view .progress-section {
    width: 150px;
}

#assignmentsContainer.list-view .progress {
    height: 4px;
    margin: 0;
    border-radius: 2px;
}

#assignmentsContainer.list-view .progress-text {
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

#assignmentsContainer.list-view .stats-group {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-left: auto;
}

#assignmentsContainer.list-view .stat-item {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
}

#assignmentsContainer.list-view .stat-value {
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
}

#assignmentsContainer.list-view .stat-label {
    font-size: 0.75rem;
    color: #6c757d;
}

#assignmentsContainer.list-view .days-left {
    color: #ff4444;
}

#assignmentsContainer.list-view .card-footer {
    margin-left: auto;
    padding: 0;
    border: none;
    display: flex;
    gap: 0.5rem;
}

/* Active button state */
.btn-group .btn.active {
    background-color: #2c3e50;
    color: white;
    border-color: #2c3e50;
}

.days-container {
    text-align: center;
    background: #fff;
    transition: all 0.2s ease-in-out;
}

.days-container:hover {
    border-color: #ffb300;
}

.days-container strong {
    font-size: 1.25rem;
    line-height: 1;
}

.days-container .text-danger {
    color: #dc3545 !important;
}

.days-container .text-primary {
    color: #2c3e50 !important;
}

.days-container .bg-danger {
    background-color: #dc3545 !important;
}

.days-container .bg-primary {
    background-color: #2c3e50 !important;
}
</style>

<script>
function confirmDelete(assignmentId, assignmentTitle) {
    if (confirm('Are you sure you want to delete the assignment "' + assignmentTitle + '"?\n\nThis will permanently delete:\n- All assessment criteria\n- All guidance items\n- All progress records\n- All progress logs\n\nThis action cannot be undone.')) {
        window.location.href = 'delete_assignment.php?id=' + assignmentId;
    }
}

// View toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('assignmentsContainer');
    const gridBtn = document.getElementById('gridView');
    const listBtn = document.getElementById('listView');
    
    // Load saved preference
    const viewPreference = localStorage.getItem('assignmentView') || 'grid';
    if (viewPreference === 'list') {
        container.classList.add('list-view');
        gridBtn.classList.remove('active');
        listBtn.classList.add('active');
    }
    
    gridBtn.addEventListener('click', function() {
        container.classList.remove('list-view');
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
        localStorage.setItem('assignmentView', 'grid');
    });
    
    listBtn.addEventListener('click', function() {
        container.classList.add('list-view');
        gridBtn.classList.remove('active');
        listBtn.classList.add('active');
        localStorage.setItem('assignmentView', 'list');
    });
});
</script>

<?php include '../includes/footer.php'; ?> 