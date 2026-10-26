<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/shop.php';
$pageTitle = 'Home';
$searchTerm = trim((string)($_GET['q'] ?? ''));
$shownProducts = get_active_products('', $searchTerm);
require __DIR__ . '/inc/head.php';
$flash = get_flash();
?>
<?php if ($flash): ?><div class="shop-flash <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>
<?php if ($searchTerm === ''): ?>
<section class="catalog-section home-section">
  <div class="section-intro"><p class="eyebrow">KJL COLLECTIVE</p><h1>Thoughtfully chosen. Made for you.</h1><p>Explore Apple devices and everyday essentials.</p></div>
  <?php require __DIR__ . '/inc/cards.php'; ?>
</section>
<?php else: ?>
<section class="catalog-section category-section"><div class="category-heading"><div><p class="eyebrow">SEARCH RESULTS</p><h1>Results for “<?= h($searchTerm) ?>”</h1></div><span><?= count($shownProducts) ?> product<?= count($shownProducts) === 1 ? '' : 's' ?></span></div><?php require __DIR__ . '/inc/cards.php'; ?></section>
<?php endif; ?>
<?php require __DIR__ . '/inc/foot.php'; ?>
