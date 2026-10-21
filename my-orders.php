<?php
/**
 * Customer Orders List / Dashboard
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin('my-orders.php');

$pageTitle = 'My Orders';
$pdo = Database::getConnection();
$userId = (int)$_SESSION['user_id'];

// Filter by status tab if requested
$statusFilter = trim($_GET['status'] ?? '');
$sql = "
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.user_id = ?
";
$params = [$userId];

if (!empty($statusFilter)) {
    if ($statusFilter === 'active') {
        $sql .= " AND o.order_status IN ('Pending', 'Confirmed', 'Preparing', 'Ready for Pickup')";
    } elseif ($statusFilter === 'completed') {
        $sql .= " AND o.order_status = 'Completed'";
    } elseif ($statusFilter === 'cancelled') {
        $sql .= " AND o.order_status = 'Cancelled'";
    }
}

$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="fw-bold text-dark mb-1">My Orders &amp; Receipts</h1>
            <p class="text-muted mb-0">Track live orders and view order history.</p>
        </div>
        <a href="menu.php" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-plus-lg me-1"></i> Place New Order
        </a>
    </div>

    <!-- Filter Tabs -->
    <div class="d-flex gap-2 mb-4 overflow-auto pb-2 scrollbar-none">
        <a href="my-orders.php" class="category-pill <?= empty($statusFilter) ? 'active' : '' ?>">
            All Orders
        </a>
        <a href="my-orders.php?status=active" class="category-pill <?= ($statusFilter === 'active') ? 'active' : '' ?>">
            <i class="bi bi-clock-history me-1"></i> Active / In Progress
        </a>
        <a href="my-orders.php?status=completed" class="category-pill <?= ($statusFilter === 'completed') ? 'active' : '' ?>">
            <i class="bi bi-check-circle me-1"></i> Completed
        </a>
        <a href="my-orders.php?status=cancelled" class="category-pill <?= ($statusFilter === 'cancelled') ? 'active' : '' ?>">
            <i class="bi bi-x-circle me-1"></i> Cancelled
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="card border-0 bg-white shadow-xs rounded-4 text-center py-5 my-3">
            <div class="avatar-circle mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem; background: #fff3e0; color: #f57c00;">
                <i class="bi bi-receipt"></i>
            </div>
            <h4 class="fw-bold text-dark">No Orders Found</h4>
            <p class="text-muted mb-4">You have not placed any canteen orders matching this filter yet.</p>
            <div>
                <a href="menu.php" class="btn btn-primary rounded-pill px-4 py-2.5">
                    <i class="bi bi-grid me-1"></i> Explore Canteen Menu
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($orders as $order): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white transition hover-lift">
                        <div class="row align-items-center g-3">
                            <!-- Col 1: Order # & Date -->
                            <div class="col-md-3">
                                <span class="badge bg-light text-secondary border font-monospace mb-1">
                                    <?= e($order['order_number']) ?>
                                </span>
                                <div class="small text-muted">
                                    <i class="bi bi-calendar3 me-1"></i> <?= date('M d, Y · h:i A', strtotime($order['created_at'])) ?>
                                </div>
                            </div>

                            <!-- Col 2: Status -->
                            <div class="col-md-3">
                                <div class="small text-muted mb-1">Status</div>
                                <div><?= getOrderStatusBadge($order['order_status']) ?></div>
                            </div>

                            <!-- Col 3: Items & Total -->
                            <div class="col-md-3">
                                <div class="small text-muted"><?= (int)$order['item_count'] ?> Item(s)</div>
                                <div class="fs-5 fw-extrabold text-primary"><?= formatPrice($order['total_amount']) ?></div>
                            </div>

                            <!-- Col 4: Action Button -->
                            <div class="col-md-3 text-md-end">
                                <a href="order-details.php?id=<?= $order['id'] ?>" class="btn btn-outline-primary rounded-pill px-3 py-1.5 fw-semibold">
                                    <i class="bi bi-radar me-1"></i> View &amp; Track
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
