<?php
/**
 * Customer Order Details & Live Tracking
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

requireLogin('my-orders.php');

$pdo = Database::getConnection();
$userId = (int)$_SESSION['user_id'];
$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    redirect('my-orders.php');
}

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND (user_id = ? OR ? = 'admin')");
$stmt->execute([$orderId, $userId, $_SESSION['user_role'] ?? 'customer']);
$order = $stmt->fetch();

if (!$order) {
    setFlashMessage('danger', 'Order not found.');
    redirect('my-orders.php');
}

// AJAX Status Polling Endpoint
if (isset($_GET['ajax']) && $_GET['ajax'] === 'status') {
    header('Content-Type: application/json');
    echo json_encode([
        'order_id'     => $order['id'],
        'order_number' => $order['order_number'],
        'order_status' => $order['order_status'],
        'updated_at'   => $order['updated_at']
    ]);
    exit;
}

// Handle Order Cancellation by Customer (Only if Pending)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    requireValidCsrf();

    if ($order['order_status'] === 'Pending') {
        $cancelStmt = $pdo->prepare("UPDATE orders SET order_status = 'Cancelled', updated_at = NOW() WHERE id = ?");
        $cancelStmt->execute([$orderId]);

        setFlashMessage('info', 'Your order has been cancelled.');
        redirect("order-details.php?id={$orderId}");
    } else {
        setFlashMessage('danger', 'Order cannot be cancelled because it is already ' . strtolower($order['order_status']) . '.');
        redirect("order-details.php?id={$orderId}");
    }
}

// Fetch Order Items
$itemStmt = $pdo->prepare("
    SELECT oi.*, f.image 
    FROM order_items oi
    LEFT JOIN food_items f ON oi.food_item_id = f.id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll();

$pageTitle = 'Order #' . $order['order_number'];

// Stepper states mapping
$statusSteps = ['Pending', 'Confirmed', 'Preparing', 'Ready for Pickup', 'Completed'];
$currentStatusIndex = array_search($order['order_status'], $statusSteps);
$isCancelled = ($order['order_status'] === 'Cancelled');

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">

    <!-- Back and Print Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="my-orders.php" class="btn btn-outline-secondary rounded-pill btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to My Orders
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill btn-sm">
            <i class="bi bi-printer me-1"></i> Print Invoice
        </button>
    </div>

    <!-- Live Order Tracking Card -->
    <div id="orderTrackingContainer" 
         class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4" 
         data-order-id="<?= $order['id'] ?>" 
         data-current-status="<?= e($order['order_status']) ?>">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <span class="text-muted small">ORDER NUMBER</span>
                <h3 class="fw-bold font-monospace text-primary mb-0"><?= e($order['order_number']) ?></h3>
            </div>
            <div class="text-md-end">
                <span class="text-muted small d-block mb-1">CURRENT STATUS</span>
                <?= getOrderStatusBadge($order['order_status']) ?>
            </div>
        </div>

        <?php if ($isCancelled): ?>
            <div class="alert alert-danger d-flex align-items-center rounded-3 mb-0" role="alert">
                <i class="bi bi-x-circle-fill fs-3 me-3"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">Order Cancelled</h5>
                    <p class="mb-0 small">This order was cancelled. Please place a new order if you would like canteen meals.</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Visual Stepper -->
            <div class="order-stepper position-relative my-4">
                <?php
                $progressPercent = ($currentStatusIndex !== false) ? ($currentStatusIndex / (count($statusSteps) - 1)) * 90 : 0;
                ?>
                <div class="stepper-progress" style="width: <?= $progressPercent ?>%;"></div>

                <?php 
                $icons = [
                    'Pending'          => 'bi-hourglass-split',
                    'Confirmed'        => 'bi-clipboard-check',
                    'Preparing'        => 'bi-fire',
                    'Ready for Pickup' => 'bi-bag-check',
                    'Completed'        => 'bi-check2-all'
                ];
                foreach ($statusSteps as $idx => $stepName): 
                    $isCompleted = ($currentStatusIndex !== false && $idx < $currentStatusIndex);
                    $isActive = ($currentStatusIndex !== false && $idx === $currentStatusIndex);
                    $stepClass = $isCompleted ? 'completed' : ($isActive ? 'active' : '');
                ?>
                    <div class="step-item <?= $stepClass ?>">
                        <div class="step-icon">
                            <i class="bi <?= $icons[$stepName] ?>"></i>
                        </div>
                        <div class="step-label d-none d-sm-block"><?= $stepName ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center text-muted small mt-2">
                <i class="bi bi-arrow-repeat me-1 text-primary"></i> Live auto-refreshing tracker. Status will update as the kitchen prepares your food.
            </div>
        <?php endif; ?>
    </div>

    <!-- Details Grid -->
    <div class="row g-4">
        <!-- Order Items -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
                <div class="card-header bg-white p-3 border-bottom">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-basket2 me-2 text-primary"></i>Items in this Order</h5>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Item</th>
                                <th>Unit Price</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end pe-4">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= getFoodImageUrl($item['image']) ?>" alt="<?= e($item['item_name']) ?>" width="44" height="44" class="rounded-3 object-fit-cover border">
                                            <span class="fw-semibold text-dark"><?= e($item['item_name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-muted"><?= formatPrice($item['unit_price']) ?></td>
                                    <td class="text-center fw-bold"><?= (int)$item['quantity'] ?></td>
                                    <td class="text-end pe-4 fw-bold text-dark"><?= formatPrice($item['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="border-top">
                            <tr>
                                <td colspan="3" class="text-end fw-bold pt-3 ps-4">Grand Total:</td>
                                <td class="text-end pe-4 fw-extrabold text-primary pt-3 fs-5"><?= formatPrice($order['total_amount']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Cancel Order Card (If Pending) -->
            <?php if ($order['order_status'] === 'Pending'): ?>
                <div class="card border-0 bg-white shadow-sm rounded-4 p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold text-danger mb-1">Need to Cancel?</h6>
                            <p class="text-muted small mb-0">You can cancel this order while it is still waiting for kitchen confirmation.</p>
                        </div>
                        <form method="POST" action="order-details.php?id=<?= $order['id'] ?>" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="cancel_order">
                            <button type="submit" class="btn btn-outline-danger rounded-pill px-4">
                                <i class="bi bi-x-circle me-1"></i> Cancel Order
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Meta Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Order Info</h5>

                <ul class="list-unstyled mb-0 small text-muted d-flex flex-column gap-3">
                    <li class="d-flex justify-content-between pb-2 border-bottom">
                        <span>Customer Name</span>
                        <span class="fw-bold text-dark"><?= e($order['customer_name']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between pb-2 border-bottom">
                        <span>Phone</span>
                        <span class="fw-bold text-dark"><?= e($order['customer_phone']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between pb-2 border-bottom">
                        <span>Ordered At</span>
                        <span class="fw-bold text-dark"><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></span>
                    </li>
                    <li class="d-flex justify-content-between pb-2 border-bottom">
                        <span>Payment Method</span>
                        <span class="badge bg-light text-dark border text-capitalize"><?= e($order['payment_method']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between pb-2 border-bottom">
                        <span>Payment Status</span>
                        <?= getPaymentStatusBadge($order['payment_status']) ?>
                    </li>
                    <li>
                        <span class="d-block mb-1">Special Instructions</span>
                        <div class="p-2 bg-light rounded text-dark fst-italic">
                            <?= !empty($order['special_instructions']) ? e($order['special_instructions']) : 'None' ?>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Pickup Desk Instructions -->
            <div class="card border-0 bg-primary-subtle text-primary-emphasis rounded-4 p-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-geo-alt-fill me-1"></i> Pickup Location</h6>
                <p class="small mb-0">Student Activity Center, Counter 2. Show your Order ID <strong><?= e($order['order_number']) ?></strong> to collect your food.</p>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
