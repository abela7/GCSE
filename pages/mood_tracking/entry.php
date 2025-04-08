<?php
// Set page title
$page_title = "Add/Edit Mood Entry";

// Include database connection and functions
require_once '../../../config/db_connect.php';
require_once '../includes/functions.php';

// Initialize variables
$entry_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$entry = null;
$is_edit_mode = false;

// If in edit mode, get the entry data
if ($entry_id) {
    $entry = getMoodEntry($entry_id);
    $is_edit_mode = ($entry !== false);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mood_level = isset($_POST['mood_level']) ? intval($_POST['mood_level']) : null;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
    $subject_id = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : null;
    $topic_id = !empty($_POST['topic_id']) ? intval($_POST['topic_id']) : null;
    $tag_ids = isset($_POST['tags']) ? $_POST['tags'] : [];
    
    if ($mood_level >= 1 && $mood_level <= 5) {
        if ($is_edit_mode) {
            // Update existing entry
            $result = updateMoodEntry($entry_id, $mood_level, $notes, $subject_id, $topic_id, $tag_ids);
            if ($result) {
                $success_message = "Mood entry updated successfully!";
                // Refresh entry data
                $entry = getMoodEntry($entry_id);
            } else {
                $error_message = "Failed to update mood entry. Please try again.";
            }
        } else {
            // Create new entry
            $result = createMoodEntry($mood_level, $notes, $subject_id, $topic_id, $tag_ids);
            if ($result) {
                $success_message = "Mood entry added successfully!";
                // Redirect to history page
                header("Location: history.php");
                exit;
            } else {
                $error_message = "Failed to add mood entry. Please try again.";
            }
        }
    } else {
        $error_message = "Invalid mood level. Please select a value between 1 and 5.";
    }
}

// Get subjects for form
$subjects_query = "SELECT * FROM subjects ORDER BY name";
$subjects_result = $conn->query($subjects_query);

// Get all tags for form
$all_tags = getMoodTags();
$tag_categories = getMoodTagCategories();

// Include header
include '../../../includes/header.php';
?>

<style>
/* General Styles */
.mood-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    margin-bottom: 1rem;
}
.mood-card:hover {
    transform: translateY(-2px);
}

