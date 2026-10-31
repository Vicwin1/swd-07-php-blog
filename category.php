<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';
$query = db()->prepare('SELECT id, name FROM categories WHERE slug = :slug LIMIT 1');
$query->execute(['slug' => $slug]);
$category = $query->fetch();
if (!$category) {
    http_response_code(404);
    public_header('Category not found');
    echo '<section class="page-heading"><h1>Category not found</h1><p>This category does not exist.</p></section>';
    public_footer();
    exit;
}
$query = db()->prepare(
    "SELECT p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at,
            a.name AS author_name
     FROM posts p JOIN admins a ON a.id = p.author_id
     WHERE p.category_id = :category_id AND p.status = 'published' AND p.published_at <= NOW()
     ORDER BY p.published_at DESC, p.id DESC"
);
$query->execute(['category_id' => $category['id']]);
$posts = $query->fetchAll();
public_header($category['name']);
?>
<section class="page-heading">
    <p class="eyebrow">Category</p>
    <h1><?= e($category['name']) ?></h1>
</section>
<?php if (!$posts): ?>
    <p class="empty-state">No published posts in this category yet.</p>
<?php else: ?>
    <div class="post-grid">
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <?php if ($post['cover_image']): ?><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><img class="post-cover" src="<?= e(url($post['cover_image'])) ?>" alt=""></a><?php endif; ?>
                <div class="post-card-content">
                    <h2><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2>
                    <p><?= e($post['excerpt'] ?: excerpt($post['body'])) ?></p>
                    <p class="post-meta">By <?= e($post['author_name']) ?> · <?= e(format_date($post['published_at'])) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php public_footer(); ?>