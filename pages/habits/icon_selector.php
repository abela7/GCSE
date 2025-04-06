<?php
// Common Font Awesome icons grouped by categories
$icon_categories = [
    'Basic' => [
        'fas fa-check-circle' => 'Check Circle',
        'fas fa-times-circle' => 'Times Circle',
        'fas fa-star' => 'Star',
        'fas fa-heart' => 'Heart',
        'fas fa-circle' => 'Circle',
        'fas fa-square' => 'Square',
        'fas fa-dot-circle' => 'Dot Circle'
    ],
    'Actions' => [
        'fas fa-check' => 'Check',
        'fas fa-times' => 'Times',
        'fas fa-plus' => 'Plus',
        'fas fa-minus' => 'Minus',
        'fas fa-sync' => 'Sync',
        'fas fa-redo' => 'Redo',
        'fas fa-undo' => 'Undo'
    ],
    'Time' => [
        'fas fa-clock' => 'Clock',
        'fas fa-calendar' => 'Calendar',
        'fas fa-calendar-check' => 'Calendar Check',
        'fas fa-calendar-times' => 'Calendar Times',
        'fas fa-hourglass-half' => 'Hourglass Half',
        'fas fa-hourglass-end' => 'Hourglass End',
        'fas fa-hourglass-start' => 'Hourglass Start'
    ],
    'Health' => [
        'fas fa-heartbeat' => 'Heartbeat',
        'fas fa-pills' => 'Pills',
        'fas fa-spa' => 'Spa',
        'fas fa-running' => 'Running',
        'fas fa-walking' => 'Walking',
        'fas fa-bed' => 'Bed',
        'fas fa-bath' => 'Bath'
    ],
    'Education' => [
        'fas fa-book' => 'Book',
        'fas fa-book-reader' => 'Book Reader',
        'fas fa-graduation-cap' => 'Graduation Cap',
        'fas fa-pencil-alt' => 'Pencil',
        'fas fa-pen' => 'Pen',
        'fas fa-clipboard-check' => 'Clipboard Check',
        'fas fa-clipboard-list' => 'Clipboard List'
    ],
    'Spiritual' => [
        'fas fa-pray' => 'Pray',
        'fas fa-church' => 'Church',
        'fas fa-mosque' => 'Mosque',
        'fas fa-synagogue' => 'Synagogue',
        'fas fa-book-bible' => 'Bible',
        'fas fa-dove' => 'Dove',
        'fas fa-praying-hands' => 'Praying Hands'
    ],
    'Social' => [
        'fas fa-users' => 'Users',
        'fas fa-user' => 'User',
        'fas fa-user-friends' => 'User Friends',
        'fas fa-user-plus' => 'User Plus',
        'fas fa-user-minus' => 'User Minus',
        'fas fa-comments' => 'Comments',
        'fas fa-comment' => 'Comment'
    ],
    'Work' => [
        'fas fa-briefcase' => 'Briefcase',
        'fas fa-tasks' => 'Tasks',
        'fas fa-clipboard' => 'Clipboard',
        'fas fa-calendar-alt' => 'Calendar Alt',
        'fas fa-chart-line' => 'Chart Line',
        'fas fa-chart-bar' => 'Chart Bar',
        'fas fa-chart-pie' => 'Chart Pie'
    ],
    'Finance' => [
        'fas fa-coins' => 'Coins',
        'fas fa-money-bill' => 'Money Bill',
        'fas fa-wallet' => 'Wallet',
        'fas fa-piggy-bank' => 'Piggy Bank',
        'fas fa-credit-card' => 'Credit Card',
        'fas fa-hand-holding-usd' => 'Hand Holding USD',
        'fas fa-chart-line' => 'Chart Line'
    ],
    'Family' => [
        'fas fa-home' => 'Home',
        'fas fa-baby' => 'Baby',
        'fas fa-child' => 'Child',
        'fas fa-user-friends' => 'User Friends',
        'fas fa-users' => 'Users',
        'fas fa-heart' => 'Heart',
        'fas fa-hand-holding-heart' => 'Hand Holding Heart'
    ],
    'Personal Growth' => [
        'fas fa-brain' => 'Brain',
        'fas fa-lightbulb' => 'Lightbulb',
        'fas fa-seedling' => 'Seedling',
        'fas fa-tree' => 'Tree',
        'fas fa-book-reader' => 'Book Reader',
        'fas fa-dumbbell' => 'Dumbbell',
        'fas fa-medal' => 'Medal'
    ]
];
?>

