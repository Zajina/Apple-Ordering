<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/inc/shop.php';

if (is_logged_in()) {
    header('Location: account.php');
    exit;
}

$errors = [];
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (!csrf_is_valid()) {
        $errors[] = 'Your form session expired. Please try again.';
    }
    if ($name === '' || mb_strlen($name) > 120) {
        $errors[] = 'Enter your name (up to 120 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($phone !== '' && mb_strlen($phone) > 30) {
        $errors[] = 'Phone number must be 30 characters or fewer.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Your password must contain at least 8 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'The passwords do not match.';
    }

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            $errors[] = 'An account with that email already exists. Please log in instead.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, phone) VALUES (:name, :email, :password_hash, :phone)'
            );
            $insert->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => $hash,
                'phone' => $phone !== '' ? $phone : null,
            ]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            merge_pending_cart((int)$pdo->lastInsertId());
            set_flash('Your KJL Collective account has been created.');
            header('Location: account.php');
            exit;
        }
    }
}

$pageTitle = 'Create Account';
require __DIR__ . '/inc/head.php';
$flash = get_flash();
?>
<section class="account-page">
  <div class="account-panel">
    <p class="account-eyebrow">WELCOME TO KJL COLLECTIVE</p>
    <h1>Create your account</h1>
    <p class="account-lead">Create an account to save your details and continue to checkout.</p>
    <?php if ($flash): ?><div class="form-notice <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="form-notice error"><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="register.php" class="account-form">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <label for="name">Full name</label>
      <input id="name" name="name" type="text" maxlength="120" autocomplete="name" value="<?= h($name) ?>" required>
      <label for="email">Email address</label>
      <input id="email" name="email" type="email" maxlength="190" autocomplete="email" value="<?= h($email) ?>" required>
      <label for="phone">Phone number <span>(optional)</span></label>
      <input id="phone" name="phone" type="tel" maxlength="30" autocomplete="tel" value="<?= h($phone) ?>">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
      <label for="confirm_password">Confirm password</label>
      <input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
      <button class="account-submit" type="submit">Create account</button>
    </form>
    <p class="account-switch">Already have an account? <a href="login.php">Log in</a></p>
  </div>
</section>
<?php require __DIR__ . '/inc/foot.php'; ?>
