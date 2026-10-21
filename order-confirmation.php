<?php
/**
 * Order Confirmation & Receipt Page
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$orderNumber = trim($_GET['order'] ?? '');
if (empty($orderNumber)) {
    redirect('my-orders.php');
}

$pdo = Database::getConnection();
$userId = (int)$_SESSION['user_id'];

// Fetch order ensuring it belongs to this user (or user is admin)
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$orderNumber, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlashMessage('danger', 'Order not found or unauthorized.');
    redirect('my-orders.php');
}

// Fetch order items
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$order['id']]);
$orderItems = $itemStmt->fetchAll();

$pageTitle = 'Order Confirmed - ' . $order['order_number'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Banner -->
            <div class="text-center mb-4">
                <div class="avatar-circle mx-auto mb-3" style="width: 76px; height: 76px; font-size: 2.2rem; background: #e8f5e9; color: #2e7d32;">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h2 class="fw-bold text-dark">Order Confirmed!</h2>
                <p class="text-muted">Thank you, <strong><?= e($order['customer_name']) ?></strong>! Your order has been transmitted directly to our canteen kitchen.</p>
                <div class="d-inline-flex align-items-center gap-2 bg-warning-subtle text-warning-emphasis px-3 py-1.5 rounded-pill small fw-semibold">
                    <i class="bi bi-hourglass-split"></i> Estimated Preparation Time: 12 – 18 Minutes
                </div>
            </div>

            <!-- Receipt Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
                <!-- Header -->
                <div class="p-4 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <span class="text-muted small">ORDER NUMBER</span>
                        <div class="fs-4 fw-bold font-monospace text-primary"><?= e($order['order_number']) ?></div>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small">STATUS</span>
                        <div><?= getOrderStatusBadge($order['order_status']) ?></div>
                    </div>
                </div>

                <!-- Body / Meta details -->
                <div class="p-4">
                    <div class="row g-3 mb-4 small text-muted">
                        <div class="col-sm-6 col-md-3">
                            <span class="d-block text-uppercase text-secondary fw-semibold">Placed On</span>
                            <span class="text-dark fw-bold"><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <span class="d-block text-uppercase text-secondary fw-semibold">Pickup Contact</span>
                            <span class="text-dark fw-bold"><?= e($order['customer_phone']) ?></span>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <span class="d-block text-uppercase text-secondary fw-semibold">Payment</span>
                            <span class="text-dark fw-bold text-capitalize"><?= e($order['payment_method']) ?> (<?= e($order['payment_status']) ?>)</span>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <span class="d-block text-uppercase text-secondary fw-semibold">Pickup Counter</span>
                            <span class="text-dark fw-bold">Hall A - Counter 2</span>
                        </div>
                    </div>

                    <?php if (!empty($order['special_instructions'])): ?>
                        <div class="alert alert-light border small mb-4 py-2">
                            <i class="bi bi-chat-left-dots text-primary me-1"></i>
                            <strong>Special Instructions:</strong> <?= e($order['special_instructions']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Items Table -->
                    <h6 class="fw-bold text-dark mb-3">Order Items</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Rate</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark"><?= e($item['item_name']) ?></td>
                                        <td class="text-center"><?= (int)$item['quantity'] ?></td>
                                        <td class="text-end text-muted"><?= formatPrice($item['unit_price']) ?></td>
                                        <td class="text-end fw-bold text-dark"><?= formatPrice($item['subtotal']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="border-top">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold pt-3 fs-5">Grand Total:</td>
                                    <td class="text-end fw-extrabold text-primary pt-3 fs-5"><?= formatPrice($order['total_amount']) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Action buttons -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between pt-2">
                        <div class="d-flex gap-2">
                            <a href="order-details.php?id=<?= $order['id'] ?>" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-radar me-1"></i> Live Order Tracker
                            </a>
                            <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
                                <i class="bi bi-printer me-1"></i> Print Receipt
                            </button>
                        </div>
                        <a href="menu.php" class="btn btn-link text-decoration-none">
                            Order More Food <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
