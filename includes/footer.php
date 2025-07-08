    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo APP_NAME; ?></h5>
                    <p class="mb-0">Empowering residents, improving services.</p>
                    <small class="text-muted">Version <?php echo APP_VERSION; ?></small>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">© <?php echo date('Y'); ?> Redcliff Municipality</p>
                    <p class="mb-0">All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/resident/') !== false ? '../assets/js/main.js' : 'assets/js/main.js'; ?>"></script>
</body>
</html>
