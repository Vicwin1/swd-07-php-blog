<?php
declare(strict_types=1);

function current_admin(): ?array
{
    return isset($_SESSION['admin']) && is_array($_SESSION['admin']) ? $_SESSION['admin'] : null;
}

function require_admin(): void
{
    if (current_admin() === null) {
        flash('error', 'Please sign in to access the admin area.');
        redirect(url('admin/login.php'));
    }
}

function attempt_admin_login(string $email, string $password): bool
{
    $query = db()->prepare('SELECT id, name, email, password_hash FROM admins WHERE email = :email LIMIT 1');
    $query->execute(['email' => strtolower(trim($email))]);
    $admin = $query->fetch();
    $validPassword = password_verify(
        $password,
        $admin['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'
    );
    if (!$admin || !$validPassword) {
        return false;
    }
    if (!session_regenerate_id(true)) {
        throw new RuntimeException('Unable to renew the session after authentication.');
    }
    $now = time();
    $_SESSION['created_at'] = $now;
    $_SESSION['last_regenerated_at'] = $now;
    $_SESSION['last_activity'] = $now;
    $_SESSION['admin'] = [
        'id' => (int) $admin['id'],
        'name' => $admin['name'],
        'email' => $admin['email'],
    ];
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
        $update = db()->prepare('UPDATE admins SET password_hash = :hash WHERE id = :id');
        $update->execute([
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $admin['id'],
        ]);
    }
    return true;
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}