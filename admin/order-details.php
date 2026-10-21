<?php
/**
 * Administrator Order Details & Status Manager
 */

define('APP_INIT', true);
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

$pdo = Database::getConnection();
$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    redirect('admin/orders.php');
}

// Fetch Order
$stmt = $pdo->prepare("
    SELECT o.*, u.email as user_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlashMessage('danger', 'Order not found.');
    redirect('admin/orders.php');
}

// Handle Status & Payment Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'update_order_status') {
        $newStatus = trim($_POST['order_status'] ?? '');
        $currentStatus = $order['order_status'];

        $validTransitions = [
            'Pending'          => ['Confirmed', 'Cancelled'],
            'Confirmed'        => ['Preparing', 'Cancelled'],
            'Preparing'        => ['Ready for Pickup', 'Cancelled'],
            'Ready for Pickup' => ['Completed', 'Cancelled'],
            'Completed'        => [],
            'Cancelled'        => []
        ];

        if ($newStatus === $currentStatus) {
            // Nothing changed
        } elseif (in_array($newStatus, $validTransitions[$currentStatus] ?? [], true)) {
            $upd = $pdo->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$newStatus, $orderId]);
            $order['order_status'] = $newStatus;

            // Auto-complete payment if moving to Completed
            if ($newStatus === 'Completed' && $order['payment_status'] === 'pending') {
                $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?")->execute([$orderId]);
                $order['payment_status'] = 'paid';
            }

            setFlashMessage('success', "Order status updated to '{$newStatus}'.");
        } else {
            setFlashMessage('danger', "Invalid status transition from {$currentStatus} to {$newStatus}.");
        }
        redirect("admin/order-details.php?id={$orderId}");

    } elseif ($action === 'update_payment') {
        $paymentStatus = trim($_POST['payment_status'] ?? 'pending');
        if (in_array($paymentStatus, ['pending', 'paid', 'refunded'], true)) {
            $upd = $pdo->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$paymentStatus, $orderId]);
            $order['payment_status'] = $paymentStatus;
            setFlashMessage('success', 'Payment status updated.');
        }
        redirect("admin/order-details.php?id={$orderId}");
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

$pageTitle = 'Order Details #' . $order['order_number'];
require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 btn-no-print">
    <div>
        <a href="orders.php" class="btn btn-outline-secondary btn-sm rounded-pill mb-2">
            <i class="bi bi-arrow-left me-1"></i> Back to Orders
        </a>
        <h2 class="fw-bold text-dark mb-0 font-monospace">Order <?= e($order['order_number']) ?></h2>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-printer me-1"></i> Print Kitchen Slip (KOT)
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Items and Details -->
    <div class="col-lg-8">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-basket text-primary me-2"></i>Ordered Dishes</h6>
                <span class="badge bg-light text-secondary border"><?= count($orderItems) ?> Items</span>
            </div>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Dish</th>
                            <th>Unit Price</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= getFoodImageUrl($item['image']) ?>" alt="<?= e($item['item_name']) ?>" width="40" height="40" class="rounded object-fit-cover border">
                                        <span class="fw-bold text-dark"><?= e($item['item_name']) ?></span>
                                    </div>
                                </td>
                                <td class="text-muted"><?= formatPrice($item['unit_price']) ?></td>
                                <td class="text-center fw-bold fs-6"><?= (int)$item['quantity'] ?></td>
                                <td class="text-end fw-bold text-dark"><?= formatPrice($item['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold fs-5 pt-3">Total Amount:</td>
                            <td class="text-end fw-extrabold text-primary fs-5 pt-3"><?= formatPrice($order['total_amount']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php if (!empty($order['special_instructions'])): ?>
            <div class="admin-card p-4 border-warning border-start border-4">
                <h6 class="fw-bold text-warning-emphasis mb-1"><i class="bi bi-chat-square-text-fill me-2"></i>Special Kitchen Request:</h6>
                <p class="mb-0 fs-6"><?= e($order['special_instructions']) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Management Controls Sidebar -->
    <div class="col-lg-4 btn-no-print">
        <!-- Status Control -->
        <div class="admin-card p-4 mb-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-sliders text-primary me-2"></i>Kitchen Workflow State</h6>

            <div class="mb-3">
                <label class="small text-muted d-block">Current Status:</label>
                <div class="mt-1"><?= getOrderStatusBadge($order['order_status']) ?></div>
            </div>

            <?php
            $validNext = [
                'Pending'          => ['Confirmed', 'Cancelled'],
                'Confirmed'        => ['Preparing', 'Cancelled'],
                'Preparing'        => ['Ready for Pickup', 'Cancelled'],
                'Ready for Pickup' => ['Completed', 'Cancelled'],
                'Completed'        => [],
                'Cancelled'        => []
            ][$order['order_status']] ?? [];
            ?>

            <?php if (!empty($validNext)): ?>
                <form method="POST" action="order-details.php?id=<?= $order['id'] ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_order_status">

                    <label class="small text-muted mb-1">Transition Status to:</label>
                    <select name="order_status" class="form-select mb-3" required>
                        <?php foreach ($validNext as $next): ?>
                            <option value="<?= $next ?>"><?= $next ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-semibold">
                        <i class="bi bi-arrow-right-circle me-1"></i> Update Kitchen Status
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-secondary py-2 small mb-0">
                    <i class="bi bi-lock me-1"></i> This order is in terminal status (<strong><?= e($order['order_status']) ?></strong>) and cannot be transitioned further.
                </div>
            <?php endif; ?>
        </div>

        <!-- Payment Control -->
        <div class="admin-card p-4 mb-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-cash-coin text-primary me-2"></i>Payment Collection</h6>

            <div class="mb-2">
                <span class="small text-muted d-block">Method:</span>
                <span class="fw-bold text-dark text-capitalize"><?= e($order['payment_method']) ?> (Counter Cash)</span>
            </div>

            <div class="mb-3">
                <span class="small text-muted d-block">Current Payment Status:</span>
                <div class="mt-1"><?= getPaymentStatusBadge($order['payment_status']) ?></div>
            </div>

            <form method="POST" action="order-details.php?id=<?= $order['id'] ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_payment">

                <div class="input-group">
                    <select name="payment_status" class="form-select">
                        <option value="pending" <?= ($order['payment_status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= ($order['payment_status'] === 'paid') ? 'selected' : '' ?>>Paid</option>
                        <option value="refunded" <?= ($order['payment_status'] === 'refunded') ? 'selected' : '' ?>>Refunded</option>
                    </select>
                    <button type="submit" class="btn btn-outline-secondary">Save</button>
                </div>
            </form>
        </div>

        <!-- Customer Card -->
        <div class="admin-card p-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-circle text-primary me-2"></i>Customer Info</h6>
            <div class="small text-muted d-flex flex-column gap-2">
                <div><strong>Name:</strong> <?= e($order['customer_name']) ?></div>
                <div><strong>Phone:</strong> <?= e($order['customer_phone']) ?></div>
                <div><strong>Email:</strong> <?= e($order['user_email']) ?></div>
                <div><strong>Order Placed:</strong> <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
