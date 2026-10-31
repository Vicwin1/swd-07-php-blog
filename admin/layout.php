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
        <link rel="stylesheet" href="/swd/07-php-blog/assets/style.css">
    </head>
    <body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <h1>SWD Blog</h1>
            <nav aria-label="Admin navigation">
                <a href="/swd/07-php-blog/admin/">Dashboard</a>
                <a href="/swd/07-php-blog/admin/posts/">Posts</a>
                <a href="/swd/07-php-blog/admin/categories/">Categories</a>
                <a href="/swd/07-php-blog/">View site</a>
                <form method="post" action="/swd/07-php-blog/admin/logout.php">
                    <?= csrf_field() ?>
                    <button class="sidebar-logout" type="submit">Logout</button>
                </form>
            </nav>
        </aside>
        <main class="admin-main">
            <div class="admin-top"><div><h1><?= e($title) ?></h1><span class="muted">Signed in as <?= e($admin['name'] ?? '') ?></span></div></div>
            <?php if ($message = flash('success')): ?><div class="alert"><?= e($message) ?></div><?php endif; ?>
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

function store_cover_image(?array $file, ?string $existing = null): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existing;
    }
    $temporaryPath = $file['tmp_name'] ?? null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK
        || !is_string($temporaryPath)
        || $temporaryPath === ''
        || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('The cover image could not be uploaded.');
    }
    $fileSize = filesize($temporaryPath);
    if ($fileSize === false || $fileSize > 2 * 1024 * 1024) {
        throw new RuntimeException('Cover images must be 2 MB or smaller.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Use a JPEG, PNG, or WebP image.');
    }
    if (getimagesize($temporaryPath) === false) {
        throw new RuntimeException('The uploaded file is not a valid image.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $destination = dirname(__DIR__) . '/uploads/' . $filename;
    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new RuntimeException('The cover image could not be saved.');
    }
    return $filename;
}

function remove_cover_image(?string $filename): void
{
    if ($filename && preg_match('/\A[a-f0-9]{32}\.(jpg|png|gif|webp)\z/', $filename)) {
        $path = dirname(__DIR__) . '/uploads/' . $filename;
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Could not remove the old cover image.');
        }
    }
}
