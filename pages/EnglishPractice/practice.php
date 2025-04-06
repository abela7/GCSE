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

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Practice Mode</h1>
        <a href="review.php" class="btn btn-outline-secondary">
            <i class="fas fa-list-alt me-1"></i> Back to Review
        </a>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
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
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No practice items found. <a href="daily_entry.php">Add some items?</a>
        </div>
    <?php else: 
        $total_items = count($practice_items);
        $first_item = array_shift($practice_items);
        $remaining_items = json_encode($practice_items);
    ?>
        <!-- Progress Bar -->
        <div class="progress mb-4" style="height: 20px;">
            <div class="progress-bar" role="progressbar" style="width: <?php echo (1/$total_items)*100; ?>%">
                1/<?php echo $total_items; ?>
            </div>
        </div>

        <!-- Flashcard -->
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center p-4">
                <!-- Favorite Button -->
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-sm toggle-favorite <?php echo $first_item['is_favorite'] ? 'btn-warning' : 'btn-outline-warning'; ?>" 
                            data-item-id="<?php echo $first_item['id']; ?>">
                        <i class="<?php echo $first_item['is_favorite'] ? 'fas' : 'far'; ?> fa-star"></i>
                    </button>
                </div>

                <!-- Term -->
                <h3 class="mb-4" id="flashcard-term"><?php echo htmlspecialchars($first_item['item_title']); ?></h3>

                <!-- Details (Hidden by default) -->
                <div id="flashcard-details" style="display: none;">
                    <div class="text-start">
                        <p class="mb-3"><strong>Meaning/Rule:</strong><br><?php echo nl2br(htmlspecialchars($first_item['item_meaning'])); ?></p>
                        <p class="mb-3"><strong>Example:</strong><br><?php echo nl2br(htmlspecialchars($first_item['item_example'])); ?></p>
                        <p class="text-muted"><small>Category: <?php echo htmlspecialchars($first_item['category_name']); ?></small></p>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="mt-4">
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
            // Flashcard data
            const practiceItems = <?php echo $remaining_items; ?>;
            const totalItems = <?php echo $total_items; ?>;
            let currentIndex = 0;

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
                    document.getElementById('flashcard-term').textContent = nextItem.item_title;
                    document.getElementById('flashcard-details').innerHTML = `
                        <div class="text-start">
                            <p class="mb-3"><strong>Meaning/Rule:</strong><br>${nextItem.item_meaning.replace(/\n/g, '<br>')}</p>
                            <p class="mb-3"><strong>Example:</strong><br>${nextItem.item_example.replace(/\n/g, '<br>')}</p>
                            <p class="text-muted"><small>Category: ${nextItem.category_name}</small></p>
                        </div>
                    `;
                    document.getElementById('flashcard-details').style.display = 'none';
                    document.getElementById('flashcard-reveal').style.display = 'inline-block';
                    this.style.display = 'none';
                    
                    // Update favorite button
                    const favoriteBtn = document.querySelector('.toggle-favorite');
                    favoriteBtn.dataset.itemId = nextItem.id;
                    favoriteBtn.className = `btn btn-sm toggle-favorite ${nextItem.is_favorite ? 'btn-warning' : 'btn-outline-warning'}`;
                    favoriteBtn.innerHTML = `<i class="${nextItem.is_favorite ? 'fas' : 'far'} fa-star"></i>`;
                    
                    // Update progress
                    const progress = ((currentIndex + 2) / totalItems) * 100;
                    document.querySelector('.progress-bar').style.width = `${progress}%`;
                    document.querySelector('.progress-bar').textContent = `${currentIndex + 2}/${totalItems}`;
                    
                    currentIndex++;
                } else {
                    // Practice complete
                    document.querySelector('.card-body').innerHTML = `
                        <div class="text-center p-4">
                            <h3 class="text-success mb-3"><i class="fas fa-check-circle fa-2x"></i></h3>
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
            document.querySelector('.toggle-favorite').addEventListener('click', function() {
                const itemId = this.dataset.itemId;
                const icon = this.querySelector('i');
                
                // Send AJAX request
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
                        this.classList.toggle('btn-warning');
                        this.classList.toggle('btn-outline-warning');
                        icon.classList.toggle('far');
                        icon.classList.toggle('fas');
                    }
                });
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.code === 'Space') {
                    const revealBtn = document.getElementById('flashcard-reveal');
                    if (revealBtn && revealBtn.style.display !== 'none') {
                        e.preventDefault();
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