<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$reference = trim((string) ($_GET['ref'] ?? ''));
$stmt = getDatabase()->prepare(
    'SELECT reference, total, status, created_at
     FROM orders
     WHERE reference = :reference
     LIMIT 1'
);
$stmt->execute([':reference' => $reference]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
}

$pageTitle = $order ? 'Order received' : 'Order not found';
include __DIR__ . '/partials/header.php';
?>

<section class="container">
    <?php if ($order): ?>
        <div class="empty-state order-success">
            <span class="hero-kicker">Order received</span>
            <h1>Thank you for your order!</h1>
            <p>Your order reference is <strong><?= e($order['reference']) ?></strong>.</p>
            <p>Total: <strong><?= money((float) $order['total']) ?></strong></p>
            <a href="<?= APP_URL ?>/index.php" class="button-primary">Continue shopping</a>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h1>Order not found</h1>
            <p>We couldn’t find an order matching that reference.</p>
            <a href="<?= APP_URL ?>/index.php" class="button-primary">Back to shop</a>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
