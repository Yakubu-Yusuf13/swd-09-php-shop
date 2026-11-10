<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Checkout';
$errors = [];
$values = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
];
$notice = null;
$pdo = getDatabase();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) {
        $postedValue = $_POST[$key] ?? '';
        $values[$key] = is_string($postedValue) ? trim($postedValue) : '';
    }

    try {
        verify_csrf();

        if ($values['name'] === '' || mb_strlen($values['name']) > 150) {
            $errors['name'] = 'Enter your name (up to 150 characters).';
        }
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email']) > 150) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if ($values['phone'] !== '' && mb_strlen($values['phone']) > 50) {
            $errors['phone'] = 'Phone number must be 50 characters or fewer.';
        }
        if ($values['address'] === '' || mb_strlen($values['address']) > 2000) {
            $errors['address'] = 'Enter a delivery address (up to 2,000 characters).';
        }

        $cart = $_SESSION['cart'] ?? [];
        if ($cart === []) {
            $errors['cart'] = 'Your cart is empty.';
        }

        if ($errors === []) {
            $quantities = [];
            foreach ($cart as $productId => $quantity) {
                $id = filter_var($productId, FILTER_VALIDATE_INT);
                if ($id === false || $id <= 0 || !is_scalar($quantity) || !is_numeric($quantity) || (int) $quantity <= 0) {
                    throw new RuntimeException('Your cart has an invalid item. Please review your cart and try again.');
                }
                $quantities[(int) $id] = (int) $quantity;
            }
            ksort($quantities);

            $pdo->beginTransaction();
            $lockedProducts = [];
            $lockProduct = $pdo->prepare(
                'SELECT id, name, price, stock
                 FROM products
                 WHERE id = :id AND is_active = 1
                 FOR UPDATE'
            );

            foreach ($quantities as $productId => $quantity) {
                $lockProduct->execute([':id' => $productId]);
                $product = $lockProduct->fetch();
                if (!$product) {
                    throw new RuntimeException('An item in your cart is no longer available. Please review your cart.');
                }
                if ((int) $product['stock'] < $quantity) {
                    throw new RuntimeException('There is not enough stock for ' . $product['name'] . '. Please update your cart.');
                }
                $lockedProducts[$productId] = $product;
            }

            $totalCents = 0;
            foreach ($lockedProducts as $productId => $product) {
                $unitPriceCents = (int) round((float) $product['price'] * 100);
                $totalCents += $unitPriceCents * $quantities[$productId];
            }
            $total = number_format($totalCents / 100, 2, '.', '');
            $reference = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

            $insertOrder = $pdo->prepare(
                'INSERT INTO orders (
                    reference, customer_name, customer_email, customer_phone,
                    delivery_address, total, status
                ) VALUES (
                    :reference, :customer_name, :customer_email, :customer_phone,
                    :delivery_address, :total, :status
                )'
            );
            $insertOrder->execute([
                ':reference' => $reference,
                ':customer_name' => $values['name'],
                ':customer_email' => $values['email'],
                ':customer_phone' => $values['phone'] !== '' ? $values['phone'] : null,
                ':delivery_address' => $values['address'],
                ':total' => $total,
                ':status' => 'pending',
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $insertItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price)
                 VALUES (:order_id, :product_id, :product_name, :quantity, :unit_price)'
            );
            $updateStock = $pdo->prepare(
                'UPDATE products
                 SET stock = stock - ?
                 WHERE id = ? AND stock >= ?'
            );

            foreach ($lockedProducts as $productId => $product) {
                $quantity = $quantities[$productId];
                $insertItem->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $productId,
                    ':product_name' => $product['name'],
                    ':quantity' => $quantity,
                    ':unit_price' => $product['price'],
                ]);
                $updateStock->execute([$quantity, $productId, $quantity]);

                if ($updateStock->rowCount() !== 1) {
                    throw new RuntimeException('Stock changed while placing your order. Please try again.');
                }
            }

            $pdo->commit();
            cart_clear();
            header('Location: ' . APP_URL . '/order-success.php?ref=' . urlencode($reference));
            exit;
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($exception instanceof PDOException || $exception instanceof Error) {
            error_log('Checkout failed: ' . $exception->getMessage());
            $errors['checkout'] = 'We could not place your order right now. Please try again.';
        } elseif ($errors === []) {
            $errors['checkout'] = $exception->getMessage();
        }
    }
}

$items = cart_items();
foreach ($items as $item) {
    if ($item['adjusted']) {
        $notice = 'Some quantities were adjusted to current stock. Review your order before continuing.';
        break;
    }
}
$total = cart_total();

include __DIR__ . '/partials/header.php';
?>

<section class="container">
    <div class="section-head">
        <h2>Checkout</h2>
    </div>

    <?php if ($notice): ?>
        <div class="alert alert-error"><?= e($notice) ?></div>
    <?php endif; ?>

    <?php if (isset($errors['checkout'])): ?>
        <div class="alert alert-error"><?= e($errors['checkout']) ?></div>
    <?php endif; ?>

    <?php if ($items && cart_count() > 0): ?>
        <div class="checkout-layout">
            <section class="checkout-panel">
                <h3>Delivery details</h3>
                <?php if (isset($errors['cart'])): ?>
                    <div class="alert alert-error"><?= e($errors['cart']) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= APP_URL ?>/checkout.php" class="checkout-form">
                    <?= csrf_field() ?>

                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" maxlength="150" autocomplete="name" value="<?= e($values['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?><small class="field-error"><?= e($errors['name']) ?></small><?php endif; ?>

                    <label for="email">Email address</label>
                    <input id="email" name="email" type="email" maxlength="150" autocomplete="email" value="<?= e($values['email']) ?>" required>
                    <?php if (isset($errors['email'])): ?><small class="field-error"><?= e($errors['email']) ?></small><?php endif; ?>

                    <label for="phone">Phone number <span class="muted">(optional)</span></label>
                    <input id="phone" name="phone" type="tel" maxlength="50" autocomplete="tel" value="<?= e($values['phone']) ?>">
                    <?php if (isset($errors['phone'])): ?><small class="field-error"><?= e($errors['phone']) ?></small><?php endif; ?>

                    <label for="address">Delivery address</label>
                    <textarea id="address" name="address" rows="4" maxlength="2000" autocomplete="street-address" required><?= e($values['address']) ?></textarea>
                    <?php if (isset($errors['address'])): ?><small class="field-error"><?= e($errors['address']) ?></small><?php endif; ?>

                    <button class="button-primary" type="submit">Place order</button>
                </form>
            </section>

            <aside class="cart-summary">
                <h3>Order summary</h3>
                <div class="checkout-items">
                    <?php foreach ($items as $item): ?>
                        <?php if ($item['quantity'] <= 0) { continue; } ?>
                        <div class="checkout-item">
                            <span><?= e($item['name']) ?> × <?= $item['quantity'] ?></span>
                            <strong><?= money($item['line_total']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="cart-summary-row total">
                    <span>Total</span>
                    <span><?= money($total) ?></span>
                </div>
                <a href="<?= APP_URL ?>/cart.php" class="button-secondary">Back to cart</a>
            </aside>
        </div>
    <?php else: ?>
        <div class="cart-empty">
            <h3>Your cart is empty.</h3>
            <p>Add products before checking out.</p>
            <a href="<?= APP_URL ?>/index.php" class="button-primary">Browse products</a>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
