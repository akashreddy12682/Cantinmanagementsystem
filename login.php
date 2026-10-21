<?php
/**
 * Customer Login Page
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// If already logged in, redirect
if (isLoggedIn()) {
    redirect('menu.php');
}

$pageTitle = 'Sign In';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful
                loginUser($user);
                
                setFlashMessage('success', "Welcome back, {$user['name']}!");

                // If user is admin and wanted admin portal, or check redirect
                if ($user['role'] === 'admin') {
                    redirect('admin/index.php');
                }

                $redirectUrl = $_SESSION['redirect_after_login'] ?? 'menu.php';
                unset($_SESSION['redirect_after_login']);
                redirect($redirectUrl);
            } else {
                $error = 'Invalid email address or password. Please try again.';
            }
        } catch (PDOException $e) {
            error_log("Login DB Error: " . $e->getMessage());
            $error = 'An internal system error occurred. Please try again later.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="p-4 p-sm-5">
                    <div class="text-center mb-4">
                        <div class="avatar-circle mx-auto mb-2" style="width: 54px; height: 54px; font-size: 1.5rem;">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </div>
                        <h3 class="fw-bold text-dark">Welcome Back</h3>
                        <p class="text-muted small">Sign in to your CampusBite account</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" novalidate>
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label for="emailInput" class="form-label small fw-semibold text-secondary">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control border-start-0 ps-0" id="emailInput" name="email" placeholder="name@college.edu" value="<?= e($email) ?>" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="passwordInput" class="form-label small fw-semibold text-secondary mb-0">Password</label>
                            </div>
                            <div class="input-group mt-1">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control border-start-0 ps-0" id="passwordInput" name="password" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold shadow-sm mb-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>

                        <div class="text-center small text-muted mb-3">
                            Don't have an account? <a href="register.php" class="text-primary fw-bold text-decoration-none">Register now</a>
                        </div>

                        <hr class="text-secondary opacity-25">

                        <div class="text-center">
                            <a href="admin/login.php" class="text-muted small text-decoration-none">
                                <i class="bi bi-shield-lock me-1"></i> Staff / Administrator Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Demo Hint Card -->
            <div class="card border-0 bg-light p-3 mt-3 rounded-3 text-center small text-muted">
                <div><i class="bi bi-info-circle me-1 text-primary"></i> <strong>Demo Credentials:</strong></div>
                <div>Customer: <code>customer@canteen.local</code> / <code>Customer@123</code></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
