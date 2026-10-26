
<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/inc/shop.php';

if (is_logged_in()) {
    header('Location: account.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_is_valid()) {
        $errors[] = 'Your form session expired. Please try again.';
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $password === ''
    ) {
        $errors[] = 'Enter your email address and password.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, name, email, password_hash
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (
            !$user ||
            !password_verify($password, $user['password_hash'])
        ) {
            $errors[] = 'The email or password is incorrect.';
        } else {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            merge_pending_cart((int) $user['id']);

            $destination = $_SESSION['redirect_after_login'] ?? 'account.php';
            unset($_SESSION['redirect_after_login']);

            if (
                !is_string($destination) ||
                $destination === '' ||
                !preg_match(
                    '~^(?!/|\\\\|[a-z]+:)[a-zA-Z0-9_./?=&%-]+$~i',
                    $destination
                ) ||
                str_contains($destination, '..')
            ) {
                $destination = 'account.php';
            }

            set_flash('Welcome back, ' . $user['name'] . '.');

            header('Location: ' . $destination);
            exit;
        }
    }
}

$pageTitle = 'Log In';

require __DIR__ . '/inc/head.php';

$flash = get_flash();
?>

<section class="account-page">
    <div class="account-panel">

        <p class="account-eyebrow">KJL COLLECTIVE</p>

        <h1>Log in</h1>

        <p class="account-lead">
            Sign in to manage your account and continue shopping.
        </p>

        <?php if ($flash): ?>
            <div class="form-notice <?= h($flash['type']) ?>">
                <?= h($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="form-notice error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php" class="account-form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= h(csrf_token()) ?>"
            >

            <label for="email">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                maxlength="190"
                autocomplete="email"
                value="<?= h($email) ?>"
                required
            >

            <label for="password">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
            >

            <button class="account-submit" type="submit">
                Log in
            </button>

        </form>

        <p class="account-switch">
            New to KJL Collective?
            <a href="register.php">Create an account</a>
        </p>

        <p class="account-back">
            <a href="index.php">Continue browsing products</a>
        </p>

    </div>
</section>

<?php require __DIR__ . '/inc/foot.php'; ?>
