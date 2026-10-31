<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
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

function slugify(string $title): string
{
    $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
    $slug = strtolower($transliterated === false ? $title : $transliterated);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    return trim($slug, '-') ?: 'untitled';
}

function unique_post_slug(string $title, ?int $excludeId = null): string
{
    $base = substr(slugify($title), 0, 240);
    $base = rtrim($base, '-') ?: 'untitled';
    $candidate = $base;
    $suffix = 2;

    while (true) {
        $sql = 'SELECT id FROM posts WHERE slug = :slug';
        $params = ['slug' => $candidate];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $statement = db()->prepare($sql . ' LIMIT 1');
        $statement->execute($params);
        if (!$statement->fetch()) {
            return $candidate;
        }

        $ending = '-' . $suffix++;
        $candidate = substr($base, 0, 260 - strlen($ending)) . $ending;
    }
}

function excerpt(string $text, int $length = 180): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length - 1) . '…';
}

function render_body_paragraphs(string $body): string
{
    $normalized = str_replace(["\r\n", "\r"], "\n", trim($body));
    if ($normalized === '') {
        return '';
    }

    $paragraphs = preg_split('/\n[ \t]*\n+/', $normalized) ?: [];
    $html = [];
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph !== '') {
            $html[] = '<p>' . nl2br(e($paragraph), false) . '</p>';
        }
    }
    return implode("\n", $html);
}

function format_date(?string $date): string
{
    if (!$date) {
        return '';
    }
    return date('F j, Y', strtotime($date));
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
        <link rel="stylesheet" href="/swd/07-php-blog/assets/style.css">
    </head>
    <body>
    <header class="site-header">
        <a class="brand" href="/swd/07-php-blog/">SWD Blog</a>
        <nav class="site-nav" aria-label="Categories">
            <?php foreach ($categories as $category): ?>
                <a href="/swd/07-php-blog/category/<?= rawurlencode($category['slug']) ?>"><?= e($category['name']) ?></a>
            <?php endforeach; ?>
        </nav>
        <form class="site-search" action="/swd/07-php-blog/search" method="get">
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
