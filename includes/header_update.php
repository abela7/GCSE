<?php
// Update header.php to include link to mood tracker in navigation

// Find the navigation section and add mood tracker link before the closing </ul> tag
?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page_title === 'Mood Tracker') ? 'active' : ''; ?>" href="/pages/mood_tracking/index.php">
                            <i class="fas fa-smile me-2"></i>Mood Tracker
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <div class="container-fluid py-4">
        <?php if (isset($page_title) && !empty($page_title)): ?>
            <h1 class="h3 mb-4"><?php echo $page_title; ?></h1>
        <?php endif; ?>
