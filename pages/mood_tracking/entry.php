<?php
// Include required files
require_once __DIR__ . '/includes/functions.php';

// Set page title
$page_title = "Mood Entry";

// Include header
require_once __DIR__ . '/../../includes/header.php';

// Initialize variables
$entry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$entry = null;
$is_edit = false;

// If entry ID is provided, get entry data
if ($entry_id > 0) {
    $entry = getMoodEntry($entry_id);
    $is_edit = ($entry !== false);
}

// Get mood tags
$mood_tags = getMoodTags();
?>

<style>
:root {
    --accent-color: #cdaf56;
    --accent-color-light: #dbc77a;
    --accent-color-dark: #b99b3e;
}

.mood-emoji {
    font-size: 2.5rem;
    cursor: pointer;
    transition: transform 0.2s;
    opacity: 0.5;
    margin: 0 5px;
}

.mood-emoji:hover, .mood-emoji.selected {
    transform: scale(1.2);
    opacity: 1;
}

.tag-badge {
    margin-right: 5px;
    margin-bottom: 5px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 1rem;
    padding: 8px 12px;
}

.tag-badge:hover, .tag-badge.selected {
    transform: translateY(-2px);
}

.btn-accent {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
    color: white;
}

.btn-accent:hover {
    background-color: var(--accent-color-dark);
    border-color: var(--accent-color-dark);
    color: white;
}

.btn-outline-accent {
    color: var(--accent-color);
    border-color: var(--accent-color);
}

.btn-outline-accent:hover {
    background-color: var(--accent-color);
    color: white;
}

/* Mobile Responsiveness Improvements */
@media (max-width: 767.98px) {
    .mood-emoji {
        font-size: 2rem;
        margin: 0 2px;
    }
    
    .tag-badge {
        font-size: 0.9rem;
        padding: 6px 10px;
    }
    
    .btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.9rem;
    }
    
    h1.h3 {
        font-size: 1.5rem;
    }
    
    .card-title {
        font-size: 1.1rem;
    }
}

