<?php if (empty($shownProducts)): ?>
  <p class="no-results">No products found. Try another search or category.</p>
<?php else: ?>
  <div class="product-grid">
  <?php foreach ($shownProducts as $product): ?>
    <article class="product-card">
      <div class="product-image">
        <img src="img/<?= h($product['image']) ?>" alt="<?= h($product['name']) ?>" loading="lazy">
      </div>
      <div class="product-info">
        <h2><?= h($product['name']) ?></h2>
        <div class="product-bottom">
          <p><?= h($product['description']) ?></p>
          <div class="product-actions">
            <form method="post" action="shop_action.php" class="inline-action">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="wishlist_add">
              <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
              <input type="hidden" name="return_to" value="<?= h($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
              <button class="mini-btn wish-btn" type="submit" aria-label="Save <?= h($product['name']) ?> to wishlist" title="Add to wishlist"><svg viewBox="0 0 40 40"><path d="M20 35 5 20C-4 9 10-1 20 10 30-1 44 9 35 20Z"/></svg></button>
            </form>
            <form method="post" action="shop_action.php" class="inline-action">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="cart_add">
              <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
              <input type="hidden" name="return_to" value="<?= h($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
              <button class="mini-btn cart-btn" type="submit" aria-label="Add <?= h($product['name']) ?> to cart" title="Add to cart"><svg viewBox="0 0 40 40"><path d="M5 8h5l4 23h19l4-17H12"/><circle cx="17" cy="36" r="2"/><circle cx="31" cy="36" r="2"/><path d="M23 12v10m-5-5h10"/></svg></button>
            </form>
          </div>
        </div>
        <p class="product-price">₱<?= number_format((float)$product['price'], 2) ?></p>
        <p class="product-stock"><?= (int)$product['stock'] > 0 ? ((int)$product['stock'] . ' in stock') : 'Out of stock' ?></p>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
