<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));

if ($slug === '') {
    http_response_code(404);
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$pdo = getDatabase();
$stmt = $pdo->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    INNER JOIN categories c ON c.id = p.category_id
    WHERE p.slug = :slug AND p.is_active = 1
    LIMIT 1');
$stmt->execute([':slug' => $slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    include __DIR__ . '/partials/header.php';
    echo '<div class="container"><div class="empty-state"><h2>Product not found</h2><p>The item you are looking for is no longer available.</p><a class="button-primary" href="' . APP_URL . '/index.php">Back to shop</a></div></div>';
    include __DIR__ . '/partials/footer.php';
    exit;
}

$pageTitle = $product['name'];

$availableCount = max(0, (int) $product['stock']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $qty = max(1, (int) ($_POST['quantity'] ?? 1));
        $qty = min($qty, $availableCount > 0 ? $availableCount : 1);

        if ($availableCount <= 0) {
            throw new RuntimeException('This product is out of stock.');
        }

        add_to_cart((int) $product['id'], $qty);
        flash('Product added to cart successfully.', 'success');
        header('Location: ' . APP_URL . '/cart.php');
        exit;
    } catch (Throwable $e) {
        flash($e->getMessage(), 'error');
    }
}

include __DIR__ . '/partials/header.php';
?>

<section class="container product-layout">
    <div class="product-gallery">
        <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>">
    </div>

    <div class="product-detail">
        <span class="eyebrow"><?= e($product['category_name']) ?></span>
        <h1><?= e($product['name']) ?></h1>
        <div class="product-detail-price"><?= money((float) $product['price']) ?></div>
        <p><?= e($product['description']) ?></p>

        <div class="qty-form">
            <form method="POST" action="<?= APP_URL ?>/product.php?slug=<?= urlencode((string) $product['slug']) ?>" style="display: flex; flex-wrap: wrap; align-items: center; gap: 14px;">
                <?= csrf_field() ?>
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity" min="1" max="<?= $availableCount ?>" value="1" <?= $availableCount <= 0 ? 'disabled' : '' ?>>
                <button type="submit" class="button-primary" <?= $availableCount <= 0 ? 'disabled' : '' ?>><?= $availableCount <= 0 ? 'Out of stock' : 'Add to cart' ?></button>
            </form>
        </div>

        <div style="margin-top: 18px; color: var(--muted); font-weight: 600;">
            <?php if ($availableCount > 0): ?>
                Available: <?= $availableCount ?> in stock
            <?php else: ?>
                <span style="color: var(--danger);">Out of stock</span>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