<!-- Icon Selector Modal -->
<div class="modal fade" id="iconSelectorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Icon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-3">
                        <!-- Icon Categories -->
                        <div class="list-group">
                            <?php foreach ($icon_categories as $category => $icons): ?>
                            <button type="button" 
                                    class="list-group-item list-group-item-action" 
                                    data-category="<?php echo $category; ?>">
                                <?php echo $category; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <!-- Icon Grid -->
                        <div class="row g-2" id="iconGrid">
                            <?php foreach ($icon_categories['Basic'] as $icon => $name): ?>
                            <div class="col-3 col-sm-2">
                                <button type="button" 
                                        class="btn btn-outline-primary w-100 icon-btn" 
                                        data-icon="<?php echo $icon; ?>"
                                        title="<?php echo $name; ?>">
                                    <i class="<?php echo $icon; ?>"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="selectIconBtn">Select Icon</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const iconCategories = <?php echo json_encode($icon_categories); ?>;
    const iconGrid = document.getElementById('iconGrid');
    const categoryButtons = document.querySelectorAll('[data-category]');
    const selectIconBtn = document.getElementById('selectIconBtn');
    let selectedIcon = '';
    let targetInput = null;

    // Function to show icons for a category
    function showCategoryIcons(category) {
        iconGrid.innerHTML = '';
        Object.entries(iconCategories[category]).forEach(([icon, name]) => {
            iconGrid.innerHTML += `
                <div class="col-3 col-sm-2">
                    <button type="button" 
                            class="btn btn-outline-primary w-100 icon-btn" 
                            data-icon="${icon}"
                            title="${name}">
                        <i class="${icon}"></i>
                    </button>
                </div>
            `;
        });
    }

    // Category button click handler
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            showCategoryIcons(this.dataset.category);
        });
    });

    // Icon button click handler
    iconGrid.addEventListener('click', function(e) {
        const iconBtn = e.target.closest('.icon-btn');
        if (iconBtn) {
            document.querySelectorAll('.icon-btn').forEach(btn => btn.classList.remove('active'));
            iconBtn.classList.add('active');
            selectedIcon = iconBtn.dataset.icon;
        }
    });

    // Select icon button click handler
    selectIconBtn.addEventListener('click', function() {
        if (selectedIcon && targetInput) {
            targetInput.value = selectedIcon;
            targetInput.nextElementSibling.innerHTML = `<i class="${selectedIcon}"></i>`;
            bootstrap.Modal.getInstance(document.getElementById('iconSelectorModal')).hide();
        }
    });

    // Function to open icon selector
    window.openIconSelector = function(inputId) {
        targetInput = document.getElementById(inputId);
        selectedIcon = targetInput.value;
        if (selectedIcon) {
            const iconBtn = document.querySelector(`[data-icon="${selectedIcon}"]`);
            if (iconBtn) iconBtn.classList.add('active');
        }
        new bootstrap.Modal(document.getElementById('iconSelectorModal')).show();
    };
});
</script>

<style>
.icon-btn {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    padding: 0.5rem;
    transition: all 0.2s;
}

.icon-btn:hover {
    transform: scale(1.1);
}

.icon-btn.active {
    background-color: var(--bs-primary);
    color: white;
}

.icon-preview {
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-right: 0.5rem;
}
</style> 