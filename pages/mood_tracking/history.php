<?php
// Set page title
$page_title = "Mood History";

// Include database connection and functions
require_once '../../../config/db_connect.php';
require_once '../includes/functions.php';

// Get filter parameters
$date = isset($_GET['date']) ? $_GET['date'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_subject = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : null;
$filter_mood = isset($_GET['mood_level']) ? intval($_GET['mood_level']) : null;
$filter_tag = isset($_GET['tag_id']) ? intval($_GET['tag_id']) : null;
$filter_time = isset($_GET['time_of_day']) ? $_GET['time_of_day'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

// If specific date is provided, use it for both start and end date
if ($date) {
    $start_date = $date;
    $end_date = $date;
}

// Prepare tag IDs array for filtering
$tag_ids = [];
if ($filter_tag) {
    $tag_ids[] = $filter_tag;
}

// Get mood entries with filters
$mood_entries = getMoodEntries($start_date, $end_date, $filter_subject, null, $filter_mood, $tag_ids, $filter_time, $search);

// Get subjects for filter
$subjects_query = "SELECT * FROM subjects ORDER BY name";
$subjects_result = $conn->query($subjects_query);

// Get all tags for filter
$all_tags = getMoodTags();

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

/* Tag Styles */
.tag-badge {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
    padding: 0.4rem 0.6rem;
    border-radius: 50rem;
}

/* Filter Collapse */
.filter-collapse {
    transition: all 0.3s ease;
}

/* Mobile Optimizations */
@media (max-width: 767.98px) {
    .mood-level {
        width: 40px;
        height: 40px;
        line-height: 40px;
        font-size: 1.25rem;
    }
    .filter-section {
        margin-bottom: 1rem;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-history me-2"></i>Mood History
                <?php if ($date): ?>
                    <span class="text-muted fs-5 ms-2">
                        <?php echo date('F j, Y', strtotime($date)); ?>
                    </span>
                <?php endif; ?>
            </h1>
            <p class="text-muted">View and filter your past mood entries</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="index.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i>Dashboard
            </a>
            <a href="entry.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add Entry
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">
                    <i class="fas fa-filter me-2"></i>Filters
                </h5>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false">
                    <i class="fas fa-sliders-h me-1"></i>Toggle Filters
                </button>
            </div>
            
            <div class="collapse show filter-collapse" id="filterCollapse">
                <form method="GET" class="row g-3">
                    <!-- Date Range -->
                    <div class="col-md-3 filter-section">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3 filter-section">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    
                    <!-- Subject -->
                    <div class="col-md-3 filter-section">
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
                    
                    <!-- Mood Level -->
                    <div class="col-md-3 filter-section">
                        <label for="mood_level" class="form-label">Mood Level</label>
                        <select class="form-select" id="mood_level" name="mood_level">
                            <option value="">All Levels</option>
                            <option value="1" <?php echo ($filter_mood == 1) ? 'selected' : ''; ?>>1 - Very Low</option>
                            <option value="2" <?php echo ($filter_mood == 2) ? 'selected' : ''; ?>>2 - Low</option>
                            <option value="3" <?php echo ($filter_mood == 3) ? 'selected' : ''; ?>>3 - Neutral</option>
                            <option value="4" <?php echo ($filter_mood == 4) ? 'selected' : ''; ?>>4 - Good</option>
                            <option value="5" <?php echo ($filter_mood == 5) ? 'selected' : ''; ?>>5 - Excellent</option>
                        </select>
                    </div>
                    
                    <!-- Tag -->
                    <div class="col-md-3 filter-section">
                        <label for="tag_id" class="form-label">Tag</label>
                        <select class="form-select" id="tag_id" name="tag_id">
                            <option value="">All Tags</option>
                            <?php foreach ($all_tags as $tag): ?>
                                <option value="<?php echo $tag['id']; ?>" <?php echo ($filter_tag == $tag['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Time of Day -->
                    <div class="col-md-3 filter-section">
                        <label for="time_of_day" class="form-label">Time of Day</label>
                        <select class="form-select" id="time_of_day" name="time_of_day">
                            <option value="">All Times</option>
                            <option value="morning" <?php echo ($filter_time == 'morning') ? 'selected' : ''; ?>>Morning (5am-12pm)</option>
                            <option value="afternoon" <?php echo ($filter_time == 'afternoon') ? 'selected' : ''; ?>>Afternoon (12pm-5pm)</option>
                            <option value="evening" <?php echo ($filter_time == 'evening') ? 'selected' : ''; ?>>Evening (5pm-9pm)</option>
                            <option value="night" <?php echo ($filter_time == 'night') ? 'selected' : ''; ?>>Night (9pm-5am)</option>
                        </select>
                    </div>
                    
                    <!-- Search -->
                    <div class="col-md-4 filter-section">
                        <label for="search" class="form-label">Search Notes</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search in notes..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    </div>
                    
                    <!-- Submit and Reset -->
                    <div class="col-md-2 d-flex align-items-end filter-section">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                    <div class="col-md-12 text-end">
                        <a href="history.php" class="btn btn-sm btn-outline-secondary">Reset Filters</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Mood Entries -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <i class="fas fa-list me-2"></i>Mood Entries
                <?php if (!empty($mood_entries)): ?>
                    <span class="badge bg-secondary ms-2"><?php echo count($mood_entries); ?> entries</span>
                <?php endif; ?>
            </h5>
            
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
                                        <div>
                                            <a href="entry.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteMoodEntry(<?php echo $entry['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($entry['notes'])): ?>
                                        <p class="mt-2 mb-2"><?php echo nl2br(htmlspecialchars($entry['notes'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($entry['tags'])): ?>
                                        <div class="mt-2">
                                            <?php foreach ($entry['tags'] as $tag): ?>
                                                <span class="badge tag-badge" style="background-color: <?php echo $tag['color']; ?>">
                                                    <?php echo htmlspecialchars($tag['name']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif (!empty($entry['factors'])): ?>
                                        <div class="mt-2">
                                            <?php foreach ($entry['factors'] as $factor): ?>
                                                <span class="badge <?php echo $factor['is_positive'] ? 'bg-success' : 'bg-danger'; ?> me-1">
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
                    <p class="text-muted mb-3">No mood entries found for the selected filters</p>
                    <a href="entry.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Mood Entry
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Function to delete mood entry
function deleteMoodEntry(entryId) {
    if (confirm('Are you sure you want to delete this mood entry?')) {
        fetch('ajax/delete_entry.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${entryId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to refresh the data
                window.location.reload();
            } else {
                alert('Error deleting mood entry: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the mood entry.');
        });
    }
}
</script>

<?php include '../../../includes/footer.php'; ?>
