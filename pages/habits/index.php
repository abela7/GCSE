<?php
require_once '../../includes/header.php';
require_once '../../includes/db_connect.php';  // Database connection

// Set timezone to London
date_default_timezone_set('Europe/London');

// Get today's date
$today = date('Y-m-d');
$today_day_of_week = date('w'); // 0 (Sunday) to 6 (Saturday)

// Function to get the start and end dates of the current week
function getWeekBounds($start_day = 0) {
    $today = date('Y-m-d');
    $today_day_of_week = date('w');
    
    // Calculate days to subtract to get to the start of the week
    $days_to_start = ($today_day_of_week - $start_day + 7) % 7;
    
    $week_start = date('Y-m-d', strtotime("-{$days_to_start} days", strtotime($today)));
    $week_end = date('Y-m-d', strtotime("+6 days", strtotime($week_start)));
    
    return ['start' => $week_start, 'end' => $week_end];
}

// Get default week bounds
$default_week_bounds = getWeekBounds(0); // Default starting on Sunday

// Get all habits with their categories and point rules
$habits_query = "SELECT h.*, hc.name as category_name, hc.color as category_color, hc.icon as category_icon,
                 hpr.name as point_rule_name, hpr.completion_points, hpr.procrastinated_points,
                 (SELECT status FROM habit_completions 
                  WHERE habit_id = h.id AND completion_date = ?) as today_status,
                 (SELECT completion_time FROM habit_completions 
                  WHERE habit_id = h.id AND completion_date = ?) as completion_time,
                 (SELECT points_earned FROM habit_completions 
                  WHERE habit_id = h.id AND completion_date = ?) as today_points,
                 
                 /* Get weekly frequency data if available */
                 (SELECT hf.times_per_week FROM habit_frequency hf WHERE hf.habit_id = h.id) as times_per_week,
                 (SELECT hf.week_starts_on FROM habit_frequency hf WHERE hf.habit_id = h.id) as week_starts_on,
                 
                 /* Count completions this week for frequency-based habits */
                 (SELECT COUNT(*) FROM habit_completions hc 
                  WHERE hc.habit_id = h.id 
                  AND hc.completion_date BETWEEN ? AND ?
                  AND hc.status = 'completed') as completions_this_week
                  
                 FROM habits h
                 LEFT JOIN habit_categories hc ON h.category_id = hc.id
                 LEFT JOIN habit_point_rules hpr ON h.point_rule_id = hpr.id
                 WHERE h.is_active = 1
                 AND (
                     /* Daily habits (no schedule entries) */
                     (NOT EXISTS (SELECT 1 FROM habit_schedule hs WHERE hs.habit_id = h.id) 
                      AND NOT EXISTS (SELECT 1 FROM habit_frequency hf WHERE hf.habit_id = h.id))
                     
                     /* OR specific day habits scheduled for today */
                     OR EXISTS (SELECT 1 FROM habit_schedule hs WHERE hs.habit_id = h.id AND hs.day_of_week = ?)
                     
                     /* OR frequency-based habits that haven't met their weekly quota */
                     OR (EXISTS (SELECT 1 FROM habit_frequency hf WHERE hf.habit_id = h.id)
                         AND (SELECT hf.times_per_week FROM habit_frequency hf WHERE hf.habit_id = h.id) > 
                             (SELECT COUNT(*) FROM habit_completions hc 
                              WHERE hc.habit_id = h.id 
                              AND hc.completion_date BETWEEN ? AND ?
                              AND hc.status = 'completed'))
                 )
                 ORDER BY h.target_time";
$stmt = $conn->prepare($habits_query);
$stmt->bind_param("ssssisss", 
    $today, $today, $today, 
    $default_week_bounds['start'], $default_week_bounds['end'],
    $today_day_of_week,
    $default_week_bounds['start'], $default_week_bounds['end']
);
$stmt->execute();
$habits_result = $stmt->get_result();

// Separate habits into morning and evening
$morning_habits = [];
$evening_habits = [];
while ($habit = $habits_result->fetch_assoc()) {
    // Set default icon if none is set
    if (empty($habit['category_icon'])) {
        $habit['category_icon'] = 'fas fa-check-circle';
    }
    
    $time = strtotime($habit['target_time']);
    if ($time < strtotime('12:00:00')) {
        $morning_habits[] = $habit;
    } else {
        $evening_habits[] = $habit;
    }
}
?>

