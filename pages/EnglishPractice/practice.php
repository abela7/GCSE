<?php
// GCSE/pages/EnglishPractice/practice.php
session_start();
require_once '../../includes/db_connect.php';
require_once '_functions.php';

// Get category filter from URL
$category_id = isset($_GET['category']) ? filter_var($_GET['category'], FILTER_VALIDATE_INT) : null;

// Get random practice items
$practice_items = get_random_practice_items($conn, 10, $category_id);

// Get all categories for filter
$categories_query = "SELECT id, category_name FROM practice_categories ORDER BY category_name";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
while ($category = mysqli_fetch_assoc($categories_result)) {
    $categories[$category['id']] = $category['category_name'];
}

$page_title = "Practice Mode - GCSE English";
include '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-3">Practice Mode</h1>
            
            <!-- Category Filter -->
            <div class="mb-4">
                <form action="" method="get" class="d-flex gap-2 align-items-center">
                    <label for="category" class="form-label mb-0">Filter by Category:</label>
                    <select name="category" id="category" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $id => $name): ?>
                            <option value="<?= htmlspecialchars($id) ?>" <?= $category_id == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if (empty($practice_items)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No practice items found. 
                    <a href="daily_entry.php" class="alert-link">Add some items</a> to start practicing!
                </div>
            <?php else: ?>
                <!-- Progress Bar -->
                <div class="progress mb-4" style="height: 25px;">
                    <div class="progress-bar" role="progressbar" style="width: 10%;" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">1/10</div>
                </div>

                <!-- Flashcard -->
                <div class="card flashcard mb-4">
                    <div class="card-body text-center p-4">
                        <h2 id="flashcard-term" class="mb-4"><?= htmlspecialchars($practice_items[0]['item_title']) ?></h2>
                        
                        <div id="flashcard-details" style="display: none;">
                            <p><strong>Meaning:</strong> <?= nl2br(htmlspecialchars($practice_items[0]['item_meaning'])) ?></p>
                            <p><strong>Example:</strong> <?= nl2br(htmlspecialchars($practice_items[0]['item_example'])) ?></p>
                            <p class="text-muted"><small>Category: <?= htmlspecialchars($practice_items[0]['category_name']) ?></small></p>
                        </div>
                        
                        <div class="d-flex justify-content-center gap-2">
                            <button id="flashcard-reveal" class="btn btn-primary">
                                <i class="fas fa-eye me-2"></i>Reveal Answer
                            </button>
                            <button id="flashcard-next" class="btn btn-success" style="display: none;">
                                <i class="fas fa-arrow-right me-2"></i>Next
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Keyboard Shortcuts Info -->
                <div class="text-center text-muted">
                    <small>
                        <i class="fas fa-keyboard me-1"></i>
                        Keyboard shortcuts: <kbd>Space</kbd> to reveal, <kbd>→</kbd> for next
                    </small>
                </div>

                <script>
                    // Remove first item as it's already displayed
                    const firstItem = <?= json_encode($practice_items[0]) ?>;
                    const remainingItems = <?= json_encode(array_slice($practice_items, 1)) ?>;
                    const practiceItems = remainingItems;
                    const currentCategory = <?= json_encode($category_id) ?>;
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?> 