<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/shop.php';
require_login();
$userId = (int)$_SESSION['user_id'];
$cartId = get_or_create_cart_id($userId);
$stmt = $pdo->prepare('SELECT p.id, p.name, p.image, p.price, p.stock, p.is_active, ci.quantity, (p.price * ci.quantity) AS line_total FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.cart_id = :cart_id ORDER BY ci.created_at DESC');
$stmt->execute(['cart_id' => $cartId]);
$items = $stmt->fetchAll();
$subtotal = 0.0;
foreach ($items as $item) $subtotal += (float)$item['line_total'];
$pageTitle = 'Shopping Cart';
require __DIR__ . '/inc/head.php';
$flash = get_flash();
?>
<section class="shop-page">
  <div class="shop-page-heading"><p class="eyebrow">KJL COLLECTIVE</p><h1>Your shopping cart</h1><p>Review your selected items before checkout.</p></div>
  <?php if ($flash): ?><div class="shop-flash <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>
  <?php if (!$items): ?>
    <div class="empty-state"><h2>Your cart is empty</h2><p>Explore our collection and add something you love.</p><a class="shop-primary" href="index.php">Browse products</a></div>
  <?php else: ?>
  <div class="shop-layout"><div class="shop-items">
    <?php foreach ($items as $item): ?>
    <article class="shop-line-item">
      <img src="img/<?= h($item['image']) ?>" alt="<?= h($item['name']) ?>">
      <div class="shop-line-info"><h2><?= h($item['name']) ?></h2><p>₱<?= number_format((float)$item['price'],2) ?> each</p><p class="shop-stock"><?= (int)$item['stock'] > 0 ? ((int)$item['stock'].' available') : 'Out of stock' ?></p>
        <div class="line-controls">
          <form method="post" action="shop_action.php" class="quantity-form"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="cart_update"><input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>"><label for="qty<?= (int)$item['id'] ?>">Qty</label><input id="qty<?= (int)$item['id'] ?>" name="quantity" type="number" min="1" max="<?= max(1,(int)$item['stock']) ?>" value="<?= (int)$item['quantity'] ?>" required><button type="submit" class="small-action">Update</button></form>
          <form method="post" action="shop_action.php"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="cart_remove"><input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>"><button type="submit" class="text-action">Remove</button></form>
        </div>
      </div>
      <strong class="line-total">₱<?= number_format((float)$item['line_total'],2) ?></strong>
    </article>
    <?php endforeach; ?>
  </div><aside class="order-summary"><h2>Order summary</h2><div><span>Subtotal</span><strong>₱<?= number_format($subtotal,2) ?></strong></div><div><span>Delivery fee</span><span>Calculated at checkout</span></div><p class="summary-note">Prices and available stock are checked again during checkout.</p><a class="shop-primary" href="checkout.php">Proceed to checkout</a><a class="shop-secondary" href="index.php">Continue shopping</a></aside></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/inc/foot.php'; ?>
