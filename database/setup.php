<?php
/**
 * Interactive & CLI Database Setup Script
 * Creates canteen_db schema and seeds initial sample data.
 */

define('APP_INIT', true);
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';

$isCli = (php_sapi_name() === 'cli');
$message = '';
$status = 'info';
$output = [];

if ($isCli || (isset($_POST['action']) && $_POST['action'] === 'run_setup')) {
    try {
        $creds = Database::getCredentials();
        
        // Connect to MySQL server (without specifying DB name first, so we can create it)
        $dsn = "mysql:host={$creds['host']};port={$creds['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $creds['user'], $creds['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $output[] = "Connected to MySQL server on {$creds['host']}:{$creds['port']}";
        
        // Read schema.sql
        $schemaFile = __DIR__ . '/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("schema.sql not found at {$schemaFile}");
        }
        $schemaSql = file_get_contents($schemaFile);
        
        // Execute schema queries
        $pdo->exec($schemaSql);
        $output[] = "Database schema imported successfully (Tables: users, categories, food_items, orders, order_items)";
        
        // Connect to the created database specifically
        $pdo->exec("USE `{$creds['dbname']}`");
        
        // Read seed.sql
        $seedFile = __DIR__ . '/seed.sql';
        if (file_exists($seedFile)) {
            $seedSql = file_get_contents($seedFile);
            $pdo->exec($seedSql);
            $output[] = "Sample seed data inserted successfully (Categories, Foods, Admin, Customer, Sample Orders)";
        }
        
        $status = 'success';
        $message = "Database setup completed successfully!";
    } catch (Throwable $e) {
        $status = 'danger';
        $message = "Database Setup Error: " . $e->getMessage();
        $output[] = "Error details: " . $e->getMessage();
    }
}

if ($isCli) {
    echo "========================================\n";
    echo " CANTEEN MANAGEMENT SYSTEM - DB SETUP  \n";
    echo "========================================\n";
    foreach ($output as $line) {
        echo "[+] $line\n";
    }
    echo "Result: $message\n";
    exit($status === 'success' ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; font-family: system-ui, -apple-system, sans-serif; }
        .setup-card { max-width: 650px; margin: 40px auto; border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .brand-header { background: linear-gradient(135deg, #e64a19, #f57c00); color: white; border-top-left-radius: 16px; border-top-right-radius: 16px; padding: 25px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card setup-card overflow-hidden">
            <div class="brand-header text-center">
                <i class="bi bi-database-fill-gear fs-1 mb-2 d-inline-block"></i>
                <h3 class="fw-bold mb-1">CampusBite Canteen System</h3>
                <p class="mb-0 text-white-50">Database Setup &amp; Migration Utility</p>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $status ?> d-flex align-items-center" role="alert">
                        <i class="bi bi-<?= $status === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2 fs-5"></i>
                        <div><?= htmlspecialchars($message) ?></div>
                    </div>
                    <?php if (!empty($output)): ?>
                        <div class="bg-dark text-light p-3 rounded-3 mb-4 font-monospace small">
                            <?php foreach ($output as $line): ?>
                                <div><span class="text-success">&gt;</span> <?= htmlspecialchars($line) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($status === 'success'): ?>
                        <div class="card bg-light border-0 p-3 mb-4">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-key-fill text-primary me-2"></i>Default Credentials</h6>
                            <div class="row g-2 small">
                                <div class="col-md-6">
                                    <div class="border rounded p-2 bg-white">
                                        <span class="badge bg-danger mb-1">Admin Portal</span><br>
                                        <strong>Email:</strong> admin@canteen.local<br>
                                        <strong>Password:</strong> Admin@123
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-2 bg-white">
                                        <span class="badge bg-success mb-1">Customer Portal</span><br>
                                        <strong>Email:</strong> customer@canteen.local<br>
                                        <strong>Password:</strong> Customer@123
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="../index.php" class="btn btn-primary flex-fill py-2"><i class="bi bi-shop me-1"></i> Open Customer Website</a>
                            <a href="../admin/login.php" class="btn btn-dark flex-fill py-2"><i class="bi bi-shield-lock me-1"></i> Open Admin Portal</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">This utility will initialize the MySQL database (<code>canteen_db</code>), create all required relational tables with indexes and constraints, and load sample categories and food items.</p>
                    
                    <div class="card bg-light border-0 p-3 mb-4">
                        <h6 class="fw-bold mb-2">Detected Database Configuration:</h6>
                        <?php $creds = Database::getCredentials(); ?>
                        <ul class="list-unstyled mb-0 small text-secondary">
                            <li><strong>Host:</strong> <?= htmlspecialchars($creds['host']) ?>:<?= htmlspecialchars($creds['port']) ?></li>
                            <li><strong>Database Name:</strong> <?= htmlspecialchars($creds['dbname']) ?></li>
                            <li><strong>Username:</strong> <?= htmlspecialchars($creds['user']) ?></li>
                            <li><strong>Password:</strong> <?= !empty($creds['pass']) ? '••••••••' : '<em>(Empty)</em>' ?></li>
                        </ul>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="run_setup">
                        <button type="submit" class="btn btn-warning text-white fw-bold w-100 py-2.5 shadow-sm" style="background: #e64a19; border-color: #e64a19;">
                            <i class="bi bi-play-circle-fill me-2"></i> Run Database Setup &amp; Seed
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white border-top text-center py-3 text-muted small">
                AWS Elastic Beanstalk &amp; XAMPP MySQL Compatible
            </div>
        </div>
    </div>
</body>
</html>
