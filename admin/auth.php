<?php

declare(strict_types=1);

function admin_password_hash(): string
{
    return (string) (getenv('ADMIN_PASSWORD_HASH') ?: '$2y$10$EUUPJx8CV7bVEXwU50ckl.2Dpk1Y3lZDmsh2KM5u2rfIdDsrO8q4C');
}

function admin_is_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        $next = urlencode($_SERVER['REQUEST_URI'] ?? '/admin/index.php');
        header('Location: ' . APP_URL . '/admin/login.php?next=' . $next);
        exit;
    }
}

function admin_login(string $username, string $password): bool
{
    $storedHash = admin_password_hash();

    return strcasecmp($username, 'admin') === 0 && password_verify($password, $storedHash);
}
