CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(32) NOT NULL UNIQUE,
    customer_name VARCHAR(150) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(50) DEFAULT NULL,
    delivery_address TEXT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id),
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categories (name, slug) VALUES
    ('Electronics', 'electronics'),
    ('Home & Living', 'home-living'),
    ('Fashion', 'fashion'),
    ('Health & Beauty', 'health-beauty'),
    ('Groceries', 'groceries');

INSERT IGNORE INTO products (
    category_id, slug, name, short_description, description, price, stock, image_url, is_active
)
SELECT
    c.id, catalog_seed.slug, catalog_seed.name, catalog_seed.short_description,
    catalog_seed.description, catalog_seed.price, catalog_seed.stock, catalog_seed.image_url, 1
FROM (
    SELECT 'electronics' AS category_slug, 'wireless-bluetooth-speaker' AS slug, 'Wireless Bluetooth Speaker' AS name, 'Portable sound with rich bass and all-day battery.' AS short_description, 'Perfect for home, office, and outdoor listening with deep bass, durable design, and Bluetooth pairing.' AS description, 24500.00 AS price, 24 AS stock, 'https://placehold.co/600x400?text=Bluetooth+Speaker' AS image_url
    UNION ALL SELECT 'electronics', 'smart-watch-pro', 'Smart Watch Pro', 'Track workouts, sleep, and notifications with ease.', 'A modern smartwatch for daily fitness tracking, notifications, and stylish everyday wear.', 68000.00, 15, 'https://placehold.co/600x400?text=Smart+Watch'
    UNION ALL SELECT 'electronics', 'usb-c-fast-charger', 'USB-C Fast Charger', 'High-speed charging for phones and tablets.', 'Built for modern devices that need safe and efficient charging throughout the day.', 12000.00, 36, 'https://placehold.co/600x400?text=Fast+Charger'
    UNION ALL SELECT 'home-living', 'minimalist-lamp', 'Minimalist Desk Lamp', 'Warm lighting with a modern finish.', 'Brighten your workspace with soft, adjustable lighting designed for comfort and style.', 18500.00, 13, 'https://placehold.co/600x400?text=Desk+Lamp'
    UNION ALL SELECT 'home-living', 'cotton-bath-linen-set', 'Cotton Bath Linen Set', 'Soft towels and robes for a fresh daily ritual.', 'Ultra-soft and absorbent linen set made to provide a premium feel for your bathroom.', 21000.00, 18, 'https://placehold.co/600x400?text=Bath+Linen'
    UNION ALL SELECT 'home-living', 'ceramic-storage-jar', 'Ceramic Storage Jar', 'A stylish kitchen essential for pantry organization.', 'Keep ingredients fresh and elevate your kitchen counter with neat, modern storage.', 9000.00, 42, 'https://placehold.co/600x400?text=Storage+Jar'
    UNION ALL SELECT 'fashion', 'classic-denim-jacket', 'Classic Denim Jacket', 'Lightweight layering for casual everyday wear.', 'Comfortable and versatile staple piece designed for cool afternoons and city outings.', 38000.00, 9, 'https://placehold.co/600x400?text=Denim+Jacket'
    UNION ALL SELECT 'fashion', 'leather-strap-watch', 'Leather Strap Watch', 'Elegant timepiece with a premium leather band.', 'A refined wrist accessory suitable for workdays, events, and smart casual styling.', 54000.00, 11, 'https://placehold.co/600x400?text=Leather+Watch'
    UNION ALL SELECT 'fashion', 'travel-backpack', 'Travel Backpack', 'Functional storage for work, study, or weekend escapes.', 'Thoughtfully designed with padded compartments, water resistance, and practical storage.', 31000.00, 17, 'https://placehold.co/600x400?text=Travel+Backpack'
    UNION ALL SELECT 'health-beauty', 'vitamin-c-serum', 'Vitamin C Glow Serum', 'Daily skin-brightening support with hydration.', 'Helps nourish tired skin and improves radiance for a fresher, healthier look.', 16000.00, 28, 'https://placehold.co/600x400?text=Glow+Serum'
    UNION ALL SELECT 'health-beauty', 'electric-face-brush', 'Electric Face Brush', 'Gentle cleansing for smoother-looking skin.', 'A compact, easy-to-use facial care helper for deep cleansing and exfoliation.', 22500.00, 12, 'https://placehold.co/600x400?text=Face+Brush'
    UNION ALL SELECT 'health-beauty', 'aroma-diffuser', 'Aroma Diffuser', 'Create a calm, fresh atmosphere at home.', 'Enjoy a soothing fragrance experience with an elegant diffuser built for everyday relaxation.', 26000.00, 14, 'https://placehold.co/600x400?text=Aroma+Diffuser'
    UNION ALL SELECT 'groceries', 'organic-rice-bag', 'Organic Rice Bag', 'Premium staple for healthy home meals.', 'Naturally grown and carefully packed for families who want a dependable pantry staple.', 11000.00, 48, 'https://placehold.co/600x400?text=Organic+Rice'
    UNION ALL SELECT 'groceries', 'olive-oil-bottle', 'Extra Virgin Olive Oil', 'Rich, smooth finishing oil for your kitchen.', 'Adds flavor and depth to everyday cooking with a premium, cold-pressed finish.', 17000.00, 19, 'https://placehold.co/600x400?text=Olive+Oil'
    UNION ALL SELECT 'groceries', 'fruit-box', 'Fresh Fruit Box', 'Seasonal fruits picked for freshness and sweetness.', 'A colorful assortment of fruit delivered fresh to help you add nutrition to your routine.', 14500.00, 0, 'https://placehold.co/600x400?text=Fruit+Box'
) AS catalog_seed
INNER JOIN categories c ON c.slug = catalog_seed.category_slug;
