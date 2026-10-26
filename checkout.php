<?php
require_once __DIR__ . '/inc/shop.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$errors = [];
$fields = [
    'recipient_name' => trim((string)($_POST['recipient_name'] ?? $_SESSION['user_name'] ?? '')),
    'phone' => trim((string)($_POST['phone'] ?? '')),
    'address_line' => trim((string)($_POST['address_line'] ?? '')),
    'barangay' => trim((string)($_POST['barangay'] ?? '')),
    'city' => trim((string)($_POST['city'] ?? '')),
    'province' => trim((string)($_POST['province'] ?? '')),
    'postal_code' => trim((string)($_POST['postal_code'] ?? '')),
    'delivery_instructions' => trim((string)($_POST['delivery_instructions'] ?? '')),
];

$cartId = get_or_create_cart_id($userId);
$stmt = $pdo->prepare(
    'SELECT p.id, p.name, p.image, p.price, p.stock, p.is_active, ci.quantity
     FROM cart_items ci JOIN products p ON p.id = ci.product_id
     WHERE ci.cart_id = :cart_id ORDER BY ci.created_at DESC'
);
$stmt->execute(['cart_id' => $cartId]);
$items = $stmt->fetchAll();

if (!$items) {
    set_flash('Your cart is empty. Add products before checking out.', 'error');
    header('Location: cart.php');
    exit;
}

$subtotal = 0.0;
foreach ($items as $item) {
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}
$deliveryFee = $subtotal >= 50000 ? 0.00 : 250.00;
$total = $subtotal + $deliveryFee;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        $errors[] = 'Your form session expired. Please try again.';
    }
    foreach ([
        'recipient_name' => 'Enter the recipient name.',
        'phone' => 'Enter a contact number.',
        'address_line' => 'Enter your house number, street, or building.',
        'barangay' => 'Enter your barangay.',
        'city' => 'Enter your city or municipality.',
        'province' => 'Enter your province.',
    ] as $key => $message) {
        if ($fields[$key] === '') $errors[] = $message;
    }
    if (strlen($fields['phone']) > 30) $errors[] = 'Contact number must be 30 characters or fewer.';
    if (strlen($fields['delivery_instructions']) > 500) $errors[] = 'Delivery instructions must be 500 characters or fewer.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Lock products and re-check active status and stock at checkout.
            $lock = $pdo->prepare(
                'SELECT p.id, p.name, p.image, p.price, p.stock, p.is_active, ci.quantity
                 FROM cart_items ci JOIN products p ON p.id = ci.product_id
                 WHERE ci.cart_id = :cart_id FOR UPDATE'
            );
            $lock->execute(['cart_id' => $cartId]);
            $lockedItems = $lock->fetchAll();

            if (!$lockedItems) {
                throw new RuntimeException('Your cart is empty. Please add products before checking out.');
            }

            $freshSubtotal = 0.0;
            foreach ($lockedItems as $item) {
                if (!(int)$item['is_active']) {
                    throw new RuntimeException($item['name'] . ' is no longer available.');
                }
                if ((int)$item['stock'] < 1 || (int)$item['quantity'] > (int)$item['stock']) {
                    throw new RuntimeException('There is not enough stock for ' . $item['name'] . '. Please update your cart.');
                }
                $freshSubtotal += (float)$item['price'] * (int)$item['quantity'];
            }

            $freshDeliveryFee = $freshSubtotal >= 50000 ? 0.00 : 250.00;
            $freshTotal = $freshSubtotal + $freshDeliveryFee;

            $addressText = $fields['address_line'] . ', Barangay ' . $fields['barangay']
                . ', ' . $fields['city'] . ', ' . $fields['province'];
            if ($fields['postal_code'] !== '') $addressText .= ' ' . $fields['postal_code'];

            $addressStmt = $pdo->prepare(
                'INSERT INTO addresses
                 (user_id, recipient_name, phone, address_line, barangay, city, province, postal_code, delivery_instructions, is_default)
                 VALUES (:user_id, :recipient_name, :phone, :address_line, :barangay, :city, :province, :postal_code, :instructions, 1)'
            );
            $addressStmt->execute([
                'user_id' => $userId,
                'recipient_name' => $fields['recipient_name'],
                'phone' => $fields['phone'],
                'address_line' => $fields['address_line'],
                'barangay' => $fields['barangay'],
                'city' => $fields['city'],
                'province' => $fields['province'],
                'postal_code' => $fields['postal_code'] !== '' ? $fields['postal_code'] : null,
                'instructions' => $fields['delivery_instructions'] !== '' ? $fields['delivery_instructions'] : null,
            ]);
            $addressId = (int)$pdo->lastInsertId();

            $orderNumber = 'KJL-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $orderStmt = $pdo->prepare(
                "INSERT INTO orders
                 (order_number, user_id, address_id, status, payment_method, subtotal, delivery_fee, total,
                  recipient_name, recipient_phone, delivery_address, delivery_instructions)
                 VALUES (:order_number, :user_id, :address_id, 'Pending', 'COD', :subtotal, :delivery_fee, :total,
                  :recipient_name, :recipient_phone, :delivery_address, :instructions)"
            );
            $orderStmt->execute([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'address_id' => $addressId,
                'subtotal' => $freshSubtotal,
                'delivery_fee' => $freshDeliveryFee,
                'total' => $freshTotal,
                'recipient_name' => $fields['recipient_name'],
                'recipient_phone' => $fields['phone'],
                'delivery_address' => $addressText,
                'instructions' => $fields['delivery_instructions'] !== '' ? $fields['delivery_instructions'] : null,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items
                 (order_id, product_id, product_name, product_image, unit_price, quantity, line_total)
                 VALUES (:order_id, :product_id, :product_name, :product_image, :unit_price, :quantity, :line_total)'
            );
            $stockStmt = $pdo->prepare(
                'UPDATE products SET stock = stock - :quantity WHERE id = :product_id AND stock >= :check_quantity'
            );

            foreach ($lockedItems as $item) {
                $quantity = (int)$item['quantity'];
                $price = (float)$item['price'];
                $lineTotal = $price * $quantity;
                $stockStmt->execute([
                    'quantity' => $quantity,
                    'product_id' => (int)$item['id'],
                    'check_quantity' => $quantity,
                ]);
                if ($stockStmt->rowCount() !== 1) {
                    throw new RuntimeException('Stock changed while placing your order. Please try again.');
                }
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => (int)$item['id'],
                    'product_name' => $item['name'],
                    'product_image' => $item['image'],
                    'unit_price' => $price,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ]);
            }

            $clear = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
            $clear->execute(['cart_id' => $cartId]);

            $pdo->commit();
            $_SESSION['last_order_number'] = $orderNumber;
            header('Location: order_success.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'We could not place your order. Please try again.';
        }
    }
}

