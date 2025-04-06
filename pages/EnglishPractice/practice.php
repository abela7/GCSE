<?php
// GCSE/pages/EnglishPractice/practice.php (Updated for Favorites)
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/_functions.php';

// Get filters
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';
$favorites_only = isset($_GET['favorites']) && $_GET['favorites'] == '1';

// Get practice items
$practice_items = get_practice_items_for_flashcards($conn, 20, $category_id, $date_filter, $favorites_only);

// Get categories for filter
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM practice_categories ORDER BY name ASC");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
    $cat_result->free();
}

$page_title = "Practice Mode - English";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">Practice Mode</h1>
            <p class="text-muted">Review your English practice items</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="review.php" class="btn btn-outline-secondary">
                <i class="fas fa-list-alt me-1"></i> Back to Review
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="practice.php" method="GET" class="row g-3">
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="date_filter" class="form-select">
                        <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                        <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>This Week</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="favoritesFilter" name="favorites" value="1" <?php echo $favorites_only ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="favoritesFilter">Favorites Only</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($practice_items)): ?>
        <div class="text-center py-5">
            <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
            <h3 class="h4 mb-2">No Practice Items Found</h3>
            <p class="text-muted mb-4">Start by adding some practice items to your collection.</p>
            <a href="daily_entry.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Add New Items
            </a>
        </div>
    <?php else: 
        $total_items = count($practice_items);
        $first_item = array_shift($practice_items);
        $remaining_items = json_encode($practice_items);
    ?>
        <!-- Progress -->
        <div class="progress mb-4" style="height: 4px;">
            <div class="progress-bar" role="progressbar" style="width: <?php echo (1/$total_items)*100; ?>%"></div>
        </div>

        <!-- Flashcard -->
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($first_item['category_name']); ?></span>
                    <button type="button" class="btn btn-link text-warning p-0 toggle-favorite" 
                            data-item-id="<?php echo $first_item['id']; ?>">
                        <i class="<?php echo $first_item['is_favorite'] ? 'fas' : 'far'; ?> fa-star"></i>
                    </button>
                </div>

                <div class="text-center mb-4">
                    <h3 class="mb-4" id="flashcard-term"><?php echo htmlspecialchars($first_item['item_title']); ?></h3>
                    
                    <div id="flashcard-details" style="display: none;">
                        <div class="text-start">
                            <p class="mb-3"><strong>Meaning/Rule:</strong><br><?php echo nl2br(htmlspecialchars($first_item['item_meaning'])); ?></p>
                            <p class="mb-3"><strong>Example:</strong><br><?php echo nl2br(htmlspecialchars($first_item['item_example'])); ?></p>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button class="btn btn-primary me-2" id="flashcard-reveal">
                        <i class="fas fa-eye me-1"></i> Reveal
                    </button>
                    <button class="btn btn-success" id="flashcard-next" style="display: none;">
                        <i class="fas fa-arrow-right me-1"></i> Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Keyboard Shortcuts -->
        <div class="text-center text-muted">
            <small><i class="fas fa-keyboard me-1"></i> Use <kbd>Space</kbd> to reveal, <kbd>→</kbd> for next</small>
        </div>

        <script>
            const practiceItems = <?php echo $remaining_items; ?>;
            const totalItems = <?php echo $total_items; ?>;
            let currentIndex = 0;
            const progressBar = document.querySelector('.progress-bar');

            // Reveal button functionality
            document.getElementById('flashcard-reveal').addEventListener('click', function() {
                document.getElementById('flashcard-details').style.display = 'block';
                this.style.display = 'none';
                document.getElementById('flashcard-next').style.display = 'inline-block';
            });

            // Next button functionality
            document.getElementById('flashcard-next').addEventListener('click', function() {
                if (currentIndex < practiceItems.length) {
                    const nextItem = practiceItems[currentIndex];
                    
                    // Update card content
                    document.getElementById('flashcard-term').textContent = nextItem.item_title;
                    document.querySelector('.badge.bg-light').textContent = nextItem.category_name;
                    document.getElementById('flashcard-details').innerHTML = `
                        <div class="text-start">
                            <p class="mb-3"><strong>Meaning/Rule:</strong><br>${nextItem.item_meaning.replace(/\n/g, '<br>')}</p>
                            <p class="mb-3"><strong>Example:</strong><br>${nextItem.item_example.replace(/\n/g, '<br>')}</p>
                        </div>
                    `;
                    document.getElementById('flashcard-details').style.display = 'none';
                    document.getElementById('flashcard-reveal').style.display = 'inline-block';
                    this.style.display = 'none';
                    
                    // Update progress
                    const progress = ((currentIndex + 2) / totalItems) * 100;
                    progressBar.style.width = `${progress}%`;
                    
                    currentIndex++;
                } else {
                    // Practice complete
                    document.querySelector('.card-body').innerHTML = `
                        <div class="text-center p-4">
                            <h3 class="text-success mb-3"><i class="fas fa-check-circle"></i></h3>
                            <h4>Practice Complete!</h4>
                            <p class="mb-4">You've reviewed all ${totalItems} items.</p>
                            <div>
                                <a href="practice.php" class="btn btn-primary me-2">
                                    <i class="fas fa-redo me-1"></i> Practice Again
                                </a>
                                <a href="review.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-list-alt me-1"></i> Review Entries
                                </a>
                            </div>
                        </div>
                    `;
                }
            });

            // Favorite button functionality
            document.querySelector('.toggle-favorite').addEventListener('click', function(e) {
                e.stopPropagation();
                const itemId = this.dataset.itemId;
                const icon = this.querySelector('i');
                
                fetch('toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'item_id=' + itemId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        icon.classList.toggle('far');
                        icon.classList.toggle('fas');
                    }
                });
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.code === 'Space') {
                    e.preventDefault();
                    const revealBtn = document.getElementById('flashcard-reveal');
                    if (revealBtn && revealBtn.style.display !== 'none') {
                        revealBtn.click();
                    }
                } else if (e.code === 'ArrowRight') {
                    const nextBtn = document.getElementById('flashcard-next');
                    if (nextBtn && nextBtn.style.display !== 'none') {
                        nextBtn.click();
                    }
                }
            });
        </script>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>