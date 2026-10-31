<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$statement = db()->prepare('SELECT id, name FROM categories WHERE slug = :slug LIMIT 1');
$statement->execute(['slug' => $slug]);
$category = $statement->fetch();
if (!$category) {
    http_response_code(404);
    public_header('Category not found');
    echo '<div class="panel"><h1>Category not found</h1><a href="/swd/07-php-blog/">Back to posts</a></div>';
    public_footer();
    exit;
}

$postsStatement = db()->prepare(
    "SELECT p.title, p.slug, p.body, p.excerpt, p.cover_image, p.published_at,
            c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     WHERE p.category_id = :category_id AND p.status = 'published'
       AND p.published_at IS NOT NULL AND p.published_at <= NOW()
     ORDER BY p.published_at DESC, p.id DESC"
);
$postsStatement->execute(['category_id' => $category['id']]);
$posts = $postsStatement->fetchAll();

public_header($category['name']);
?>
<header class="page-heading">
    <p class="eyebrow">Browsing category</p>
    <h1><?= e($category['name']) ?></h1>
</header>
<?php if (!$posts): ?><div class="panel">No posts in this category yet.</div><?php else: ?>
<div class="post-grid">
    <?php foreach ($posts as $post): ?>
        <article class="post-card">
            <?php if ($post['cover_image']): ?><a class="post-card-image" href="/swd/07-php-blog/post/<?= rawurlencode($post['slug']) ?>"><img class="post-cover" src="/swd/07-php-blog/uploads/<?= e($post['cover_image']) ?>" alt=""></a><?php endif; ?>
            <div class="post-card-content">
                <p class="meta"><?= e(format_date($post['published_at'])) ?></p>
                <h2><a href="/swd/07-php-blog/post/<?= rawurlencode($post['slug']) ?>"><?= e($post['title']) ?></a></h2>
                <p class="post-excerpt"><?= e($post['excerpt'] ?: excerpt($post['body'])) ?></p>
                <p class="post-byline">By <?= e($post['author_name']) ?></p>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; public_footer(); ?>
