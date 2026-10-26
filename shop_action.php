<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/shop.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid()) {
    http_response_code(400);
    exit('Invalid request. Please go back and try again.');
}
$action = (string)($_POST['action'] ?? '');
$productId = (int)($_POST['product_id'] ?? 0);
$returnTo = (string)($_POST['return_to'] ?? 'index.php');
if (!preg_match('/^(?!\/|\\\\|[a-z]+:)[a-zA-Z0-9_\/.?=&%-]*$/i', $returnTo)) $returnTo = 'index.php';
$product = $productId > 0 ? get_product($productId) : null;
if (!$product) {
    set_flash('That product could not be found.', 'error');
    header('Location: ' . $returnTo); exit;
}

if ($action === 'cart_add') {
    if (!is_logged_in()) {
        $_SESSION['pending_cart_product_id'] = $productId;
        $_SESSION['redirect_after_login'] = 'cart.php';
        set_flash('Please log in or create an account to add items to your cart.', 'error');
        header('Location: login.php?required=1'); exit;
    }
    $message = add_product_to_cart((int)$_SESSION['user_id'], $productId, 1);
    set_flash($message, str_contains($message, 'added') || str_contains($message, 'updated') ? 'success' : 'error');
} elseif ($action === 'wishlist_add') {
    if (is_logged_in()) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (:user_id, :product_id)');
        $stmt->execute(['user_id' => (int)$_SESSION['user_id'], 'product_id' => $productId]);
        set_flash($stmt->rowCount() ? 'Added to your wishlist.' : 'This product is already in your wishlist.');
    } else {
        if (!isset($_SESSION['guest_wishlist']) || !is_array($_SESSION['guest_wishlist'])) $_SESSION['guest_wishlist'] = [];
        if (!in_array($productId, $_SESSION['guest_wishlist'], true)) $_SESSION['guest_wishlist'][] = $productId;
        set_flash('Saved to your wishlist. Log in later to keep it in your account.');
    }
} elseif ($action === 'wishlist_remove') {
    if (is_logged_in()) {
        $stmt = $pdo->prepare('DELETE FROM wishlists WHERE user_id = :user_id AND product_id = :product_id');
        $stmt->execute(['user_id' => (int)$_SESSION['user_id'], 'product_id' => $productId]);
    } else {
        $_SESSION['guest_wishlist'] = array_values(array_filter($_SESSION['guest_wishlist'] ?? [], fn($id) => (int)$id !== $productId));
    }
    set_flash('Removed from your wishlist.');
} elseif ($action === 'cart_remove') {
    require_login();
    $cartId = get_or_create_cart_id((int)$_SESSION['user_id']);
    $stmt = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id');
    $stmt->execute(['cart_id' => $cartId, 'product_id' => $productId]);
    set_flash('Item removed from your cart.');
} elseif ($action === 'cart_update') {
    require_login();
    $quantity = (int)($_POST['quantity'] ?? 1);
    $cartId = get_or_create_cart_id((int)$_SESSION['user_id']);
    $stmt = $pdo->prepare('SELECT p.stock FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.cart_id = :cart_id AND ci.product_id = :product_id');
    $stmt->execute(['cart_id' => $cartId, 'product_id' => $productId]);
    $stock = $stmt->fetchColumn();
    if ($stock === false) {
        set_flash('Cart item not found.', 'error');
    } elseif ((int)$stock < 1) {
        $del = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id');
        $del->execute(['cart_id' => $cartId, 'product_id' => $productId]);
        set_flash('The item was removed because it is out of stock.', 'error');
    } else {
        $quantity = max(1, min($quantity, (int)$stock));
        $up = $pdo->prepare('UPDATE cart_items SET quantity = :quantity WHERE cart_id = :cart_id AND product_id = :product_id');
        $up->execute(['quantity' => $quantity, 'cart_id' => $cartId, 'product_id' => $productId]);
        set_flash('Cart quantity updated.');
    }
} else {
    set_flash('Unknown action.', 'error');
}
header('Location: ' . ($action === 'cart_remove' || $action === 'cart_update' ? 'cart.php' : $returnTo));
exit;
