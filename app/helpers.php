<?php
declare(strict_types=1);

const APP_BASE_URL = '/swd/07-php-blog';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return APP_BASE_URL . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

function redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function post_value(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($value) ? $value : null;
}

function slugify(string $text): string
{
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $slug = strtolower($ascii === false ? $text : $ascii);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '', '-') ?: 'untitled';
}

function unique_slug(string $table, string $name, ?int $excludeId = null): string
{
    if (!in_array($table, ['posts', 'categories'], true)) {
        throw new InvalidArgumentException('Unsupported slug table.');
    }
    $maxLength = $table === 'posts' ? 260 : 160;
    $base = substr(slugify($name), 0, $maxLength);
    $candidate = $base;
    for ($suffix = 2; ; $suffix++) {
        $sql = "SELECT id FROM {$table} WHERE slug = :slug";
        $params = ['slug' => $candidate];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $excludeId;
        }
        $query = db()->prepare($sql . ' LIMIT 1');
        $query->execute($params);
        if (!$query->fetch()) {
            return $candidate;
        }
        $ending = '-' . $suffix;
        $candidate = substr($base, 0, $maxLength - strlen($ending)) . $ending;
    }
}

function excerpt(string $text, int $length = 180): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1) . '…' : $text;
}

function render_body(string $body): string
{
    $body = str_replace(["\r\n", "\r"], "\n", trim($body));
    if ($body === '') {
        return '';
    }
    $paragraphs = preg_split('/\n[ \t]*\n+/', $body) ?: [];
    return implode("\n", array_map(
        static fn(string $paragraph): string => '<p>' . nl2br(e(trim($paragraph)), false) . '</p>',
        array_filter($paragraphs, static fn(string $paragraph): bool => trim($paragraph) !== '')
    ));
}

function format_date(?string $date): string
{
    return $date === null ? '' : date('F j, Y', strtotime($date));
}

function save_uploaded_image(?array $upload): ?string
{
    if ($upload === null || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'] ?? '')) {
        throw new RuntimeException('The image upload did not complete successfully.');
    }
    if (($upload['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Cover images must be no larger than 5 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if (!is_string($mime) || !isset($extensions[$mime]) || getimagesize($upload['tmp_name']) === false) {
        throw new RuntimeException('Choose a valid JPG, PNG, GIF, or WebP image.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $destination = dirname(__DIR__) . '/uploads/' . $filename;
    if (!move_uploaded_file($upload['tmp_name'], $destination)) {
        throw new RuntimeException('The uploaded image could not be saved.');
    }
    return 'uploads/' . $filename;
}

function delete_uploaded_image(?string $image): void
{
    if ($image === null || !preg_match('/\Auploads\/[a-f0-9]{32}\.(?:jpg|png|gif|webp)\z/', $image)) {
        return;
    }
    $path = dirname(__DIR__) . '/' . $image;
    if (is_file($path) && !unlink($path)) {
        error_log('Unable to remove uploaded cover image: ' . $path);
    }
}

function public_header(string $title): void
{
    $categories = db()->query('SELECT name, slug FROM categories ORDER BY name')->fetchAll();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | SWD Blog</title>
        <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
    </head>
    <body>
    <header class="site-header">
        <a class="brand" href="<?= e(url()) ?>">SWD Blog</a>
        <nav class="site-nav" aria-label="Categories">
            <?php foreach ($categories as $category): ?>
                <a href="<?= e(url('category.php?slug=' . rawurlencode($category['slug']))) ?>"><?= e($category['name']) ?></a>
            <?php endforeach; ?>
            <a href="<?= e(url('admin/login.php')) ?>">Admin</a>
        </nav>
        <form class="site-search" action="<?= e(url('search.php')) ?>" method="get">
            <label class="sr-only" for="site-query">Search posts</label>
            <input id="site-query" name="q" type="search" placeholder="Search posts">
            <button type="submit">Search</button>
        </form>
    </header>
    <main class="site-main">
    <?php
}

function public_footer(): void
{
    ?>
    </main>
    <footer class="site-footer">SWD Blog</footer>
    </body>
    </html>
    <?php
}