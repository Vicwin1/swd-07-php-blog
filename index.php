<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$perPage = 6;
$total = (int) db()->query(
    "SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at <= NOW()"
)->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
$query = db()->prepare(
    "SELECT p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at,
            c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     WHERE p.status = 'published' AND p.published_at <= NOW()
     ORDER BY p.published_at DESC, p.id DESC
     LIMIT :limit OFFSET :offset"
);
$query->bindValue('limit', $perPage, PDO::PARAM_INT);
$query->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$query->execute();
$posts = $query->fetchAll();

public_header('Latest posts');
?>
<section class="page-heading">
    <p class="eyebrow">Stories and updates</p>
    <h1>Latest posts</h1>
    <p>News, ideas, and updates from our community.</p>
</section>
<?php if (!$posts): ?>
    <p class="empty-state">There are no published posts yet.</p>
<?php else: ?>
    <div class="post-grid">
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <?php if ($post['cover_image']): ?>
                    <a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>">
                        <img class="post-cover" src="<?= e(url($post['cover_image'])) ?>" alt="">
                    </a>
                <?php endif; ?>
                <div class="post-card-content">
                    <a class="eyebrow" href="<?= e(url('category.php?slug=' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
                    <h2><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2>
                    <p><?= e($post['excerpt'] ?: excerpt($post['body'])) ?></p>
                    <p class="post-meta">By <?= e($post['author_name']) ?> · <?= e(format_date($post['published_at'])) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php if ($page > 1): ?><a href="<?= e(url('?page=' . ($page - 1))) ?>">Previous</a><?php endif; ?>
            <span>Page <?= $page ?> of <?= $pages ?></span>
            <?php if ($page < $pages): ?><a href="<?= e(url('?page=' . ($page + 1))) ?>">Next</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php public_footer(); ?>