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

// Get all tags for selection (includes 'impact_type')
$all_tags = getMoodTags();

// Group tags by impact type
$grouped_tags = [
    'positive' => [],
    'negative' => [],
    'neutral' => []
];
if ($all_tags) {
    foreach ($all_tags as $tag) {
        $grouped_tags[$tag['impact_type']][] = $tag;
    }
}

?>

<style>
:root {
    --accent-color: #cdaf56;
    --accent-color-light: #e0cb8c;
    --accent-color-dark: #b09339;
    --positive-bg: rgba(40, 167, 69, 0.1);
    --negative-bg: rgba(220, 53, 69, 0.1);
    --neutral-bg: rgba(108, 117, 125, 0.1);
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
    opacity: 0.7;
}
.mood-emoji:hover {
    opacity: 1;
    transform: scale(1.1);
}
.mood-emoji.selected {
    opacity: 1;
    transform: scale(1.2);
    /* Simple border or subtle shadow for selection */
    border-bottom: 3px solid var(--accent-color);
    padding-bottom: 5px;
}

/* Tag Group Styles */
.tag-group {
    margin-bottom: 1.5rem;
    padding: 1rem;
    border-radius: 8px;
}
.tag-group-positive { background-color: var(--positive-bg); border-left: 4px solid #28a745; }
.tag-group-negative { background-color: var(--negative-bg); border-left: 4px solid #dc3545; }
.tag-group-neutral { background-color: var(--neutral-bg); border-left: 4px solid #6c757d; }

.tag-group-title {
    font-weight: 600;
    margin-bottom: 0.75rem;
    font-size: 1rem;
}
.tag-group-positive .tag-group-title { color: #28a745; }
.tag-group-negative .tag-group-title { color: #dc3545; }
.tag-group-neutral .tag-group-title { color: #6c757d; }

.tag-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.tag-option {
    padding: 0.4rem 0.8rem;
    border-radius: 50rem;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #fff;
    font-weight: 500;
    border: 1px solid transparent;
}
.tag-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.tag-option.selected {
    border: 2px solid #333; /* Clearer selection indicator */
    transform: scale(1.05);
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

/* Responsive Styles */
@media (max-width: 767.98px) {
    .mood-emoji {
        font-size: 2.5rem;
    }
    .tag-option {
        padding: 0.3rem 0.7rem;
        font-size: 0.85rem;
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
    .tag-group {
        padding: 0.75rem;
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
                        <div class="mb-4 text-center">
                            <label class="form-label d-block mb-3">How are you feeling?</label>
                            <div class="d-flex justify-content-around align-items-center">
                                <?php 
                                $mood_scale = [ // Ensure this matches mood_analysis.php scale if used elsewhere
                                    1 => ['label' => 'Very Bad', 'emoji' => '😢'],
                                    2 => ['label' => 'Bad', 'emoji' => '😕'],
                                    3 => ['label' => 'Neutral', 'emoji' => '😐'],
                                    4 => ['label' => 'Good', 'emoji' => '🙂'],
                                    5 => ['label' => 'Very Good', 'emoji' => '😄']
                                ];
                                foreach ($mood_scale as $level => $mood):
                                    $is_selected = ($entry && $entry['mood_level'] == $level);
                                ?>
                                <div class="mood-selector">
                                    <div class="mood-emoji <?php echo $is_selected ? 'selected' : ''; ?>" 
                                         data-value="<?php echo $level; ?>"
                                         onclick="selectMood(this, <?php echo $level; ?>)"><?php echo $mood['emoji']; ?></div>
                                    <div class="small text-muted mt-1"><?php echo $mood['label']; ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="mood_level" name="mood_level" value="<?php echo $entry ? $entry['mood_level'] : ''; ?>" required>
                            <div id="mood-error" class="invalid-feedback d-block text-center mt-2" style="display: none !important;">Please select your mood.</div>
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
                            <label for="notes" class="form-label">What's on your mind? (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Add any details about your mood or activities..."><?php echo $entry ? htmlspecialchars($entry['notes']) : ''; ?></textarea>
                        </div>
                        
                        <!-- Tags -->
                        <div class="mb-4">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>What influenced your mood? (Optional)</span>
                                <a href="settings.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-cog me-1"></i>Manage Tags
                                </a>
                            </label>
                            
                            <?php if (!empty($all_tags)): ?>
                                <?php 
                                $selected_tag_ids = [];
                                if ($entry && !empty($entry['tags'])) {
                                    $selected_tag_ids = array_column($entry['tags'], 'id');
                                }
                                
                                // Define groups and titles
                                $tag_groups_config = [
                                    'positive' => ['title' => '😊 Positive Influences', 'icon' => 'fa-smile'],
                                    'negative' => ['title' => '😕 Negative Influences', 'icon' => 'fa-frown'],
                                    'neutral' => ['title' => '↔️ Other Factors', 'icon' => 'fa-meh']
                                ];
                                
                                $has_tags_to_display = false; // Flag to check if any group has tags
                                foreach ($tag_groups_config as $type => $config):
                                    if (!empty($grouped_tags[$type])):
                                        $has_tags_to_display = true;
                                ?>
                                <div class="tag-group tag-group-<?php echo $type; ?>">
                                    <div class="tag-group-title">
                                        <i class="fas <?php echo $config['icon']; ?> me-2"></i><?php echo $config['title']; ?>
                                    </div>
                                    <div class="tag-selector" id="tag_selector_<?php echo $type; ?>">
                                        <?php foreach ($grouped_tags[$type] as $tag): 
                                            $is_selected = in_array($tag['id'], $selected_tag_ids);
                                        ?>
                                            <div class="tag-option <?php echo $is_selected ? 'selected' : ''; ?>" 
                                                 style="background-color: <?php echo htmlspecialchars($tag['color']); ?>"
                                                 data-id="<?php echo $tag['id']; ?>"
                                                 onclick="toggleTag(this)">
                                                <?php echo htmlspecialchars($tag['name']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php 
                                    endif;
                                endforeach; 
                                
                                // Only show hidden input if there were tags displayed
                                if ($has_tags_to_display):
                                ?>
                                <input type="hidden" id="selected_tags" name="tags" value="<?php echo implode(',', $selected_tag_ids); ?>">
                                <?php endif; ?>
                            <?php else: // This case means getMoodTags returned empty or false ?>
                                <div class="alert alert-secondary">
                                    No active tags found. <a href="settings.php" class="alert-link">Manage or create tags</a> to categorize your entries.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-accent btn-lg">
                                <i class="fas fa-save me-1"></i><?php echo $entry_id ? 'Update Mood' : 'Save Mood'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedMoodLevel = <?php echo $entry ? $entry['mood_level'] : 'null'; ?>;
let selectedTags = new Set(<?php echo json_encode($selected_tag_ids ?? []); ?>.map(String));

function selectMood(element, level) {
    // Remove selected class from all emojis
    document.querySelectorAll('.mood-emoji').forEach(el => el.classList.remove('selected'));
    // Add selected class to the clicked emoji
    element.classList.add('selected');
    // Update hidden input
    document.getElementById('mood_level').value = level;
    selectedMoodLevel = level;
    // Hide error message if shown
    document.getElementById('mood-error').style.display = 'none';
}

function toggleTag(element) {
    const tagId = element.dataset.id;
    if (selectedTags.has(tagId)) {
        selectedTags.delete(tagId);
        element.classList.remove('selected');
    } else {
        selectedTags.add(tagId);
        element.classList.add('selected');
    }
    // Update hidden input
    document.getElementById('selected_tags').value = Array.from(selectedTags).join(',');
}

// Initialize selected state on load
document.addEventListener('DOMContentLoaded', function() {
    if (selectedMoodLevel !== null) {
        const selectedEmoji = document.querySelector(`.mood-emoji[data-value="${selectedMoodLevel}"]`);
        if (selectedEmoji) {
            selectedEmoji.classList.add('selected');
        }
    }
    
    selectedTags.forEach(tagId => {
        const selectedTagElement = document.querySelector(`.tag-option[data-id="${tagId}"]`);
        if (selectedTagElement) {
            selectedTagElement.classList.add('selected');
        }
    });

    // Form validation
    const form = document.getElementById('mood_entry_form');
    form.addEventListener('submit', function(event) {
        if (selectedMoodLevel === null) {
            event.preventDefault();
            const moodError = document.getElementById('mood-error');
            moodError.style.display = 'block';
            moodError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            document.getElementById('mood-error').style.display = 'none';
            // Optional: Add AJAX submission here if preferred over full page reload
        }
    });
});

</script>

<?php
// Include footer
require_once __DIR__ . '/../../includes/footer.php';
?>
