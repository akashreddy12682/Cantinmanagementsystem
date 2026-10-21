<?php
/**
 * Application Global Configuration
 */

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// Ensure session starts with security flags
if (session_status() === PHP_SESSION_NONE) {
    // Only set cookie params if headers not sent yet
    if (!headers_sent()) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                  (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        
        session_set_cookie_params([
            'lifetime' => 86400 * 7, // 7 days
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    session_start();
}

// Application Constants
define('APP_NAME', 'CampusBite Canteen');
define('APP_TAGLINE', 'Fresh, Fast & Delicious Meals on Campus');
define('CURRENCY_SYMBOL', '₹');
define('CURRENCY_CODE', 'INR');
define('APP_ENV', getenv('APP_ENV') ?: 'production');

// Image upload directory paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'foods');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

/**
 * Determine dynamic base URL for links and assets
 */
function getBaseUrl(): string {
    static $baseUrl = null;
    if ($baseUrl !== null) {
        return $baseUrl;
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') 
                ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Calculate subfolder relative to webroot
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    
    // Check if script is under /admin/
    if (str_contains($scriptName, '/admin/')) {
        $dir = substr($scriptName, 0, strpos($scriptName, '/admin/'));
    } else {
        $dir = dirname($scriptName);
    }
    
    $dir = trim(str_replace('\\', '/', $dir), '/');
    $baseUrl = $protocol . $host . ($dir !== '' ? '/' . $dir : '');
    return rtrim($baseUrl, '/');
}
