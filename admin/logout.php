<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

unset($_SESSION['admin_logged_in']);
$_SESSION['flash'] = ['message' => 'You have been logged out.', 'type' => 'success'];

header('Location: ' . APP_URL . '/admin/login.php');
exit;
