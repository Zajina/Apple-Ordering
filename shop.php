
<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Get active products
|--------------------------------------------------------------------------
| Supports category filtering and case-insensitive product searching.
*/
function get_active_products(
    string $category = '',
    string $search = ''
): array {
    global $pdo;

    $sql = '
        SELECT
            id,
            product_code,
            name,
            category,
            image,
            description,
            price,
            stock
        FROM products
        WHERE is_active = 1
    ';

    $params = [];

    if ($category !== '') {
        $sql .= ' AND category = :category';
        $params['category'] = $category;
    }

    if ($search !== '') {
        $sql .= '
            AND (
                LOWER(name) LIKE :search_name
                OR LOWER(description) LIKE :search_description
                OR LOWER(product_code) LIKE :search_code
            )
        ';

        $searchTerm = '%' . strtolower(trim($search)) . '%';

        $params['search_name'] = $searchTerm;
        $params['search_description'] = $searchTerm;
        $params['search_code'] = $searchTerm;
    }

    $sql .= ' ORDER BY id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| Get one active product
|--------------------------------------------------------------------------
*/
function get_product(int $id): ?array {
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT
            id,
            product_code,
            name,
            category,
            image,
            description,
            price,
            stock
         FROM products
         WHERE id = :id
           AND is_active = 1
         LIMIT 1'
    );

    $stmt->execute(['id' => $id]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    return $product ?: null;
}

/*
|--------------------------------------------------------------------------
| Get or create a user's shopping cart
|--------------------------------------------------------------------------
*/
function get_or_create_cart_id(int $userId): int {
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT id
         FROM carts
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $stmt->execute(['user_id' => $userId]);

    $cartId = $stmt->fetchColumn();

    if ($cartId !== false) {
        return (int) $cartId;
    }

    $insert = $pdo->prepare(
        'INSERT INTO carts (user_id)
         VALUES (:user_id)'
    );

    $insert->execute(['user_id' => $userId]);

    return (int) $pdo->lastInsertId();
}

/*
|--------------------------------------------------------------------------
| Add a product to the shopping cart
|--------------------------------------------------------------------------
*/
function add_product_to_cart(
    int $userId,
    int $productId,
    int $quantity = 1
): string {
    global $pdo;

    $product = get_product($productId);

    if (!$product) {
        return 'That product is no longer available.';
    }

    $stock = (int) $product['stock'];

    if ($stock < 1) {
        return 'This product is currently out of stock.';
    }

    $cartId = get_or_create_cart_id($userId);

    $stmt = $pdo->prepare(
        'SELECT quantity
         FROM cart_items
         WHERE cart_id = :cart_id
           AND product_id = :product_id
         LIMIT 1'
    );

    $stmt->execute([
        'cart_id' => $cartId,
        'product_id' => $productId,
    ]);

    $existing = $stmt->fetchColumn();

    $currentQuantity = ($existing !== false)
        ? (int) $existing
        : 0;

    $newQuantity = min(
        $stock,
        $currentQuantity + max(1, $quantity)
    );

    if ($existing !== false) {
        $update = $pdo->prepare(
            'UPDATE cart_items
             SET quantity = :quantity
             WHERE cart_id = :cart_id
               AND product_id = :product_id'
        );

        $update->execute([
            'quantity' => $newQuantity,
            'cart_id' => $cartId,
            'product_id' => $productId,
        ]);
    } else {
        $insert = $pdo->prepare(
            'INSERT INTO cart_items
                (cart_id, product_id, quantity)
             VALUES
                (:cart_id, :product_id, :quantity)'
        );

        $insert->execute([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $newQuantity,
        ]);
    }

    return ($existing !== false)
        ? 'Cart quantity updated.'
        : 'Product added to your cart.';
}

/*
|--------------------------------------------------------------------------
| Merge a guest wishlist into the user's account
|--------------------------------------------------------------------------
*/
function merge_guest_wishlist(int $userId): void {
    global $pdo;

    $ids = $_SESSION['guest_wishlist'] ?? [];

    if (!is_array($ids) || empty($ids)) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO wishlists (user_id, product_id)
         SELECT :user_id, id
         FROM products
         WHERE id = :product_id
           AND is_active = 1'
    );

    foreach (array_unique(array_map('intval', $ids)) as $id) {
        if ($id > 0) {
            $stmt->execute([
                'user_id' => $userId,
                'product_id' => $id,
            ]);
        }
    }

    unset($_SESSION['guest_wishlist']);
}

/*
|--------------------------------------------------------------------------
| Merge pending cart and wishlist items after login
|--------------------------------------------------------------------------
*/
function merge_pending_cart(int $userId): void {
    $id = (int) ($_SESSION['pending_cart_product_id'] ?? 0);

    if ($id > 0) {
        $message = add_product_to_cart($userId, $id, 1);

        unset($_SESSION['pending_cart_product_id']);

        $type = (
            strpos($message, 'added') !== false ||
            strpos($message, 'updated') !== false
        ) ? 'success' : 'error';

        set_flash($message, $type);
    }

    merge_guest_wishlist($userId);
}
