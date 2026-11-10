<?php

declare(strict_types=1);

function cart_product(int $productId): ?array
{
    $stmt = getDatabase()->prepare(
        'SELECT id, name, short_description, price, stock, image_url
         FROM products
         WHERE id = :id AND is_active = 1
         LIMIT 1'
    );
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();

    return $product ?: null;
}

function cart_add(int $id, int $qty): bool
{
    $product = cart_product($id);
    if ($product === null) {
        throw new RuntimeException('This product is no longer available.');
    }

    $stock = max(0, (int) $product['stock']);
    if ($stock === 0) {
        throw new RuntimeException('This product is out of stock.');
    }

    $current = $_SESSION['cart'][$id] ?? 0;
    $currentQty = is_scalar($current) && is_numeric($current) ? max(0, (int) $current) : 0;
    $requestedQty = $currentQty + max(1, $qty);
    $adjusted = $requestedQty > $stock;
    $newQty = min($requestedQty, $stock);

    $_SESSION['cart'][$id] = $newQty;

    return $adjusted;
}

function cart_update(int $id, int $qty): bool
{
    $product = cart_product($id);
    if ($product === null) {
        cart_remove($id);
        throw new RuntimeException('This product is no longer available.');
    }

    $stock = max(0, (int) $product['stock']);
    if ($stock === 0) {
        cart_remove($id);

        return true;
    }

    $requestedQty = max(1, $qty);
    $newQty = min($requestedQty, $stock);
    $_SESSION['cart'][$id] = $newQty;

    return $newQty !== $requestedQty;
}

function cart_remove(int $id): void
{
    unset($_SESSION['cart'][$id]);
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

function cart_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    $ids = [];

    foreach ($cart as $id => $quantity) {
        $productId = filter_var($id, FILTER_VALIDATE_INT);
        if ($productId !== false && $productId > 0 && is_scalar($quantity) && is_numeric($quantity) && (int) $quantity > 0) {
            $ids[] = (int) $productId;
        } else {
            unset($_SESSION['cart'][$id]);
        }
    }

    if ($ids === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = getDatabase()->prepare(
        'SELECT id, name, short_description, price, stock, image_url
         FROM products
         WHERE id IN (' . $placeholders . ') AND is_active = 1'
    );
    $stmt->execute($ids);
    $products = $stmt->fetchAll();
    $lines = [];
    $foundIds = [];

    foreach ($products as $product) {
        $id = (int) $product['id'];
        $foundIds[$id] = true;
        $stock = max(0, (int) $product['stock']);
        $requestedQty = max(0, (int) ($_SESSION['cart'][$id] ?? 0));
        $quantity = min($requestedQty, $stock);
        $adjusted = $quantity !== $requestedQty;

        if ($quantity === 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id] = $quantity;
        }

        $price = (float) $product['price'];
        $lines[] = [
            'product_id' => $id,
            'name' => $product['name'],
            'short_description' => $product['short_description'],
            'image_url' => $product['image_url'],
            'quantity' => $quantity,
            'price' => $price,
            'line_total' => $price * $quantity,
            'stock' => $stock,
            'stock_ok' => $quantity > 0 && $quantity <= $stock,
            'adjusted' => $adjusted,
        ];
    }

    foreach ($ids as $id) {
        if (!isset($foundIds[$id])) {
            unset($_SESSION['cart'][$id]);
        }
    }

    return $lines;
}

function cart_total(): float
{
    $total = 0.0;
    foreach (cart_items() as $item) {
        $total += $item['line_total'];
    }

    return $total;
}

function cart_count(): int
{
    $count = 0;
    foreach ($_SESSION['cart'] ?? [] as $quantity) {
        if (is_scalar($quantity) && is_numeric($quantity)) {
            $count += max(0, (int) $quantity);
        }
    }

    return $count;
}
