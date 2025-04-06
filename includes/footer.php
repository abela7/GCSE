</div> <!-- End of main container -->
        </div> <!-- End of wrapper -->
        
        <!-- Footer -->
        <footer class="footer mt-5 py-3 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> GCSE Tracker</p>
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
        <script src="/GCSE/assets/js/main.js"></script>
        
        <?php if (isset($page_scripts)): ?>
            <?php echo $page_scripts; ?>
        <?php endif; ?>
    </body>
</html>