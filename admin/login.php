<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (current_admin() !== null) {
    redirect('/swd/07-php-blog/admin/');
}

$adminCount = (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
$error = flash('error');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = post_value('email');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (attempt_admin_login($email, $password)) {
        redirect('/swd/07-php-blog/admin/');
    }
    $error = 'The email or password was not correct.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin sign in | SWD Blog</title>
    <link rel="stylesheet" href="/swd/07-php-blog/assets/style.css">
</head>
<body>
<main class="site-main">
    <form class="form-card" method="post" action="">
        <h1>Admin sign in</h1>
        <?php if ($adminCount === 0): ?>
            <div class="alert">No administrator account exists yet. Create the first admin from a terminal in the project folder:<br><code>C:\xampp\php\php.exe create-admin.php "Your Name" you@example.com</code></div>
        <?php endif; ?>
        <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <label class="field">Email<input class="form-control" type="email" name="email" required maxlength="190" autocomplete="username" value="<?= e(post_value('email')) ?>"></label>
        <label class="field">Password<input class="form-control" type="password" name="password" required autocomplete="current-password"></label>
        <button type="submit">Sign in</button>
        <p><a href="/swd/07-php-blog/">Back to site</a></p>
    </form>
</main>
</body>
</html>
