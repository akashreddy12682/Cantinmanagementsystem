<?php
/**
 * Customer Profile Management
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

requireLogin('profile.php');

$pageTitle = 'My Profile';
$pdo = Database::getConnection();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    logoutUser();
    redirect('login.php');
}

$profileSuccess = '';
$profileError = '';
$pwdSuccess = '';
$pwdError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name) || strlen($name) < 3) {
            $profileError = 'Full name must be at least 3 characters.';
        } else {
            $cleanedPhone = preg_replace('/[^0-9]/', '', $phone);
            $update = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $update->execute([$name, $cleanedPhone, $userId]);

            $_SESSION['user_name'] = $name;
            $_SESSION['user_phone'] = $cleanedPhone;
            $user['name'] = $name;
            $user['phone'] = $cleanedPhone;

            $profileSuccess = 'Profile details updated successfully!';
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Fetch current hash
        $hashStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $hashStmt->execute([$userId]);
        $currentHash = $hashStmt->fetchColumn();

        if (!password_verify($currentPassword, $currentHash)) {
            $pwdError = 'Your current password was entered incorrectly.';
        } elseif (strlen($newPassword) < 8) {
            $pwdError = 'New password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $pwdError = 'New password and confirmation do not match.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updatePwd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $updatePwd->execute([$newHash, $userId]);
            $pwdSuccess = 'Your password has been changed successfully!';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar Profile Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white">
                <div class="avatar-circle mx-auto mb-3" style="width: 72px; height: 72px; font-size: 2rem;">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <h4 class="fw-bold text-dark mb-1"><?= e($user['name']) ?></h4>
                <p class="text-muted small mb-2"><?= e($user['email']) ?></p>
                <div class="mb-3">
                    <span class="badge bg-primary text-uppercase px-3 py-1.5"><?= e($user['role']) ?></span>
                </div>
                <hr class="my-3 opacity-25">
                <div class="text-start small text-muted">
                    <div class="mb-2"><i class="bi bi-telephone me-2 text-primary"></i> <strong>Phone:</strong> <?= e($user['phone']) ?></div>
                    <div class="mb-2"><i class="bi bi-calendar-check me-2 text-primary"></i> <strong>Member Since:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?></div>
                </div>
                <a href="my-orders.php" class="btn btn-outline-primary rounded-pill w-100 mt-3">
                    <i class="bi bi-receipt me-1"></i> View Order History
                </a>
            </div>
        </div>

        <!-- Main Edit Forms -->
        <div class="col-lg-8">
            <!-- Edit Information -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-gear me-2 text-primary"></i>Personal Information</h5>

                <?php if (!empty($profileSuccess)): ?>
                    <div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i> <?= e($profileSuccess) ?></div>
                <?php endif; ?>
                <?php if (!empty($profileError)): ?>
                    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i> <?= e($profileError) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?= e($user['phone']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Email Address (Read-only)</label>
                            <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly>
                            <div class="form-text small">Email cannot be changed directly for security reasons.</div>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock me-2 text-primary"></i>Change Password</h5>

                <?php if (!empty($pwdSuccess)): ?>
                    <div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i> <?= e($pwdSuccess) ?></div>
                <?php endif; ?>
                <?php if (!empty($pwdError)): ?>
                    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle me-1"></i> <?= e($pwdError) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">New Password (Min. 8 chars)</label>
                            <input type="password" name="new_password" class="form-control" placeholder="••••••••" required minlength="8">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required minlength="8">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-outline-secondary rounded-pill px-4">
                                <i class="bi bi-key me-1"></i> Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
