<?php
/**
 * Shopping Cart Management Page
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$pageTitle = 'My Cart';
$pdo = Database::getConnection();

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Cart Actions (POST requests with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $foodId = (int)($_POST['food_id'] ?? 0);
        $qty = max(1, min(20, (int)($_POST['quantity'] ?? 1)));

        // Verify item from DB
        $stmt = $pdo->prepare("SELECT id, name, price, image, is_available FROM food_items WHERE id = ?");
        $stmt->execute([$foodId]);
        $food = $stmt->fetch();

        if ($food && $food['is_available']) {
            if (isset($_SESSION['cart'][$foodId])) {
                $_SESSION['cart'][$foodId]['quantity'] = min(20, $_SESSION['cart'][$foodId]['quantity'] + $qty);
            } else {
                $_SESSION['cart'][$foodId] = [
                    'id'       => $food['id'],
                    'name'     => $food['name'],
                    'price'    => (float)$food['price'],
                    'image'    => $food['image'],
                    'quantity' => $qty
                ];
            }
            setFlashMessage('success', "Added {$food['name']} to your cart!");
        } else {
            setFlashMessage('danger', "This food item is currently sold out and cannot be added.");
        }
        redirect('cart.php');

    } elseif ($action === 'update') {
        $foodId = (int)($_POST['food_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);

        if (isset($_SESSION['cart'][$foodId])) {
            if ($qty <= 0) {
                unset($_SESSION['cart'][$foodId]);
                setFlashMessage('info', "Item removed from your cart.");
            } else {
                $_SESSION['cart'][$foodId]['quantity'] = min(20, $qty);
                setFlashMessage('success', "Cart quantity updated.");
            }
        }
        redirect('cart.php');

    } elseif ($action === 'remove') {
        $foodId = (int)($_POST['food_id'] ?? 0);
        if (isset($_SESSION['cart'][$foodId])) {
            $name = $_SESSION['cart'][$foodId]['name'];
            unset($_SESSION['cart'][$foodId]);
            setFlashMessage('info', "Removed {$name} from your cart.");
        }
        redirect('cart.php');

    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        setFlashMessage('info', "Your cart has been cleared.");
        redirect('cart.php');
    }
}

// Synchronize and validate Cart items against database prices and stock
$cartItems = [];
$cartTotal = 0.0;
$hasUnavailableItems = false;
$priceChangedNotice = false;

if (!empty($_SESSION['cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price, image, is_available FROM food_items WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $dbItems = $stmt->fetchAll();
        $dbItemsById = [];
        foreach ($dbItems as $item) {
            $dbItemsById[$item['id']] = $item;
        }

        foreach ($_SESSION['cart'] as $id => $sessionItem) {
            if (!isset($dbItemsById[$id])) {
                // Item deleted from database
                unset($_SESSION['cart'][$id]);
                continue;
            }

            $dbFood = $dbItemsById[$id];
            $currentPrice = (float)$dbFood['price'];

            // Price sync
            if ((float)$sessionItem['price'] !== $currentPrice) {
                $_SESSION['cart'][$id]['price'] = $currentPrice;
                $priceChangedNotice = true;
            }

            $isAvailable = (bool)$dbFood['is_available'];
            if (!$isAvailable) {
                $hasUnavailableItems = true;
            }

            $quantity = (int)$sessionItem['quantity'];
            $subtotal = $currentPrice * $quantity;
            $cartTotal += $subtotal;

            $cartItems[] = [
                'id'           => $id,
                'name'         => $dbFood['name'],
                'price'        => $currentPrice,
                'image'        => $dbFood['image'],
                'quantity'     => $quantity,
                'subtotal'     => $subtotal,
                'is_available' => $isAvailable
            ];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold text-dark mb-1">Shopping Cart</h1>
            <p class="text-muted mb-0">Review items before placing your order.</p>
        </div>
        <?php if (!empty($cartItems)): ?>
            <form method="POST" action="cart.php" onsubmit="return confirm('Are you sure you want to empty your entire cart?');">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                    <i class="bi bi-trash3 me-1"></i> Clear Cart
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($priceChangedNotice): ?>
        <div class="alert alert-warning py-2 small mb-4">
            <i class="bi bi-info-circle me-1"></i> One or more prices were updated to match current canteen rates.
        </div>
    <?php endif; ?>

    <?php if ($hasUnavailableItems): ?>
        <div class="alert alert-danger py-2 small mb-4">
            <i class="bi bi-exclamation-octagon me-1"></i> <strong>Sold Out Items:</strong> One or more items in your cart are currently unavailable. Please remove them before proceeding to checkout.
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <div class="card border-0 bg-white shadow-xs rounded-4 text-center py-5 my-4">
            <div class="avatar-circle mx-auto mb-3" style="width: 72px; height: 72px; font-size: 2.2rem; background: #fff3e0; color: #f57c00;">
                <i class="bi bi-cart-x"></i>
            </div>
            <h4 class="fw-bold text-dark">Your Cart is Empty</h4>
            <p class="text-muted mb-4">You haven't added any delicious food items to your plate yet.</p>
            <div>
                <a href="menu.php" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold">
                    <i class="bi bi-grid me-1"></i> Browse Food Menu
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Items Table -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Item</th>
                                    <th>Price</th>
                                    <th style="width: 140px;">Quantity</th>
                                    <th>Subtotal</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <tr class="<?= !$item['is_available'] ? 'table-danger' : '' ?>">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= getFoodImageUrl($item['image']) ?>" alt="<?= e($item['name']) ?>" width="54" height="54" class="rounded-3 object-fit-cover border">
                                                <div>
                                                    <div class="fw-bold text-dark"><?= e($item['name']) ?></div>
                                                    <?php if (!$item['is_available']): ?>
                                                        <span class="badge bg-danger small">Sold Out</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-semibold text-muted">
                                            <?= formatPrice($item['price']) ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="cart.php" class="d-inline">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="food_id" value="<?= $item['id'] ?>">
                                                <div class="quantity-control">
                                                    <button type="submit" name="quantity" value="<?= $item['quantity'] - 1 ?>" class="btn-sm">-</button>
                                                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="20" class="cart-qty-input">
                                                    <button type="submit" name="quantity" value="<?= $item['quantity'] + 1 ?>" class="btn-sm">+</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?= formatPrice($item['subtotal']) ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <form method="POST" action="cart.php" class="d-inline">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="food_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm border-0" title="Remove">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="menu.php" class="btn btn-outline-secondary rounded-pill">
                        <i class="bi bi-arrow-left me-1"></i> Continue Adding Food
                    </a>
                </div>
            </div>

            <!-- Order Summary Card -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 90px;">
                    <h5 class="fw-bold text-dark mb-3">Order Summary</h5>
                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Items Count</span>
                        <span><?= count($cartItems) ?> items</span>
                    </div>
                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Subtotal</span>
                        <span><?= formatPrice($cartTotal) ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted mb-3">
                        <span>Canteen Packing Fee</span>
                        <span class="text-success fw-semibold">FREE (₹0.00)</span>
                    </div>
                    <hr class="opacity-25">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fs-5 fw-bold text-dark">Total Amount</span>
                        <span class="fs-4 fw-extrabold text-primary"><?= formatPrice($cartTotal) ?></span>
                    </div>

                    <?php if ($hasUnavailableItems): ?>
                        <button class="btn btn-secondary w-100 py-3 rounded-pill fw-bold disabled" disabled>
                            Remove Sold-out Items to Order
                        </button>
                    <?php else: ?>
                        <a href="checkout.php" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Proceed to Checkout
                        </a>
                    <?php endif; ?>

                    <div class="text-center mt-3 small text-muted">
                        <i class="bi bi-shield-lock me-1 text-success"></i> Secure Cash-on-Pickup Checkout
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
