<?php
/**
 * Global Utility Functions
 */

if (!defined('APP_INIT')) {
    require_once dirname(__DIR__) . '/config/app.php';
}

/**
 * Escape HTML output to prevent XSS
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency amount with INR symbol
 */
function formatPrice(float|int|string $amount): string {
    return CURRENCY_SYMBOL . ' ' . number_format((float)$amount, 2);
}

/**
 * Set a session flash message
 */
function setFlashMessage(string $type, string $message): void {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Render flash messages as Bootstrap alerts and clear them from session
 */
function displayFlashMessages(): string {
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $html = '<div class="flash-messages-container mb-3">';
    foreach ($_SESSION['flash'] as $msg) {
        $type = htmlspecialchars($msg['type'], ENT_QUOTES, 'UTF-8');
        $text = htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8');
        $icon = match($type) {
            'success' => '<i class="bi bi-check-circle-fill me-2"></i>',
            'danger'  => '<i class="bi bi-exclamation-triangle-fill me-2"></i>',
            'warning' => '<i class="bi bi-exclamation-circle-fill me-2"></i>',
            default   => '<i class="bi bi-info-circle-fill me-2"></i>',
        };
        $html .= <<<HTML
        <div class="alert alert-{$type} alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
            {$icon}
            <div>{$text}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
HTML;
    }
    $html .= '</div>';
    unset($_SESSION['flash']);
    return $html;
}

/**
 * Safe redirect to a URL or route
 */
function redirect(string $path): void {
    if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
        $path = getBaseUrl() . '/' . ltrim($path, '/');
    }
    header("Location: {$path}");
    exit;
}

/**
 * Generate a unique, readable order number
 * Format: ORD-YYYYMMDD-XXXX (e.g. ORD-20261021-8F3A)
 */
function generateOrderNumber(): string {
    $datePart = date('Ymd');
    $randomPart = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    return "ORD-{$datePart}-{$randomPart}";
}

/**
 * Get CSS badge markup for order statuses
 */
function getOrderStatusBadge(string $status): string {
    $map = [
        'Pending'          => ['class' => 'bg-warning text-dark', 'icon' => 'bi-hourglass-split'],
        'Confirmed'        => ['class' => 'bg-info text-dark',    'icon' => 'bi-clipboard-check'],
        'Preparing'        => ['class' => 'bg-primary text-white', 'icon' => 'bi-fire'],
        'Ready for Pickup' => ['class' => 'bg-success text-white', 'icon' => 'bi-bag-check'],
        'Completed'        => ['class' => 'bg-secondary text-white', 'icon' => 'bi-check2-all'],
        'Cancelled'        => ['class' => 'bg-danger text-white', 'icon' => 'bi-x-circle']
    ];

    $cfg = $map[$status] ?? ['class' => 'bg-secondary text-white', 'icon' => 'bi-tag'];
    $escapedStatus = e($status);
    return "<span class=\"badge {$cfg['class']} px-2.5 py-1.5 rounded-pill shadow-xs\"><i class=\"bi {$cfg['icon']} me-1\"></i>{$escapedStatus}</span>";
}

/**
 * Get CSS badge markup for payment statuses
 */
function getPaymentStatusBadge(string $status): string {
    $map = [
        'paid'     => 'bg-success',
        'pending'  => 'bg-warning text-dark',
        'refunded' => 'bg-danger'
    ];
    $cls = $map[strtolower($status)] ?? 'bg-secondary';
    $label = ucfirst($status);
    return "<span class=\"badge {$cls} text-uppercase font-monospace px-2 py-1\">{$label}</span>";
}

/**
 * Get total items in session cart
 */
function getCartItemCount(): int {
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += (int)($item['quantity'] ?? 0);
    }
    return $total;
}

/**
 * Return web-accessible URL for a food image or fallback image
 */
function getFoodImageUrl(?string $image): string {
    $baseUrl = getBaseUrl();
    if (!empty($image) && file_exists(UPLOAD_DIR . DIRECTORY_SEPARATOR . $image)) {
        return $baseUrl . '/assets/images/foods/' . rawurlencode($image);
    }
    return $baseUrl . '/assets/images/food-placeholder.svg';
}

/**
 * Securely validate and handle uploaded food item image
 * Returns array: ['success' => bool, 'filename' => ?string, 'error' => ?string]
 */
function handleImageUpload(array $file): array {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'File upload failed with system code ' . $file['error']];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'filename' => null, 'error' => 'Image exceeds the maximum allowed size of 2MB.'];
    }

    // Verify MIME type using fileinfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES, true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Invalid image format. Only JPEG, PNG, and WebP are allowed.'];
    }

    $ext = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => ''
    };

    if (empty($ext)) {
        return ['success' => false, 'filename' => null, 'error' => 'Unsupported image format.'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0755, true);
    }

    // Generate non-guessable random file name
    $newFilename = 'food_' . bin2hex(random_bytes(10)) . '.' . $ext;
    $targetPath = UPLOAD_DIR . DIRECTORY_SEPARATOR . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'filename' => null, 'error' => 'Failed to save the uploaded image to the server disk.'];
    }

    return ['success' => true, 'filename' => $newFilename, 'error' => null];
}
