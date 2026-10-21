<?php
/**
 * Administrator Order Management
 */

define('APP_INIT', true);
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

$pageTitle = 'Order Management';
$pdo = Database::getConnection();

// Handle Quick Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = trim($_POST['order_status'] ?? '');

        // Fetch current status
        $curStmt = $pdo->prepare("SELECT order_status FROM orders WHERE id = ?");
        $curStmt->execute([$orderId]);
        $currentStatus = $curStmt->fetchColumn();

        if ($currentStatus) {
            // Valid transitions
            $validTransitions = [
                'Pending'          => ['Confirmed', 'Cancelled'],
                'Confirmed'        => ['Preparing', 'Cancelled'],
                'Preparing'        => ['Ready for Pickup', 'Cancelled'],
                'Ready for Pickup' => ['Completed', 'Cancelled'],
                'Completed'        => [], // Terminal
                'Cancelled'        => []  // Terminal
            ];

            if ($currentStatus === $newStatus) {
                // No change
            } elseif (in_array($newStatus, $validTransitions[$currentStatus] ?? [], true)) {
                $update = $pdo->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
                $update->execute([$newStatus, $orderId]);
                
                // If moving to Completed, auto mark payment as paid if cash
                if ($newStatus === 'Completed') {
                    $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ? AND payment_status = 'pending'")->execute([$orderId]);
                }
                
                setFlashMessage('success', "Order status successfully changed to '{$newStatus}'.");
            } else {
                setFlashMessage('danger', "Invalid status transition from '{$currentStatus}' to '{$newStatus}'.");
            }
        }
        redirect('admin/orders.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    }
}

// Filters & Search
$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$dateFilter = trim($_GET['date'] ?? '');

$sql = "
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($statusFilter)) {
    $sql .= " AND o.order_status = ?";
    $params[] = $statusFilter;
}

if (!empty($dateFilter)) {
    if ($dateFilter === 'today') {
        $sql .= " AND DATE(o.created_at) = CURDATE()";
    } elseif ($dateFilter === 'yesterday') {
        $sql .= " AND DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($dateFilter === '7days') {
        $sql .= " AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } else {
        // Specific YYYY-MM-DD
        $sql .= " AND DATE(o.created_at) = ?";
        $params[] = $dateFilter;
    }
}

$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Orders Management</h2>
        <p class="text-muted mb-0">Track kitchen preparation, counter pickup, and cash collections.</p>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="admin-card p-3 mb-4">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="Search order#, name, phone..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <?php foreach (['Pending', 'Confirmed', 'Preparing', 'Ready for Pickup', 'Completed', 'Cancelled'] as $st): ?>
                    <option value="<?= $st ?>" <?= ($statusFilter === $st) ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="date" class="form-select">
                <option value="">All Dates</option>
                <option value="today" <?= ($dateFilter === 'today') ? 'selected' : '' ?>>Today</option>
                <option value="yesterday" <?= ($dateFilter === 'yesterday') ? 'selected' : '' ?>>Yesterday</option>
                <option value="7days" <?= ($dateFilter === '7days') ? 'selected' : '' ?>>Past 7 Days</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
            <?php if (!empty($search) || !empty($statusFilter) || !empty($dateFilter)): ?>
                <a href="orders.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-x-circle"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Placed</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No orders matching your criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <a href="order-details.php?id=<?= $o['id'] ?>" class="fw-bold font-monospace text-decoration-none">
                                    <?= e($o['order_number']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($o['customer_name']) ?></div>
                                <small class="text-muted"><?= e($o['customer_phone']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= (int)$o['item_count'] ?> items</span>
                            </td>
                            <td class="fw-bold text-dark"><?= formatPrice($o['total_amount']) ?></td>
                            <td><?= getPaymentStatusBadge($o['payment_status']) ?></td>
                            <td><?= getOrderStatusBadge($o['order_status']) ?></td>
                            <td class="small text-muted"><?= date('M d, h:i A', strtotime($o['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="order-details.php?id=<?= $o['id'] ?>" class="btn btn-primary btn-sm rounded-pill px-3 py-1">
                                    <i class="bi bi-pencil-square me-1"></i> Manage
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
