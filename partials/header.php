<?php
$flash = flash();
$brandName = 'Swd Shop';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) : 'Swd Shop' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/styles.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="<?= APP_URL ?>/index.php" class="brand">
                <span class="brand-mark">S</span>
                <span><?= e($brandName) ?></span>
            </a>

            <nav class="main-nav" aria-label="Main navigation">
                <a href="<?= APP_URL ?>/index.php">Home</a>
                <a href="<?= APP_URL ?>/index.php#products">Shop</a>
                <a href="<?= APP_URL ?>/cart.php">Cart</a>
            </nav>

            <div class="header-actions">
                <form method="GET" action="<?= APP_URL ?>/index.php" class="mini-search">
                    <input type="search" name="q" value="<?= isset($_GET['q']) ? e($_GET['q']) : '' ?>" placeholder="Search products...">
                    <button type="submit">Search</button>
                </form>
                <a href="<?= APP_URL ?>/cart.php" class="cart-button" aria-label="Cart">
                    <span>Cart</span>
                    <span class="cart-badge"><?= cart_count() ?></span>
                </a>
            </div>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="container">
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        </div>
    <?php endif; ?>

    <main class="site-main">
