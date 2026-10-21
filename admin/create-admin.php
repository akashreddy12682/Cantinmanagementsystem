<?php
/**
 * Secure Administrator Provisioning Script
 * 
 * Can be executed via CLI:
 *   php admin/create-admin.php "Admin Name" "admin@example.com" "SecurePassword123" "9876543210"
 * Or via web if authenticated or using a one-time install key.
 */

define('APP_INIT', true);
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$isCli = (php_sapi_name() === 'cli');
$success = false;
$error = '';

if ($isCli) {
    if ($argc < 4) {
        echo "Usage: php admin/create-admin.php <Name> <Email> <Password> [Phone]\n";
        echo "Example: php admin/create-admin.php \"Chief Admin\" \"admin@college.edu\" \"CollegeAdmin#2026\" \"9876543210\"\n";
        exit(1);
    }

    $name = trim($argv[1]);
    $email = trim($argv[2]);
    $password = $argv[3];
    $phone = $argv[4] ?? '0000000000';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Error: Invalid email format.\n";
        exit(1);
    }

    if (strlen($password) < 8) {
        echo "Error: Password must be at least 8 characters.\n";
        exit(1);
    }

    try {
        $pdo = Database::getConnection();
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "Error: A user with email '{$email}' already exists.\n";
            exit(1);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'admin')");
        $insert->execute([$name, $email, $phone, $hash]);

        echo "SUCCESS: Administrator '{$name}' ({$email}) created successfully!\n";
        exit(0);
    } catch (Throwable $e) {
        echo "Database Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Web Access Handling: Allowed only if logged in as Admin OR if no Admin exists in the database
$pdo = Database::getConnection();
$adminCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$adminCount = (int)$adminCountStmt->fetchColumn();

$canCreate = ($adminCount === 0) || isAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canCreate) {
        $error = "Unauthorized. An administrator account already exists. Please login as admin to create additional administrators.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error = "Name, email, and password are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please provide a valid email address.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
        } else {
            // Check if email already in use
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "A user with this email address already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'admin')");
                $insert->execute([$name, $email, $phone, $hash]);
                $success = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provision Administrator - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0f172a; font-family: system-ui, -apple-system, sans-serif; color: #f8fafc; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 14px; }
        .form-control { background-color: #0f172a; border-color: #334155; color: #f8fafc; }
        .form-control:focus { background-color: #0f172a; border-color: #f97316; color: #fff; box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.25); }
    </style>
</head>
<body class="py-5">
    <div class="container" style="max-width: 520px;">
        <div class="card shadow-lg p-4">
            <div class="text-center mb-4">
                <i class="bi bi-shield-lock-fill text-warning fs-1"></i>
                <h3 class="fw-bold mt-2 text-white">Create Administrator</h3>
                <p class="text-muted small">CampusBite Administrative Provisioning</p>
            </div>

            <?php if (!$canCreate): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-shield-x me-2"></i> An administrator already exists in the system. To create additional administrators, please <a href="login.php" class="alert-link text-white fw-bold">log in as an administrator</a> first.
                </div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-outline-light"><i class="bi bi-box-arrow-in-right me-1"></i> Go to Admin Login</a>
                </div>
            <?php elseif ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i> Administrator account for <strong><?= htmlspecialchars($email) ?></strong> was created successfully!
                </div>
                <a href="login.php" class="btn btn-warning w-100 py-2.5 text-dark fw-bold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Proceed to Admin Login
                </a>
            <?php else: ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Canteen Manager" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Admin Email</label>
                        <input type="email" name="email" class="form-control" placeholder="admin@canteen.local" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" placeholder="9876543210" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Password (Min. 8 characters)</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="8">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small text-muted">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-warning text-dark fw-bold w-100 py-2.5">
                        <i class="bi bi-person-plus-fill me-1"></i> Create Administrator Account
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
