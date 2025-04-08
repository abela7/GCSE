<?php
// Add mood widget to dashboard.php

// Get recent mood data
require_once '../functions/mood_functions.php';

// Get mood entries for the last 7 days
$recent_moods = getMoodEntries(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));
$mood_stats = getMoodStatistics(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));

// Calculate average mood if data exists
$avg_mood = !empty($mood_stats['average_mood']) ? $mood_stats['average_mood'] : 0;
$mood_level_class = '';
$mood_emoji = '';

if ($avg_mood > 0) {
    $rounded_mood = round($avg_mood);
    $mood_level_class = "mood-level-{$rounded_mood}";
    
    switch ($rounded_mood) {
        case 1: $mood_emoji = '😢'; break;
        case 2: $mood_emoji = '😕'; break;
        case 3: $mood_emoji = '😐'; break;
        case 4: $mood_emoji = '🙂'; break;
        case 5: $mood_emoji = '😄'; break;
    }
}
?>

<!-- Mood Tracking Widget -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-3"><i class="fas fa-smile me-2"></i>Mood Tracker</h5>
        
        <?php if (!empty($recent_moods)): ?>
            <div class="d-flex align-items-center mb-3">
                <div class="mood-level <?php echo $mood_level_class; ?> me-3" style="width: 50px; height: 50px; border-radius: 50%; text-align: center; line-height: 50px; font-weight: bold; font-size: 1.5rem;">
                    <?php echo number_format($avg_mood, 1); ?>
                </div>
                <div>
                    <div class="fs-4 mb-1"><?php echo $mood_emoji; ?></div>
                    <div class="text-muted">Average mood (last 7 days)</div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center">
                <a href="/pages/mood_tracker.php" class="btn btn-sm btn-primary">View Mood History</a>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickMoodModal">
                    <i class="fas fa-plus me-1"></i>Add Mood
                </button>
            </div>
        <?php else: ?>
            <p class="text-muted mb-3">Start tracking your mood to see patterns in your study experience</p>
            <a href="/pages/mood_tracker.php" class="btn btn-primary">Get Started with Mood Tracking</a>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Mood Modal -->
<div class="modal fade" id="quickMoodModal" tabindex="-1" aria-labelledby="quickMoodModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/pages/mood_tracker.php">
                <input type="hidden" name="action" value="add_mood">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickMoodModalLabel">How are you feeling?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Mood Level Selection -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <?php for ($i = 1; $i <= 5; $i++): 
                                $emoji = '';
                                switch ($i) {
                                    case 1: $emoji = '😢'; break;
                                    case 2: $emoji = '😕'; break;
                                    case 3: $emoji = '😐'; break;
                                    case 4: $emoji = '🙂'; break;
                                    case 5: $emoji = '😄'; break;
                                }
                            ?>
                                <div class="form-check mood-option text-center">
                                    <input class="form-check-input visually-hidden" type="radio" name="mood_level" id="quickMood<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($i === 3) ? 'checked' : ''; ?>>
                                    <label class="form-check-label d-block" for="quickMood<?php echo $i; ?>">
                                        <div class="fs-3 mb-2"><?php echo $emoji; ?></div>
                                        <div class="mood-level mood-level-<?php echo $i; ?>" style="width: 40px; height: 40px; border-radius: 50%; text-align: center; line-height: 40px; font-weight: bold; margin: 0 auto;">
                                            <?php echo $i; ?>
                                        </div>
                                    </label>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="quickNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="quickNotes" name="notes" rows="2" placeholder="Add any notes about your mood..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.mood-level-1 { background-color: #ff6b6b; color: white; }
.mood-level-2 { background-color: #ffa06b; color: white; }
.mood-level-3 { background-color: #ffd56b; color: black; }
.mood-level-4 { background-color: #c2e06b; color: black; }
.mood-level-5 { background-color: #6be07b; color: white; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Style the mood selection on click
    const moodOptions = document.querySelectorAll('.mood-option input');
    moodOptions.forEach(option => {
        option.addEventListener('change', function() {
            document.querySelectorAll('.mood-option label').forEach(label => {
                label.style.transform = 'scale(1)';
                label.style.opacity = '0.7';
            });
            
            if (this.checked) {
                this.parentElement.querySelector('label').style.transform = 'scale(1.1)';
                this.parentElement.querySelector('label').style.opacity = '1';
            }
        });
    });
    
    // Trigger change event on the default selected mood
    document.querySelector('.mood-option input:checked').dispatchEvent(new Event('change'));
});
</script>
