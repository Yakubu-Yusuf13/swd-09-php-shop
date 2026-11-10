<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/auth.php';

admin_require_login();

$pageTitle = 'Order details';
$pdo = getDatabase();
$orderId = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);

if ($orderId === false || $orderId <= 0) {
    http_response_code(404);
    include __DIR__ . '/../partials/header.php';
    echo '<section class="container"><div class="empty-state"><h1>Order not found</h1><a href="' . APP_URL . '/admin/orders.php" class="button-primary">Back to orders</a></div></section>';
    include __DIR__ . '/../partials/footer.php';
    exit;
}

$orderStmt = $pdo->prepare(
    'SELECT * FROM orders WHERE id = :id LIMIT 1'
);
$orderStmt->execute([':id' => $orderId]);
$order = $orderStmt->fetch();

if (!$order) {
    http_response_code(404);
    include __DIR__ . '/../partials/header.php';
    echo '<section class="container"><div class="empty-state"><h1>Order not found</h1><a href="' . APP_URL . '/admin/orders.php" class="button-primary">Back to orders</a></div></section>';
    include __DIR__ . '/../partials/footer.php';
    exit;
}

$itemsStmt = $pdo->prepare(
    'SELECT product_id, product_name, quantity, unit_price, (quantity * unit_price) AS line_total
     FROM order_items
     WHERE order_id = :order_id
     ORDER BY id ASC'
);
$itemsStmt->execute([':order_id' => $orderId]);
$items = $itemsStmt->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $newStatus = trim((string) ($_POST['status'] ?? ''));
        $allowedStatuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            throw new RuntimeException('Invalid status update.');
        }

        if ($newStatus === $order['status']) {
            $_SESSION['flash'] = ['message' => 'No changes were needed.', 'type' => 'info'];
            header('Location: ' . APP_URL . '/admin/order.php?id=' . $orderId);
            exit;
        }

        if ($newStatus === 'cancelled' && $order['status'] !== 'cancelled') {
            $pdo->beginTransaction();
            $cancelledItems = $pdo->prepare(
                'SELECT product_id, quantity FROM order_items WHERE order_id = :order_id FOR UPDATE'
            );
            $cancelledItems->execute([':order_id' => $orderId]);
            $rows = $cancelledItems->fetchAll();

            foreach ($rows as $row) {
                $pdo->prepare(
                    'UPDATE products SET stock = stock + :quantity WHERE id = :product_id'
                )->execute([
                    ':quantity' => (int) $row['quantity'],
                    ':product_id' => (int) $row['product_id'],
                ]);
            }

            $updateOrder = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
            $updateOrder->execute([':status' => 'cancelled', ':id' => $orderId]);
            $pdo->commit();

            $_SESSION['flash'] = ['message' => 'Order cancelled and stock restored.', 'type' => 'success'];
            header('Location: ' . APP_URL . '/admin/order.php?id=' . $orderId);
            exit;
        }

        $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id')->execute([
            ':status' => $newStatus,
            ':id' => $orderId,
        ]);

        $_SESSION['flash'] = ['message' => 'Order status updated.', 'type' => 'success'];
        header('Location: ' . APP_URL . '/admin/order.php?id=' . $orderId);
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = $e->getMessage();
    }
}

$order['status'] = (string) $order['status'];

include __DIR__ . '/../partials/header.php';
?>

<section class="container admin-shell">
    <div class="admin-header">
        <div>
            <span class="hero-kicker">Order details</span>
            <h1><?= e($order['reference']) ?></h1>
        </div>
        <div class="admin-actions">
            <a href="<?= APP_URL ?>/admin/orders.php" class="button-secondary">Back to orders</a>
            <a href="<?= APP_URL ?>/admin/logout.php" class="button-danger">Log out</a>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error"><?= e($errors[0]) ?></div>
    <?php endif; ?>

    <div class="detail-grid">
        <div class="panel-box">
            <h3>Customer</h3>
            <p><strong>Name:</strong> <?= e($order['customer_name']) ?></p>
            <p><strong>Email:</strong> <?= e($order['customer_email']) ?></p>
            <p><strong>Phone:</strong> <?= e($order['customer_phone'] ?? '—') ?></p>
            <p><strong>Address:</strong><br><?= nl2br(e($order['delivery_address'])) ?></p>
        </div>

        <div class="panel-box">
            <h3>Order summary</h3>
            <p><strong>Status:</strong> <span class="status-badge status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></p>
            <p><strong>Placed:</strong> <?= e(date('d M Y, H:i', strtotime((string) $order['created_at']))) ?></p>
            <p><strong>Total:</strong> <?= money((float) $order['total']) ?></p>

            <form method="POST" class="status-form" action="<?= APP_URL ?>/admin/order.php?id=<?= (int) $orderId ?>">
                <?= csrf_field() ?>
                <label for="status">Update status</label>
                <select id="status" name="status">
                    <?php foreach (['pending', 'paid', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button-primary">Save status</button>
            </form>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit price</th>
                    <th>Line total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?></td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td><?= money((float) $item['unit_price']) ?></td>
                        <td><?= money((float) $item['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
