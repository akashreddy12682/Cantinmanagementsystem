<?php
/**
 * Database Connection & Configuration
 * 
 * Supports both local development (XAMPP / MySQL) and 
 * AWS Elastic Beanstalk production with AWS RDS MySQL.
 */

// Load .env file if it exists (for local or staging environments)
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove surrounding quotes if present
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            if (!getenv($key)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

class Database {
    private static ?PDO $instance = null;

    /**
     * Get database credentials with precedence:
     * 1. AWS RDS Environment Variables (RDS_HOSTNAME, RDS_PORT, etc.)
     * 2. Standard Environment Variables (DB_HOST, DB_PORT, etc.)
     * 3. Local Development Fallbacks (localhost, root, '', canteen_db)
     */
    public static function getCredentials(): array {
        $host = getenv('RDS_HOSTNAME') ?: getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('RDS_PORT') ?: getenv('DB_PORT') ?: '3306';
        $dbname = getenv('RDS_DB_NAME') ?: getenv('DB_NAME') ?: 'canteen_db';
        $user = getenv('RDS_USERNAME') ?: getenv('DB_USER') ?: 'root';
        $pass = getenv('RDS_PASSWORD');
        if ($pass === false) {
            $pass = getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: '';
        }

        return [
            'host' => $host,
            'port' => $port,
            'dbname' => $dbname,
            'user' => $user,
            'pass' => $pass,
        ];
    }

    /**
     * Singleton PDO connection
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $c = self::getCredentials();
            $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['dbname']};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, $c['user'], $c['pass'], $options);
            } catch (PDOException $e) {
                // In production, log error instead of exposing database credentials
                error_log("Database Connection Error: " . $e->getMessage());
                $appEnv = getenv('APP_ENV') ?: 'production';
                if ($appEnv === 'development') {
                    throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
                } else {
                    die("Database connection failed. Please contact the canteen administrator or try again later.");
                }
            }
        }
        return self::$instance;
    }

    /**
     * Test connection without terminating application
     */
    public static function testConnection(): array {
        $c = self::getCredentials();
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['dbname']};charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            $stmt = $pdo->query("SELECT 1");
            return [
                'success' => true,
                'message' => "Connected successfully to {$c['dbname']} on {$c['host']}:{$c['port']}",
                'details' => [
                    'host' => $c['host'],
                    'port' => $c['port'],
                    'dbname' => $c['dbname'],
                    'user' => $c['user']
                ]
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'details' => [
                    'host' => $c['host'],
                    'port' => $c['port'],
                    'dbname' => $c['dbname'],
                    'user' => $c['user']
                ]
            ];
        }
    }
}
