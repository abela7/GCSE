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

<div class="container-fluid px-4 py-5 practice-container">
    <!-- Header Section -->
    <div class="row align-items-center mb-5">
        <div class="col-md-6">
            <h1 class="display-6 fw-bold mb-0">Practice Mode</h1>
            <p class="text-muted mb-0">Master your English skills through interactive flashcards</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="review.php" class="btn btn-outline-secondary">
                <i class="fas fa-list-alt me-1"></i> Back to Review
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm mb-5 border-0">
        <div class="card-body p-4">
            <form action="practice.php" method="GET" class="row g-4">
                <div class="col-md-4">
                    <label class="form-label text-muted mb-2">Category</label>
                    <select name="category" class="form-select form-select-lg">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted mb-2">Time Period</label>
                    <select name="date_filter" class="form-select form-select-lg">
                        <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                        <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>This Week</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted mb-2">Options</label>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="favoritesFilter" name="favorites" value="1" <?php echo $favorites_only ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="favoritesFilter">Favorites Only</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-filter me-2"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($practice_items)): ?>
        <div class="text-center py-5">
            <div class="empty-state">
                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                <h3 class="h4 mb-2">No Practice Items Found</h3>
                <p class="text-muted mb-4">Start by adding some practice items to your collection.</p>
                <a href="daily_entry.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i> Add New Items
                </a>
            </div>
        </div>
    <?php else: 
        $total_items = count($practice_items);
        $first_item = array_shift($practice_items);
        $remaining_items = json_encode($practice_items);
    ?>
        <!-- Progress Section -->
        <div class="progress-section mb-5">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Progress</span>
                <span class="text-muted" id="progress-text">1/<?php echo $total_items; ?></span>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo (1/$total_items)*100; ?>%"></div>
            </div>
        </div>

        <!-- Flashcard Container -->
        <div class="flashcard-container">
            <div class="flashcard" id="flashcard">
                <div class="flashcard-inner">
                    <!-- Front of Card -->
                    <div class="flashcard-front">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($first_item['category_name']); ?></span>
                            <button type="button" class="btn btn-link text-warning p-0 toggle-favorite" 
                                    data-item-id="<?php echo $first_item['id']; ?>">
                                <i class="<?php echo $first_item['is_favorite'] ? 'fas' : 'far'; ?> fa-star fa-lg"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <h2 class="card-title mb-4" id="flashcard-term"><?php echo htmlspecialchars($first_item['item_title']); ?></h2>
                            <button class="btn btn-primary btn-lg reveal-btn" id="flashcard-reveal">
                                <i class="fas fa-eye me-2"></i> Reveal Answer
                            </button>
                        </div>
                    </div>

                    <!-- Back of Card -->
                    <div class="flashcard-back" id="flashcard-details">
                        <div class="card-body">
                            <div class="answer-content">
                                <div class="mb-4">
                                    <h4 class="text-muted mb-2">Meaning/Rule</h4>
                                    <p class="lead"><?php echo nl2br(htmlspecialchars($first_item['item_meaning'])); ?></p>
                                </div>
                                <div class="mb-4">
                                    <h4 class="text-muted mb-2">Example</h4>
                                    <p class="lead"><?php echo nl2br(htmlspecialchars($first_item['item_example'])); ?></p>
                                </div>
                            </div>
                            <button class="btn btn-success btn-lg next-btn" id="flashcard-next">
                                <i class="fas fa-arrow-right me-2"></i> Next Card
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls Info -->
        <div class="text-center mt-4">
            <div class="controls-info">
                <span class="badge bg-light text-dark me-2">
                    <i class="fas fa-keyboard me-1"></i> Space to reveal
                </span>
                <span class="badge bg-light text-dark me-2">
                    <i class="fas fa-arrow-right me-1"></i> → for next
                </span>
                <span class="badge bg-light text-dark">
                    <i class="fas fa-star me-1"></i> F to favorite
                </span>
            </div>
        </div>

        <script>
            // Flashcard data
            const practiceItems = <?php echo $remaining_items; ?>;
            const totalItems = <?php echo $total_items; ?>;
            let currentIndex = 0;
            const flashcard = document.getElementById('flashcard');
            const progressText = document.getElementById('progress-text');
            const progressBar = document.querySelector('.progress-bar');

            // Initialize card flip
            function flipCard() {
                flashcard.classList.toggle('flipped');
            }

            // Reveal button functionality
            document.getElementById('flashcard-reveal').addEventListener('click', function() {
                flipCard();
            });

            // Next button functionality
            document.getElementById('flashcard-next').addEventListener('click', function() {
                if (currentIndex < practiceItems.length) {
                    const nextItem = practiceItems[currentIndex];
                    
                    // Update card content
                    document.getElementById('flashcard-term').textContent = nextItem.item_title;
                    document.querySelector('.badge.bg-light').textContent = nextItem.category_name;
                    
                    // Update favorite button
                    const favoriteBtn = document.querySelector('.toggle-favorite');
                    favoriteBtn.dataset.itemId = nextItem.id;
                    favoriteBtn.innerHTML = `<i class="${nextItem.is_favorite ? 'fas' : 'far'} fa-star fa-lg"></i>`;
                    
                    // Update progress
                    const progress = ((currentIndex + 2) / totalItems) * 100;
                    progressBar.style.width = `${progress}%`;
                    progressText.textContent = `${currentIndex + 2}/${totalItems}`;
                    
                    // Flip card back
                    setTimeout(() => {
                        flashcard.classList.remove('flipped');
                    }, 300);
                    
                    currentIndex++;
                } else {
                    // Practice complete
                    document.querySelector('.flashcard-container').innerHTML = `
                        <div class="completion-screen text-center py-5">
                            <div class="completion-icon mb-4">
                                <i class="fas fa-check-circle fa-4x text-success"></i>
                            </div>
                            <h2 class="mb-3">Practice Complete!</h2>
                            <p class="lead text-muted mb-4">You've reviewed all ${totalItems} items.</p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="practice.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-redo me-2"></i> Practice Again
                                </a>
                                <a href="review.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-list-alt me-2"></i> Review Entries
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
                        // Show toast notification
                        showToast(data.message);
                    }
                });
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.code === 'Space') {
                    e.preventDefault();
                    const revealBtn = document.getElementById('flashcard-reveal');
                    if (revealBtn && !flashcard.classList.contains('flipped')) {
                        revealBtn.click();
                    }
                } else if (e.code === 'ArrowRight') {
                    const nextBtn = document.getElementById('flashcard-next');
                    if (nextBtn && flashcard.classList.contains('flipped')) {
                        nextBtn.click();
                    }
                } else if (e.code === 'KeyF') {
                    const favoriteBtn = document.querySelector('.toggle-favorite');
                    if (favoriteBtn) {
                        favoriteBtn.click();
                    }
                }
            });

            // Toast notification function
            function showToast(message) {
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-white bg-primary border-0';
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');
                
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                
                document.body.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                toast.addEventListener('hidden.bs.toast', function() {
                    document.body.removeChild(toast);
                });
            }
        </script>

        <style>
            .practice-container {
                max-width: 1200px;
                margin: 0 auto;
            }

            .flashcard-container {
                perspective: 1000px;
                margin: 0 auto;
                max-width: 800px;
            }

            .flashcard {
                position: relative;
                width: 100%;
                height: 400px;
                transition: transform 0.6s;
                transform-style: preserve-3d;
            }

            .flashcard.flipped {
                transform: rotateY(180deg);
            }

            .flashcard-front,
            .flashcard-back {
                position: absolute;
                width: 100%;
                height: 100%;
                backface-visibility: hidden;
                border-radius: 15px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }

            .flashcard-front {
                background: white;
            }

            .flashcard-back {
                background: white;
                transform: rotateY(180deg);
                padding: 2rem;
            }

            .card-header {
                background: transparent;
                border-bottom: 1px solid rgba(0,0,0,0.1);
                padding: 1rem;
            }

            .card-body {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                padding: 2rem;
                height: calc(100% - 60px);
            }

            .reveal-btn,
            .next-btn {
                padding: 0.75rem 2rem;
                font-size: 1.1rem;
                border-radius: 50px;
                transition: all 0.3s ease;
            }

            .reveal-btn:hover,
            .next-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }

            .empty-state {
                padding: 4rem 2rem;
                text-align: center;
            }

            .completion-screen {
                background: white;
                border-radius: 15px;
                padding: 3rem;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }

            .completion-icon {
                color: #28a745;
            }

            .controls-info {
                background: rgba(255,255,255,0.9);
                padding: 0.5rem 1rem;
                border-radius: 50px;
                display: inline-block;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }

            @media (max-width: 768px) {
                .flashcard {
                    height: 500px;
                }
                
                .card-body {
                    padding: 1.5rem;
                }
                
                .reveal-btn,
                .next-btn {
                    width: 100%;
                }
            }
        </style>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>