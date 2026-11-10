<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Order lookup';
$reference = trim((string) ($_POST['reference'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$order = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($reference === '' || $email === '') {
        $errors[] = 'Enter both the order reference and email address.';
    } else {
        $stmt = getDatabase()->prepare(
            'SELECT * FROM orders WHERE reference = :reference AND LOWER(customer_email) = LOWER(:email) LIMIT 1'
        );
        $stmt->execute([
            ':reference' => $reference,
            ':email' => $email,
        ]);

        $order = $stmt->fetch();
        if (!$order) {
            $errors[] = 'No matching order was found for that reference and email.';
        }
    }
}

include __DIR__ . '/partials/header.php';
?>

<section class="container">
    <div class="section-head">
        <h2>Track your order</h2>
    </div>

    <div class="lookup-layout">
        <form method="POST" class="panel-form" action="<?= APP_URL ?>/order-lookup.php">
            <label for="reference">Order reference</label>
            <input id="reference" name="reference" type="text" value="<?= e($reference) ?>" placeholder="ORD-20261110-ABCD" required>

            <label for="email">Email address</label>
            <input id="email" name="email" type="email" value="<?= e($email) ?>" placeholder="you@example.com" required>

            <button type="submit" class="button-primary">Check status</button>
        </form>

        <?php if ($errors): ?>
            <div class="alert alert-error"><?= e($errors[0]) ?></div>
        <?php elseif ($order): ?>
            <div class="status-panel">
                <span class="hero-kicker">Order status</span>
                <h3><?= e($order['reference']) ?></h3>
                <p><strong>Status:</strong> <?= e($order['status']) ?></p>
                <p><strong>Placed:</strong> <?= e(date('d M Y, H:i', strtotime((string) $order['created_at']))) ?></p>
                <p><strong>Total:</strong> <?= money((float) $order['total']) ?></p>
                <p><strong>Customer:</strong> <?= e($order['customer_name']) ?></p>
                <p><strong>Address:</strong> <?= nl2br(e($order['delivery_address'])) ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
