<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'This script can only be run from the command line.';
    exit(1);
}

$pdo = getDatabase();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_id INT UNSIGNED NOT NULL,
        slug VARCHAR(150) NOT NULL UNIQUE,
        name VARCHAR(150) NOT NULL,
        short_description VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        image_url VARCHAR(255) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(150) NOT NULL,
        customer_email VARCHAR(150) NOT NULL,
        customer_phone VARCHAR(50) DEFAULT NULL,
        delivery_address TEXT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS order_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$categories = [
    ['name' => 'Electronics', 'slug' => 'electronics'],
    ['name' => 'Home & Living', 'slug' => 'home-living'],
    ['name' => 'Fashion', 'slug' => 'fashion'],
    ['name' => 'Health & Beauty', 'slug' => 'health-beauty'],
    ['name' => 'Groceries', 'slug' => 'groceries'],
];

$insertCategory = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug) ON DUPLICATE KEY UPDATE name = VALUES(name)');
$categoryIds = [];
foreach ($categories as $category) {
    $insertCategory->execute($category);
    $categoryIds[$category['slug']] = (int) $pdo->lastInsertId();
}

$products = [
    ['category_slug' => 'electronics', 'slug' => 'wireless-bluetooth-speaker', 'name' => 'Wireless Bluetooth Speaker', 'short_description' => 'Portable sound with rich bass and all-day battery.', 'description' => 'Perfect for home, office, and outdoor listening with deep bass, durable design, and Bluetooth pairing.', 'price' => 24500, 'stock' => 24, 'image_url' => 'https://placehold.co/600x400?text=Bluetooth+Speaker'],
    ['category_slug' => 'electronics', 'slug' => 'smart-watch-pro', 'name' => 'Smart Watch Pro', 'short_description' => 'Track workouts, sleep, and notifications with ease.', 'description' => 'A modern smartwatch for daily fitness tracking, notifications, and stylish everyday wear.', 'price' => 68000, 'stock' => 15, 'image_url' => 'https://placehold.co/600x400?text=Smart+Watch'],
    ['category_slug' => 'electronics', 'slug' => 'usb-c-fast-charger', 'name' => 'USB-C Fast Charger', 'short_description' => 'High-speed charging for phones and tablets.', 'description' => 'Built for modern devices that need safe and efficient charging throughout the day.', 'price' => 12000, 'stock' => 36, 'image_url' => 'https://placehold.co/600x400?text=Fast+Charger'],
    ['category_slug' => 'home-living', 'slug' => 'minimalist-lamp', 'name' => 'Minimalist Desk Lamp', 'short_description' => 'Warm lighting with a modern finish.', 'description' => 'Brighten your workspace with soft, adjustable lighting designed for comfort and style.', 'price' => 18500, 'stock' => 13, 'image_url' => 'https://placehold.co/600x400?text=Desk+Lamp'],
    ['category_slug' => 'home-living', 'slug' => 'cotton-bath-linen-set', 'name' => 'Cotton Bath Linen Set', 'short_description' => 'Soft towels and robes for a fresh daily ritual.', 'description' => 'Ultra-soft and absorbent linen set made to provide a premium feel for your bathroom.', 'price' => 21000, 'stock' => 18, 'image_url' => 'https://placehold.co/600x400?text=Bath+Linen'],
    ['category_slug' => 'home-living', 'slug' => 'ceramic-storage-jar', 'name' => 'Ceramic Storage Jar', 'short_description' => 'A stylish kitchen essential for pantry organization.', 'description' => 'Keep ingredients fresh and elevate your kitchen counter with neat, modern storage.', 'price' => 9000, 'stock' => 42, 'image_url' => 'https://placehold.co/600x400?text=Storage+Jar'],
    ['category_slug' => 'fashion', 'slug' => 'classic-denim-jacket', 'name' => 'Classic Denim Jacket', 'short_description' => 'Lightweight layering for casual everyday wear.', 'description' => 'Comfortable and versatile staple piece designed for cool afternoons and city outings.', 'price' => 38000, 'stock' => 9, 'image_url' => 'https://placehold.co/600x400?text=Denim+Jacket'],
    ['category_slug' => 'fashion', 'slug' => 'leather-strap-watch', 'name' => 'Leather Strap Watch', 'short_description' => 'Elegant timepiece with a premium leather band.', 'description' => 'A refined wrist accessory suitable for workdays, events, and smart casual styling.', 'price' => 54000, 'stock' => 11, 'image_url' => 'https://placehold.co/600x400?text=Leather+Watch'],
    ['category_slug' => 'fashion', 'slug' => 'travel-backpack', 'name' => 'Travel Backpack', 'short_description' => 'Functional storage for work, study, or weekend escapes.', 'description' => 'Thoughtfully designed with padded compartments, water resistance, and practical storage.', 'price' => 31000, 'stock' => 17, 'image_url' => 'https://placehold.co/600x400?text=Travel+Backpack'],
    ['category_slug' => 'health-beauty', 'slug' => 'vitamin-c-serum', 'name' => 'Vitamin C Glow Serum', 'short_description' => 'Daily skin-brightening support with hydration.', 'description' => 'Helps nourish tired skin and improves radiance for a fresher, healthier look.', 'price' => 16000, 'stock' => 28, 'image_url' => 'https://placehold.co/600x400?text=Glow+Serum'],
    ['category_slug' => 'health-beauty', 'slug' => 'electric-face-brush', 'name' => 'Electric Face Brush', 'short_description' => 'Gentle cleansing for smoother-looking skin.', 'description' => 'A compact, easy-to-use facial care helper for deep cleansing and exfoliation.', 'price' => 22500, 'stock' => 12, 'image_url' => 'https://placehold.co/600x400?text=Face+Brush'],
    ['category_slug' => 'health-beauty', 'slug' => 'aroma-diffuser', 'name' => 'Aroma Diffuser', 'short_description' => 'Create a calm, fresh atmosphere at home.', 'description' => 'Enjoy a soothing fragrance experience with an elegant diffuser built for everyday relaxation.', 'price' => 26000, 'stock' => 14, 'image_url' => 'https://placehold.co/600x400?text=Aroma+Diffuser'],
    ['category_slug' => 'groceries', 'slug' => 'organic-rice-bag', 'name' => 'Organic Rice Bag', 'short_description' => 'Premium staple for healthy home meals.', 'description' => 'Naturally grown and carefully packed for families who want a dependable pantry staple.', 'price' => 11000, 'stock' => 48, 'image_url' => 'https://placehold.co/600x400?text=Organic+Rice'],
    ['category_slug' => 'groceries', 'slug' => 'olive-oil-bottle', 'name' => 'Extra Virgin Olive Oil', 'short_description' => 'Rich, smooth finishing oil for your kitchen.', 'description' => 'Adds flavor and depth to everyday cooking with a premium, cold-pressed finish.', 'price' => 17000, 'stock' => 19, 'image_url' => 'https://placehold.co/600x400?text=Olive+Oil'],
    ['category_slug' => 'groceries', 'slug' => 'fruit-box', 'name' => 'Fresh Fruit Box', 'short_description' => 'Seasonal fruits picked for freshness and sweetness.', 'description' => 'A colorful assortment of fruit delivered fresh to help you add nutrition to your routine.', 'price' => 14500, 'stock' => 0, 'image_url' => 'https://placehold.co/600x400?text=Fruit+Box'],
];

$insertProduct = $pdo->prepare('
    INSERT INTO products (
        category_id, slug, name, short_description, description, price, stock, image_url, is_active
    ) VALUES (
        :category_id, :slug, :name, :short_description, :description, :price, :stock, :image_url, :is_active
    ) ON DUPLICATE KEY UPDATE
        category_id = VALUES(category_id),
        name = VALUES(name),
        short_description = VALUES(short_description),
        description = VALUES(description),
        price = VALUES(price),
        stock = VALUES(stock),
        image_url = VALUES(image_url),
        is_active = VALUES(is_active)
');

foreach ($products as $product) {
    $insertProduct->execute([
        'category_id' => $categoryIds[$product['category_slug']],
        'slug' => $product['slug'],
        'name' => $product['name'],
        'short_description' => $product['short_description'],
        'description' => $product['description'],
        'price' => (float) $product['price'],
        'stock' => (int) $product['stock'],
        'image_url' => $product['image_url'],
        'is_active' => 1,
    ]);
}

fwrite(STDOUT, "Seeded " . count($products) . " products across " . count($categories) . " categories.\n");
