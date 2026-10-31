<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$statement = db()->prepare('SELECT id, name, description FROM categories WHERE slug = :slug LIMIT 1');
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
    "SELECT p.title, p.slug, p.body, p.excerpt, p.cover_image, p.published_at
     FROM posts p WHERE p.category_id = :category_id AND p.status = 'published' AND p.published_at <= NOW()
     ORDER BY p.published_at DESC, p.id DESC"
);
$postsStatement->execute(['category_id' => $category['id']]);
$posts = $postsStatement->fetchAll();

public_header($category['name']);
?>
<h1><?= e($category['name']) ?></h1>
<?php if ($category['description']): ?><p><?= e($category['description']) ?></p><?php endif; ?>
<?php if (!$posts): ?><div class="panel">No posts in this category yet.</div><?php else: ?>
<div class="post-grid">
    <?php foreach ($posts as $post): ?>
        <article class="post-card">
            <?php if ($post['cover_image']): ?><img class="post-cover" src="/swd/07-php-blog/uploads/<?= e($post['cover_image']) ?>" alt=""><?php endif; ?>
            <p class="meta"><?= e(format_date($post['published_at'])) ?></p>
            <h2><a href="/swd/07-php-blog/post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h2>
            <p><?= e($post['excerpt'] ?: excerpt($post['body'])) ?></p>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; public_footer(); ?>
