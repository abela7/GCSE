<?php
// GCSE/pages/tasks/_task_card.php (Habit UI Style)
// Expects $task (array) and $formatted_selected_date (string)

// Determine status for styling and logic
$task_status = $task['status'] ?? 'pending';
$is_final_status = in_array($task_status, ['completed', 'not_done']);

// *** TEMPORARY DEBUGGING ***
echo "<!-- Task ID: {$task['id']} | Fetched Status: {$task_status} | Instance ID: " . ($task['instance_id'] ?? 'none') . " -->\n";
// *** END DEBUGGING ***

// Determine status class for visual indication on the card
$status_card_class = match($task_status) {
    'completed' => 'status-completed',
    'snoozed' => 'status-snoozed',
    'not_done' => 'status-not_done',
    default => 'status-pending'
};

// *** TEMPORARY DEBUGGING ***
echo "<!-- Task ID: {$task['id']} | Assigned Class: {$status_card_class} -->\n";
// *** END DEBUGGING ***

// Disable actions if main task is inactive, unless already completed/not_done
$disabled_actions = !$task['is_active'] && !$is_final_status;
$disabled_tooltip = $disabled_actions ? 'title="Task definition is inactive"' : '';

// Prepare category color with transparency for icon background
$category_color_rgb = $task['category_color'] ?? '#6c757d'; // Default grey
// Basic hex to rgb conversion (assumes #RRGGBB format)
$r = hexdec(substr($category_color_rgb, 1, 2));
$g = hexdec(substr($category_color_rgb, 3, 2));
$b = hexdec(substr($category_color_rgb, 5, 2));
$icon_bg_color = "rgba($r, $g, $b, 0.1)"; // 10% opacity

?>
<div class="card task-card-habit-style mb-3 shadow-sm <?php echo $status_card_class; ?>" id="task-card-<?php echo $task['id']; ?>">
    <div class="card-body p-3">
        <!-- Top Section: Icon and Text -->
        <div class="d-flex align-items-center mb-3">
            <!-- Icon Background Wrapper -->
            <div class="task-icon-background flex-shrink-0 me-3" style="background-color: <?php echo $icon_bg_color; ?>;">
                <i class="<?php echo htmlspecialchars($task['category_icon'] ?? 'fas fa-tasks'); ?> fa-lg" style="color: <?php echo htmlspecialchars($task['category_color'] ?? '#6c757d'); ?>;"></i>
            </div>
            <!-- Text Details -->
            <div class="task-text-details flex-grow-1">
                <h6 class="task-title mb-0"><?php echo htmlspecialchars($task['title']); ?></h6>
                <small class="task-meta text-muted d-block">
                    <?php echo htmlspecialchars($task['category_name']); ?>
                    <?php if (!empty($task['due_time'])): ?>
                        • <?php echo date('g:i A', strtotime($task['due_time'])); ?>
                    <?php endif; ?>
                    <?php if ($task['task_type'] === 'recurring'): ?>
                         • <i class="fas fa-redo-alt fa-xs" title="Recurring Task"></i>
                    <?php endif; ?>
                </small>
            </div>
             <!-- Edit link (optional, subtle placement) -->
             <a href="manage_tasks.php?action=edit_task&id=<?php echo $task['id']; ?>&date=<?php echo htmlspecialchars($formatted_selected_date); ?>" class="btn btn-sm btn-link text-muted ms-auto flex-shrink-0" title="Edit Task Definition">
                 <i class="fas fa-pencil-alt"></i>
             </a>
        </div>

        <!-- Bottom Section: Action Buttons or Status Badge -->
        <div class="task-card-actions">
            <?php if ($is_final_status): ?>
                <!-- Show Status Badge Only -->
                <div class="alert alert-<?php echo $task_status === 'completed' ? 'success' : 'danger'; ?> text-center py-2 px-3 mb-0" role="alert">
                    <strong><?php echo ucfirst(str_replace('_', ' ', $task_status)); ?></strong>
                </div>
            <?php else: ?>
                <!-- Show Action Buttons -->
                <div class="d-flex gap-2">
                    <!-- Done Button -->
                    <button type="button" class="btn btn-success w-100 action-btn" 
                            onclick="handleTaskAction(<?php echo $task['id']; ?>, 'done')" 
                            title="Mark as Done" 
                            <?php echo $disabled_tooltip; echo $disabled_actions ? 'disabled' : ''; ?>>
                        <i class="fas fa-check"></i>
                    </button>
                    <!-- Snooze Button -->
                    <button type="button" class="btn btn-warning w-100 action-btn" 
                            onclick="showSnoozeOptions(<?php echo $task['id']; ?>)" 
                            title="Snooze" 
                            <?php echo $disabled_tooltip; echo $disabled_actions ? 'disabled' : ''; ?>>
                        <i class="fas fa-clock"></i>
                    </button>
                    <!-- Not Done Button -->
                    <button type="button" class="btn btn-danger w-100 action-btn" 
                            onclick="handleTaskAction(<?php echo $task['id']; ?>, 'not_done')" 
                            title="Mark as Not Done" 
                            <?php echo $disabled_tooltip; echo $disabled_actions ? 'disabled' : ''; ?>>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function handleTaskAction(taskId, action) {
    const taskCard = document.getElementById('task-card-' + taskId);
    if (!taskCard) return;

    // Disable the card while processing
    taskCard.style.opacity = '0.7';
    taskCard.style.pointerEvents = 'none';

    // Map action to status - ensure we use the correct enum values from database
    let status;
    switch(action) {
        case 'done':
            status = 'completed';
            break;
        case 'not_done':
            status = 'not_done';
            break;
        default:
            status = action;
    }

    // Log the action for debugging
    console.log('Updating task:', taskId, 'with status:', status);

    // Prepare the form data
    const formData = new URLSearchParams();
    formData.append('action', 'update_task_status');
    formData.append('task_id', taskId);
    formData.append('status', status);

    // Send the request
    fetch('task_actions.php', {
        method: 'POST',
        body: formData,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data); // Debug log
        if (data.success) {
            // Fade out and remove the task card
            taskCard.style.transition = 'all 0.3s ease';
            taskCard.style.opacity = '0';
            taskCard.style.transform = 'translateX(20px)';
            setTimeout(() => {
                window.location.reload();
            }, 300);
        } else {
            // Show error and reset the card
            console.error('Server error:', data.message);
            alert('Error: ' + data.message);
            taskCard.style.opacity = '1';
            taskCard.style.pointerEvents = 'auto';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the task.');
        taskCard.style.opacity = '1';
        taskCard.style.pointerEvents = 'auto';
    });
}
</script>