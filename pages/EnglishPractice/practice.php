<?php
// GCSE/pages/EnglishPractice/practice.php
session_start();
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/_functions.php';

// --- Determine Filters ---
$category_id = isset($_GET['category']) ? filter_var($_GET['category'], FILTER_VALIDATE_INT) : null;
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : null; // e.g., 'today', 'week', 'all', or specific Y-m-d
$week_num_filter = ($date_filter === 'week' && isset($_GET['week_num'])) ? filter_var($_GET['week_num'], FILTER_VALIDATE_INT) : null; // Week number if date_filter is week

// Adjust date_filter logic if 'week' is selected
if ($date_filter === 'week') {
     // Get start/end date for the week if week_num is valid
    $week_dates = get_week_dates($conn, $week_num_filter);
     if ($week_dates) {
         // Modify get_practice_items_for_flashcards to accept a date range maybe?
         // For now, let's keep it simple and maybe just fetch MORE items if week selected?
         // Or ideally, the function should handle 'week' with week_num directly.
         // Let's update the function signature later if needed. We pass null for now.
          $limit = 30; // Get more items for weekly practice
          $practice_items = get_practice_items_for_flashcards($conn, $limit, $category_id, null); // Fetch across time for the week
          // Could refine SQL in function later to accept date range based on week_num
     } else {
          $_SESSION['warning_ep'] = "Could not find date range for specified week.";
          $practice_items = get_practice_items_for_flashcards($conn, 10, $category_id, 'today'); // Fallback to today
     }

} elseif ($date_filter) {
     $practice_items = get_practice_items_for_flashcards($conn, 10, $category_id, $date_filter);
} else {
     $practice_items = get_practice_items_for_flashcards($conn, 20, $category_id, null); // Default: All time, 20 items
}

// --- Get All Categories for Filter Dropdown ---
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM practice_categories ORDER BY id ASC");
if ($cat_result) { while ($row = $cat_result->fetch_assoc()) { $categories[] = $row; } $cat_result->free(); }

// --- Start HTML ---
$page_title = "Practice Mode - English";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4 mb-5 english-practice-page">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <h1 class="h3 mb-0">Practice Mode</h1>
         <a href="review.php" class="btn btn-sm btn-outline-secondary"> <i class="fas fa-list-alt me-1"></i> Back to Review </a>
    </div>

     <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body bg-light rounded">
            <form action="practice.php" method="GET" class="d-flex flex-column flex-md-row gap-2 align-items-md-center">
                 <div class="flex-grow-1">
                     <label for="category" class="form-label visually-hidden">Category</label>
                    <select name="category" id="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php if ($category_id == $cat['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="flex-grow-1">
                     <label for="date_filter" class="form-label visually-hidden">Time Period</label>
                     <select name="date_filter" id="date_filter" class="form-select form-select-sm">
                         <option value="all"   <?php if($date_filter=='all' || $date_filter == null) echo 'selected';?>>All Time</option>
                         <option value="today" <?php if($date_filter=='today') echo 'selected';?>>Today</option>
                         <option value="week"  <?php if($date_filter=='week') echo 'selected';?>>This Week</option>
                         <!-- Add more specific ranges if needed -->
                     </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-filter me-1"></i> Filter & Shuffle
                </button>
            </form>
        </div>
    </div>


    <?php if (empty($practice_items)): ?>
        <div class="alert alert-info text-center mt-4">
            <i class="fas fa-info-circle me-2"></i>
            No practice items found matching your filters.
             <a href="daily_entry.php" class="alert-link">Add some items?</a>
        </div>
    <?php else:
        $total_item_count = count($practice_items); // Get total before slicing
        $first_item = array_shift($practice_items); // Get first item, remove from array
    ?>
        <!-- Progress Bar Wrapper -->
        <div class="flashcard-progress mb-3" style="max-width: 700px; margin-left: auto; margin-right: auto;">
             <div class="progress" style="height: 20px;">
                <div class="progress-bar" role="progressbar" style="width: <?php echo (1/$total_item_count)*100; ?>%;"
                     aria-valuenow="1" aria-valuemin="0" aria-valuemax="<?php echo $total_item_count; ?>">
                     1/<?php echo $total_item_count; ?>
                 </div>
            </div>
         </div>

        <!-- Flashcard Container -->
        <div class="flashcard-container">
            <div class="card flashcard" id="flashcard">
                <div class="card-body">
                    <!-- Term (Question Part) -->
                     <div class="term" id="flashcard-term">
                        <?php echo $first_item['item_title']; ?>
                    </div>
                     <!-- Details (Answer Part - Initially Hidden) -->
                    <div class="details" id="flashcard-details" style="display: none;">
                         <p class="mb-2"><strong>Meaning/Rule:</strong><br><?php echo nl2br($first_item['item_meaning']); ?></p>
                         <p class="mb-2"><strong>Example:</strong><br><?php echo nl2br($first_item['item_example']); ?></p>
                         <p class="text-muted mb-0"><small>Category: <?php echo $first_item['category_name']; ?></small></p>
                    </div>
                     <!-- Actions -->
                    <div class="flashcard-actions mt-3">
                        <button class="btn btn-outline-primary" id="flashcard-reveal">
                             <i class="fas fa-eye me-1"></i>Reveal
                        </button>
                        <button class="btn btn-primary" id="flashcard-next" style="display: none;">
                            <i class="fas fa-arrow-right me-1"></i>Next
                        </button>
                    </div>
                 </div>
            </div>
        </div>

        <!-- Keyboard Shortcuts Info -->
        <div class="text-center text-muted mt-3">
            <small class="keyboard-hint">
                <i class="fas fa-keyboard me-1"></i>
                Use <kbd>Space</kbd> to reveal, <kbd>→</kbd> for next.
            </small>
        </div>

        <!-- Pass remaining items to JavaScript -->
        <script>
            const practiceItems = <?php echo json_encode($practice_items); // Pass remaining items ?>;
            const totalItems = <?php echo $total_item_count; ?>; // Pass total count
            const currentCategory = <?php echo json_encode($category_id); // Pass filter for Practice Again button ?>;
        </script>

    <?php endif; ?>
</div>

<?php
// Include footer (will load script.js)
require_once __DIR__ . '/../../includes/footer.php';
?>