<?php
require_once __DIR__ . '/inc/shop.php';
require_login();

$stmt = $pdo->prepare(
    'SELECT id, order_number, status, payment_method, subtotal, delivery_fee, total,
            recipient_name, recipient_phone, delivery_address, created_at
     FROM orders WHERE user_id = :user_id ORDER BY created_at DESC'
);
$stmt->execute(['user_id' => (int)$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders';
require __DIR__ . '/inc/head.php';
$flash = get_flash();
?>
<section class="shop-page">
  <div class="shop-page-heading">
    <p class="eyebrow">KJL COLLECTIVE</p>
    <h1>My orders</h1>
    <p>Review your order history and current order statuses.</p>
  </div>
  <?php if ($flash): ?><div class="shop-flash <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>
  <?php if (!$orders): ?>
    <div class="empty-state"><h2>No orders yet</h2><p>Your completed checkouts will appear here.</p><a class="shop-primary" href="index.php">Browse products</a></div>
  <?php else: ?>
    <div class="order-history">
      <?php foreach ($orders as $order): ?>
        <article class="order-history-card">
          <div class="order-history-top">
            <div><p class="eyebrow">ORDER NUMBER</p><h2><?= h($order['order_number']) ?></h2><p><?= h(date('M j, Y · g:i A', strtotime($order['created_at']))) ?></p></div>
            <span class="order-status"><?= h($order['status']) ?></span>
          </div>
          <div class="order-history-details">
            <p><strong>Recipient:</strong> <?= h($order['recipient_name']) ?></p>
            <p><strong>Contact:</strong> <?= h($order['recipient_phone']) ?></p>
            <p><strong>Delivery address:</strong> <?= h($order['delivery_address']) ?></p>
            <p><strong>Payment:</strong> Cash on Delivery</p>
            <p><strong>Subtotal:</strong> ₱<?= number_format((float)$order['subtotal'], 2) ?></p>
            <p><strong>Delivery fee:</strong> ₱<?= number_format((float)$order['delivery_fee'], 2) ?></p>
            <p class="order-history-total"><strong>Total:</strong> ₱<?= number_format((float)$order['total'], 2) ?></p>
          </div>
          <?php
            $itemStmt = $pdo->prepare('SELECT product_name, product_image, unit_price, quantity, line_total FROM order_items WHERE order_id = :order_id ORDER BY id');
            $itemStmt->execute(['order_id' => (int)$order['id']]);
            $orderItems = $itemStmt->fetchAll();
          ?>
          <div class="order-history-items">
            <h3>Items ordered</h3>
            <?php foreach ($orderItems as $item): ?>
              <div class="order-history-item">
                <img src="img/<?= h($item['product_image']) ?>" alt="<?= h($item['product_name']) ?>">
                <span><?= h($item['product_name']) ?> × <?= (int)$item['quantity'] ?></span>
                <strong>₱<?= number_format((float)$item['line_total'], 2) ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/inc/foot.php'; ?>
