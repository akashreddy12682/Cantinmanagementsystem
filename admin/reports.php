<?php
/**
 * Administrator Sales & Operational Reports
 */

define('APP_INIT', true);
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pageTitle = 'Sales Reports';
$pdo = Database::getConnection();

// Date range calculation
$preset = trim($_GET['preset'] ?? '30days');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

if (empty($startDate) || empty($endDate)) {
    if ($preset === 'today') {
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
    } elseif ($preset === 'this_month') {
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
    } elseif ($preset === 'last_month') {
        $startDate = date('Y-m-01', strtotime('first day of last month'));
        $endDate = date('Y-m-t', strtotime('last day of last month'));
    } else { // 30 days default
        $startDate = date('Y-m-d', strtotime('-29 days'));
        $endDate = date('Y-m-d');
    }
}

// Summary Metrics for the selected date range
$summaryStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN order_status = 'Completed' THEN total_amount ELSE 0 END), 0) as total_revenue,
        COUNT(CASE WHEN order_status = 'Completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN order_status = 'Cancelled' THEN 1 END) as cancelled_orders,
        COALESCE(AVG(CASE WHEN order_status = 'Completed' THEN total_amount ELSE NULL END), 0) as avg_order_value
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$summaryStmt->execute([$startDate, $endDate]);
$summary = $summaryStmt->fetch();

// Daily Sales Breakdown
$dailyStmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as sale_date,
        COUNT(*) as total_orders,
        COUNT(CASE WHEN order_status = 'Completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN order_status = 'Cancelled' THEN 1 END) as cancelled_orders,
        COALESCE(SUM(CASE WHEN order_status = 'Completed' THEN total_amount ELSE 0 END), 0) as revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY sale_date DESC
");
$dailyStmt->execute([$startDate, $endDate]);
$dailyReports = $dailyStmt->fetchAll();

// Top Selling Dishes in this Period
$topItemsStmt = $pdo->prepare("
    SELECT 
        oi.item_name,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.subtotal) as total_sales
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.order_status = 'Completed'
    GROUP BY oi.item_name
    ORDER BY total_quantity DESC
    LIMIT 6
");
$topItemsStmt->execute([$startDate, $endDate]);
$topItems = $topItemsStmt->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 btn-no-print">
    <div>
        <h2 class="fw-bold text-dark mb-1">Sales &amp; Analytics Reports</h2>
        <p class="text-muted mb-0">Financial metrics, completed collections, and kitchen performance.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-printer me-1"></i> Print / Export Report
        </button>
    </div>
</div>

<!-- Date Filter Form -->
<div class="admin-card p-3 mb-4 btn-no-print">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small text-secondary fw-semibold mb-1">Preset Timeframe</label>
            <select name="preset" class="form-select" onchange="this.form.submit()">
                <option value="today" <?= ($preset === 'today') ? 'selected' : '' ?>>Today</option>
                <option value="30days" <?= ($preset === '30days') ? 'selected' : '' ?>>Last 30 Days</option>
                <option value="this_month" <?= ($preset === 'this_month') ? 'selected' : '' ?>>This Month</option>
                <option value="last_month" <?= ($preset === 'last_month') ? 'selected' : '' ?>>Last Month</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-secondary fw-semibold mb-1">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-secondary fw-semibold mb-1">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-filter me-1"></i> Apply Filter
            </button>
        </div>
    </form>
</div>

<!-- Print Report Header -->
<div class="d-none d-print-block text-center mb-4">
    <h3><?= APP_NAME ?> - Sales Report</h3>
    <p class="text-muted">Period: <?= date('d M Y', strtotime($startDate)) ?> to <?= date('d M Y', strtotime($endDate)) ?></p>
</div>

<!-- Summary Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-label">Total Realized Revenue</span>
            <div class="stat-value text-success"><?= formatPrice($summary['total_revenue']) ?></div>
            <small class="text-muted"><?= (int)$summary['completed_orders'] ?> completed orders</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-label">Total Orders Placed</span>
            <div class="stat-value text-dark"><?= (int)$summary['total_orders'] ?></div>
            <small class="text-muted">During selected window</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-label">Average Order Value</span>
            <div class="stat-value text-primary"><?= formatPrice($summary['avg_order_value']) ?></div>
            <small class="text-muted">Per completed order</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-label">Cancelled Orders</span>
            <div class="stat-value text-danger"><?= (int)$summary['cancelled_orders'] ?></div>
            <small class="text-muted">Orders revoked / unserved</small>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Top Selling Items -->
    <div class="col-lg-5">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-trophy text-warning me-2"></i>Top Dishes by Volume</h6>
            </div>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Dish Name</th>
                            <th class="text-center">Qty Sold</th>
                            <th class="text-end">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topItems)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">No completed orders in this date range.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topItems as $item): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= e($item['item_name']) ?></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$item['total_quantity'] ?></span></td>
                                    <td class="text-end fw-bold text-success"><?= formatPrice($item['total_sales']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Daily Breakdown Table -->
    <div class="col-lg-7">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-calendar3 text-primary me-2"></i>Day-by-Day Breakdown</h6>
            </div>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-center">Orders</th>
                            <th class="text-center">Completed</th>
                            <th class="text-center">Cancelled</th>
                            <th class="text-end">Sales (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dailyReports)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No orders found for the chosen date range.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dailyReports as $dr): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?= date('D, M d, Y', strtotime($dr['sale_date'])) ?></td>
                                    <td class="text-center"><?= (int)$dr['total_orders'] ?></td>
                                    <td class="text-center text-success fw-bold"><?= (int)$dr['completed_orders'] ?></td>
                                    <td class="text-center text-danger"><?= (int)$dr['cancelled_orders'] ?></td>
                                    <td class="text-end fw-extrabold text-success"><?= formatPrice($dr['revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
