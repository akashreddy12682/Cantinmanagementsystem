<?php
/**
 * Checkout and Order Placement Page
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

requireLogin('checkout.php');

$pageTitle = 'Order Checkout';
$pdo = Database::getConnection();
$currentUser = getCurrentUser();

// Check if cart is empty
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    setFlashMessage('warning', 'Your cart is empty. Please add items before checking out.');
    redirect('menu.php');
}

// Fetch all cart items from database to compute tamper-proof prices and check stock
$ids = array_map('intval', array_keys($_SESSION['cart']));
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, name, price, is_available FROM food_items WHERE id IN ($placeholders)");
$stmt->execute($ids);
$dbFoods = $stmt->fetchAll();
$dbFoodsById = [];
foreach ($dbFoods as $f) {
    $dbFoodsById[$f['id']] = $f;
}

$validatedItems = [];
$totalAmount = 0.0;
$hasUnavailable = false;

foreach ($_SESSION['cart'] as $id => $sessionItem) {
    if (!isset($dbFoodsById[$id])) {
        continue;
    }
    $food = $dbFoodsById[$id];
    if (!$food['is_available']) {
        $hasUnavailable = true;
    }
    $price = (float)$food['price'];
    $qty = (int)$sessionItem['quantity'];
    $subtotal = $price * $qty;
    $totalAmount += $subtotal;

    $validatedItems[] = [
        'food_id'    => $food['id'],
        'item_name'  => $food['name'],
        'unit_price' => $price,
        'quantity'   => $qty,
        'subtotal'   => $subtotal
    ];
}

if ($hasUnavailable) {
    setFlashMessage('danger', 'One or more items in your cart are currently sold out. Please review your cart.');
    redirect('cart.php');
}

if (empty($validatedItems)) {
    setFlashMessage('warning', 'No valid items found in your cart.');
    redirect('menu.php');
}

$error = '';
$customerName = $_POST['customer_name'] ?? $currentUser['name'];
$customerPhone = $_POST['customer_phone'] ?? $currentUser['phone'];
$specialInstructions = trim($_POST['special_instructions'] ?? '');

// Process Order Placement via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $customerName = trim($_POST['customer_name'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $cleanedPhone = preg_replace('/[^0-9]/', '', $customerPhone);

    if (empty($customerName) || strlen($customerName) < 3) {
        $error = 'Please provide a valid recipient name for your order pickup.';
    } elseif (strlen($cleanedPhone) < 10) {
        $error = 'Please provide a valid 10-digit phone number for pickup confirmation.';
    } else {
        // Begin Database Transaction
        try {
            $pdo->beginTransaction();

            $orderNumber = generateOrderNumber();
            $userId = (int)$currentUser['id'];

            // 1. Insert into orders table
            $orderInsert = $pdo->prepare("
                INSERT INTO orders (
                    order_number, user_id, customer_name, customer_phone, 
                    total_amount, payment_method, payment_status, order_status, 
                    special_instructions
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, 'cash', 'pending', 'Pending', 
                    ?
                )
            ");
            $orderInsert->execute([
                $orderNumber,
                $userId,
                $customerName,
                $cleanedPhone,
                $totalAmount,
                !empty($specialInstructions) ? $specialInstructions : null
            ]);

            $orderId = (int)$pdo->lastInsertId();

            // 2. Insert into order_items table
            $itemInsert = $pdo->prepare("
                INSERT INTO order_items (
                    order_id, food_item_id, item_name, unit_price, quantity, subtotal
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($validatedItems as $item) {
                $itemInsert->execute([
                    $orderId,
                    $item['food_id'],
                    $item['item_name'],
                    $item['unit_price'],
                    $item['quantity'],
                    $item['subtotal']
                ]);
            }

            // Commit Transaction
            $pdo->commit();

            // Clear Cart Session
            $_SESSION['cart'] = [];

            // Redirect to Confirmation
            setFlashMessage('success', "Order placed successfully! Your order number is {$orderNumber}.");
            redirect("order-confirmation.php?order=" . urlencode($orderNumber));

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Order Placement Error: " . $e->getMessage());
            $error = "Failed to place your order due to a server error. Please try again. (" . $e->getMessage() . ")";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">

    <div class="mb-4">
        <h1 class="fw-bold text-dark mb-1">Confirm &amp; Place Order</h1>
        <p class="text-muted mb-0">Review details and confirm counter pickup.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="checkout.php">
        <?= csrfField() ?>

        <div class="row g-4">
            <!-- Left Column: Customer and Instructions -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-badge me-2 text-primary"></i>Pickup Contact Information</h5>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Pickup Name</label>
                        <input type="text" name="customer_name" class="form-control" value="<?= e($customerName) ?>" required>
                        <div class="form-text small">This name will be announced at the pickup counter.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Phone Number</label>
                        <input type="tel" name="customer_phone" class="form-control" value="<?= e($customerPhone) ?>" required>
                        <div class="form-text small">For SMS / Counter verification</div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-secondary">Special Kitchen Instructions (Optional)</label>
                        <textarea name="special_instructions" class="form-control" rows="3" placeholder="e.g. Extra spicy, no onions, pack sambar separately..."><?= e($specialInstructions) ?></textarea>
                    </div>
                </div>

                <!-- Payment Method Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-wallet2 me-2 text-primary"></i>Payment Method</h5>

                    <div class="border rounded-3 p-3 bg-light d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-circle" style="background: #2e7d32;">
                                <i class="bi bi-cash-stack text-white"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Cash at Counter</div>
                                <div class="small text-muted">Pay in Indian Rupees (₹) at the pickup desk when collecting your meal.</div>
                            </div>
                        </div>
                        <span class="badge bg-success px-3 py-1.5"><i class="bi bi-check-lg me-1"></i> Selected</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Order Items Breakdown -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 90px;">
                    <h5 class="fw-bold text-dark mb-3">Order Details (<?= count($validatedItems) ?> items)</h5>

                    <div class="border-bottom pb-3 mb-3" style="max-height: 240px; overflow-y: auto;">
                        <?php foreach ($validatedItems as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <div>
                                    <div class="fw-bold text-dark"><?= e($item['item_name']) ?></div>
                                    <div class="text-muted"><?= $item['quantity'] ?> × <?= formatPrice($item['unit_price']) ?></div>
                                </div>
                                <div class="fw-semibold text-dark">
                                    <?= formatPrice($item['subtotal']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between text-muted mb-2 small">
                        <span>Subtotal</span>
                        <span><?= formatPrice($totalAmount) ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted mb-3 small">
                        <span>Convenience &amp; Platform Fee</span>
                        <span class="text-success fw-bold">FREE</span>
                    </div>

                    <hr class="opacity-25 my-2">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fs-5 fw-bold text-dark">Grand Total</span>
                        <span class="fs-4 fw-extrabold text-primary"><?= formatPrice($totalAmount) ?></span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                        <i class="bi bi-bag-check-fill me-1"></i> Place Order Now (<?= formatPrice($totalAmount) ?>)
                    </button>

                    <div class="text-center mt-3 small text-muted">
                        <a href="cart.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i> Back to Cart</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
