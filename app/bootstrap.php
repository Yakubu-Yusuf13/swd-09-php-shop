<?php

declare(strict_types=1);

set_exception_handler(static function (Throwable $exception): void {
    error_log('Unhandled application exception: ' . $exception);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Temporarily unavailable</title><body><main><h1>Temporarily unavailable</h1><p>We could not load the shop right now. Please try again later.</p></main></body></html>';
});

$redisSessionDsn = getenv('REDIS_SESSION_DSN');
if (getenv('VERCEL') === '1' && (!is_string($redisSessionDsn) || $redisSessionDsn === '')) {
    throw new RuntimeException('Set REDIS_SESSION_DSN in the Vercel project environment variables.');
}
if (is_string($redisSessionDsn) && $redisSessionDsn !== '') {
    if (!extension_loaded('redis')) {
        throw new RuntimeException('The Redis extension is required when REDIS_SESSION_DSN is configured.');
    }

    ini_set('session.save_handler', 'redis');
    ini_set('session.save_path', $redisSessionDsn);
}

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (getenv('VERCEL') === '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$configuredAppUrl = getenv('APP_URL');
if ($configuredAppUrl !== false) {
    $appUrl = rtrim($configuredAppUrl, '/');
} else {
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $isLocalHost = $host === ''
        || $host === 'localhost'
        || str_starts_with($host, 'localhost:')
        || $host === '127.0.0.1'
        || str_starts_with($host, '127.0.0.1:');
    $appUrl = $isLocalHost ? '/swd/09-php-shop' : '';
}
define('APP_URL', $appUrl);

function getDatabase(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbname = getenv('DB_NAME') ?: 'swd_shop';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $temporarySslCaPath = null;
    $sslCaContents = getenv('DB_SSL_CA');
    $sslCaPath = getenv('DB_SSL_CA_PATH');
    if (is_string($sslCaContents) && $sslCaContents !== '') {
        if (!defined('PDO::MYSQL_ATTR_SSL_CA')) {
            throw new RuntimeException('PDO MySQL SSL support is required when DB_SSL_CA is configured.');
        }

        $temporarySslCaPath = tempnam(sys_get_temp_dir(), 'swd-shop-ca-');
        if ($temporarySslCaPath === false) {
            throw new RuntimeException('Could not write the configured MySQL CA certificate.');
        }

        if (file_put_contents($temporarySslCaPath, $sslCaContents, LOCK_EX) === false) {
            unlink($temporarySslCaPath);
            throw new RuntimeException('Could not write the configured MySQL CA certificate.');
        }
        $sslCaPath = $temporarySslCaPath;
    }

    if (is_string($sslCaPath) && $sslCaPath !== '') {
        if (!defined('PDO::MYSQL_ATTR_SSL_CA')) {
            throw new RuntimeException('PDO MySQL SSL support is required when DB_SSL_CA_PATH is configured.');
        }
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCaPath;

        if (!defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            throw new RuntimeException('PDO MySQL certificate verification is unavailable.');
        }
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    try {
        $dsn = getenv('DB_DSN');
        if (!is_string($dsn) || $dsn === '') {
            $configuredHost = getenv('DB_HOST');
            if (getenv('VERCEL') === '1' && (!is_string($configuredHost) || $configuredHost === '')) {
                throw new RuntimeException('Set DB_HOST or DB_DSN in the Vercel project environment variables.');
            }

            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            if (!ctype_digit((string) $port)) {
                throw new RuntimeException('DB_PORT must be a valid port number.');
            }

            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbname);

            if (getenv('DB_HOST') === false) {
                if (!preg_match('/^[A-Za-z0-9_]+$/', $dbname)) {
                    throw new RuntimeException('DB_NAME may contain only letters, numbers, and underscores.');
                }

                $rootPdo = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port), $user, $pass, $options);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }

        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } finally {
        if ($temporarySslCaPath !== null && is_file($temporarySslCaPath) && !unlink($temporarySslCaPath)) {
            error_log('Could not remove temporary MySQL CA certificate.');
        }
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float $amount): string
{
    return '₦' . number_format($amount, 2);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        throw new RuntimeException('Invalid CSRF token.');
    }
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type,
        ];

        return $_SESSION['flash'];
    }

    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

require_once __DIR__ . '/cart.php';
