<?php
// GCSE/pages/EnglishPractice/practice.php
session_start();
require_once __DIR__ . '/../../config/db_connect.php';
require_once '_functions.php';

// Get category filter if set
$category_id = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);

// Get practice items
$practice_items = get_random_practice_items($conn, 10, $category_id);

// Get all categories for filter
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM practice_categories ORDER BY name ASC");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
    $cat_result->free();
}

// Set page title and include header
$page_title = "Practice Mode";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <!-- Header -->
    <div class="text-center mb-4">
        <h1 class="h3">Practice Mode</h1>
        <p class="text-muted">Test your knowledge with flashcards</p>
    </div>

    <!-- Category Filter -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form action="practice.php" method="GET" class="d-flex gap-2">
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($practice_items)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i>
            No practice items found. Try a different category or add some items first.
        </div>
    <?php else: ?>
        <!-- Flashcard Container -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="flashcard">
                    <div class="term" id="flashcard-term">
                        <?php echo htmlspecialchars($practice_items[0]['item_title']); ?>
                    </div>
                    <div class="details" id="flashcard-details">
                        <p><strong>Meaning:</strong> <?php echo htmlspecialchars($practice_items[0]['item_meaning']); ?></p>
                        <p><strong>Example:</strong> <?php echo htmlspecialchars($practice_items[0]['item_example']); ?></p>
                        <p class="text-muted"><small>Category: <?php echo htmlspecialchars($practice_items[0]['category_name']); ?></small></p>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary" id="flashcard-reveal">
                            <i class="fas fa-eye me-2"></i>Reveal Answer
                        </button>
                        <button class="btn btn-success" id="flashcard-next" style="display: none;">
                            <i class="fas fa-arrow-right me-2"></i>Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress -->
        <div class="row justify-content-center mt-3">
            <div class="col-md-8">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 10%;" 
                         aria-valuenow="1" aria-valuemin="0" aria-valuemax="10">1/10</div>
                </div>
            </div>
        </div>

        <!-- Store remaining items for JavaScript -->
        <script>
            const practiceItems = <?php echo json_encode(array_slice($practice_items, 1)); ?>;
            let currentIndex = 0;
            const totalItems = <?php echo count($practice_items); ?>;
            
            document.getElementById('flashcard-next').addEventListener('click', function() {
                if (currentIndex < practiceItems.length) {
                    // Update flashcard content
                    document.getElementById('flashcard-term').textContent = practiceItems[currentIndex].item_title;
                    document.getElementById('flashcard-details').innerHTML = `
                        <p><strong>Meaning:</strong> ${practiceItems[currentIndex].item_meaning}</p>
                        <p><strong>Example:</strong> ${practiceItems[currentIndex].item_example}</p>
                        <p class="text-muted"><small>Category: ${practiceItems[currentIndex].category_name}</small></p>
                    `;
                    
                    // Reset visibility
                    document.getElementById('flashcard-details').style.display = 'none';
                    document.getElementById('flashcard-reveal').style.display = 'inline-block';
                    this.style.display = 'none';
                    
                    // Update progress
                    currentIndex++;
                    const progress = ((currentIndex + 1) / totalItems) * 100;
                    document.querySelector('.progress-bar').style.width = progress + '%';
                    document.querySelector('.progress-bar').textContent = `${currentIndex + 1}/${totalItems}`;
                } else {
                    // End of practice
                    document.querySelector('.flashcard').innerHTML = `
                        <h3>Practice Complete!</h3>
                        <p class="mb-4">You've reviewed all ${totalItems} items.</p>
                        <a href="practice.php" class="btn btn-primary">
                            <i class="fas fa-redo me-2"></i>Practice Again
                        </a>
                    `;
                }
            });
        </script>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?> 