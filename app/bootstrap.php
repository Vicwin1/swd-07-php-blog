<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80000) {
    throw new RuntimeException('PHP 8.0 or later is required.');
}

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'UTC');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_name('swd_blog_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => APP_BASE_URL . '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    $now = time();
    $createdAt = (int) ($_SESSION['created_at'] ?? 0);
    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);
    $expired = ($lastActivity > 0 && $now - $lastActivity > 1800)
        || ($createdAt > 0 && $now - $createdAt > 28800);
    if ($expired) {
        $_SESSION = [];
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Unable to renew the expired session.');
        }
        $_SESSION['created_at'] = $now;
        $_SESSION['last_regenerated_at'] = $now;
    } elseif ($createdAt === 0) {
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Unable to initialize a secure session.');
        }
        $_SESSION['created_at'] = $now;
        $_SESSION['last_regenerated_at'] = $now;
    } elseif ($now - (int) ($_SESSION['last_regenerated_at'] ?? 0) > 900) {
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Unable to rotate the session ID.');
        }
        $_SESSION['last_regenerated_at'] = $now;
    }
    $_SESSION['last_activity'] = $now;
}