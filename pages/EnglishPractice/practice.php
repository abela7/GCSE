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

<style>
.flashcard-container {
    perspective: 1000px;
    margin: 20px auto;
    max-width: 700px;
}

.flashcard {
    position: relative;
    width: 100%;
    height: 400px;
    transition: transform 0.6s;
    transform-style: preserve-3d;
    cursor: pointer;
}

.flashcard.is-flipped {
    transform: rotateY(180deg);
}

.flashcard-front, .flashcard-back {
    position: absolute;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    background: white;
    padding: 2rem;
    display: flex;
    flex-direction: column;
}

.flashcard-back {
    transform: rotateY(180deg);
}

.flashcard-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.flashcard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.category-badge {
    background-color: #f8f9fa;
    color: #6c757d;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 500;
}

.favorite-btn {
    background-color: #fff;
    border: 2px solid #ffc107;
    color: #ffc107;
}

.favorite-btn:hover {
    background-color: #fff3cd;
}

.favorite-btn.is-favorite {
    background-color: #ffc107;
    color: #000;
}

.flashcard-title {
    font-size: 2rem;
    margin-bottom: 1.5rem;
    color: #2c3e50;
    font-weight: 600;
}

.flashcard-details {
    font-size: 1.1rem;
    line-height: 1.6;
}

.flashcard-example {
    margin-top: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 10px;
    font-style: italic;
}

.progress-container {
    max-width: 700px;
    margin: 0 auto 2rem;
}

.progress {
    height: 6px;
    border-radius: 3px;
    background-color: #e9ecef;
}

.progress-bar {
    background-color: #4CAF50;
    transition: width 0.3s ease;
}

.controls {
    max-width: 700px;
    margin: 1.5rem auto;
    display: flex;
    justify-content: center;
    gap: 1rem;
}

.control-btn {
    width: 45px;
    height: 45px;
    padding: 0;
    border-radius: 50%;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.3s;
    margin: 0 5px;
}

.reveal-btn {
    background-color: #4CAF50;
    color: white;
}

.reveal-btn:hover {
    background-color: #45a049;
}

.next-btn {
    background-color: #2196F3;
    color: white;
}

.next-btn:hover {
    background-color: #1e88e5;
}

.back-btn {
    background-color: #2196F3;
    color: white;
}

.back-btn:hover {
    background-color: #1e88e5;
}

.filters-container {
    max-width: 700px;
    margin: 0 auto 2rem;
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}
</style>

