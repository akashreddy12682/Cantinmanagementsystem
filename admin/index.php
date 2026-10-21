<?php
/**
 * Administrator Dashboard Overview
 */

define('APP_INIT', true);
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pageTitle = 'Dashboard Overview';
$pdo = Database::getConnection();

// 1. Total Orders
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// 2. Today's Orders
$todayOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// 3. Total Registered Customers
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

// 4. Total Food Items
$totalFoods = (int)$pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();

// 5. Today's Sales from Completed Orders
$todaySalesStmt = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) 
    FROM orders 
    WHERE DATE(created_at) = CURDATE() AND order_status = 'Completed'
");
$todaySales = (float)$todaySalesStmt->fetchColumn();

// 6. Recent Orders (Last 8)
$recentOrders = $pdo->query("
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 8
")->fetchAll();

// 7. Order Status Distribution for Chart
$statusDist = $pdo->query("
    SELECT order_status, COUNT(*) as count 
    FROM orders 
    GROUP BY order_status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Ensure all keys exist
$allStatuses = ['Pending', 'Confirmed', 'Preparing', 'Ready for Pickup', 'Completed', 'Cancelled'];
$statusCounts = [];
foreach ($allStatuses as $st) {
    $statusCounts[$st] = (int)($statusDist[$st] ?? 0);
}

// 8. Last 7 Days Sales Trend
$sevenDaySales = $pdo->query("
    SELECT DATE(created_at) as sale_date, COALESCE(SUM(total_amount), 0) as daily_total
    FROM orders
    WHERE order_status = 'Completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY sale_date ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$chartDays = [];
$chartRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chartDays[] = date('D (d M)', strtotime($d));
    $chartRevenue[] = (float)($sevenDaySales[$d] ?? 0.0);
}

require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Dashboard</h2>
        <p class="text-muted mb-0">Overview of canteen orders, revenue, and active kitchen operations.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="orders.php" class="btn btn-primary rounded-pill px-3">
            <i class="bi bi-receipt me-1"></i> Manage Orders
        </a>
        <a href="foods.php" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-plus-lg me-1"></i> Add Food Item
        </a>
    </div>
</div>

<!-- KPI Metrics Grid -->
<div class="row g-3 mb-4">
    <!-- Today's Revenue -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="stat-label">Today's Sales</span>
                <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-currency-rupee"></i></div>
            </div>
            <div class="stat-value text-success"><?= formatPrice($todaySales) ?></div>
            <small class="text-muted"><i class="bi bi-check-circle-fill text-success me-1"></i> From completed orders</small>
        </div>
    </div>

    <!-- Today's Orders -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="stat-label">Today's Orders</span>
                <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-bag-check-fill"></i></div>
            </div>
            <div class="stat-value"><?= $todayOrders ?></div>
            <small class="text-muted"><i class="bi bi-clock-history me-1"></i> Placed today</small>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="stat-label">Total Lifetime Orders</span>
                <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-receipt-cutoff"></i></div>
            </div>
            <div class="stat-value"><?= $totalOrders ?></div>
            <small class="text-muted"><i class="bi bi-database me-1"></i> All recorded orders</small>
        </div>
    </div>

    <!-- Registered Customers -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="stat-label">Total Customers</span>
                <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-people-fill"></i></div>
            </div>
            <div class="stat-value"><?= $totalCustomers ?></div>
            <small class="text-muted"><?= $totalFoods ?> food items on menu</small>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-4 mb-4">
    <!-- 7 Day Revenue Trend -->
    <div class="col-lg-7">
        <div class="admin-card h-100 mb-0">
            <div class="admin-card-header">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-line text-primary me-2"></i>7-Day Sales Trend (₹)</h6>
            </div>
            <div class="p-3">
                <canvas id="revenueTrendChart" height="130"></canvas>
            </div>
        </div>
    </div>

    <!-- Order Status Distribution -->
    <div class="col-lg-5">
        <div class="admin-card h-100 mb-0">
            <div class="admin-card-header">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart text-primary me-2"></i>Order Status Breakdown</h6>
            </div>
            <div class="p-3">
                <canvas id="statusDonutChart" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Recent Orders</h6>
        <a href="orders.php" class="btn btn-outline-primary btn-sm rounded-pill">View All Orders</a>
    </div>
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Time</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No orders placed yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentOrders as $ro): ?>
                        <tr>
                            <td>
                                <a href="order-details.php?id=<?= $ro['id'] ?>" class="fw-bold font-monospace text-decoration-none">
                                    <?= e($ro['order_number']) ?>
                                </a>
                            </td>
                            <td class="fw-semibold text-dark"><?= e($ro['customer_name']) ?></td>
                            <td class="text-muted small"><?= e($ro['customer_phone']) ?></td>
                            <td class="fw-bold text-dark"><?= formatPrice($ro['total_amount']) ?></td>
                            <td><?= getOrderStatusBadge($ro['order_status']) ?></td>
                            <td><?= getPaymentStatusBadge($ro['payment_status']) ?></td>
                            <td class="text-muted small"><?= date('h:i A', strtotime($ro['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="order-details.php?id=<?= $ro['id'] ?>" class="btn btn-outline-secondary btn-sm py-1 px-2.5 rounded-pill">
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Revenue Bar Chart
    const ctxRevenue = document.getElementById('revenueTrendChart');
    if (ctxRevenue) {
        new Chart(ctxRevenue, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartDays) ?>,
                datasets: [{
                    label: 'Sales (₹)',
                    data: <?= json_encode($chartRevenue) ?>,
                    backgroundColor: 'rgba(230, 74, 25, 0.8)',
                    hoverBackgroundColor: '#e64a19',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹' + value; }
                        }
                    }
                }
            }
        });
    }

    // 2. Status Donut Chart
    const ctxStatus = document.getElementById('statusDonutChart');
    if (ctxStatus) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($statusCounts)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($statusCounts)) ?>,
                    backgroundColor: [
                        '#f59e0b', // Pending (Amber)
                        '#06b6d4', // Confirmed (Cyan)
                        '#3b82f6', // Preparing (Blue)
                        '#10b981', // Ready for Pickup (Green)
                        '#64748b', // Completed (Slate)
                        '#ef4444'  // Cancelled (Red)
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
