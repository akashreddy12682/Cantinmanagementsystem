<?php
/**
 * Customer Registration Page
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// If already logged in, redirect to menu
if (isLoggedIn()) {
    redirect('menu.php');
}

$pageTitle = 'Student & Staff Registration';
$errors = [];
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name)) {
        $errors[] = 'Full Name is required.';
    } elseif (strlen($name) < 3 || strlen($name) > 100) {
        $errors[] = 'Name must be between 3 and 100 characters.';
    }

    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Indian phone format or general 10-digit check
    $cleanedPhone = preg_replace('/[^0-9]/', '', $phone);
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    } elseif (strlen($cleanedPhone) < 10 || strlen($cleanedPhone) > 12) {
        $errors[] = 'Please provide a valid 10-digit phone number.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    // Check for duplicate email in database
    if (empty($errors)) {
        try {
            $pdo = Database::getConnection();
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $errors[] = 'An account with this email address already exists. Please log in instead.';
            } else {
                // Insert new customer
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (name, email, phone, password_hash, role)
                    VALUES (?, ?, ?, ?, 'customer')
                ");
                $insertStmt->execute([$name, $email, $cleanedPhone, $passwordHash]);
                
                $newUserId = (int)$pdo->lastInsertId();

                // Automatically log the user in
                loginUser([
                    'id'    => $newUserId,
                    'name'  => $name,
                    'email' => $email,
                    'phone' => $cleanedPhone,
                    'role'  => 'customer'
                ]);

                setFlashMessage('success', "Welcome to CampusBite, {$name}! Your account was created successfully.");
                
                $redirectUrl = $_SESSION['redirect_after_login'] ?? 'menu.php';
                unset($_SESSION['redirect_after_login']);
                redirect($redirectUrl);
            }
        } catch (PDOException $e) {
            error_log("Registration DB Error: " . $e->getMessage());
            $errors[] = 'An error occurred while creating your account. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="p-4 p-sm-5">
                    <div class="text-center mb-4">
                        <div class="avatar-circle mx-auto mb-2" style="width: 54px; height: 54px; font-size: 1.5rem;">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <h3 class="fw-bold text-dark">Create Account</h3>
                        <p class="text-muted small">Join CampusBite to order fresh food quickly</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger py-2 px-3 small rounded-3">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php" novalidate>
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label for="nameInput" class="form-label small fw-semibold text-secondary">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="nameInput" name="name" placeholder="e.g. Akash Reddy" value="<?= e($name) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="emailInput" class="form-label small fw-semibold text-secondary">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control border-start-0 ps-0" id="emailInput" name="email" placeholder="name@college.edu" value="<?= e($email) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phoneInput" class="form-label small fw-semibold text-secondary">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-phone"></i></span>
                                <input type="tel" class="form-control border-start-0 ps-0" id="phoneInput" name="phone" placeholder="9876543210" value="<?= e($phone) ?>" required>
                            </div>
                            <div class="form-text small">Used for order pickup alerts &amp; SMS confirmation</div>
                        </div>

                        <div class="mb-3">
                            <label for="passwordInput" class="form-label small fw-semibold text-secondary">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control border-start-0 ps-0" id="passwordInput" name="password" placeholder="Min. 8 characters" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirmInput" class="form-label small fw-semibold text-secondary">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control border-start-0 ps-0" id="confirmInput" name="confirm_password" placeholder="Repeat password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold shadow-sm mb-3">
                            <i class="bi bi-check2-circle me-1"></i> Complete Registration
                        </button>

                        <div class="text-center small text-muted">
                            Already registered? <a href="login.php" class="text-primary fw-bold text-decoration-none">Sign In here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
