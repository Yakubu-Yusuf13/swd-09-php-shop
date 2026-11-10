<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/auth.php';

admin_require_login();

$pageTitle = 'Admin orders';
$pdo = getDatabase();

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$dateFilter = trim((string) ($_GET['date'] ?? ''));

$sql = 'SELECT o.id, o.reference, o.customer_name, o.customer_email, o.status, o.total, o.created_at,
               COUNT(oi.id) AS item_count
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE 1 = 1';
$params = [];

if ($statusFilter !== '') {
    $sql .= ' AND o.status = :status';
    $params[':status'] = $statusFilter;
}

if ($dateFilter !== '') {
    $sql .= ' AND DATE(o.created_at) = :date';
    $params[':date'] = $dateFilter;
}

$sql .= ' GROUP BY o.id, o.reference, o.customer_name, o.customer_email, o.status, o.total, o.created_at ORDER BY o.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
include __DIR__ . '/../partials/header.php';
?>

<section class="container admin-shell">
    <div class="admin-header">
        <div>
            <span class="hero-kicker">Orders</span>
            <h1>Order management</h1>
        </div>
        <div class="admin-actions">
            <a href="<?= APP_URL ?>/admin/index.php" class="button-secondary">Dashboard</a>
            <a href="<?= APP_URL ?>/admin/logout.php" class="button-danger">Log out</a>
        </div>
    </div>

    <form method="GET" class="admin-filter-bar">
        <div class="filter-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="date">Date</label>
            <input id="date" name="date" type="date" value="<?= e($dateFilter) ?>">
        </div>

        <button type="submit" class="button-primary">Filter</button>
        <a href="<?= APP_URL ?>/admin/orders.php" class="button-secondary">Clear</a>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Items</th>
                    <th>Placed</th>
                    <th>View</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders): ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><a href="<?= APP_URL ?>/admin/order.php?id=<?= (int) $order['id'] ?>"><?= e($order['reference']) ?></a></td>
                            <td>
                                <div><?= e($order['customer_name']) ?></div>
                                <small><?= e($order['customer_email']) ?></small>
                            </td>
                            <td><span class="status-badge status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                            <td><?= money((float) $order['total']) ?></td>
                            <td><?= (int) $order['item_count'] ?></td>
                            <td><?= e(date('d M Y', strtotime((string) $order['created_at']))) ?></td>
                            <td><a href="<?= APP_URL ?>/admin/order.php?id=<?= (int) $order['id'] ?>" class="link-button">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-cell">No orders match the current filters.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
