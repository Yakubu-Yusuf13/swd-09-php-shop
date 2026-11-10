<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Cart';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $action = (string) ($_POST['action'] ?? '');
        $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        $adjusted = false;

        switch ($action) {
            case 'update':
                if ($productId === false || $productId === null || $productId <= 0) {
                    throw new RuntimeException('Invalid product.');
                }

                $adjusted = cart_update($productId, (int) ($_POST['quantity'] ?? 1));
                break;

            case 'remove':
                if ($productId === false || $productId === null || $productId <= 0) {
                    throw new RuntimeException('Invalid product.');
                }

                cart_remove($productId);
                break;

            case 'clear':
                cart_clear();
                break;

            default:
                throw new RuntimeException('Invalid cart action.');
        }

        $message = match ($action) {
            'clear' => 'Your cart has been cleared.',
            'remove' => 'Product removed from your cart.',
            default => $adjusted
                ? 'Quantity adjusted to the available stock.'
                : 'Cart updated.',
        };
        flash($message, 'success');
        header('Location: ' . APP_URL . '/cart.php');
        exit;
    } catch (Throwable $e) {
        flash($e->getMessage(), 'error');
    }
}

$items = cart_items();
if (array_filter($items, static fn (array $item): bool => $item['adjusted'])) {
    flash('Some cart quantities were adjusted to match current stock.', 'error');
}
$total = cart_total();

include __DIR__ . '/partials/header.php';
?>

<section class="container">
    <div class="section-head">
        <h2>Your cart</h2>
    </div>

    <?php if ($items): ?>
        <div class="cart-layout">
            <div class="cart-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Unit price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="cart-product">
                                        <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>">
                                        <div>
                                            <strong><?= e($item['name']) ?></strong>
                                            <small><?= e($item['short_description']) ?></small>
                                            <?php if (!$item['stock_ok']): ?>
                                                <span class="stock-warning">Out of stock</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= money($item['price']) ?></td>
                                <td>
                                    <?php if ($item['stock_ok']): ?>
                                        <form method="POST" action="<?= APP_URL ?>/cart.php" class="cart-item-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                            <input
                                                type="number"
                                                name="quantity"
                                                min="1"
                                                max="<?= $item['stock'] ?>"
                                                value="<?= $item['quantity'] ?>"
                                                aria-label="Quantity for <?= e($item['name']) ?>"
                                            >
                                            <button type="submit" name="action" value="update" class="button-small">Update</button>
                                            <button type="submit" name="action" value="remove" class="button-small button-remove">Remove</button>
                                        </form>
                                    <?php else: ?>
                                        <span>—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= money($item['line_total']) ?></td>
                                <td>
                                    <?php if (!$item['stock_ok']): ?>
                                        <form method="POST" action="<?= APP_URL ?>/cart.php">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                            <button type="submit" name="action" value="remove" class="button-small button-remove">Remove</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
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
                    <span>Subtotal</span>
                    <span><?= money($total) ?></span>
                </div>
                <div class="cart-summary-row total">
                    <span>Total</span>
                    <span><?= money($total) ?></span>
                </div>
                <div class="cart-actions">
                    <a href="<?= APP_URL ?>/index.php" class="button-secondary">Continue shopping</a>
                    <?php if ($total > 0): ?>
                        <a href="<?= APP_URL ?>/checkout.php" class="button-primary">Proceed to checkout</a>
                    <?php endif; ?>
                    <form method="POST" action="<?= APP_URL ?>/cart.php">
                        <?= csrf_field() ?>
                        <button type="submit" name="action" value="clear" class="button-danger">Clear cart</button>
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
