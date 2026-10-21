<?php
/**
 * Customer Home Page
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Welcome';

try {
    $pdo = Database::getConnection();

    // Fetch popular categories with item count
    $catStmt = $pdo->query("
        SELECT c.id, c.name, c.description, COUNT(f.id) as item_count
        FROM categories c
        LEFT JOIN food_items f ON f.category_id = c.id AND f.is_available = 1
        GROUP BY c.id, c.name, c.description
        ORDER BY c.id ASC
    ");
    $categories = $catStmt->fetchAll();

    // Fetch today's specials / featured food items
    $featStmt = $pdo->query("
        SELECT f.*, c.name as category_name
        FROM food_items f
        JOIN categories c ON f.category_id = c.id
        WHERE f.is_available = 1
        ORDER BY f.id ASC
        LIMIT 6
    ");
    $featuredItems = $featStmt->fetchAll();

} catch (PDOException $e) {
    $categories = [];
    $featuredItems = [];
    $dbError = $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">

    <?php if (isset($dbError) && APP_ENV === 'development'): ?>
        <div class="alert alert-warning mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Database connection note: <?= e($dbError) ?>. If this is a fresh setup, please run <a href="database/setup.php" class="fw-bold">Database Setup</a>.
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <div class="hero-banner p-4 p-md-5 mb-5 text-white">
        <div class="row align-items-center position-relative" style="z-index: 2;">
            <div class="col-lg-7">
                <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill mb-3 fw-bold">
                    <i class="bi bi-stars me-1"></i> Quick &amp; Contactless Campus Dining
                </span>
                <h1 class="display-5 fw-extrabold mb-3 lh-sm">
                    Skip The Long Queue,<br>
                    <span class="text-warning">Pick Up Fresh Food Fast!</span>
                </h1>
                <p class="lead text-white-50 mb-4 pe-lg-4">
                    Order your favorite breakfast dosas, spicy biryanis, hot chai, and snacks directly from your phone. Collect your hot meal when it's ready.
                </p>

                <!-- Search Bar -->
                <form action="menu.php" method="GET" class="d-flex flex-column flex-sm-row gap-2 mb-4" style="max-width: 500px;">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control border-0 py-2.5 shadow-none" placeholder="Search biryani, sandwich, coffee, thali..." required>
                    </div>
                    <button type="submit" class="btn btn-warning text-dark fw-bold px-4 py-2.5">Find Food</button>
                </form>

                <div class="d-flex flex-wrap gap-3">
                    <a href="menu.php" class="btn btn-primary btn-lg rounded-pill px-4 py-2.5 fw-bold">
                        <i class="bi bi-book-half me-2"></i> Explore Full Menu
                    </a>
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-outline-light btn-lg rounded-pill px-4 py-2.5">
                            <i class="bi bi-person-plus me-1"></i> Create Student Account
                        </a>
                    <?php else: ?>
                        <a href="my-orders.php" class="btn btn-outline-light btn-lg rounded-pill px-4 py-2.5">
                            <i class="bi bi-clock-history me-1"></i> View My Orders
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hero Illustration / Highlights -->
            <div class="col-lg-5 d-none d-lg-block text-center">
                <div class="bg-white bg-opacity-10 p-4 rounded-4 backdrop-blur border border-white border-opacity-10 text-start">
                    <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom border-white border-opacity-10">
                        <div class="stat-icon bg-warning text-dark"><i class="bi bi-lightning-fill"></i></div>
                        <div>
                            <div class="fw-bold fs-5 text-white">10-15 Min Ready</div>
                            <small class="text-white-50">Instant order transmission to kitchen</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom border-white border-opacity-10">
                        <div class="stat-icon bg-success text-white"><i class="bi bi-wallet2"></i></div>
                        <div>
                            <div class="fw-bold fs-5 text-white">Cash at Counter</div>
                            <small class="text-white-50">Pay easily in Indian Rupees (₹) on pickup</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-info text-white"><i class="bi bi-bell-fill"></i></div>
                        <div>
                            <div class="fw-bold fs-5 text-white">Live Status Alerts</div>
                            <small class="text-white-50">Track Preparing &amp; Ready for Pickup stages</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Categories -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-primary fw-bold text-uppercase small tracking-wide">Browse by Taste</span>
                <h2 class="fw-bold text-dark mb-0">Popular Categories</h2>
            </div>
            <a href="menu.php" class="text-decoration-none fw-semibold text-primary">
                View All <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="row g-3">
            <?php
            $catIcons = [
                'Breakfast' => 'bi-sunrise',
                'Meals'     => 'bi-egg-fried',
                'Snacks'    => 'bi-basket2',
                'Beverages' => 'bi-cup-hot',
                'Desserts'  => 'bi-cake2'
            ];
            foreach ($categories as $cat):
                $icon = $catIcons[$cat['name']] ?? 'bi-tag';
            ?>
                <div class="col-6 col-md-4 col-lg-2-4">
                    <a href="menu.php?cat=<?= (int)$cat['id'] ?>" class="text-decoration-none">
                        <div class="card h-100 border-0 shadow-xs rounded-4 text-center p-3 hover-lift bg-white transition">
                            <div class="avatar-circle mx-auto mb-2" style="width: 52px; height: 52px; font-size: 1.5rem;">
                                <i class="bi <?= $icon ?>"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1"><?= e($cat['name']) ?></h6>
                            <span class="badge bg-light text-muted border"><?= (int)$cat['item_count'] ?> Items</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Today's Specials / Featured Items -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-primary fw-bold text-uppercase small tracking-wide">Chef's Recommendations</span>
                <h2 class="fw-bold text-dark mb-0">Today's Specials</h2>
            </div>
            <a href="menu.php" class="text-decoration-none fw-semibold text-primary">
                Full Food Menu <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php if (empty($featuredItems)): ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No food items found. Please run the database seed.</p>
                </div>
            <?php else: ?>
                <?php foreach ($featuredItems as $food): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="food-card">
                            <div class="food-card-img-wrapper">
                                <img src="<?= getFoodImageUrl($food['image']) ?>" alt="<?= e($food['name']) ?>" loading="lazy">
                                <span class="badge bg-dark bg-opacity-75 food-badge-overlay rounded-pill px-2.5 py-1">
                                    <?= e($food['category_name']) ?>
                                </span>
                                <div class="food-price-tag">
                                    <?= formatPrice($food['price']) ?>
                                </div>
                            </div>
                            <div class="food-card-body">
                                <h3 class="food-card-title"><?= e($food['name']) ?></h3>
                                <p class="food-card-desc"><?= e($food['description']) ?></p>

                                <form action="cart.php" method="POST" class="mt-auto">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="food_id" value="<?= (int)$food['id'] ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="quantity-control">
                                            <button type="button" class="quantity-btn-minus">-</button>
                                            <input type="number" name="quantity" value="1" min="1" max="10" readonly>
                                            <button type="button" class="quantity-btn-plus">+</button>
                                        </div>
                                        <button type="submit" class="btn btn-primary flex-grow-1 rounded-pill fw-semibold py-2">
                                            <i class="bi bi-cart-plus me-1"></i> Add
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Why Choose CampusBite Banner -->
    <section class="bg-white rounded-4 p-4 p-md-5 border mb-4">
        <div class="text-center mb-4">
            <span class="text-primary fw-bold text-uppercase small">College Smart Dining</span>
            <h2 class="fw-bold text-dark">Why Students &amp; Staff Love CampusBite</h2>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="p-3">
                    <div class="avatar-circle mx-auto mb-3" style="width: 58px; height: 58px; font-size: 1.6rem; background: #e3f2fd; color: #1976d2;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Zero Queue Waiting</h5>
                    <p class="text-muted small">Place your meal order between lectures and pick it up during lunch break without standing in endless counter queues.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3">
                    <div class="avatar-circle mx-auto mb-3" style="width: 58px; height: 58px; font-size: 1.6rem; background: #e8f5e9; color: #2e7d32;">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <h5 class="fw-bold text-dark">100% Hygienic &amp; Fresh</h5>
                    <p class="text-muted small">Our kitchen adheres to the highest food safety and hygiene protocols with freshly ground spices and filtered water.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3">
                    <div class="avatar-circle mx-auto mb-3" style="width: 58px; height: 58px; font-size: 1.6rem; background: #fff3e0; color: #e65100;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Transparent Indian Rupee (₹) Pricing</h5>
                    <p class="text-muted small">Student-friendly subsidized prices with no hidden charges. Pay safely at the counter upon order verification.</p>
                </div>
            </div>
        </div>
    </section>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
