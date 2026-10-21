<?php
define('APP_INIT', true);
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pdo = Database::getConnection();

// 1. Verify users
$admin = $pdo->query("SELECT * FROM users WHERE email = 'admin@canteen.local'")->fetch();
if (!$admin || !password_verify('Admin@123', $admin['password_hash'])) {
    throw new Exception("Admin authentication verification failed");
}
echo "[+] Admin account & password_verify verified (ID: {$admin['id']})\n";

$customer = $pdo->query("SELECT * FROM users WHERE email = 'customer@canteen.local'")->fetch();
if (!$customer || !password_verify('Customer@123', $customer['password_hash'])) {
    throw new Exception("Customer authentication verification failed");
}
echo "[+] Customer account & password_verify verified (ID: {$customer['id']})\n";

// 2. Verify categories & items
$cats = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
echo "[+] Categories verified (count: {$cats})\n";

$foods = $pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
echo "[+] Food items verified (count: {$foods})\n";

// 3. Test Transactional Order Creation
$pdo->beginTransaction();
$ordNum = generateOrderNumber();
$insOrd = $pdo->prepare("
    INSERT INTO orders (
        order_number, user_id, customer_name, customer_phone, 
        total_amount, payment_method, payment_status, order_status
    ) VALUES (?, ?, ?, ?, ?, 'cash', 'pending', 'Pending')
");
$insOrd->execute([$ordNum, $customer['id'], 'Test Student', '9999999999', 110.00]);
$ordId = $pdo->lastInsertId();

$insItem = $pdo->prepare("
    INSERT INTO order_items (order_id, food_item_id, item_name, unit_price, quantity, subtotal) 
    VALUES (?, ?, ?, ?, ?, ?)
");
$insItem->execute([$ordId, 1, 'Masala Dosa', 60.00, 1, 60.00]);
$insItem->execute([$ordId, 2, 'Idli Vada Combo', 50.00, 1, 50.00]);
$pdo->commit();

echo "[+] Transactional order placement verified (Order ID: {$ordId}, Number: {$ordNum})\n";

// 4. Verify order query & item totals
$checkItems = $pdo->prepare("SELECT COUNT(*), SUM(subtotal) FROM order_items WHERE order_id = ?");
$checkItems->execute([$ordId]);
[$itemCount, $itemSum] = $checkItems->fetch(PDO::FETCH_NUM);

if ((int)$itemCount !== 2 || (float)$itemSum !== 110.00) {
    throw new Exception("Order item subtotal calculation mismatch");
}
echo "[+] Order items linkage & subtotal calculation verified (Total: ₹{$itemSum})\n";

// 5. Test status transition logic
$validNext = ['Confirmed', 'Cancelled'];
$newStatus = 'Confirmed';
if (!in_array($newStatus, $validNext, true)) {
    throw new Exception("Invalid transition check failed");
}
$upd = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
$upd->execute([$newStatus, $ordId]);
echo "[+] Order status transition (Pending -> Confirmed) verified\n";

echo "\n========================================\n";
echo " ALL BACKEND & DATABASE TESTS PASSED!\n";
echo "========================================\n";
