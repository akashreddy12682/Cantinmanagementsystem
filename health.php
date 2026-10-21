<?php
/**
 * Health Check Endpoint for AWS Elastic Beanstalk & Application Load Balancers
 * 
 * Returns HTTP 200 OK with JSON diagnostics if operational.
 * Returns HTTP 503 Service Unavailable if critical subsystems fail.
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$startTime = microtime(true);
$dbCheck = Database::testConnection();
$responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);

$status = $dbCheck['success'] ? 'UP' : 'DEGRADED';
$httpCode = $dbCheck['success'] ? 200 : 503;

http_response_code($httpCode);

$response = [
    'status'        => $status,
    'timestamp'     => date('c'),
    'app_name'      => APP_NAME,
    'environment'   => APP_ENV,
    'php_version'   => PHP_VERSION,
    'database'      => [
        'connected'     => $dbCheck['success'],
        'message'       => $dbCheck['message'],
        'response_ms'   => $responseTimeMs,
    ],
    'disk'          => [
        'upload_dir_writable' => is_writable(UPLOAD_DIR) || is_writable(dirname(UPLOAD_DIR))
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
exit;
