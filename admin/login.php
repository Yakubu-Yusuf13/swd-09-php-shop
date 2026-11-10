<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/auth.php';

$pageTitle = 'Admin login';
$errors = [];
$username = 'admin';
$next = trim((string) ($_GET['next'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? 'admin'));
    $password = (string) ($_POST['password'] ?? '');

    if (admin_login($username, $password)) {
        $_SESSION['admin_logged_in'] = true;
        $redirect = $next !== '' ? $next : APP_URL . '/admin/index.php';
        header('Location: ' . $redirect);
        exit;
    }

    $errors['login'] = 'Invalid username or password.';
}

include __DIR__ . '/../partials/header.php';
?>

<section class="container">
    <div class="auth-card">
        <span class="hero-kicker">Restricted area</span>
        <h1>Admin login</h1>
        <p>Use your admin account to manage orders and fulfilment.</p>

        <?php if ($errors): ?>
            <div class="alert alert-error"><?= e($errors['login']) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form" action="<?= APP_URL ?>/admin/login.php<?= $next !== '' ? '?next=' . e(urlencode($next)) : '' ?>">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" value="<?= e($username) ?>" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <button type="submit" class="button-primary">Sign in</button>
        </form>
    </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
