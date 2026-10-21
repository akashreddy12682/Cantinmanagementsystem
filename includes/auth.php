<?php
/**
 * Authentication and Authorization Helper Functions
 */

if (!defined('APP_INIT')) {
    require_once dirname(__DIR__) . '/config/app.php';
}

/**
 * Check if a user is currently logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Check if the currently logged-in user is an administrator
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get data of the currently logged-in user
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'customer'
    ];
}

/**
 * Enforce authentication for customer pages
 */
function requireLogin(string $redirectAfter = ''): void {
    if (!isLoggedIn()) {
        if (!empty($redirectAfter)) {
            $_SESSION['redirect_after_login'] = $redirectAfter;
        } else {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        }
        setFlashMessage('warning', 'Please log in to proceed.');
        redirect('login.php');
    }
}

/**
 * Enforce administrator authentication
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        $_SESSION['admin_redirect_after'] = $_SERVER['REQUEST_URI'] ?? '';
        setFlashMessage('danger', 'Access denied. Administrator privileges required.');
        redirect('admin/login.php');
    }
}

/**
 * Authenticate and log a user into session securely
 */
function loginUser(array $user): void {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_phone'] = $user['phone'] ?? '';
    $_SESSION['user_role']  = $user['role'] ?? 'customer';
    $_SESSION['logged_at']  = time();
}

/**
 * Securely log out current user and destroy session
 */
function logoutUser(): void {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}
