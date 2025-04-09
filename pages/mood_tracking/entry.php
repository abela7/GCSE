<?php
// Include required files
require_once __DIR__ . '/includes/functions.php';

// Set page title
$page_title = "Mood Entry";

// Include header
require_once __DIR__ . '/../../includes/header.php';

// Initialize variables
$entry_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$entry_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d H:i:s');
$entry = null;

// If editing existing entry, get the data
if ($entry_id) {
    $entry = getMoodEntry($entry_id);
    if (!$entry) {
        echo '<div class="alert alert-danger">Entry not found</div>';
        exit;
    }
    $entry_date = $entry['date'];
}

// Get all tags for selection
$all_tags = getMoodTags();
?>

<style>
:root {
    --accent-color: #cdaf56;
    --accent-color-light: #e0cb8c;
    --accent-color-dark: #b09339;
}

/* Card Styles */
.entry-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

/* Mood Emoji Styles */
.mood-emoji {
    font-size: 3rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
}
.mood-emoji:hover {
    transform: scale(1.1);
}
.mood-emoji.selected {
    transform: scale(1.2);
    text-shadow: 0 0 10px rgba(0,0,0,0.2);
}

/* Tag Styles */
.tag-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 1rem 0;
}
.tag-option {
    padding: 0.5rem 1rem;
    border-radius: 50rem;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #fff;
    font-weight: 500;
    opacity: 0.7;
}
.tag-option:hover {
    opacity: 1;
    transform: translateY(-2px);
}
.tag-option.selected {
    opacity: 1;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

/* Button Styles */
.btn-accent {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
    color: #fff;
}
.btn-accent:hover {
    background-color: var(--accent-color-dark);
    border-color: var(--accent-color-dark);
    color: #fff;
}
.btn-outline-accent {
    color: var(--accent-color);
    border-color: var(--accent-color);
}
.btn-outline-accent:hover {
    background-color: var(--accent-color);
    color: #fff;
}

/* Date/Time Picker Styles */
.datetime-container {
    position: relative;
}
.datetime-container .form-control {
    padding-right: 2.5rem;
}
.datetime-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

/* Mobile Optimizations */
@media (max-width: 767.98px) {
    .mood-emoji {
        font-size: 2.5rem;
    }
    .tag-option {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }
    .btn {
        padding: 0.5rem 1rem;
        font-size: 1rem;
    }
    .form-control {
        font-size: 1rem;
        padding: 0.75rem;
        height: auto;
    }
    .form-label {
        font-size: 1rem;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0" style="color: var(--accent-color);">
                <i class="fas fa-edit me-2"></i><?php echo $entry_id ? 'Edit Mood Entry' : 'New Mood Entry'; ?>
            </h1>
            <p class="text-muted">Record how you're feeling</p>
        </div>
        <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
            <a href="index.php" class="btn btn-outline-accent">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-md-10 mx-auto">
            <div class="entry-card card">
                <div class="card-body p-4">
                    <form id="mood_entry_form" method="POST" action="ajax/save_mood.php">
                        <?php if ($entry_id): ?>
                            <input type="hidden" name="entry_id" value="<?php echo $entry_id; ?>">
                        <?php endif; ?>
                        
                        <!-- Mood Selection -->
                        <div class="mb-4">
                            <label class="form-label">How are you feeling?</label>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-center mood-selector">
                                    <div class="mood-emoji" data-value="1" onclick="selectMood(this, 1)">😢</div>
                                    <div class="small text-muted">Very Bad</div>
                                </div>
                                <div class="text-center mood-selector">
                                    <div class="mood-emoji" data-value="2" onclick="selectMood(this, 2)">😕</div>
                                    <div class="small text-muted">Bad</div>
                                </div>
                                <div class="text-center mood-selector">
                                    <div class="mood-emoji" data-value="3" onclick="selectMood(this, 3)">😐</div>
                                    <div class="small text-muted">Neutral</div>
                                </div>
                                <div class="text-center mood-selector">
                                    <div class="mood-emoji" data-value="4" onclick="selectMood(this, 4)">🙂</div>
                                    <div class="small text-muted">Good</div>
                                </div>
                                <div class="text-center mood-selector">
                                    <div class="mood-emoji" data-value="5" onclick="selectMood(this, 5)">😄</div>
                                    <div class="small text-muted">Very Good</div>
                                </div>
                            </div>
                            <input type="hidden" id="mood_level" name="mood_level" value="<?php echo $entry ? $entry['mood_level'] : ''; ?>" required>
                        </div>
                        
                        <!-- Date and Time -->
                        <div class="mb-4">
                            <label for="date_time" class="form-label">Date & Time</label>
                            <div class="datetime-container">
                                <input type="datetime-local" class="form-control" id="date_time" name="date_time" 
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($entry_date)); ?>" required>
                                <span class="datetime-icon"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4" 
                                      placeholder="What's on your mind? How are you feeling?"><?php echo $entry ? $entry['notes'] : ''; ?></textarea>
                        </div>
                        
                        <!-- Tags -->
                        <div class="mb-4">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Tags (Optional)</span>
                                <a href="settings.php" class="btn btn-sm btn-outline-accent">
                                    <i class="fas fa-cog me-1"></i>Manage Tags
                                </a>
                            </label>
                            
                            <?php if (!empty($all_tags)): ?>
                                <div class="tag-selector" id="tag_selector">
                                    <?php 
                                    $selected_tag_ids = [];
                                    if ($entry && !empty($entry['tags'])) {
                                        foreach ($entry['tags'] as $tag) {
                                            $selected_tag_ids[] = $tag['id'];
                                        }
                                    }
                                    
                                    foreach ($all_tags as $tag): 
                                        $is_selected = in_array($tag['id'], $selected_tag_ids);
                                    ?>
                                        <div class="tag-option <?php echo $is_selected ? 'selected' : ''; ?>" 
                                             style="background-color: <?php echo $tag['color']; ?>"
                                             data-id="<?php echo $tag['id']; ?>"
                                             onclick="toggleTag(this)">
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" id="selected_tags" name="tags" value="<?php echo implode(',', $selected_tag_ids); ?>">
                            <?php else: ?>
                                <div class="alert alert-info">
                                    No tags available. <a href="settings.php">Create some tags</a> to categorize your mood entries.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="d-flex flex-column flex-md-row justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary mb-3 mb-md-0" onclick="window.location.href='index.php'">Cancel</button>
                            <button type="submit" class="btn btn-accent" id="save_button">
                                <i class="fas fa-save me-1"></i><?php echo $entry_id ? 'Update Entry' : 'Save Entry'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to select mood
function selectMood(element, value) {
    // Remove selected class from all emojis
    document.querySelectorAll('.mood-emoji').forEach(emoji => {
        emoji.classList.remove('selected');
    });
    
    // Add selected class to clicked emoji
    element.classList.add('selected');
    
    // Update hidden input value
    document.getElementById('mood_level').value = value;
}

// Function to toggle tag selection
function toggleTag(element) {
    const tagId = element.dataset.id;
    
    // Toggle selected class
    element.classList.toggle('selected');
    
    // Update hidden input with selected tag IDs
    const selectedTags = [];
    document.querySelectorAll('.tag-option.selected').forEach(tag => {
        selectedTags.push(tag.dataset.id);
    });
    
    document.getElementById('selected_tags').value = selectedTags.join(',');
}

// Set initial mood selection if editing
document.addEventListener('DOMContentLoaded', function() {
    const moodLevel = document.getElementById('mood_level').value;
    if (moodLevel) {
        const moodEmoji = document.querySelector(`.mood-emoji[data-value="${moodLevel}"]`);
        if (moodEmoji) {
            moodEmoji.classList.add('selected');
        }
    }
    
    // Form submission handling
    document.getElementById('mood_entry_form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate mood selection
        const moodLevel = document.getElementById('mood_level').value;
        if (!moodLevel) {
            alert('Please select a mood level');
            return;
        }
        
        // Show loading state
        const saveButton = document.getElementById('save_button');
        const originalText = saveButton.innerHTML;
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        
        // Submit form via AJAX
        const formData = new FormData(this);
        
        fetch('ajax/save_mood.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to dashboard
                window.location.href = 'index.php';
            } else {
                // Show error
                alert('Error: ' + data.message);
                saveButton.disabled = false;
                saveButton.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving. Please try again.');
            saveButton.disabled = false;
            saveButton.innerHTML = originalText;
        });
    });
    
    // Fix iOS zoom on input focus
    document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea').forEach(input => {
        input.style.fontSize = '16px';
    });
});
</script>

<?php
// Include footer
require_once __DIR__ . '/../../includes/footer.php';
?>
