<?php
/**
 * Customer Facing Header Template
 */

if (!defined('APP_INIT')) {
    require_once dirname(__DIR__) . '/config/app.php';
}
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

$currentUser = getCurrentUser();
$cartCount = getCartItemCount();
$baseUrl = getBaseUrl();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' . APP_NAME : APP_NAME . ' - ' . APP_TAGLINE ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom Application CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Top Announcement Bar -->
    <div class="top-announcement bg-dark text-white py-1 small">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <span><i class="bi bi-clock me-1 text-warning"></i> Open Today: 8:00 AM – 9:00 PM</span>
                <span class="d-none d-md-inline text-white-50">|</span>
                <span class="d-none d-md-inline"><i class="bi bi-geo-alt me-1 text-warning"></i> Central Campus Food Hall</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark"><i class="bi bi-lightning-charge-fill me-1"></i>Order &amp; Pick Up Fast</span>
                <?php if (isAdmin()): ?>
                    <a href="<?= $baseUrl ?>/admin/index.php" class="btn btn-xs btn-outline-warning text-white ms-2 py-0 px-2" style="font-size: 0.75rem;">
                        <i class="bi bi-speedometer2 me-1"></i> Admin Console
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm main-navbar">
        <div class="container">
            <!-- Brand Logo -->
            <a class="navbar-brand d-flex align-items-center" href="<?= $baseUrl ?>/index.php">
                <img src="<?= $baseUrl ?>/assets/images/logo.svg" alt="<?= APP_NAME ?>" height="42" class="d-inline-block align-text-top">
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Nav Links -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'index.php') ? 'active fw-bold' : '' ?>" href="<?= $baseUrl ?>/index.php">
                            <i class="bi bi-house-door me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'menu.php') ? 'active fw-bold' : '' ?>" href="<?= $baseUrl ?>/menu.php">
                            <i class="bi bi-grid me-1"></i> Food Menu
                        </a>
                    </li>
                    <?php if ($currentUser): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'my-orders.php' || $currentPage === 'order-details.php') ? 'active fw-bold' : '' ?>" href="<?= $baseUrl ?>/my-orders.php">
                            <i class="bi bi-clock-history me-1"></i> My Orders
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Right Action Buttons -->
                <div class="d-flex align-items-center gap-2">
                    <!-- Cart Button with Live Badge -->
                    <a href="<?= $baseUrl ?>/cart.php" class="btn btn-outline-primary position-relative rounded-pill px-3 py-1.5 cart-nav-btn">
                        <i class="bi bi-cart3 me-1 fs-6"></i>
                        <span class="d-none d-sm-inline">Cart</span>
                        <span id="navCartCount" class="badge bg-danger rounded-pill ms-1 <?= ($cartCount === 0) ? 'd-none' : '' ?>">
                            <?= $cartCount ?>
                        </span>
                    </a>

                    <!-- User Account / Login State -->
                    <?php if ($currentUser): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle rounded-pill px-3 py-1.5 d-flex align-items-center gap-2" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="avatar-circle"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></div>
                                <span class="d-none d-md-inline fw-semibold"><?= e(explode(' ', $currentUser['name'])[0]) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2" aria-labelledby="userMenuDropdown">
                                <li class="px-3 py-2 border-bottom">
                                    <div class="fw-bold text-dark"><?= e($currentUser['name']) ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 180px;"><?= e($currentUser['email']) ?></div>
                                    <span class="badge bg-secondary-subtle text-secondary text-uppercase mt-1" style="font-size: 0.7rem;"><?= e($currentUser['role']) ?></span>
                                </li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>/my-orders.php"><i class="bi bi-receipt me-2 text-primary"></i>My Orders</a></li>
                                <li><a class="dropdown-item py-2" href="<?= $baseUrl ?>/profile.php"><i class="bi bi-person me-2 text-info"></i>My Profile</a></li>
                                <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2 text-warning fw-semibold" href="<?= $baseUrl ?>/admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger" href="<?= $baseUrl ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= $baseUrl ?>/login.php" class="btn btn-outline-secondary rounded-pill px-3 py-1.5">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </a>
                        <a href="<?= $baseUrl ?>/register.php" class="btn btn-primary rounded-pill px-3 py-1.5 text-white fw-semibold">
                            Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages Container -->
    <div class="container mt-3">
        <?= displayFlashMessages() ?>
    </div>

    <!-- Main Page Content Starts -->
    <main class="flex-grow-1">