$pageTitle = 'Checkout';
require __DIR__ . '/inc/head.php';
?>
<section class="shop-page">
  <div class="shop-page-heading">
    <p class="eyebrow">KJL COLLECTIVE</p>
    <h1>Checkout</h1>
    <p>Enter your delivery details and review your order.</p>
  </div>

  <?php if ($errors): ?>
    <div class="shop-flash error"><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <div class="shop-layout checkout-layout">
    <div class="shop-items">
      <form method="post" action="checkout.php" class="account-form checkout-form">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <h2>Delivery information</h2>

        <label for="recipient_name">Recipient name</label>
        <input id="recipient_name" name="recipient_name" maxlength="120" value="<?= h($fields['recipient_name']) ?>" required>

        <label for="phone">Contact number</label>
        <input id="phone" name="phone" type="tel" maxlength="30" autocomplete="tel" value="<?= h($fields['phone']) ?>" required>

        <label for="address_line">House number, street, or building</label>
        <input id="address_line" name="address_line" maxlength="255" autocomplete="street-address" value="<?= h($fields['address_line']) ?>" required>

        <label for="barangay">Barangay</label>
        <input id="barangay" name="barangay" maxlength="120" value="<?= h($fields['barangay']) ?>" required>

        <label for="city">City / Municipality</label>
        <input id="city" name="city" maxlength="120" value="<?= h($fields['city']) ?>" required>

        <label for="province">Province</label>
        <input id="province" name="province" maxlength="120" value="<?= h($fields['province']) ?>" required>

        <label for="postal_code">Postal code (optional)</label>
        <input id="postal_code" name="postal_code" maxlength="20" value="<?= h($fields['postal_code']) ?>">

        <label for="delivery_instructions">Delivery instructions (optional)</label>
        <textarea id="delivery_instructions" name="delivery_instructions" maxlength="500" rows="3"><?= h($fields['delivery_instructions']) ?></textarea>

        <h2>Payment method</h2>
        <div class="payment-option"><strong>Cash on Delivery (COD)</strong><p>Pay the courier when your order arrives.</p></div>

        <button class="shop-primary" type="submit">Place order</button>
      </form>
    </div>

    <aside class="order-summary">
      <h2>Order summary</h2>
      <?php foreach ($items as $item): ?>
        <div class="checkout-summary-item">
          <span><?= h($item['name']) ?> × <?= (int)$item['quantity'] ?></span>
          <strong>₱<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></strong>
        </div>
      <?php endforeach; ?>
      <div><span>Subtotal</span><strong>₱<?= number_format($subtotal, 2) ?></strong></div>
      <div><span>Delivery fee</span><strong><?= $deliveryFee > 0 ? '₱' . number_format($deliveryFee, 2) : 'FREE' ?></strong></div>
      <div class="summary-total"><span>Total</span><strong>₱<?= number_format($total, 2) ?></strong></div>
      <p class="summary-note">Delivery is ₱250 for orders below ₱50,000 and free for orders of ₱50,000 or more.</p>
      <a class="shop-secondary" href="cart.php">Back to cart</a>
    </aside>
  </div>
</section>
<?php require __DIR__ . '/inc/foot.php'; ?>