/* Touch-friendly improvements */
@media (max-width: 576px) {
    .mood-emoji {
        font-size: 2.2rem;
        padding: 10px;
        margin: 0;
    }
    
    .mood-emoji-container {
        justify-content: space-between;
        width: 100%;
    }
    
    .tag-badge {
        padding: 8px 12px;
        margin-bottom: 10px;
    }
    
    .form-control, .form-select {
        font-size: 16px; /* Prevents iOS zoom on focus */
        padding: 12px;
        height: auto;
    }
    
    .btn {
        padding: 12px 16px;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-3"><?php echo $is_edit ? 'Edit Mood Entry' : 'New Mood Entry'; ?></h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="index.php" class="btn btn-outline-accent">
                <i class="fas fa-arrow-left me-2"></i>Back to Tracker
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form id="mood-entry-form">
                        <?php if ($is_edit): ?>
                        <input type="hidden" name="entry_id" value="<?php echo $entry_id; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <label class="form-label">How are you feeling?</label>
                            <div class="d-flex justify-content-between mood-emoji-container">
                                <div class="text-center">
                                    <div class="mood-emoji <?php echo ($is_edit && $entry['mood_level'] == 1) ? 'selected' : ''; ?>" data-level="1">😢</div>
                                    <div>Very Low</div>
                                </div>
                                <div class="text-center">
                                    <div class="mood-emoji <?php echo ($is_edit && $entry['mood_level'] == 2) ? 'selected' : ''; ?>" data-level="2">😕</div>
                                    <div>Low</div>
                                </div>
                                <div class="text-center">
                                    <div class="mood-emoji <?php echo ($is_edit && $entry['mood_level'] == 3) ? 'selected' : ''; ?>" data-level="3">😐</div>
                                    <div>Neutral</div>
                                </div>
                                <div class="text-center">
                                    <div class="mood-emoji <?php echo ($is_edit && $entry['mood_level'] == 4) ? 'selected' : ''; ?>" data-level="4">🙂</div>
                                    <div>Good</div>
                                </div>
                                <div class="text-center">
                                    <div class="mood-emoji <?php echo ($is_edit && $entry['mood_level'] == 5) ? 'selected' : ''; ?>" data-level="5">😄</div>
                                    <div>Excellent</div>
                                </div>
                            </div>
                            <input type="hidden" id="mood-level" name="mood_level" value="<?php echo $is_edit ? $entry['mood_level'] : ''; ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="notes" class="form-label">Notes (optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="What's on your mind?"><?php echo $is_edit ? htmlspecialchars($entry['notes']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Tags</label>
                            <div class="tag-container d-flex flex-wrap">
                                <?php 
                                $selected_tag_ids = [];
                                if ($is_edit && isset($entry['tags'])) {
                                    foreach ($entry['tags'] as $tag) {
                                        $selected_tag_ids[] = $tag['id'];
                                    }
                                }
                                
                                foreach ($mood_tags as $tag): 
                                    $is_selected = in_array($tag['id'], $selected_tag_ids);
                                ?>
                                <span class="badge tag-badge <?php echo $is_selected ? 'selected' : ''; ?>" 
                                      style="background-color: <?php echo htmlspecialchars($tag['color']); ?>"
                                      data-tag-id="<?php echo $tag['id']; ?>">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="selected-tags" name="tags" value="<?php echo implode(',', $selected_tag_ids); ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label for="date-time" class="form-label">Date and Time</label>
                            <input type="datetime-local" class="form-control" id="date-time" name="date_time" 
                                   value="<?php echo $is_edit ? date('Y-m-d\TH:i', strtotime($entry['date'])) : date('Y-m-d\TH:i'); ?>">
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <?php if ($is_edit): ?>
                            <button type="button" id="delete-btn" class="btn btn-danger">
                                <i class="fas fa-trash me-2"></i>Delete
                            </button>
                            <?php else: ?>
                            <div></div>
                            <?php endif; ?>
                            
                            <div>
                                <button type="button" id="cancel-btn" class="btn btn-outline-secondary me-2">Cancel</button>
                                <button type="submit" class="btn btn-accent">
                                    <i class="fas fa-save me-2"></i><?php echo $is_edit ? 'Update' : 'Save'; ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mood emoji selection
    const moodEmojis = document.querySelectorAll('.mood-emoji');
    const moodLevelInput = document.getElementById('mood-level');
    
    moodEmojis.forEach(emoji => {
        emoji.addEventListener('click', function() {
            // Remove selected class from all emojis
            moodEmojis.forEach(e => e.classList.remove('selected'));
            
            // Add selected class to clicked emoji
            this.classList.add('selected');
            
            // Set mood level value
            moodLevelInput.value = this.dataset.level;
        });
    });
    
    // Tag selection
    const tagBadges = document.querySelectorAll('.tag-badge');
    const selectedTagsInput = document.getElementById('selected-tags');
    let selectedTags = selectedTagsInput.value ? selectedTagsInput.value.split(',') : [];
    
    tagBadges.forEach(badge => {
        badge.addEventListener('click', function() {
            const tagId = this.dataset.tagId;
            
            if (this.classList.contains('selected')) {
                // Remove tag from selection
                this.classList.remove('selected');
                const index = selectedTags.indexOf(tagId);
                if (index > -1) {
                    selectedTags.splice(index, 1);
                }
            } else {
                // Add tag to selection
                this.classList.add('selected');
                selectedTags.push(tagId);
            }
            
            // Update hidden input
            selectedTagsInput.value = selectedTags.join(',');
        });
    });
    
    // Form submission
    const moodEntryForm = document.getElementById('mood-entry-form');
    
    moodEntryForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!moodLevelInput.value) {
            alert('Please select your mood level');
            return;
        }
        
        // Collect form data
        const formData = new FormData(this);
        
        // Send AJAX request
        fetch('ajax/save_mood.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert('Mood entry saved successfully!');
                
                // Redirect to mood tracker
                window.location.href = 'index.php';
            } else {
                // Show error message
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving your mood entry');
        });
    });
    
    // Cancel button
    document.getElementById('cancel-btn').addEventListener('click', function() {
        window.location.href = 'index.php';
    });
    
    // Delete button (for edit mode)
    const deleteBtn = document.getElementById('delete-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this mood entry?')) {
                const entryId = <?php echo $is_edit ? $entry_id : 0; ?>;
                
                fetch('ajax/delete_entry.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'entry_id=' + entryId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Mood entry deleted successfully!');
                        window.location.href = 'index.php';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the mood entry');
                });
            }
        });
    }
});
</script>

<?php
include '../../includes/footer.php';
?>
