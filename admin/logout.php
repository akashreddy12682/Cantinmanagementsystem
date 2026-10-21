<?php
/**
 * Administrator Logout Action
 */

define('APP_INIT', true);
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

logoutUser();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
setFlashMessage('info', 'Administrator session ended.');
redirect('admin/login.php');