<div class="container-fluid">
    <!-- Greeting Section -->
    <div class="greeting-section">
        <?php
        $hour = date('H');
        $greeting = '';
        $greeting_icon = '';
        
        if ($hour >= 5 && $hour < 12) {
            $greeting = 'Good Morning';
            $greeting_icon = '<i class="fas fa-sun"></i>';
        } elseif ($hour >= 12 && $hour < 17) {
            $greeting = 'Good Afternoon';
            $greeting_icon = '<i class="fas fa-sun"></i>';
        } elseif ($hour >= 17 && $hour < 22) {
            $greeting = 'Good Evening';
            $greeting_icon = '<i class="fas fa-moon"></i>';
        } else {
            $greeting = 'Good Night';
            $greeting_icon = '<i class="fas fa-moon"></i>';
        }
        ?>
        <div class="greeting-container">
            <div class="greeting-left">
                <div class="greeting-icon"><?php echo $greeting_icon; ?></div>
                <span class="greeting-text"><?php echo $greeting; ?> Abela • <?php echo date('l, j F Y'); ?></span>
            </div>
            <div class="greeting-actions">
                <a href="reports.php" class="action-btn analytics-btn">
                    <i class="fas fa-chart-line"></i>
                </a>
                <a href="manage_habits.php" class="action-btn settings-btn">
                    <i class="fas fa-cog"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Morning Habits Section -->
        <div class="col-lg-6">
            <div class="section-header">
                <i class="fas fa-sun" style="color: #f39c12;"></i>
                <span>Morning Habits</span>
            </div>
            <div class="d-flex flex-column gap-3">
                <?php if (count($morning_habits) > 0): ?>
                    <?php foreach ($morning_habits as $habit): ?>
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">
                                        <?php if (!empty($habit['category_icon'])): ?>
                                            <i class="<?= htmlspecialchars($habit['category_icon']) ?> me-2" 
                                               style="color: <?= htmlspecialchars($habit['category_color']) ?>"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($habit['name']) ?>
                                    </h5>
                                    <div>
                                        <?php if (!empty($habit['category_name'])): ?>
                                            <span class="badge rounded-pill" 
                                                  style="background-color: <?= htmlspecialchars($habit['category_color']) ?>">
                                                <?= htmlspecialchars($habit['category_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($habit['description'])): ?>
                                    <p class="card-text text-muted mb-3"><?= htmlspecialchars($habit['description']) ?></p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <i class="fas fa-clock me-1"></i>
                                        <span class="text-muted">Target Time:</span> 
                                        <?= date('g:i A', strtotime($habit['target_time'])) ?>
                                    </div>
                                    
                                    <?php if (!empty($habit['times_per_week'])): ?>
                                        <div>
                                            <i class="fas fa-calendar-check me-1"></i>
                                            <span class="badge bg-info">
                                                <?= $habit['completions_this_week'] ?>/<?= $habit['times_per_week'] ?> times this week
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php 
                                $status_class = '';
                                $status_text = 'Not Completed';
                                $btn_text = 'Mark Complete';
                                $btn_icon = 'fas fa-check';
                                
                                if ($habit['today_status'] == 'completed') {
                                    $status_class = 'bg-success';
                                    $status_text = 'Completed';
                                    $btn_text = 'Completed';
                                    $btn_icon = 'fas fa-check-double';
                                } elseif ($habit['today_status'] == 'procrastinated') {
                                    $status_class = 'bg-warning';
                                    $status_text = 'Procrastinated';
                                    $btn_text = 'Mark Complete';
                                    $btn_icon = 'fas fa-check';
                                }
                                ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge <?= $status_class ?> me-2"><?= $status_text ?></span>
                                        <?php if (!empty($habit['completion_time'])): ?>
                                            <small class="text-muted">
                                                Completed at <?= htmlspecialchars($habit['completion_time']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex">
                                        <?php if ($habit['today_status'] != 'completed'): ?>
                                            <button class="btn btn-sm btn-primary me-2 mark-complete-btn" 
                                                    data-habit-id="<?= $habit['id'] ?>"
                                                    data-points="<?= $habit['completion_points'] ?>">
                                                <i class="<?= $btn_icon ?> me-1"></i> <?= $btn_text ?>
                                            </button>
                                            
                                            <button class="btn btn-sm btn-outline-warning procrastinate-btn"
                                                    data-habit-id="<?= $habit['id'] ?>"
                                                    data-points="<?= $habit['procrastinated_points'] ?>">
                                                <i class="fas fa-clock me-1"></i> Procrastinate
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-plus-circle me-1"></i> 
                                                <?= $habit['today_points'] ?> points earned
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card border-0">
                        <div class="card-body">
                            <h3 class="mb-1 text-truncate fw-bold">No morning habits found</h3>
                            <div class="text-muted text-truncate">
                                It looks like you haven't added any morning habits yet.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evening Habits Section -->
        <div class="col-lg-6">
            <div class="section-header">
                <i class="fas fa-moon" style="color: #2c3e50;"></i>
                <span>Evening Habits</span>
            </div>
            <div class="d-flex flex-column gap-3">
                <?php if (count($evening_habits) > 0): ?>
                    <?php foreach ($evening_habits as $habit): ?>
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">
                                        <?php if (!empty($habit['category_icon'])): ?>
                                            <i class="<?= htmlspecialchars($habit['category_icon']) ?> me-2" 
                                               style="color: <?= htmlspecialchars($habit['category_color']) ?>"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($habit['name']) ?>
                                    </h5>
                                    <div>
                                        <?php if (!empty($habit['category_name'])): ?>
                                            <span class="badge rounded-pill" 
                                                  style="background-color: <?= htmlspecialchars($habit['category_color']) ?>">
                                                <?= htmlspecialchars($habit['category_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($habit['description'])): ?>
                                    <p class="card-text text-muted mb-3"><?= htmlspecialchars($habit['description']) ?></p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <i class="fas fa-clock me-1"></i>
                                        <span class="text-muted">Target Time:</span> 
                                        <?= date('g:i A', strtotime($habit['target_time'])) ?>
                                    </div>
                                    
                                    <?php if (!empty($habit['times_per_week'])): ?>
                                        <div>
                                            <i class="fas fa-calendar-check me-1"></i>
                                            <span class="badge bg-info">
                                                <?= $habit['completions_this_week'] ?>/<?= $habit['times_per_week'] ?> times this week
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php 
                                $status_class = '';
                                $status_text = 'Not Completed';
                                $btn_text = 'Mark Complete';
                                $btn_icon = 'fas fa-check';
                                
                                if ($habit['today_status'] == 'completed') {
                                    $status_class = 'bg-success';
                                    $status_text = 'Completed';
                                    $btn_text = 'Completed';
                                    $btn_icon = 'fas fa-check-double';
                                } elseif ($habit['today_status'] == 'procrastinated') {
                                    $status_class = 'bg-warning';
                                    $status_text = 'Procrastinated';
                                    $btn_text = 'Mark Complete';
                                    $btn_icon = 'fas fa-check';
                                }
                                ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge <?= $status_class ?> me-2"><?= $status_text ?></span>
                                        <?php if (!empty($habit['completion_time'])): ?>
                                            <small class="text-muted">
                                                Completed at <?= htmlspecialchars($habit['completion_time']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex">
                                        <?php if ($habit['today_status'] != 'completed'): ?>
                                            <button class="btn btn-sm btn-primary me-2 mark-complete-btn" 
                                                    data-habit-id="<?= $habit['id'] ?>"
                                                    data-points="<?= $habit['completion_points'] ?>">
                                                <i class="<?= $btn_icon ?> me-1"></i> <?= $btn_text ?>
                                            </button>
                                            
                                            <button class="btn btn-sm btn-outline-warning procrastinate-btn"
                                                    data-habit-id="<?= $habit['id'] ?>"
                                                    data-points="<?= $habit['procrastinated_points'] ?>">
                                                <i class="fas fa-clock me-1"></i> Procrastinate
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-plus-circle me-1"></i> 
                                                <?= $habit['today_points'] ?> points earned
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card border-0">
                        <div class="card-body">
                            <h3 class="mb-1 text-truncate fw-bold">No evening habits found</h3>
                            <div class="text-muted text-truncate">
                                It looks like you haven't added any evening habits yet.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Reason Modal -->
<div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reasonModalLabel">Select a Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reasonForm" method="POST" action="update_habit_status.php">
                <div class="modal-body">
                    <input type="hidden" name="habit_id" id="modalHabitId">
                    <input type="hidden" name="status" id="modalStatus">
                    <input type="hidden" name="scroll_position" id="modalScrollPosition">
                    <div class="mb-3">
                        <label for="reason_id" class="form-label">Why are you choosing this option?</label>
                        <select class="form-select" name="reason_id" id="reason_id" required>
                            <option value="">Choose a reason...</option>
                            <?php
                            // Get reasons from database
                            $reasons_query = "SELECT id, reason_text FROM habit_reasons WHERE is_default = 1";
                            $reasons_result = $conn->query($reasons_query);
                            while ($reason = $reasons_result->fetch_assoc()) {
                                echo '<option value="' . $reason['id'] . '">' . htmlspecialchars($reason['reason_text']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control" name="notes" id="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the modal
    const reasonModal = new bootstrap.Modal(document.getElementById('reasonModal'));
    
    // Handle Later and Skip buttons
    document.querySelectorAll('.btn-later, .btn-skip').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const habitId = form.querySelector('input[name="habit_id"]').value;
            const status = form.querySelector('input[name="status"]').value;
            
            // Set modal form values
            document.getElementById('modalHabitId').value = habitId;
            document.getElementById('modalStatus').value = status;
            document.getElementById('modalScrollPosition').value = window.scrollY;
            
            // Update modal title based on status
            const modalTitle = document.getElementById('reasonModalLabel');
            modalTitle.textContent = status === 'procrastinated' ? 'Why are you procrastinating?' : 'Why are you skipping?';
            
            // Show the modal
            reasonModal.show();
        });
    });
    
    // Handle Done and Reset buttons (maintain scroll position)
    document.querySelectorAll('form:not(#reasonForm)').forEach(form => {
        form.addEventListener('submit', function(e) {
            const scrollPosition = window.scrollY;
            const scrollInput = document.createElement('input');
            scrollInput.type = 'hidden';
            scrollInput.name = 'scroll_position';
            scrollInput.value = scrollPosition;
            this.appendChild(scrollInput);
        });
    });

    // Handle modal form submission
    const reasonForm = document.getElementById('reasonForm');
    if (reasonForm) {
        reasonForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get the selected reason
            const reasonSelect = document.getElementById('reason_id');
            if (!reasonSelect.value) {
                alert('Please select a reason');
                return;
            }
            
            // Add scroll position to form
            const scrollInput = document.createElement('input');
            scrollInput.type = 'hidden';
            scrollInput.name = 'scroll_position';
            scrollInput.value = window.scrollY;
            this.appendChild(scrollInput);
            
            // Submit the form
            this.submit();
        });
    }

    // Mark complete button functionality
    document.querySelectorAll('.mark-complete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const habitId = this.getAttribute('data-habit-id');
            const points = this.getAttribute('data-points');
            
            fetch('update_habit_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `habit_id=${habitId}&status=completed`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success notification
                    showNotification(`Habit completed! +${points} points`, 'success');
                    
                    // Refresh the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification('Error updating habit status', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error updating habit status', 'danger');
            });
        });
    });
    
    // Procrastinate button functionality
    document.querySelectorAll('.procrastinate-btn').forEach(button => {
        button.addEventListener('click', function() {
            const habitId = this.getAttribute('data-habit-id');
            const points = this.getAttribute('data-points');
            
            fetch('update_habit_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `habit_id=${habitId}&status=procrastinated`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success notification
                    showNotification(`Habit procrastinated. +${points} points`, 'warning');
                    
                    // Refresh the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification('Error updating habit status', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Network error updating habit status', 'danger');
            });
        });
    });
    
    // Notification helper function
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.classList.add('toast', 'show', `bg-${type}`, 'text-white');
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'assertive');
        notification.setAttribute('aria-atomic', 'true');
        
        notification.innerHTML = `
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        // Add to notification container or create one
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.classList.add('toast-container', 'position-fixed', 'bottom-0', 'end-0', 'p-3');
            document.body.appendChild(container);
        }
        
        container.appendChild(notification);
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
});
</script>

<style>
/* Base Styles */
:root {
    --primary-color: #cdaf56;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --text-color: #2d3436;
    --text-muted: #636e72;
    --border-radius: 16px;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --transition-speed: 0.3s;
}

body {
    color: var(--text-color);
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
}

.container-fluid {
    padding: 0 1rem;
}

/* Card Styles */
.card {
    transition: all var(--transition-speed) ease;
    border-radius: var(--border-radius) !important;
    box-shadow: var(--card-shadow);
    background: white;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.card.border-success {
    border-color: var(--success-color) !important;
    background: linear-gradient(to right, rgba(40, 167, 69, 0.05), white) !important;
}

.card.border-warning {
    border-color: var(--warning-color) !important;
    background: linear-gradient(to right, rgba(255, 193, 7, 0.05), white) !important;
}

.card.border-danger {
    border-color: var(--danger-color) !important;
    background: linear-gradient(to right, rgba(220, 53, 69, 0.05), white) !important;
}

/* Habit Icon */
.habit-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.03), rgba(0, 0, 0, 0.06));
    border-radius: 14px;
    transition: all var(--transition-speed);
}

.habit-icon i {
    transition: all var(--transition-speed);
}

.card:hover .habit-icon {
    transform: scale(1.1);
}

/* Button Styles */
.btn {
    border-radius: 12px;
    transition: all var(--transition-speed);
    font-weight: 500;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-outline-success {
    border-width: 2px;
}

.btn-outline-warning {
    border-width: 2px;
}

.btn-outline-danger {
    border-width: 2px;
}

/* Status Message Styles */
.status-message {
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 10px;
    background-color: rgba(0, 0, 0, 0.03);
}

/* Section Headers */
.section-header {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-header i {
    font-size: 1.25rem;
}

/* Responsive Styles */
@media (min-width: 992px) {
    .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 2rem;
    }
    
    .greeting-section {
        padding: 1rem 0;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .habit-icon {
        width: 56px;
        height: 56px;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
    }
    
    .row {
        margin-left: -1rem;
        margin-right: -1rem;
    }
    
    .col-lg-6 {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding: 0 1rem;
    }
    
    .greeting-section {
        padding: 0.75rem 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .habit-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
    }
    
    .btn {
        padding: 0.5rem;
        font-size: 0.9rem;
    }
    
    .btn i {
        font-size: 1rem;
    }
    
    h1.h4 {
        font-size: 1.25rem;
    }
    
    .section-header {
        font-size: 1rem;
        margin-bottom: 1rem;
    }
}

/* Animation Keyframes */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.3s ease-out;
}

/* Enhanced Greeting Styles */
.greeting-section {
    background: white;
    padding: 0.75rem 1rem;
    margin-bottom: 1.5rem;
}

.greeting-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.greeting-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.greeting-icon {
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.05);
}

.greeting-icon i {
    font-size: 1rem;
    color: #cdaf56;
}

.greeting-text {
    font-size: 1rem;
    color: #2d3436;
    font-weight: 500;
}

.greeting-text span {
    color: #636e72;
    font-weight: normal;
}

.greeting-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 2rem;
    height: 2rem;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    color: inherit;
}

.action-btn i {
    font-size: 0.9rem;
    color: #2d3436;
}

.action-btn:hover {
    background: rgba(0, 0, 0, 0.05);
    text-decoration: none;
    color: inherit;
}

.settings-btn {
    background: #cdaf56;
}

.settings-btn i {
    color: #2d3436;
}

.settings-btn:hover {
    background: #c4a64d;
}

@media (max-width: 576px) {
    .greeting-section {
        padding: 0.5rem 0.75rem;
    }

    .greeting-icon {
        width: 1.75rem;
        height: 1.75rem;
    }

    .greeting-icon i {
        font-size: 0.9rem;
    }

    .greeting-text {
        font-size: 0.9rem;
    }

    .action-btn {
        width: 1.75rem;
        height: 1.75rem;
    }

    .action-btn i {
        font-size: 0.8rem;
    }
}

/* Modal Styles */
.modal-content {
    border-radius: var(--border-radius);
    border: none;
}

.modal-header {
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding: 1rem 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid rgba(0,0,0,0.1);
    padding: 1rem 1.5rem;
}

.form-select {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all var(--transition-speed);
}

.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(205, 175, 86, 0.25);
}

.form-control {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all var(--transition-speed);
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(205, 175, 86, 0.25);
}

/* Mobile Responsive Modal */
@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .modal-content {
        border-radius: 16px;
    }
    
    .modal-header {
        padding: 1rem;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-footer {
        padding: 1rem;
    }
    
    .form-select,
    .form-control {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?> 