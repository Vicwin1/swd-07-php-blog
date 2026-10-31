<?php
declare(strict_types=1);

function admin_header(string $title): void
{
    $admin = current_admin();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | SWD Blog Admin</title>
        <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
    </head>
    <body class="admin-page">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a class="admin-brand" href="<?= e(url('admin/')) ?>">SWD Blog</a>
            <p class="admin-user"><?= e($admin['name'] ?? '') ?></p>
            <nav aria-label="Admin navigation">
                <a href="<?= e(url('admin/')) ?>">Dashboard</a>
                <a href="<?= e(url('admin/posts/')) ?>">Posts</a>
                <a href="<?= e(url('admin/categories/')) ?>">Categories</a>
                <a href="<?= e(url()) ?>" target="_blank" rel="noopener">View site</a>
                <form method="post" action="<?= e(url('admin/logout.php')) ?>">
                    <?= csrf_field() ?>
                    <button class="sidebar-logout" type="submit">Logout</button>
                </form>
            </nav>
        </aside>
        <main class="admin-content">
            <header class="admin-topbar"><h1><?= e($title) ?></h1></header>
            <?php if ($message = flash('success')): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
            <?php if ($message = flash('error')): ?><div class="alert error"><?= e($message) ?></div><?php endif; ?>
    <?php
}

function admin_footer(): void
{
    ?>
        </main>
    </div>
    </body>
    </html>
    <?php
}