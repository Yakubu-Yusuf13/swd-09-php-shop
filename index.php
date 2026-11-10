<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Shop';

$categorySlug = trim((string) ($_GET['category'] ?? ''));
$searchTerm = trim((string) ($_GET['q'] ?? ''));
$sort = $_GET['sort'] ?? 'newest';

$pdo = getDatabase();

$categories = $pdo->query('SELECT id, name, slug FROM categories ORDER BY name ASC')->fetchAll();

$sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1';
$params = [];

if ($categorySlug !== '') {
    $sql .= ' AND c.slug = :category_slug';
    $params[':category_slug'] = $categorySlug;
}

if ($searchTerm !== '') {
    $sql .= ' AND (p.name LIKE :search OR p.short_description LIKE :search OR p.description LIKE :search)';
    $params[':search'] = '%' . $searchTerm . '%';
}

$sql .= ' ORDER BY p.created_at DESC';

switch ($sort) {
    case 'price_asc':
        $sql = preg_replace('/ORDER BY p\.created_at DESC$/', 'ORDER BY p.price ASC', $sql) ?? $sql;
        break;
    case 'price_desc':
        $sql = preg_replace('/ORDER BY p\.created_at DESC$/', 'ORDER BY p.price DESC', $sql) ?? $sql;
        break;
    case 'newest':
    default:
        $sql = preg_replace('/ORDER BY p\.created_at DESC$/', 'ORDER BY p.created_at DESC', $sql) ?? $sql;
        break;
}

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$products = $stmt->fetchAll();

include __DIR__ . '/partials/header.php';
?>

<section class="container hero" aria-label="Featured banner">
    <div class="hero-copy">
        <span class="hero-kicker">Curated essentials</span>
        <h1>Bring home smarter products.</h1>
        <p>Discover everyday favourites from electronics, home, fashion, beauty, and groceries — all selected for comfort, quality, and value.</p>
        <div class="hero-actions">
            <a href="#products" class="button-primary">Shop now</a>
            <a href="<?= APP_URL ?>/index.php?category=groceries" class="button-secondary">Browse deals</a>
        </div>
    </div>

    <div class="hero-card">
        <img class="hero-image" src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=900&q=80" alt="Featured shopping assortment">
    </div>
</section>

<section class="container" id="products">
    <div class="section-head">
        <h2>Latest arrivals</h2>
    </div>

    <div class="filter-bar">
        <div class="filter-group">
            <label for="categoryFilter">Category</label>
            <select id="categoryFilter" onchange="window.location='<?= APP_URL ?>/index.php?<?= $searchTerm !== '' ? 'q=' . urlencode($searchTerm) . '&' : '' ?>sort=<?= urlencode($sort) ?>&category=' + this.value">
                <option value="">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['slug']) ?>" <?= $categorySlug === $category['slug'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="sortFilter">Sort by</label>
            <select id="sortFilter" onchange="window.location='<?= APP_URL ?>/index.php?<?= $categorySlug !== '' ? 'category=' . urlencode($categorySlug) . '&' : '' ?><?= $searchTerm !== '' ? 'q=' . urlencode($searchTerm) . '&' : '' ?>sort=' + this.value">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
            </select>
        </div>

        <form method="GET" class="filter-group" action="<?= APP_URL ?>/index.php">
            <?php if ($categorySlug !== ''): ?>
                <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
            <?php endif; ?>
            <?php if ($sort !== 'newest'): ?>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <?php endif; ?>
            <label for="searchInput">Search</label>
            <input id="searchInput" type="search" name="q" value="<?= e($searchTerm) ?>" placeholder="Search products...">
            <button class="button-primary" type="submit">Apply</button>
        </form>
    </div>

    <?php if ($products): ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <div class="product-image-wrap">
                        <img class="product-image" src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>">
                        <span class="product-badge category"><?= e($product['category_name']) ?></span>
                        <?php if ((int) $product['stock'] <= 0): ?>
                            <span class="product-badge out">Out of stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-body">
                        <div class="product-meta">
                            <span class="product-category"><?= e($product['category_name']) ?></span>
                            <span class="product-price"><?= money((float) $product['price']) ?></span>
                        </div>
                        <h3><?= e($product['name']) ?></h3>
                        <p><?= e($product['short_description']) ?></p>

                        <div class="product-actions">
                            <div class="product-stock">
                                <?php if ((int) $product['stock'] > 0): ?>
                                    In stock: <strong><?= (int) $product['stock'] ?></strong>
                                <?php else: ?>
                                    <span style="color: var(--danger); font-weight: 700;">Out of stock</span>
                                <?php endif; ?>
                            </div>
                            <a class="product-link" href="<?= APP_URL ?>/product.php?slug=<?= urlencode((string) $product['slug']) ?>">View item</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h3>No products matched your selection.</h3>
            <p>Try another category, a different keyword, or clear the filters.</p>
            <a href="<?= APP_URL ?>/index.php" class="button-primary">Reset filters</a>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
