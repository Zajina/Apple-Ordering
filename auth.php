<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function h(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'account.php';
        header('Location: login.php?required=1');
        exit;
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_is_valid(): bool {
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function set_flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash(): ?array {
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
