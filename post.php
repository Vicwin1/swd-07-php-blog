<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$statement = db()->prepare(
    "SELECT p.title, p.slug, p.body, p.cover_image, p.published_at, c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id
     WHERE p.slug = :slug AND p.status = 'published' AND p.published_at <= NOW() LIMIT 1"
);
$statement->execute(['slug' => $slug]);
$post = $statement->fetch();
if (!$post) {
    http_response_code(404);
    public_header('Post not found');
    echo '<div class="panel"><h1>Post not found</h1><p>This post may have been moved or is not published.</p><a href="/swd/07-php-blog/">Back to posts</a></div>';
    public_footer();
    exit;
}

public_header($post['title']);
?>
<article class="article">
    <p class="meta"><a href="/swd/07-php-blog/category.php?slug=<?= e($post['category_slug']) ?>"><?= e($post['category_name']) ?></a> · <?= e(format_date($post['published_at'])) ?> · By <?= e($post['author_name']) ?></p>
    <h1><?= e($post['title']) ?></h1>
    <?php if ($post['cover_image']): ?><img class="post-cover" src="/swd/07-php-blog/uploads/<?= e($post['cover_image']) ?>" alt=""><?php endif; ?>
    <div class="article-body"><?= e($post['body']) ?></div>
</article>
<?php public_footer(); ?>
