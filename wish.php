<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/shop.php';
if (is_logged_in()) {
    merge_guest_wishlist((int)$_SESSION['user_id']);
    $stmt = $pdo->prepare('SELECT p.id,p.name,p.image,p.description,p.price,p.stock FROM wishlists w JOIN products p ON p.id=w.product_id WHERE w.user_id=:user_id AND p.is_active=1 ORDER BY w.created_at DESC');
    $stmt->execute(['user_id'=>(int)$_SESSION['user_id']]);
    $items = $stmt->fetchAll();
} else {
    $items = [];
    foreach (array_reverse($_SESSION['guest_wishlist'] ?? []) as $id) {
        $product = get_product((int)$id);
        if ($product) $items[] = $product;
    }
}
$pageTitle = 'Wishlist';
require __DIR__ . '/inc/head.php';
$flash = get_flash();
?>
<section class="shop-page">
  <div class="shop-page-heading"><p class="eyebrow">KJL COLLECTIVE</p><h1>Your wishlist</h1><p>Keep the products you love in one place.</p></div>
  <?php if ($flash): ?><div class="shop-flash <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>
  <?php if (!is_logged_in() && $items): ?><p class="guest-wishlist-note">Your favorites are saved in this browser session. Log in or create an account to keep them in your account.</p><?php endif; ?>
  <?php if (!$items): ?><div class="empty-state"><h2>Your wishlist is empty</h2><p>Tap the heart on a product to save it here.</p><a class="shop-primary" href="index.php">Browse products</a></div>
  <?php else: ?><div class="wishlist-grid">
    <?php foreach ($items as $item): ?><article class="wishlist-card"><img src="img/<?= h($item['image']) ?>" alt="<?= h($item['name']) ?>"><div><h2><?= h($item['name']) ?></h2><p><?= h($item['description']) ?></p><strong>₱<?= number_format((float)$item['price'],2) ?></strong><div class="wishlist-actions">
      <form method="post" action="shop_action.php"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="cart_add"><input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="return_to" value="wish.php"><button class="small-action" type="submit">Add to cart</button></form>
      <form method="post" action="shop_action.php"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="wishlist_remove"><input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="return_to" value="wish.php"><button class="text-action" type="submit">Remove</button></form>
    </div></div></article><?php endforeach; ?>
  </div><?php endif; ?>
</section>
<?php require __DIR__ . '/inc/foot.php'; ?>
