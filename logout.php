<?php
/**
 * User Logout Action
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();

// Start a clean session just to carry the logout flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
setFlashMessage('info', 'You have been logged out successfully.');
redirect('index.php');
