<?php
/**
 * Administrator Customers Management
 */

define('APP_INIT', true);
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pageTitle = 'Customer Management';
$pdo = Database::getConnection();

$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT 
        u.id, u.name, u.email, u.phone, u.role, u.created_at,
        COUNT(o.id) as total_orders,
        COALESCE(SUM(CASE WHEN o.order_status = 'Completed' THEN o.total_amount ELSE 0 END), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    WHERE u.role = 'customer'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Customer Accounts</h2>
        <p class="text-muted mb-0">View registered students, staff, and their canteen ordering statistics.</p>
    </div>
</div>

<!-- Search -->
<div class="admin-card p-3 mb-4">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="Search by name, email, or phone..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary px-4">Search</button>
            <?php if (!empty($search)): ?>
                <a href="customers.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-x-circle"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Customers Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Orders Placed</th>
                    <th>Total Spent</th>
                    <th>Member Since</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No registered customers found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                        <?= strtoupper(substr($c['name'], 0, 1)) ?>
                                    </div>
                                    <span class="fw-bold text-dark"><?= e($c['name']) ?></span>
                                </div>
                            </td>
                            <td class="text-muted"><?= e($c['email']) ?></td>
                            <td class="text-dark font-monospace small"><?= e($c['phone']) ?></td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= (int)$c['total_orders'] ?> orders</span>
                            </td>
                            <td class="fw-bold text-success"><?= formatPrice($c['total_spent']) ?></td>
                            <td class="text-muted small"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="orders.php?q=<?= urlencode($c['email']) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1" title="View customer orders">
                                    <i class="bi bi-receipt me-1"></i> Orders
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
