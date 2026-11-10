<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/auth.php';

admin_require_login();

$pageTitle = 'Admin dashboard';
$pdo = getDatabase();

$todaySales = $pdo->query(
    "SELECT COALESCE(SUM(total), 0) AS total
     FROM orders
     WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'"
)->fetch();

$statusBreakdown = $pdo->query(
    "SELECT status, COUNT(*) AS total
     FROM orders
     GROUP BY status
     ORDER BY FIELD(status, 'pending', 'paid', 'shipped', 'delivered', 'cancelled')"
)->fetchAll();

$topProducts = $pdo->query(
    "SELECT p.name, SUM(oi.quantity) AS quantity_sold
     FROM order_items oi
     INNER JOIN products p ON p.id = oi.product_id
     GROUP BY oi.product_id, p.name
     ORDER BY quantity_sold DESC
     LIMIT 5"
)->fetchAll();

include __DIR__ . '/../partials/header.php';
?>

<section class="container admin-shell">
    <div class="admin-header">
        <div>
            <span class="hero-kicker">Dashboard</span>
            <h1>Store overview</h1>
        </div>
        <div class="admin-actions">
            <a href="<?= APP_URL ?>/admin/orders.php" class="button-secondary">Manage orders</a>
            <a href="<?= APP_URL ?>/admin/logout.php" class="button-danger">Log out</a>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="stat-card highlight">
            <span>Today's sales</span>
            <strong><?= money((float) ($todaySales['total'] ?? 0)) ?></strong>
        </div>

        <?php foreach ($statusBreakdown as $row): ?>
            <div class="stat-card">
                <span><?= e(ucfirst((string) $row['status'])) ?></span>
                <strong><?= (int) $row['total'] ?></strong>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="panel-box">
        <h3>Top 5 products by quantity sold</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Units sold</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($topProducts): ?>
                    <?php foreach ($topProducts as $product): ?>
                        <tr>
                            <td><?= e($product['name']) ?></td>
                            <td><?= (int) $product['quantity_sold'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2" class="empty-cell">No sales yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
