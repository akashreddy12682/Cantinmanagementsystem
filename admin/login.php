<?php
/**
 * Administrator Secure Login Portal
 */

define('APP_INIT', true);
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

// If already admin, redirect to dashboard
if (isAdmin()) {
    redirect('admin/index.php');
}

$pageTitle = 'Admin Login';
$error = '';
$email = '';

$pdo = Database::getConnection();

// Check if any admin exists in database
$adminCheck = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$hasAdmin = ($adminCheck > 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please provide both email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['role'] !== 'admin') {
                    $error = 'Access denied. This account does not have administrator privileges.';
                } else {
                    // Valid admin
                    loginUser($user);
                    setFlashMessage('success', "Welcome back, {$user['name']}!");
                    
                    $target = $_SESSION['admin_redirect_after'] ?? 'admin/index.php';
                    unset($_SESSION['admin_redirect_after']);
                    redirect($target);
                }
            } else {
                $error = 'Invalid administrator credentials. Access refused.';
            }
        } catch (PDOException $e) {
            error_log("Admin Login Error: " . $e->getMessage());
            $error = 'System authentication error. Please try again.';
        }
    }
}
$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 18px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }
        .brand-header {
            background: linear-gradient(135deg, #e64a19, #f57c00);
            padding: 2.2rem 2rem;
            text-align: center;
        }
        .form-control {
            background-color: #0f172a;
            border: 1px solid #334155;
            color: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 10px;
        }
        .form-control:focus {
            background-color: #0f172a;
            border-color: #f97316;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.25);
        }
        .btn-admin {
            background: linear-gradient(135deg, #e64a19, #f57c00);
            border: none;
            color: #ffffff;
            font-weight: 700;
            padding: 0.8rem;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .btn-admin:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            color: #ffffff;
        }
    </style>
</head>
<body class="p-3">

    <div class="login-card">
        <div class="brand-header text-white">
            <div class="avatar-circle mx-auto mb-2" style="width: 58px; height: 58px; font-size: 1.8rem; background: rgba(255,255,255,0.2);">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h4 class="fw-bold mb-1">CampusBite Admin</h4>
            <small class="text-white-50">Canteen Staff &amp; Management Portal</small>
        </div>

        <div class="p-4 p-sm-5">
            <?php if (!$hasAdmin): ?>
                <div class="alert alert-warning py-2 small mb-4">
                    <i class="bi bi-info-circle me-1"></i> No administrator account found. <a href="create-admin.php" class="alert-link fw-bold">Click here to create the initial admin</a>.
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4">
                    <i class="bi bi-shield-x me-1"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label small text-secondary fw-semibold">Admin Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-person-fill"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="admin@canteen.local" value="<?= e($email) ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small text-secondary fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-key-fill"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-admin w-100 mb-3 shadow">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Authenticate &amp; Access
                </button>

                <div class="text-center small text-secondary">
                    <a href="../index.php" class="text-secondary text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i> Return to Public Canteen Site
                    </a>
                </div>
            </form>

            <div class="card bg-dark bg-opacity-50 border-0 p-3 mt-4 rounded-3 text-center small text-secondary">
                <div><i class="bi bi-info-circle text-warning me-1"></i> <strong>Initial Admin Seed:</strong></div>
                <div><code>admin@canteen.local</code> / <code>Admin@123</code></div>
            </div>
        </div>
    </div>

</body>
</html>
