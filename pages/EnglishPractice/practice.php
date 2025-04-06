<?php
// GCSE/pages/EnglishPractice/practice.php (Updated for Favorites)
session_start();
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/_functions.php';

// --- Determine Filters ---
$category_id = isset($_GET['category']) ? filter_var($_GET['category'], FILTER_VALIDATE_INT) : null;
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : null;
// New Filter: Favorites Only
$favorites_only = isset($_GET['favorites']) && $_GET['favorites'] == '1';

// Get practice items based on filters
$limit = $favorites_only ? 50 : 20; // Show more if filtering by favorites maybe? Adjust as needed
$practice_items = get_practice_items_for_flashcards($conn, $limit, $category_id, $date_filter, $favorites_only);


// --- Get Categories for Filter ---
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM practice_categories ORDER BY id ASC"); // Order by ID consistently
if ($cat_result) { while ($row = $cat_result->fetch_assoc()) { $categories[] = $row; } $cat_result->free(); }

// --- Start HTML ---
$page_title = "Practice Mode - English";
require_once __DIR__ . '/../../includes/header.php'; // Uses conditional CSS loading
?>

<div class="container mt-4 mb-5 english-practice-page">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <h1 class="h3 mb-0 me-3">Practice Mode</h1>
        <a href="review.php" class="btn btn-sm btn-outline-secondary"> <i class="fas fa-list-alt me-1"></i> Back to Review </a>
    </div>

     <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body bg-light rounded">
            <form action="practice.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
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
                 <div class="col-md-4">
                    <label for="date_filter" class="form-label visually-hidden">Time Period</label>
                     <select name="date_filter" id="date_filter" class="form-select form-select-sm">
                         <option value="all" <?php if($date_filter=='all' || $date_filter == null) echo 'selected';?>>All Time</option>
                         <option value="today" <?php if($date_filter=='today') echo 'selected';?>>Today</option>
                         <option value="week" <?php if($date_filter=='week') echo 'selected';?>>This Week</option>
                         <!-- Add specific date if needed, more complex UI required -->
                     </select>
                </div>
                 <div class="col-md-2">
                    <div class="form-check form-switch mt-2 mt-md-0">
                         <input class="form-check-input" type="checkbox" name="favorites" value="1" id="favoritesFilter" <?php if($favorites_only) echo 'checked'; ?>>
                         <label class="form-check-label small" for="favoritesFilter">Favorites Only</label>
                    </div>
                 </div>

                <div class="col-md-2 text-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

     <!-- Session Messages -->
     <?php if (!empty($_SESSION['success_ep'])): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success_ep']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success_ep']); endif; ?>
     <?php if (!empty($_SESSION['error_ep'])): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_SESSION['error_ep']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['error_ep']); endif; ?>
     <?php if (!empty($_SESSION['warning_ep'])): ?><div class="alert alert-warning alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_SESSION['warning_ep']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['warning_ep']); endif; ?>


    <?php if (empty($practice_items)): ?>
        <div class="alert alert-info text-center mt-4">
            <i class="fas fa-info-circle me-2"></i>
            No practice items found matching your filters.
             <a href="daily_entry.php" class="alert-link">Add some items?</a>
        </div>
    <?php else:
        $total_item_count = count($practice_items); // Get total before modifying
        $first_item = array_shift($practice_items); // Get first item, remaining in $practice_items
        $remaining_items_js = json_encode($practice_items); // Encode remaining for JS
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
            <div class="card flashcard mb-4" id="flashcard">
                <div class="card-body">
                    <!-- Favorite Toggle Button -->
                    <button type="button" id="favorite-button"
                            class="btn btn-sm btn-outline-warning favorite-btn toggle-favorite <?php echo $first_item['is_favorite'] ? 'btn-warning' : 'btn-outline-warning'; ?>"
                            data-item-id="<?php echo $first_item['id']; ?>"
                            title="<?php echo $first_item['is_favorite'] ? 'Remove from Favorites' : 'Add to Favorites'; ?>">
                        <i class="<?php echo $first_item['is_favorite'] ? 'fas' : 'far'; ?> fa-star"></i>
                    </button>

                    <!-- Term -->
                    <div class="term mt-2" id="flashcard-term">
                        <?php echo $first_item['item_title']; ?>
                    </div>

                    <!-- Details -->
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
        <div class="text-center text-muted mt-3"> <small class="keyboard-hint"> <i class="fas fa-keyboard me-1"></i> Use <kbd>Space</kbd> to reveal, <kbd>→</kbd> for next, <kbd>F</kbd> to favorite. </small> </div>

        <!-- Pass remaining items to JavaScript -->
        <script>
            // Define global variables
            const practiceItems = <?php echo $remaining_items_js; ?>;
            const totalItems = <?php echo $total_item_count; ?>;
            const currentCategory = <?php echo json_encode($category_id); ?>;
            const currentDateFilter = <?php echo json_encode($date_filter); ?>;
            const currentFavoritesOnly = <?php echo json_encode($favorites_only); ?>;
            let currentItemId = <?php echo $first_item['id']; ?>;
            let currentIsFavorite = <?php echo $first_item['is_favorite']; ?>;

            // Initialize when DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM loaded, initializing...');
                
                // Initialize flashcards
                if (document.getElementById('flashcard-term')) {
                    console.log('Initializing flashcards...');
                    initializeFlashcards();
                }
                
                // Initialize favorites
                if (document.querySelector('.toggle-favorite')) {
                    console.log('Initializing favorites...');
                    initializeFavorites();
                }
            });
        </script>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>