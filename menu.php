<?php
/**
 * Food Menu Page - Browsing, Filtering & Searching
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Food Menu';

$pdo = Database::getConnection();

// Fetch categories for filter chips
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY id ASC")->fetchAll();

// Handle search and category filters
$selectedCat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$searchQuery = trim($_GET['q'] ?? '');

$sql = "
    SELECT f.*, c.name as category_name 
    FROM food_items f
    JOIN categories c ON f.category_id = c.id
    WHERE 1=1
";
$params = [];

if ($selectedCat > 0) {
    $sql .= " AND f.category_id = ?";
    $params[] = $selectedCat;
}

if (!empty($searchQuery)) {
    $sql .= " AND (f.name LIKE ? OR f.description LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

$sql .= " ORDER BY f.is_available DESC, f.category_id ASC, f.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$foodItems = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">

    <!-- Header & Search Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <h1 class="fw-bold text-dark mb-1">Canteen Food Menu</h1>
            <p class="text-muted mb-0">Browse fresh snacks, meals, breakfast, and refreshments.</p>
        </div>
        <div class="col-md-6">
            <form method="GET" action="menu.php" class="d-flex gap-2 justify-content-md-end">
                <?php if ($selectedCat > 0): ?>
                    <input type="hidden" name="cat" value="<?= $selectedCat ?>">
                <?php endif; ?>
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Search menu..." value="<?= e($searchQuery) ?>">
                </div>
                <button type="submit" class="btn btn-primary px-3"><i class="bi bi-search"></i></button>
                <?php if (!empty($searchQuery) || $selectedCat > 0): ?>
                    <a href="menu.php" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Category Filter Chips -->
    <div class="d-flex gap-2 overflow-auto pb-3 mb-4 scrollbar-none">
        <a href="menu.php<?= !empty($searchQuery) ? '?q=' . urlencode($searchQuery) : '' ?>" 
           class="category-pill <?= ($selectedCat === 0) ? 'active' : '' ?>">
            <i class="bi bi-grid-fill me-1.5"></i> All Items
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="menu.php?cat=<?= (int)$cat['id'] ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" 
               class="category-pill <?= ($selectedCat === (int)$cat['id']) ? 'active' : '' ?>">
                <?= e($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Active Search/Filter Alert if filtered -->
    <?php if (!empty($searchQuery) || $selectedCat > 0): ?>
        <div class="alert alert-light border d-flex justify-content-between align-items-center py-2 px-3 mb-4 rounded-3">
            <div>
                <span class="text-muted small">Showing results for:</span>
                <?php if ($selectedCat > 0): 
                    $catName = '';
                    foreach ($categories as $c) { if ($c['id'] == $selectedCat) $catName = $c['name']; }
                ?>
                    <span class="badge bg-primary ms-1"><?= e($catName) ?></span>
                <?php endif; ?>
                <?php if (!empty($searchQuery)): ?>
                    <span class="badge bg-secondary ms-1">"<?= e($searchQuery) ?>"</span>
                <?php endif; ?>
                <span class="text-muted small ms-2">(<?= count($foodItems) ?> items found)</span>
            </div>
            <a href="menu.php" class="text-decoration-none small text-danger fw-semibold"><i class="bi bi-x me-1"></i>Clear Filters</a>
        </div>
    <?php endif; ?>

    <!-- Food Cards Grid -->
    <?php if (empty($foodItems)): ?>
        <div class="card border-0 bg-white shadow-xs rounded-4 text-center py-5 my-4">
            <div class="avatar-circle mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem; background: #fff3e0; color: #f57c00;">
                <i class="bi bi-search"></i>
            </div>
            <h4 class="fw-bold text-dark">No Food Items Found</h4>
            <p class="text-muted mb-4">We couldn't find any dishes matching your current filter or search criteria.</p>
            <div>
                <a href="menu.php" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> View All Menu Items
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($foodItems as $food): 
                $isAvailable = (bool)$food['is_available'];
            ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="food-card <?= !$isAvailable ? 'opacity-75' : '' ?>">
                        <div class="food-card-img-wrapper">
                            <img src="<?= getFoodImageUrl($food['image']) ?>" alt="<?= e($food['name']) ?>" loading="lazy">
                            <span class="badge bg-dark bg-opacity-75 food-badge-overlay rounded-pill px-2.5 py-1">
                                <?= e($food['category_name']) ?>
                            </span>

                            <?php if ($isAvailable): ?>
                                <div class="food-price-tag">
                                    <?= formatPrice($food['price']) ?>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-danger position-absolute top-0 end-0 m-2 px-2.5 py-1.5 rounded-pill shadow-xs">
                                    <i class="bi bi-x-circle-fill me-1"></i> Sold Out
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="food-card-body">
                            <h3 class="food-card-title"><?= e($food['name']) ?></h3>
                            <p class="food-card-desc"><?= e($food['description']) ?></p>

                            <?php if ($isAvailable): ?>
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
                            <?php else: ?>
                                <div class="mt-auto">
                                    <button class="btn btn-secondary w-100 rounded-pill py-2 disabled" disabled>
                                        <i class="bi bi-slash-circle me-1"></i> Currently Unavailable
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
