<?php
// includes/footer.php
?>
            </div> <!-- End Main Content Area -->
            
            <footer class="bg-white border-top mt-auto py-3">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; <?php echo APP_NAME . ' ' . date('Y'); ?></div>
                        <div>
                            <a href="#" class="text-decoration-none text-muted">Privacy Policy</a>
                            &middot;
                            <a href="#" class="text-decoration-none text-muted">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
            
        </div> <!-- End Page Content -->
    </div> <!-- End Wrapper -->

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
    <!-- Real-time WebSocket Client -->
    <script src="<?php echo BASE_URL; ?>assets/js/notifications.js"></script>

    <!-- Real-time Floating Toast Container -->
    <div aria-live="polite" aria-atomic="true" class="position-relative">
        <div id="vms-toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>
    </div>
</body>
</html>
