
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'KJL Collective';
$activeCategory = $activeCategory ?? '';
$searchTerm = trim($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | KJL Collective</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="inc/account.css">
  <link rel="stylesheet" href="inc/shop.css">
  <script src="script.js" defer></script>
</head>
<body>
<header class="site-head">
  <div class="brand-row">
    <a class="star-logo" href="index.php" aria-label="KJL Collective home">
      <svg viewBox="0 0 70 70" aria-hidden="true">
        <path d="M35 5 43 27 66 25 48 40 55 63 35 49 15 63 22 40 4 25 27 27Z" fill="none" stroke="currentColor" stroke-width="4"/>
        <path d="M35 15 39 31 54 30 42 40 46 54 34 45 23 54 27 40 16 31 31 31Z" fill="currentColor"/>
      </svg>
    </a>

    <a class="brand" href="index.php">
      <span class="brand-script">kjl collective</span>
      <span class="brand-credit">CHEY VILLAMOR&nbsp; | &nbsp;NICOLETTE LAMSON&nbsp; | &nbsp;ANN BAYTO</span>
    </a>

    <div class="head-actions">
      <form class="search-form" action="index.php" method="get">
        <input
          type="search"
          name="q"
          placeholder="Search products"
          value=""
          aria-label="Search products"
          autocomplete="off"
        >
        <button class="icon-btn search-btn" type="submit" aria-label="Search">
          <svg viewBox="0 0 40 40">
            <circle cx="17" cy="17" r="12"/>
            <path d="m26 26 10 10"/>
          </svg>
        </button>
      </form>

      <a class="icon-btn" href="wish.php" aria-label="Wishlist">
        <svg viewBox="0 0 40 40">
          <path d="M20 35 5 20C-4 9 10-1 20 10 30-1 44 9 35 20Z"/>
        </svg>
      </a>

      <a class="icon-btn" href="cart.php" aria-label="Shopping cart">
        <svg viewBox="0 0 40 40">
          <path d="M8 13h24l2 23H6Z"/>
          <path d="M14 14V10a6 6 0 0 1 12 0v4"/>
        </svg>
      </a>

      <div class="account-wrap">
        <button class="icon-btn account-toggle" type="button" aria-label="Account menu" aria-expanded="false">
          <svg viewBox="0 0 40 40">
            <circle cx="20" cy="12" r="8"/>
            <path d="M5 37c0-10 6-15 15-15s15 5 15 15Z"/>
          </svg>
        </button>

        <div class="account-menu">
          <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="account.php">My account</a>
            <a href="orders.php">My orders</a>
            <a href="logout.php">Log out</a>
          <?php else: ?>
            <a href="login.php">Log in</a>
            <a href="register.php">Create account</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <nav class="main-nav" aria-label="Product categories">
    <?php foreach (($categoryNames ?? [
        'iphone' => 'iPhone',
        'ipad' => 'iPad',
        'mac' => 'MacBook',
        'watch' => 'Watch',
        'audio' => 'AirPods',
        'access' => 'Accessories'
    ]) as $key => $label): ?>
      <a
        class="<?= $activeCategory === $key ? 'active' : '' ?>"
        href="<?= htmlspecialchars([
            'iphone' => 'iphone.php',
            'ipad' => 'ipad.php',
            'mac' => 'mac.php',
            'watch' => 'watch.php',
            'audio' => 'audio.php',
            'access' => 'access.php'
        ][$key]) ?>"
      ><?= htmlspecialchars($label) ?></a>
    <?php endforeach; ?>
  </nav>
</header>
<main>
