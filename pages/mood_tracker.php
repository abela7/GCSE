<?php
// Set page title
$page_title = "Mood Tracker";

// Include database connection and functions
require_once '../config/db_connect.php';
require_once '../functions/mood_functions.php';

// Process form submission for new mood entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_mood') {
    $mood_level = isset($_POST['mood_level']) ? intval($_POST['mood_level']) : null;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
    $subject_id = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : null;
    $topic_id = !empty($_POST['topic_id']) ? intval($_POST['topic_id']) : null;
    $factor_ids = isset($_POST['factors']) ? $_POST['factors'] : [];
    
    if ($mood_level >= 1 && $mood_level <= 5) {
        $result = createMoodEntry($mood_level, $notes, $subject_id, $topic_id, $factor_ids);
        if ($result) {
            $success_message = "Mood entry added successfully!";
        } else {
            $error_message = "Failed to add mood entry. Please try again.";
        }
    } else {
        $error_message = "Invalid mood level. Please select a value between 1 and 5.";
    }
}

// Process delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_mood') {
    $mood_id = isset($_POST['mood_id']) ? intval($_POST['mood_id']) : null;
    
    if ($mood_id) {
        $result = deleteMoodEntry($mood_id);
        if ($result) {
            $success_message = "Mood entry deleted successfully!";
        } else {
            $error_message = "Failed to delete mood entry. Please try again.";
        }
    }
}

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_subject = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : null;

// Get mood entries
$mood_entries = getMoodEntries($start_date, $end_date, $filter_subject);

// Get mood statistics
$mood_stats = getMoodStatistics($start_date, $end_date, $filter_subject);

// Get mood factors for form
$mood_factors = getMoodFactors();

// Get subjects for form
$subjects_query = "SELECT * FROM subjects ORDER BY name";
$subjects_result = $conn->query($subjects_query);

// Include header
include '../includes/header.php';
?>

