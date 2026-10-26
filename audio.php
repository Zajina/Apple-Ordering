<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/shop.php';
$pageTitle = 'AirPods';
$activeCategory = 'audio';
$shownProducts = get_active_products('audio');
require __DIR__ . '/inc/head.php';
$flash = get_flash();
?>
<?php if ($flash): ?><div class="shop-flash <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>
<section class="catalog-section category-section">
  <div class="category-heading"><div><p class="eyebrow">KJL COLLECTIVE / PRODUCTS</p><h1>AirPods</h1></div><span><?= count($shownProducts) ?> item<?= count($shownProducts) === 1 ? '' : 's' ?></span></div>
  <?php require __DIR__ . '/inc/cards.php'; ?>
</section>
<?php require __DIR__ . '/inc/foot.php'; ?>
