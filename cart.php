<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Cart';

$pdo = getDatabase();
$cart = $_SESSION['cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $action = $_POST['action'] ?? ''; 
        if ($action === 'clear') {
            empty_cart();
            flash('Your cart has been cleared.', 'success');
            header('Location: ' . APP_URL . '/cart.php');
            exit;
        }
    } catch (Throwable $e) {
        flash($e->getMessage(), 'error');
    }
}

$products = [];
$total = 0.0;

if ($cart) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id IN (' . $placeholders . ') AND is_active = 1');
    $stmt->execute(array_map('intval', $ids));
    $products = $stmt->fetchAll();

    foreach ($products as $product) {
        $qty = (int) ($cart[(string) $product['id']]['qty'] ?? 0);
        if ($qty <= 0) {
            continue;
        }

        $subtotal = (float) $product['price'] * $qty;
        $total += $subtotal;
    }
}

include __DIR__ . '/partials/header.php';
?>

<section class="container">
    <div class="section-head">
        <h2>Your cart</h2>
    </div>

    <?php if ($cart): ?>
        <div style="display:grid; grid-template-columns: 1.35fr 0.65fr; gap: 24px; align-items:start;">
            <div class="cart-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <?php
                                $qty = (int) ($cart[(string) $product['id']]['qty'] ?? 0);
                                if ($qty <= 0) { continue; }
                                $subtotal = (float) $product['price'] * $qty;
                            ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" style="width:72px; height:72px; object-fit:cover; border-radius:12px;">
                                        <div>
                                            <strong><?= e($product['name']) ?></strong><br>
                                            <small><?= e($product['short_description']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= money((float) $product['price']) ?></td>
                                <td><?= $qty ?></td>
                                <td><?= money($subtotal) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <aside class="cart-summary">
                <h3>Order summary</h3>
                <div class="cart-summary-row">
                    <span>Items</span>
                    <span><?= cart_count() ?></span>
                </div>
                <div class="cart-summary-row">
                    <span>Delivery</span>
                    <span>₦0.00</span>
                </div>
                <div class="cart-summary-row total">
                    <span>Total</span>
                    <span><?= money($total) ?></span>
                </div>

                <div class="cart-actions">
                    <a href="<?= APP_URL ?>/index.php" class="button-secondary">Continue shopping</a>
                    <form method="POST" action="<?= APP_URL ?>/cart.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="button-danger">Clear cart</button>
                    </form>
                </div>
            </aside>
        </div>
    <?php else: ?>
        <div class="cart-empty">
            <h3>Your cart is empty.</h3>
            <p>Add a few essentials from the shop and come back here.</p>
            <a href="<?= APP_URL ?>/index.php" class="button-primary">Browse products</a>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
