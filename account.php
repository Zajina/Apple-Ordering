<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/db.php';

$stmt = $pdo->prepare('SELECT id, name, email, phone, created_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => (int)$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}
$pageTitle = 'My Account';
require __DIR__ . '/inc/head.php';
$flash = get_flash();
?>
<section class="account-page">
  <div class="account-panel account-panel-wide">
    <p class="account-eyebrow">YOUR KJL COLLECTIVE ACCOUNT</p>
    <h1>My account</h1>
    <?php if ($flash): ?><div class="form-notice <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>
    <p class="account-lead">You are signed in. Your account details are shown below.</p>
    <div class="profile-list">
      <div><span>Full name</span><strong><?= h($user['name']) ?></strong></div>
      <div><span>Email address</span><strong><?= h($user['email']) ?></strong></div>
      <div><span>Phone number</span><strong><?= h($user['phone'] ?: 'Not provided') ?></strong></div>
      <div><span>Member since</span><strong><?= h(date('F j, Y', strtotime($user['created_at']))) ?></strong></div>
    </div>
    <div class="account-links">
      <a class="account-submit link-button" href="orders.php">View my orders</a>
      <a class="secondary-button" href="index.php">Continue shopping</a>
      <a class="secondary-button" href="logout.php">Log out</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/inc/foot.php'; ?>
