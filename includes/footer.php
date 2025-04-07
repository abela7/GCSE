</div> <!-- End of main container -->
        </div> <!-- End of wrapper -->
        
        <!-- Footer -->
        <footer class="footer mt-5 py-3 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0 text-muted">&copy; 2025 GCSE Tracker</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="#" class="text-muted me-2">Privacy Policy</a>
                        <a href="#" class="text-muted me-2">Terms of Use</a>
                        <a href="#" class="text-muted">Contact</a>
                    </div>
                </div>
            </div>
        </footer>
        
        <!-- Bootstrap JS Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- jQuery (for additional functionality) -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        
        <!-- Custom JavaScript -->
        <script src="/assets/js/main.js"></script>
        
        <?php
        // --- Conditional JS Loading ---
        // Get the directory of the currently running script
        $current_page_directory = basename(dirname($_SERVER['PHP_SELF'])); // Gets the last folder name

        if ($current_page_directory === 'EnglishPractice') {
            // Construct the path relative to the includes folder
            echo '<script src="/pages/EnglishPractice/script.js"></script>'; // USE ABSOLUTE PATH FROM WEB ROOT
        } elseif ($current_page_directory === 'tasks') {
            // Example for task specific JS
             echo '<script src="/assets/js/tasks.js"></script>'; // Or /pages/tasks/script.js if moved
        }
        // Add more elseif conditions for other feature-specific JS files
        // --- End Conditional JS ---
        ?>
        
        <?php if (isset($page_scripts)): ?>
            <?php echo $page_scripts; ?>
        <?php endif; ?>
        
        <!-- Mobile Navigation for PWA -->
        <?php if (!isset($hide_mobile_nav)): ?>
        <div class="mobile-nav d-md-none">
            <a href="/dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="/pages/tasks/task_list.php" class="<?php echo (dirname($_SERVER['PHP_SELF']) == '/pages/tasks') ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i>
                <span>Tasks</span>
            </a>
            <a href="/Status.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'Status.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Status</span>
            </a>
            <a href="/pages/habits/index.php" class="<?php echo (dirname($_SERVER['PHP_SELF']) == '/pages/habits') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Habits</span>
            </a>
            <a href="/pages/subjects/index.php" class="<?php echo (dirname($_SERVER['PHP_SELF']) == '/pages/subjects') ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>Subjects</span>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- PWA Script -->
        <script src="/assets/js/pwa.js"></script>
        
        <!-- Any custom scripts -->
        <?php if (isset($custom_scripts)) echo $custom_scripts; ?>
    </body>
</html>