<div class="container py-4">
    <!-- Header -->
    <div class="text-center mb-4">
        <h1 class="display-5 mb-2">Practice Mode</h1>
        <p class="text-muted">Master your English practice items</p>
    </div>

    <!-- Filters -->
    <div class="filters-container">
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
                    <label class="form-check-label" for="favoritesFilter">Favorites</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </form>
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
        <div class="progress-container">
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: <?php echo (1/$total_items)*100; ?>%"></div>
            </div>
        </div>

        <!-- Flashcard -->
        <div class="flashcard-container">
            <div class="flashcard" id="flashcard">
                <div class="flashcard-front">
                    <div class="flashcard-header">
                        <span class="category-badge"><?php echo htmlspecialchars($first_item['category_name']); ?></span>
                    </div>
                    <div class="flashcard-content">
                        <h2 class="flashcard-title" id="flashcard-term"><?php 
                            echo html_entity_decode(htmlspecialchars_decode($first_item['item_title']), ENT_QUOTES | ENT_HTML5, 'UTF-8'); 
                        ?></h2>
                        <p class="text-muted">Click to reveal the answer</p>
                    </div>
                </div>
                <div class="flashcard-back">
                    <div class="flashcard-header">
                        <span class="category-badge"><?php echo htmlspecialchars($first_item['category_name']); ?></span>
                    </div>
                    <div class="flashcard-content">
                        <div class="flashcard-details">
                            <h3 class="h5 mb-3">Meaning/Rule:</h3>
                            <p><?php echo nl2br(htmlspecialchars($first_item['item_meaning'])); ?></p>
                            <div class="flashcard-example">
                                <h3 class="h5 mb-2">Example:</h3>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($first_item['item_example'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <button class="control-btn favorite-btn toggle-favorite" data-item-id="<?php echo $first_item['id']; ?>" title="Add to Favorites">
                <i class="<?php echo $first_item['is_favorite'] ? 'fas' : 'far'; ?> fa-star"></i>
            </button>
            <button class="control-btn back-btn" id="flashcard-back" style="display: none;" title="Previous Card">
                <i class="fas fa-arrow-left"></i>
            </button>
            <button class="control-btn reveal-btn" id="flashcard-reveal" title="Flip Card">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button class="control-btn next-btn" id="flashcard-next" style="display: none;" title="Next Card">
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>

        <!-- Keyboard Shortcuts -->
        <div class="text-center text-muted">
            <small><i class="fas fa-keyboard me-1"></i> Use <kbd>Space</kbd> to flip, <kbd>←</kbd> for previous, <kbd>→</kbd> for next</small>
        </div>

        <script>
            const practiceItems = <?php echo $remaining_items; ?>;
            const totalItems = <?php echo $total_items; ?>;
            let currentIndex = 0;
            const progressBar = document.querySelector('.progress-bar');
            const flashcard = document.getElementById('flashcard');
            const viewedItems = [<?php echo json_encode($first_item); ?>]; // Store viewed items

            // Function to update card content
            function updateCardContent(item, direction = 'next') {
                // Reset card to front
                flashcard.classList.remove('is-flipped');
                
                // Update card content with proper decoding
                const decodedTitle = item.item_title.replace(/&(?:amp|#0*39|#x0*27);/g, "'");
                document.getElementById('flashcard-term').textContent = decodedTitle;
                document.querySelectorAll('.category-badge').forEach(badge => {
                    badge.textContent = item.category_name;
                });

                // Update favorite button
                const favoriteBtn = document.querySelector('.toggle-favorite');
                favoriteBtn.dataset.itemId = item.id;
                const icon = favoriteBtn.querySelector('i');
                if (item.is_favorite) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    favoriteBtn.classList.add('is-favorite');
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    favoriteBtn.classList.remove('is-favorite');
                }
                
                // Update back content
                const backContent = flashcard.querySelector('.flashcard-back .flashcard-details');
                backContent.innerHTML = `
                    <h3 class="h5 mb-3">Meaning/Rule:</h3>
                    <p>${item.item_meaning.replace(/\n/g, '<br>')}</p>
                    <div class="flashcard-example">
                        <h3 class="h5 mb-2">Example:</h3>
                        <p class="mb-0">${item.item_example.replace(/\n/g, '<br>')}</p>
                    </div>
                `;
                
                // Reset buttons
                document.getElementById('flashcard-reveal').querySelector('i').classList.remove('fa-rotate-180');
                document.getElementById('flashcard-next').style.display = 'none';
                
                // Update navigation buttons visibility
                document.getElementById('flashcard-back').style.display = currentIndex > 0 ? 'inline-flex' : 'none';
                
                // Update progress
                const progress = ((currentIndex + 1) / totalItems) * 100;
                progressBar.style.width = `${progress}%`;
            }

            // Favorite button functionality
            document.querySelector('.toggle-favorite').addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent card flip
                const itemId = this.dataset.itemId;
                const icon = this.querySelector('i');
                
                fetch('toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `item_id=${itemId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        icon.classList.toggle('far');
                        icon.classList.toggle('fas');
                        this.classList.toggle('is-favorite');
                        
                        // Update the item in viewedItems array
                        const currentItem = viewedItems[currentIndex];
                        if (currentItem) {
                            currentItem.is_favorite = !currentItem.is_favorite;
                        }
                        console.log(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            });

            // Flip card functionality
            document.getElementById('flashcard-reveal').addEventListener('click', function() {
                flashcard.classList.toggle('is-flipped');
                if (flashcard.classList.contains('is-flipped')) {
                    document.getElementById('flashcard-next').style.display = 'inline-flex';
                    this.querySelector('i').classList.add('fa-rotate-180');
                } else {
                    this.querySelector('i').classList.remove('fa-rotate-180');
                }
            });

            // Back button functionality
            document.getElementById('flashcard-back').addEventListener('click', function() {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateCardContent(viewedItems[currentIndex], 'back');
                }
            });

            // Next button functionality
            document.getElementById('flashcard-next').addEventListener('click', function() {
                if (currentIndex < practiceItems.length) {
                    const nextItem = practiceItems[currentIndex];
                    viewedItems.push(nextItem); // Store the item
                    currentIndex++;
                    updateCardContent(nextItem, 'next');
                } else {
                    // Practice complete
                    document.querySelector('.flashcard-container').innerHTML = `
                        <div class="text-center p-4">
                            <h3 class="text-success mb-3"><i class="fas fa-check-circle fa-3x"></i></h3>
                            <h4 class="mb-3">Practice Complete!</h4>
                            <p class="mb-4">You've reviewed all ${totalItems} items.</p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="practice.php" class="btn btn-primary">
                                    <i class="fas fa-redo me-1"></i> Practice Again
                                </a>
                                <a href="review.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-list-alt me-1"></i> Review Entries
                                </a>
                            </div>
                        </div>
                    `;
                    document.querySelector('.controls').style.display = 'none';
                }
            });

            // Click anywhere on card to flip (except favorite button)
            flashcard.addEventListener('click', function(e) {
                const favoriteBtn = e.target.closest('.toggle-favorite');
                if (!favoriteBtn) {
                    document.getElementById('flashcard-reveal').click();
                }
            });

            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.code === 'Space') {
                    e.preventDefault();
                    document.getElementById('flashcard-reveal').click();
                } else if (e.code === 'ArrowRight' && !flashcard.classList.contains('is-flipped')) {
                    document.getElementById('flashcard-next').click();
                } else if (e.code === 'ArrowLeft' && !flashcard.classList.contains('is-flipped')) {
                    document.getElementById('flashcard-back').click();
                }
            });
        </script>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>