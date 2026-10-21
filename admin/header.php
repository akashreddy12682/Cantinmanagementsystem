<?php
/**
 * Administrator Layout Header
 */

if (!defined('APP_INIT')) {
    require_once dirname(__DIR__) . '/config/app.php';
}
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

// Enforce admin privileges on all admin pages
requireAdmin();

$adminUser = getCurrentUser();
$baseUrl = getBaseUrl();
$currentAdminPage = basename($_SERVER['PHP_SELF']);

// Count pending orders for badge
try {
    $pdo = Database::getConnection();
    $pendingStmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('Pending', 'Confirmed')");
    $pendingOrdersCount = (int)$pendingStmt->fetchColumn();
} catch (Exception $e) {
    $pendingOrdersCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - Admin' : 'Admin Console - ' . APP_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin.css">
</head>
<body class="admin-body">

    <!-- Admin Sidebar Navigation -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-brand">
            <a href="<?= $baseUrl ?>/admin/index.php" class="d-flex align-items-center text-decoration-none text-white gap-2">
                <div class="avatar-circle" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                    <div class="fw-bold fs-6 lh-1">CampusBite</div>
                    <small class="text-secondary" style="font-size: 0.72rem; letter-spacing: 0.8px;">ADMIN CONSOLE</small>
                </div>
            </a>
        </div>

        <nav class="admin-nav">
            <a href="<?= $baseUrl ?>/admin/index.php" class="admin-nav-item <?= ($currentAdminPage === 'index.php') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= $baseUrl ?>/admin/orders.php" class="admin-nav-item <?= ($currentAdminPage === 'orders.php' || $currentAdminPage === 'order-details.php') ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i>
                <span>Orders</span>
                <?php if ($pendingOrdersCount > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= $pendingOrdersCount ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= $baseUrl ?>/admin/foods.php" class="admin-nav-item <?= ($currentAdminPage === 'foods.php') ? 'active' : '' ?>">
                <i class="bi bi-egg-fried"></i>
                <span>Food Items</span>
            </a>

            <a href="<?= $baseUrl ?>/admin/categories.php" class="admin-nav-item <?= ($currentAdminPage === 'categories.php') ? 'active' : '' ?>">
                <i class="bi bi-folder2"></i>
                <span>Categories</span>
            </a>

            <a href="<?= $baseUrl ?>/admin/customers.php" class="admin-nav-item <?= ($currentAdminPage === 'customers.php') ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>Customers</span>
            </a>

            <a href="<?= $baseUrl ?>/admin/reports.php" class="admin-nav-item <?= ($currentAdminPage === 'reports.php') ? 'active' : '' ?>">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Sales Reports</span>
            </a>

            <div class="px-4 py-3 mt-3 text-uppercase text-secondary small" style="font-size: 0.7rem; letter-spacing: 1px;">External</div>

            <a href="<?= $baseUrl ?>/index.php" class="admin-nav-item" target="_blank">
                <i class="bi bi-shop"></i>
                <span>Visit Canteen</span>
                <i class="bi bi-box-arrow-up-right ms-auto small opacity-50"></i>
            </a>

            <a href="<?= $baseUrl ?>/admin/logout.php" class="admin-nav-item text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content Shell -->
    <div class="admin-main">
        <!-- Top Header -->
        <header class="admin-header">
            <button class="btn btn-outline-secondary d-lg-none btn-sm me-2" type="button" onclick="document.getElementById('adminSidebar').classList.toggle('show')">
                <i class="bi bi-list fs-5"></i>
            </button>

            <div class="d-none d-sm-flex align-items-center gap-2 text-muted small">
                <i class="bi bi-calendar-event text-primary"></i>
                <span><?= date('l, d F Y') ?></span>
                <span class="mx-2">•</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> System Online
                </span>
            </div>

            <div class="d-flex align-items-center gap-3 ms-auto">
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle rounded-pill px-3 py-1.5 border d-flex align-items-center gap-2 small" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-fill-gear text-primary"></i>
                        <span class="fw-semibold text-dark"><?= e($adminUser['name']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                        <li class="px-3 py-1.5 small text-muted"><?= e($adminUser['email']) ?></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-1.5 text-danger small" href="<?= $baseUrl ?>/admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Admin Page Body -->
        <div class="admin-content">
            <?= displayFlashMessages() ?>
