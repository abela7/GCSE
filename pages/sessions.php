<?php
// Set page title
$page_title = "Study Sessions";

// Set breadcrumbs
$breadcrumbs = [
    'Study Sessions' => null
];

// Set page actions
$page_actions = '
<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSessionModal">
    <i class="fas fa-plus me-1"></i> Add Session
</button>
';

// Include database connection
require_once '../config/db_connect.php';

// Get sessions
$sessions_query = "SELECT s.*, sub.name as subject_name, sub.color as subject_color 
                  FROM sessions s 
                  JOIN subjects sub ON s.subject_id = sub.id 
                  ORDER BY s.date DESC";
$sessions_result = $conn->query($sessions_query);

// Get subjects for dropdown
$subjects_query = "SELECT * FROM subjects ORDER BY name ASC";
$subjects_result = $conn->query($subjects_query);

// Include header
include '../includes/header.php';
?>

<!-- Add Session Modal -->
<div class="modal fade" id="addSessionModal" tabindex="-1" aria-labelledby="addSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSessionModalLabel">Add Study Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addSessionForm" action="../includes/add_session.php" method="post">
                    <div class="mb-3">
                        <label for="sessionSubject" class="form-label">Subject</label>
                        <select class="form-select" id="sessionSubject" name="subject_id" required>
                            <option value="">-- Select Subject --</option>
                            <?php 
                            if ($subjects_result->num_rows > 0) {
                                $subjects_result->data_seek(0);
                                while ($subject = $subjects_result->fetch_assoc()) {
                                    echo '<option value="' . $subject['id'] . '">' . htmlspecialchars($subject['name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sessionDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="sessionDate" name="date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="sessionDuration" class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" id="sessionDuration" name="duration" min="5" max="480" required value="60">
                    </div>
                    
                    <div class="mb-3">
                        <label for="sessionNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="sessionNotes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Session</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Session Modal -->
<div class="modal fade" id="editSessionModal" tabindex="-1" aria-labelledby="editSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSessionModalLabel">Edit Study Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editSessionForm" action="../includes/edit_session.php" method="post">
                    <input type="hidden" id="editSessionId" name="session_id">
                    
                    <div class="mb-3">
                        <label for="editSessionSubject" class="form-label">Subject</label>
                        <select class="form-select" id="editSessionSubject" name="subject_id" required>
                            <option value="">-- Select Subject --</option>
                            <?php 
                            if ($subjects_result->num_rows > 0) {
                                $subjects_result->data_seek(0);
                                while ($subject = $subjects_result->fetch_assoc()) {
                                    echo '<option value="' . $subject['id'] . '">' . htmlspecialchars($subject['name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editSessionDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="editSessionDate" name="date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editSessionDuration" class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" id="editSessionDuration" name="duration" min="5" max="480" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editSessionNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="editSessionNotes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Session</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container for JavaScript notifications -->
<div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

<!-- Sessions Overview -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Study Time Overview</h5>
            </div>
            <div class="card-body">
                <?php
                // Calculate total study time
                $total_time_query = "SELECT SUM(duration) as total_time FROM sessions";
                $total_time_result = $conn->query($total_time_query);
                $total_minutes = $total_time_result->fetch_assoc()['total_time'] ?? 0;
                
                // Calculate study time by subject
                $subject_time_query = "SELECT s.name, s.color, SUM(se.duration) as subject_time 
                                      FROM sessions se 
                                      JOIN subjects s ON se.subject_id = s.id 
                                      GROUP BY se.subject_id 
                                      ORDER BY subject_time DESC";
                $subject_time_result = $conn->query($subject_time_query);
                
                // Format total time
                $total_hours = floor($total_minutes / 60);
                $remaining_minutes = $total_minutes % 60;
                $total_time_formatted = $total_hours . 'h ' . $remaining_minutes . 'm';
                ?>
                
                <div class="text-center mb-4">
                    <h2 class="display-4"><?php echo $total_time_formatted; ?></h2>
                    <p class="text-muted">Total study time</p>
                </div>
                
                <?php if ($subject_time_result->num_rows > 0): ?>
                    <h6>Time by Subject</h6>
                    <?php while ($subject = $subject_time_result->fetch_assoc()): 
                        $subject_percentage = ($subject['subject_time'] / $total_minutes) * 100;
                        $subject_hours = floor($subject['subject_time'] / 60);
                        $subject_minutes = $subject['subject_time'] % 60;
                        $subject_time_formatted = $subject_hours . 'h ' . $subject_minutes . 'm';
                    ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span><?php echo htmlspecialchars($subject['name']); ?></span>
                                <span><?php echo $subject_time_formatted; ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $subject_percentage; ?>%; background-color: <?php echo $subject['color']; ?>" 
                                     aria-valuenow="<?php echo $subject_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted">No study sessions recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php
                // Get recent sessions
                $recent_sessions_query = "SELECT s.*, sub.name as subject_name, sub.color as subject_color 
                                         FROM sessions s 
                                         JOIN subjects sub ON s.subject_id = sub.id 
                                         ORDER BY s.date DESC 
                                         LIMIT 5";
                $recent_sessions_result = $conn->query($recent_sessions_query);
                
                if ($recent_sessions_result->num_rows > 0):
                ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($session = $recent_sessions_result->fetch_assoc()): 
                            $session_hours = floor($session['duration'] / 60);
                            $session_minutes = $session['duration'] % 60;
                            $session_duration = ($session_hours > 0 ? $session_hours . 'h ' : '') . $session_minutes . 'm';
                        ?>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <span class="badge" style="background-color: <?php echo $session['subject_color']; ?>">
                                            <?php echo htmlspecialchars($session['subject_name']); ?>
                                        </span>
                                        <span class="ms-2"><?php echo $session_duration; ?></span>
                                    </div>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($session['date'])); ?></small>
                                </div>
                                <?php if ($session['notes']): ?>
                                    <small class="d-block text-muted mt-1"><?php echo htmlspecialchars($session['notes']); ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">No recent study sessions.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Sessions List -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Study Sessions</h5>
    </div>
    <div class="card-body">
        <?php if ($sessions_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Duration</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($session = $sessions_result->fetch_assoc()): 
                            $session_hours = floor($session['duration'] / 60);
                            $session_minutes = $session['duration'] % 60;
                            $session_duration = ($session_hours > 0 ? $session_hours . 'h ' : '') . $session_minutes . 'm';
                        ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($session['date'])); ?></td>
                                <td>
                                    <span class="badge" style="background-color: <?php echo $session['subject_color']; ?>">
                                        <?php echo htmlspecialchars($session['subject_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo $session_duration; ?></td>
                                <td><?php echo htmlspecialchars($session['notes'] ?? ''); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary edit-session-btn" data-session-id="<?php echo $session['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger delete-session-btn" data-session-id="<?php echo $session['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No study sessions recorded yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add session form submission
    const addSessionForm = document.getElementById('addSessionForm');
    
    if (addSessionForm) {
        addSessionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert('Study session added successfully!', 'success');
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addSessionModal'));
                    modal.hide();
                    
                    // Reload page to show new session
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error message
                    showAlert('Error adding study session: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while adding the study session.', 'danger');
            });
        });
    }
    
    // Edit session buttons
    const editSessionBtns = document.querySelectorAll('.edit-session-btn');
    const editSessionModal = new bootstrap.Modal(document.getElementById('editSessionModal'));
    
    editSessionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const sessionId = this.getAttribute('data-session-id');
            console.log('Edit button clicked for session ID:', sessionId);
            
            if (!sessionId) {
                showAlert('Error: Session ID is missing', 'danger');
                return;
            }
            
            // Store session ID in a data attribute on the form for redundancy
            const editForm = document.getElementById('editSessionForm');
            editForm.setAttribute('data-session-id', sessionId);
            
            // Clear form fields before loading new data
            document.getElementById('editSessionId').value = '';
            document.getElementById('editSessionSubject').value = '';
            document.getElementById('editSessionDate').value = '';
            document.getElementById('editSessionDuration').value = '';
            document.getElementById('editSessionNotes').value = '';
            
            // Show loading indicator in the modal
            const modalBody = document.querySelector('#editSessionModal .modal-body');
            const loadingIndicator = document.createElement('div');
            loadingIndicator.id = 'editFormLoading';
            loadingIndicator.className = 'text-center my-3';
            loadingIndicator.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading session data...</p>';
            
            // Insert loading indicator at the beginning of the form
            editForm.style.display = 'none';
            modalBody.insertBefore(loadingIndicator, editForm);
            
            // Show modal while loading
            editSessionModal.show();
            
            // Use absolute path to avoid path resolution issues
            fetch(`../includes/get_session.php?session_id=${sessionId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`Server returned ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Session data:', data);
                    
                    // Remove loading indicator and show form
                    const loadingElem = document.getElementById('editFormLoading');
                    if (loadingElem) loadingElem.remove();
                    editForm.style.display = 'block';
                    
                    if (data.success) {
                        // Populate form with session data
                        document.getElementById('editSessionId').value = data.session.id;
                        document.getElementById('editSessionSubject').value = data.session.subject_id;
                        document.getElementById('editSessionDate').value = data.session.date;
                        document.getElementById('editSessionDuration').value = data.session.duration;
                        document.getElementById('editSessionNotes').value = data.session.notes || '';
                    } else {
                        showAlert('Error loading session data: ' + data.message, 'danger');
                        editSessionModal.hide();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Remove loading indicator
                    const loadingElem = document.getElementById('editFormLoading');
                    if (loadingElem) loadingElem.remove();
                    editForm.style.display = 'block';
                    
                    showAlert('An error occurred while loading the session data: ' + error.message, 'danger');
                    editSessionModal.hide();
                });
        });
    });
    
    // Edit session form submission
    const editSessionForm = document.getElementById('editSessionForm');
    
    if (editSessionForm) {
        editSessionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const sessionId = document.getElementById('editSessionId').value || this.getAttribute('data-session-id');
            
            // Add session ID to form data if it's missing
            if (!formData.has('session_id') || !formData.get('session_id')) {
                formData.set('session_id', sessionId);
            }
            
            // Validate session ID
            if (!sessionId) {
                showAlert('Error: Session ID is missing', 'danger');
                return;
            }
            
            // Disable submit button to prevent double submission
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                if (data.success) {
                    // Show success message
                    showAlert('Study session updated successfully!', 'success');
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editSessionModal'));
                    modal.hide();
                    
                    // Reload page to show updated session
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error message
                    showAlert('Error updating study session: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                showAlert('An error occurred while updating the study session: ' + error.message, 'danger');
            });
        });
    }
    
    // Delete session buttons
    const deleteSessionBtns = document.querySelectorAll('.delete-session-btn');
    
    deleteSessionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const sessionId = this.getAttribute('data-session-id');
            
            if (!sessionId) {
                showAlert('Error: Session ID is missing', 'danger');
                return;
            }
            
            if (confirm('Are you sure you want to delete this study session?')) {
                // Disable button to prevent multiple clicks
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                
                // Create FormData object instead of using URL encoding directly
                const formData = new FormData();
                formData.append('session_id', sessionId);
                
                // Log what we're sending
                console.log('Deleting session with ID:', sessionId);
                
                fetch('../includes/delete_session.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Delete response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`Server returned ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Delete response data:', data);
                    if (data.success) {
                        // Show success message
                        showAlert('Study session deleted successfully!', 'success');
                        
                        // Remove row from table
                        this.closest('tr').remove();
                        
                        // Reload page to update statistics
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // Re-enable button if there's an error
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-trash"></i>';
                        
                        // Show error message
                        showAlert('Error deleting study session: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Re-enable button
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-trash"></i>';
                    
                    showAlert('An error occurred while deleting the study session: ' + error.message, 'danger');
                });
            }
        });
    });
    
    // Function to show alerts
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container');
        if (!alertContainer) return;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        alertContainer.appendChild(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => {
                alertContainer.removeChild(alert);
            }, 150);
        }, 5000);
    }
});
</script>

<?php
// Include footer
include '../includes/footer.php';

// Close database connection
close_connection($conn);
?>