/* Mood Level Indicators */
.mood-level {
    font-size: 1.5rem;
    font-weight: bold;
    text-align: center;
    width: 50px;
    height: 50px;
    line-height: 50px;
    border-radius: 50%;
    margin-right: 1rem;
}
.mood-level-1 { background-color: #ff6b6b; color: white; }
.mood-level-2 { background-color: #ffa06b; color: white; }
.mood-level-3 { background-color: #ffd56b; color: black; }
.mood-level-4 { background-color: #c2e06b; color: black; }
.mood-level-5 { background-color: #6be07b; color: white; }
.mood-emoji { font-size: 2rem; }

/* Mood Selection */
.mood-option {
    text-align: center;
    margin-bottom: 1rem;
}
.mood-option label {
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: all 0.2s;
}
.mood-option label:hover {
    background-color: #f8f9fa;
}
.mood-option input:checked + label {
    transform: scale(1.1);
    background-color: #f0f0f0;
}

/* Tag Selection */
.tag-option {
    margin-bottom: 0.5rem;
}
.tag-badge {
    display: inline-block;
    padding: 0.4rem 0.6rem;
    border-radius: 50rem;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}
.tag-checkbox {
    display: none;
}
.tag-checkbox:checked + .tag-badge {
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px #007bff;
}

/* Mobile Optimizations */
@media (max-width: 767.98px) {
    .mood-level {
        width: 40px;
        height: 40px;
        line-height: 40px;
        font-size: 1.25rem;
    }
    .mood-emoji {
        font-size: 1.5rem;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-smile me-2"></i>
                <?php echo $is_edit_mode ? 'Edit Mood Entry' : 'Add Mood Entry'; ?>
            </h1>
            <p class="text-muted">
                <?php echo $is_edit_mode ? 'Update your mood entry details' : 'Record how you\'re feeling right now'; ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" id="moodEntryForm">
                <!-- Mood Level Selection -->
                <div class="mb-4">
                    <label class="form-label fw-bold">How are you feeling?</label>
                    <div class="row justify-content-center">
                        <?php for ($i = 1; $i <= 5; $i++): 
                            $emoji = '';
                            $mood_text = '';
                            switch ($i) {
                                case 1: 
                                    $emoji = '😢'; 
                                    $mood_text = 'Very Low';
                                    break;
                                case 2: 
                                    $emoji = '😕'; 
                                    $mood_text = 'Low';
                                    break;
                                case 3: 
                                    $emoji = '😐'; 
                                    $mood_text = 'Neutral';
                                    break;
                                case 4: 
                                    $emoji = '🙂'; 
                                    $mood_text = 'Good';
                                    break;
                                case 5: 
                                    $emoji = '😄'; 
                                    $mood_text = 'Excellent';
                                    break;
                            }
                            $is_checked = ($is_edit_mode && $entry['mood_level'] == $i) || (!$is_edit_mode && $i === 3);
                        ?>
                            <div class="col-4 col-md-2 mood-option">
                                <input class="form-check-input visually-hidden" type="radio" name="mood_level" 
                                       id="mood<?php echo $i; ?>" value="<?php echo $i; ?>" 
                                       <?php echo $is_checked ? 'checked' : ''; ?>>
                                <label class="form-check-label d-block" for="mood<?php echo $i; ?>">
                                    <div class="mood-emoji mb-2"><?php echo $emoji; ?></div>
                                    <div class="mood-level mood-level-<?php echo $i; ?>" style="margin: 0 auto;"><?php echo $i; ?></div>
                                    <div class="mt-2 small"><?php echo $mood_text; ?></div>
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Subject and Topic -->
                    <div class="col-md-6 mb-3">
                        <label for="subject_id" class="form-label fw-bold">Subject (Optional)</label>
                        <select class="form-select" id="subject_id" name="subject_id">
                            <option value="">Select Subject</option>
                            <?php 
                            // Reset the result pointer
                            $subjects_result->data_seek(0);
                            while ($subject = $subjects_result->fetch_assoc()): 
                                $is_selected = $is_edit_mode && $entry['associated_subject_id'] == $subject['id'];
                            ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="topic_id" class="form-label fw-bold">Topic (Optional)</label>
                        <select class="form-select" id="topic_id" name="topic_id" <?php echo empty($entry['associated_subject_id']) ? 'disabled' : ''; ?>>
                            <option value="">Select Topic</option>
                            <?php if ($is_edit_mode && !empty($entry['associated_subject_id']) && !empty($entry['associated_topic_id'])): ?>
                                <option value="<?php echo $entry['associated_topic_id']; ?>" selected>
                                    <?php echo htmlspecialchars($entry['topic_name']); ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Tags -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Tags</label>
                    <div class="mb-2">
                        <small class="text-muted">Select tags that relate to your current mood</small>
                    </div>
                    
                    <?php if (!empty($tag_categories)): ?>
                        <ul class="nav nav-tabs mb-3" id="tagTabs" role="tablist">
                            <?php foreach ($tag_categories as $index => $category): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                                            id="<?php echo strtolower(str_replace(' ', '-', $category)); ?>-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#<?php echo strtolower(str_replace(' ', '-', $category)); ?>" 
                                            type="button" role="tab" aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                                        <?php echo htmlspecialchars($category); ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="other-tab" data-bs-toggle="tab" data-bs-target="#other" type="button" role="tab" aria-selected="false">
                                    Other
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="tagTabContent">
                            <?php foreach ($tag_categories as $index => $category): ?>
                                <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                                     id="<?php echo strtolower(str_replace(' ', '-', $category)); ?>" 
                                     role="tabpanel">
                                    <div class="row">
                                        <?php 
                                        $category_tags = array_filter($all_tags, function($tag) use ($category) {
                                            return $tag['category'] === $category;
                                        });
                                        
                                        foreach ($category_tags as $tag): 
                                            $is_checked = $is_edit_mode && !empty($entry['tags']) && 
                                                         array_filter($entry['tags'], function($t) use ($tag) {
                                                             return $t['id'] == $tag['id'];
                                                         });
                                        ?>
                                            <div class="col-6 col-md-3 tag-option">
                                                <input type="checkbox" class="tag-checkbox" name="tags[]" 
                                                       id="tag<?php echo $tag['id']; ?>" value="<?php echo $tag['id']; ?>"
                                                       <?php echo !empty($is_checked) ? 'checked' : ''; ?>>
                                                <label for="tag<?php echo $tag['id']; ?>" class="tag-badge" style="background-color: <?php echo $tag['color']; ?>; cursor: pointer;">
                                                    <?php echo htmlspecialchars($tag['name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="tab-pane fade" id="other" role="tabpanel">
                                <div class="row">
                                    <?php 
                                    $other_tags = array_filter($all_tags, function($tag) {
                                        return $tag['category'] === null;
                                    });
                                    
                                    foreach ($other_tags as $tag): 
                                        $is_checked = $is_edit_mode && !empty($entry['tags']) && 
                                                     array_filter($entry['tags'], function($t) use ($tag) {
                                                         return $t['id'] == $tag['id'];
                                                     });
                                    ?>
                                        <div class="col-6 col-md-3 tag-option">
                                            <input type="checkbox" class="tag-checkbox" name="tags[]" 
                                                   id="tag<?php echo $tag['id']; ?>" value="<?php echo $tag['id']; ?>"
                                                   <?php echo !empty($is_checked) ? 'checked' : ''; ?>>
                                            <label for="tag<?php echo $tag['id']; ?>" class="tag-badge" style="background-color: <?php echo $tag['color']; ?>; cursor: pointer;">
                                                <?php echo htmlspecialchars($tag['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="col-12 mt-3">
                                        <a href="settings.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-plus me-1"></i>Add Custom Tags
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($all_tags as $tag): 
                                $is_checked = $is_edit_mode && !empty($entry['tags']) && 
                                             array_filter($entry['tags'], function($t) use ($tag) {
                                                 return $t['id'] == $tag['id'];
                                             });
                            ?>
                                <div class="col-6 col-md-3 tag-option">
                                    <input type="checkbox" class="tag-checkbox" name="tags[]" 
                                           id="tag<?php echo $tag['id']; ?>" value="<?php echo $tag['id']; ?>"
                                           <?php echo !empty($is_checked) ? 'checked' : ''; ?>>
                                    <label for="tag<?php echo $tag['id']; ?>" class="tag-badge" style="background-color: <?php echo $tag['color']; ?>; cursor: pointer;">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="col-12 mt-3">
                                <a href="settings.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-plus me-1"></i>Add Custom Tags
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Notes -->
                <div class="mb-4">
                    <label for="notes" class="form-label fw-bold">Notes (Optional)</label>
                    <textarea class="form-control" id="notes" name="notes" rows="4" 
                              placeholder="Add any thoughts or details about how you're feeling..."><?php echo $is_edit_mode ? htmlspecialchars($entry['notes']) : ''; ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="<?php echo $is_edit_mode ? 'history.php' : 'index.php'; ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $is_edit_mode ? 'Update Mood Entry' : 'Save Mood Entry'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Style the mood selection on click
    const moodOptions = document.querySelectorAll('.mood-option input');
    moodOptions.forEach(option => {
        option.addEventListener('change', function() {
            document.querySelectorAll('.mood-option label').forEach(label => {
                label.style.transform = 'scale(1)';
                label.style.backgroundColor = '';
            });
            
            if (this.checked) {
                this.parentElement.querySelector('label').style.transform = 'scale(1.1)';
                this.parentElement.querySelector('label').style.backgroundColor = '#f0f0f0';
            }
        });
    });
    
    // Trigger change event on the default selected mood
    document.querySelector('.mood-option input:checked').dispatchEvent(new Event('change'));
    
    // Handle subject change to load topics
    const subjectSelect = document.getElementById('subject_id');
    const topicSelect = document.getElementById('topic_id');
    
    subjectSelect.addEventListener('change', function() {
        const subjectId = this.value;
        
        if (subjectId) {
            topicSelect.disabled = true;
            topicSelect.innerHTML = '<option value="">Loading topics...</option>';
            
            fetch(`../ajax/get_topics.php?subject_id=${subjectId}`)
                .then(response => response.json())
                .then(data => {
                    topicSelect.innerHTML = '<option value="">Select Topic</option>';
                    
                    data.forEach(topic => {
                        const option = document.createElement('option');
                        option.value = topic.id;
                        option.textContent = topic.name;
                        topicSelect.appendChild(option);
                    });
                    
                    topicSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error fetching topics:', error);
                    topicSelect.innerHTML = '<option value="">Error loading topics</option>';
                    topicSelect.disabled = true;
                });
        } else {
            topicSelect.innerHTML = '<option value="">Select Topic</option>';
            topicSelect.disabled = true;
        }
    });
    
    // If in edit mode and subject is selected, trigger change to load topics
    if (subjectSelect.value) {
        subjectSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php include '../../../includes/footer.php'; ?>
