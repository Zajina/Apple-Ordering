<?php
require_once __DIR__ . '/inc/shop.php';
require_login();

$orderNumber = (string)($_SESSION['last_order_number'] ?? '');
unset($_SESSION['last_order_number']);

if ($orderNumber === '') {
    header('Location: orders.php');
    exit;
}

$stmt = $pdo->prepare('SELECT order_number, status, payment_method, subtotal, delivery_fee, total, recipient_name, recipient_phone, delivery_address, created_at FROM orders WHERE order_number = :number AND user_id = :user_id LIMIT 1');
$stmt->execute(['number' => $orderNumber, 'user_id' => (int)$_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

$pageTitle = 'Order Placed';
require __DIR__ . '/inc/head.php';
?>
<section class="shop-page">
  <div class="empty-state order-success">
    <p class="eyebrow">KJL COLLECTIVE</p>
    <h1>Thank you for your order!</h1>
    <p>Your order has been placed successfully.</p>
    <p><strong>Order number: <?= h($order['order_number']) ?></strong></p>
    <p>Status: <?= h($order['status']) ?></p>
    <p>Payment: Cash on Delivery</p>
    <div class="success-total"><span>Order total</span><strong>₱<?= number_format((float)$order['total'], 2) ?></strong></div>
    <a class="shop-primary" href="orders.php">View my orders</a>
    <a class="shop-secondary" href="index.php">Continue shopping</a>
  </div>
</section>
<?php require __DIR__ . '/inc/foot.php'; ?>
