<?php
/**
 * Administrator Layout Footer
 */

if (!defined('APP_INIT')) {
    require_once dirname(__DIR__) . '/config/app.php';
}
?>
        </div>
        <!-- End admin-content -->

        <footer class="bg-white border-top py-3 px-4 text-muted small d-flex justify-content-between align-items-center">
            <div>
                &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong> Admin Console
            </div>
            <div>
                <span class="badge bg-light text-secondary border">PHP <?= PHP_VERSION ?></span>
                <span class="badge bg-light text-secondary border">AWS Ready</span>
            </div>
        </footer>
    </div>
    <!-- End admin-main -->

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js for Admin Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</body>
</html>
