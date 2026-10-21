<?php
/**
 * Customer Facing Footer Template
 */

if (!defined('APP_INIT')) {
    require_once dirname(__DIR__) . '/config/app.php';
}
$baseUrl = getBaseUrl();
?>
    </main>
    <!-- Main Page Content Ends -->

    <!-- Footer -->
    <footer class="bg-dark text-white pt-5 pb-4 mt-5 border-top border-secondary-subtle">
        <div class="container">
            <div class="row g-4">
                <!-- Col 1: About Canteen -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= $baseUrl ?>/assets/images/logo.svg" alt="<?= APP_NAME ?>" height="36" class="bg-white p-1 rounded">
                    </div>
                    <p class="text-white-50 small mb-3">
                        CampusBite is the official campus smart canteen ordering system. Skip long food counters, order fresh meals from your phone, and pick them up hot and ready!
                    </p>
                    <div class="d-flex gap-2">
                        <span class="badge bg-secondary"><i class="bi bi-shield-check me-1"></i> Hygienic Kitchen</span>
                        <span class="badge bg-secondary"><i class="bi bi-stopwatch me-1"></i> Quick Turnaround</span>
                    </div>
                </div>

                <!-- Col 2: Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-uppercase fw-bold text-warning mb-3 small">Quick Links</h6>
                    <ul class="list-unstyled text-white-50 small mb-0 d-flex flex-column gap-2">
                        <li><a href="<?= $baseUrl ?>/index.php" class="text-white-50 text-decoration-none hover-white">Home</a></li>
                        <li><a href="<?= $baseUrl ?>/menu.php" class="text-white-50 text-decoration-none hover-white">Food Menu</a></li>
                        <li><a href="<?= $baseUrl ?>/cart.php" class="text-white-50 text-decoration-none hover-white">My Cart</a></li>
                        <li><a href="<?= $baseUrl ?>/my-orders.php" class="text-white-50 text-decoration-none hover-white">Order Tracking</a></li>
                    </ul>
                </div>

                <!-- Col 3: Popular Categories -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-uppercase fw-bold text-warning mb-3 small">Categories</h6>
                    <ul class="list-unstyled text-white-50 small mb-0 d-flex flex-column gap-2">
                        <li><a href="<?= $baseUrl ?>/menu.php?cat=1" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right me-1 text-warning"></i> Breakfast Specials</a></li>
                        <li><a href="<?= $baseUrl ?>/menu.php?cat=2" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right me-1 text-warning"></i> Thalis &amp; Meals</a></li>
                        <li><a href="<?= $baseUrl ?>/menu.php?cat=3" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right me-1 text-warning"></i> Evening Snacks</a></li>
                        <li><a href="<?= $baseUrl ?>/menu.php?cat=4" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right me-1 text-warning"></i> Chai &amp; Beverages</a></li>
                        <li><a href="<?= $baseUrl ?>/menu.php?cat=5" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right me-1 text-warning"></i> Sweets &amp; Desserts</a></li>
                    </ul>
                </div>

                <!-- Col 4: Timings & Counter -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-uppercase fw-bold text-warning mb-3 small">Counter &amp; Support</h6>
                    <p class="text-white-50 small mb-2"><i class="bi bi-clock me-2 text-warning"></i> Monday – Saturday: 8 AM – 9 PM</p>
                    <p class="text-white-50 small mb-2"><i class="bi bi-geo-alt me-2 text-warning"></i> Student Activity Center, Ground Floor</p>
                    <p class="text-white-50 small mb-3"><i class="bi bi-telephone me-2 text-warning"></i> Canteen Hotline: +91 98765 43210</p>
                    <div class="mt-2">
                        <a href="<?= $baseUrl ?>/admin/login.php" class="btn btn-outline-secondary btn-sm py-1 px-2 text-white-50" style="font-size: 0.75rem;">
                            <i class="bi bi-lock-fill me-1"></i> Admin Portal
                        </a>
                    </div>
                </div>
            </div>

            <hr class="border-secondary my-4">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-white-50 gap-2">
                <div>
                    &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong>. Built for College Canteen Management.
                </div>
                <div>
                    <span class="badge bg-dark border border-secondary text-light">PHP 8.2</span>
                    <span class="badge bg-dark border border-secondary text-light">MySQL / Amazon RDS</span>
                    <span class="badge bg-dark border border-secondary text-light">AWS Elastic Beanstalk</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Custom Application JS -->
    <script src="<?= $baseUrl ?>/assets/js/main.js"></script>
</body>
</html>
