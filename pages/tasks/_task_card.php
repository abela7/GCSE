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
<div class="card task-card-habit-style mb-3 shadow-sm <?php echo $status_card_class; ?>">
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
                    <!-- Done Form -->
                    <form method="post" action="manage_tasks.php" class="flex-fill">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                        <input type="hidden" name="status" value="completed">
                        <input type="hidden" name="current_view_date" value="<?php echo htmlspecialchars($formatted_selected_date); ?>">
                        <?php if (!empty($task['instance_id'])): ?><input type="hidden" name="due_date" value="<?php echo htmlspecialchars($task['due_date']); ?>"><?php endif; ?>
                        <button type="submit" class="btn btn-success w-100 action-btn" title="Mark as Done" <?php echo $disabled_tooltip; echo $disabled_actions ? 'disabled' : ''; ?>>
                            <i class="fas fa-check"></i>
                        </button>
                    </form>
                    <!-- Snooze Form -->
                    <form method="post" action="manage_tasks.php" class="flex-fill">
                        <input type="hidden" name="action" value="snooze_task">
                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                        <input type="hidden" name="current_view_date" value="<?php echo htmlspecialchars($formatted_selected_date); ?>">
                        <?php if (!empty($task['instance_id'])): // Pass instance date ONLY if it's an instance ?>
                        <input type="hidden" name="due_date" value="<?php echo htmlspecialchars($task['due_date']); ?>">
                        <?php endif; ?>
                        <button type="submit" class="btn btn-warning w-100 action-btn" title="Snooze (<?php echo DEFAULT_SNOOZE_MINUTES; ?> min)" <?php echo $disabled_tooltip; echo $disabled_actions ? 'disabled' : ''; ?>>
                            <i class="fas fa-clock"></i>
                        </button>
                    </form>
                    <!-- Not Done Form -->
                    <form method="post" action="manage_tasks.php" class="flex-fill">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                        <input type="hidden" name="status" value="not_done">
                        <input type="hidden" name="current_view_date" value="<?php echo htmlspecialchars($formatted_selected_date); ?>">
                         <?php if (!empty($task['instance_id'])): ?><input type="hidden" name="due_date" value="<?php echo htmlspecialchars($task['due_date']); ?>"><?php endif; ?>
                        <button type="submit" class="btn btn-danger w-100 action-btn" title="Mark as Not Done" <?php echo $disabled_tooltip; echo $disabled_actions ? 'disabled' : ''; ?>>
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>