<style>
.mood-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    margin-bottom: 1rem;
}
.mood-card:hover {
    transform: translateY(-2px);
}
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
.factor-badge {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}
.positive-factor { background-color: #28a745; }
.negative-factor { background-color: #dc3545; }
.chart-container {
    height: 250px;
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0"><i class="fas fa-smile me-2"></i>Mood Tracker</h1>
            <p class="text-muted">Track your mood while studying to identify patterns and optimize your learning experience</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMoodModal">
                <i class="fas fa-plus me-2"></i>Add Mood Entry
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="subject_id" class="form-label">Subject</label>
                    <select class="form-select" id="subject_id" name="subject_id">
                        <option value="">All Subjects</option>
                        <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                            <option value="<?php echo $subject['id']; ?>" <?php echo ($filter_subject == $subject['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mood Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Average Mood</h5>
                    <div class="d-flex align-items-center justify-content-center py-4">
                        <div class="mood-level mood-level-<?php echo round($mood_stats['average_mood']); ?> me-3">
                            <?php echo number_format($mood_stats['average_mood'], 1); ?>
                        </div>
                        <div class="mood-emoji">
                            <?php 
                            $avg_mood = round($mood_stats['average_mood']);
                            $emoji = '';
                            switch ($avg_mood) {
                                case 1: $emoji = '😢'; break;
                                case 2: $emoji = '😕'; break;
                                case 3: $emoji = '😐'; break;
                                case 4: $emoji = '🙂'; break;
                                case 5: $emoji = '😄'; break;
                            }
                            echo $emoji;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Mood Distribution</h5>
                    <div class="chart-container">
                        <canvas id="moodDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Common Factors</h5>
                    <?php if (!empty($mood_stats['common_factors'])): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($mood_stats['common_factors'] as $factor): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($factor['name']); ?>
                                    <span class="badge bg-<?php echo $factor['is_positive'] ? 'success' : 'danger'; ?> rounded-pill">
                                        <?php echo $factor['count']; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No mood factors recorded yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Mood Entries -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Mood History</h5>
            
            <?php if (!empty($mood_entries)): ?>
                <?php foreach ($mood_entries as $entry): ?>
                    <div class="card mood-card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="mood-level mood-level-<?php echo $entry['mood_level']; ?>">
                                    <?php echo $entry['mood_level']; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <?php 
                                                $mood_date = new DateTime($entry['date']);
                                                echo $mood_date->format('D, j M Y - g:i A'); 
                                                ?>
                                            </h6>
                                            <?php if (!empty($entry['subject_name'])): ?>
                                                <span class="badge bg-primary me-2"><?php echo htmlspecialchars($entry['subject_name']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($entry['topic_name'])): ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($entry['topic_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this mood entry?');">
                                            <input type="hidden" name="action" value="delete_mood">
                                            <input type="hidden" name="mood_id" value="<?php echo $entry['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <?php if (!empty($entry['notes'])): ?>
                                        <p class="mt-2 mb-2"><?php echo nl2br(htmlspecialchars($entry['notes'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($entry['factors'])): ?>
                                        <div class="mt-2">
                                            <?php foreach ($entry['factors'] as $factor): ?>
                                                <span class="badge factor-badge <?php echo $factor['is_positive'] ? 'positive-factor' : 'negative-factor'; ?>">
                                                    <?php echo htmlspecialchars($factor['name']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <p class="text-muted mb-3">No mood entries found for the selected period</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMoodModal">
                        <i class="fas fa-plus me-2"></i>Add Your First Mood Entry
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Mood Modal -->
<div class="modal fade" id="addMoodModal" tabindex="-1" aria-labelledby="addMoodModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_mood">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMoodModalLabel">Add Mood Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Mood Level Selection -->
                    <div class="mb-4">
                        <label class="form-label">How are you feeling?</label>
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
                                    <input class="form-check-input visually-hidden" type="radio" name="mood_level" id="mood<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($i === 3) ? 'checked' : ''; ?>>
                                    <label class="form-check-label d-block" for="mood<?php echo $i; ?>">
                                        <div class="mood-emoji mb-2"><?php echo $emoji; ?></div>
                                        <div class="mood-level mood-level-<?php echo $i; ?>" style="margin: 0 auto;"><?php echo $i; ?></div>
                                    </label>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Subject and Topic -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="subject_id_modal" class="form-label">Subject (Optional)</label>
                            <select class="form-select" id="subject_id_modal" name="subject_id">
                                <option value="">Select Subject</option>
                                <?php 
                                // Reset the result pointer
                                $subjects_result->data_seek(0);
                                while ($subject = $subjects_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $subject['id']; ?>">
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="topic_id" class="form-label">Topic (Optional)</label>
                            <select class="form-select" id="topic_id" name="topic_id" disabled>
                                <option value="">Select Topic</option>
                                <!-- Topics will be loaded via JavaScript -->
                            </select>
                        </div>
                    </div>
                    
                    <!-- Mood Factors -->
                    <div class="mb-3">
                        <label class="form-label">Factors affecting your mood (Optional)</label>
                        <div class="row">
                            <?php foreach ($mood_factors as $factor): ?>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="factors[]" value="<?php echo $factor['id']; ?>" id="factor<?php echo $factor['id']; ?>">
                                        <label class="form-check-label" for="factor<?php echo $factor['id']; ?>">
                                            <?php echo htmlspecialchars($factor['name']); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Add any additional notes about your mood..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Mood Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for Charts and Dynamic Topics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mood Distribution Chart
    const ctx = document.getElementById('moodDistributionChart').getContext('2d');
    
    // Prepare data for chart
    const moodLabels = ['Very Low', 'Low', 'Neutral', 'Good', 'Excellent'];
    const moodCounts = [0, 0, 0, 0, 0]; // Default to 0 for all levels
    
    <?php if (!empty($mood_stats['mood_distribution'])): ?>
        <?php foreach ($mood_stats['mood_distribution'] as $dist): ?>
            moodCounts[<?php echo $dist['mood_level'] - 1; ?>] = <?php echo $dist['count']; ?>;
        <?php endforeach; ?>
    <?php endif; ?>
    
    const moodColors = [
        '#ff6b6b', // Level 1
        '#ffa06b', // Level 2
        '#ffd56b', // Level 3
        '#c2e06b', // Level 4
        '#6be07b'  // Level 5
    ];
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: moodLabels,
            datasets: [{
                label: 'Mood Distribution',
                data: moodCounts,
                backgroundColor: moodColors,
                borderColor: moodColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Dynamic Topics based on Subject selection
    const subjectSelect = document.getElementById('subject_id_modal');
    const topicSelect = document.getElementById('topic_id');
    
    subjectSelect.addEventListener('change', function() {
        const subjectId = this.value;
        
        // Reset and disable topic select if no subject selected
        if (!subjectId) {
            topicSelect.innerHTML = '<option value="">Select Topic</option>';
            topicSelect.disabled = true;
            return;
        }
        
        // Enable topic select
        topicSelect.disabled = false;
        
        // Load topics via AJAX
        fetch(`../ajax/get_topics.php?subject_id=${subjectId}`)
            .then(response => response.json())
            .then(data => {
                let options = '<option value="">Select Topic</option>';
                
                data.forEach(topic => {
                    options += `<option value="${topic.id}">${topic.name}</option>`;
                });
                
                topicSelect.innerHTML = options;
            })
            .catch(error => {
                console.error('Error loading topics:', error);
                topicSelect.innerHTML = '<option value="">Error loading topics</option>';
            });
    });
    
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

<?php
// Display success/error messages
if (isset($success_message)) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            alert('{$success_message}');
        });
    </script>";
}

if (isset($error_message)) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            alert('{$error_message}');
        });
    </script>";
}

// Include footer
include '../includes/footer.php';
close_connection($conn);
?>
