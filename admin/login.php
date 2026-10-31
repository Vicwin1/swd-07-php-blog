<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (current_admin() !== null) {
    redirect(url('admin/'));
}
$error = flash('error');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = post_value('email');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (attempt_admin_login($email, $password)) {
        redirect(url('admin/'));
    }
    $error = 'The email or password was not correct.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin sign in | SWD Blog</title>
    <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body class="login-page">
<main class="site-main login-main">
    <form class="form-card login-card" method="post">
        <a class="login-brand" href="<?= e(url()) ?>">SWD Blog</a>
        <h1>Admin sign in</h1>
        <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <label class="field">Email<input class="form-control" type="email" name="email" required maxlength="190" autocomplete="username" value="<?= e(post_value('email')) ?>"></label>
        <label class="field">Password<input class="form-control" type="password" name="password" required autocomplete="current-password"></label>
        <button type="submit">Sign in</button>
        <p><a href="<?= e(url()) ?>">Back to site</a></p>
    </form>
</main>
</body>
</html>