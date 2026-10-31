<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';
$query = db()->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     WHERE p.slug = :slug AND p.status = 'published' AND p.published_at <= NOW()
     LIMIT 1"
);
$query->execute(['slug' => $slug]);
$post = $query->fetch();
if (!$post) {
    http_response_code(404);
}
public_header($post ? $post['title'] : 'Post not found');
?>
<?php if (!$post): ?>
    <section class="page-heading"><h1>Post not found</h1><p>This post may have been removed or is not published.</p></section>
<?php else: ?>
    <article class="article">
        <a class="eyebrow" href="<?= e(url('category.php?slug=' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
        <h1><?= e($post['title']) ?></h1>
        <p class="post-meta">By <?= e($post['author_name']) ?> · <?= e(format_date($post['published_at'])) ?></p>
        <?php if ($post['cover_image']): ?><img class="article-cover" src="<?= e(url($post['cover_image'])) ?>" alt=""><?php endif; ?>
        <?php if ($post['excerpt']): ?><p class="article-excerpt"><?= e($post['excerpt']) ?></p><?php endif; ?>
        <div class="article-body"><?= render_body($post['body']) ?></div>
    </article>
<?php endif; ?>
<?php public_footer(); ?>