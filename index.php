<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;
$count = (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= NOW()")->fetchColumn();
$totalPages = max(1, (int) ceil($count / $perPage));
$page = min($page, $totalPages);
$statement = db()->prepare(
    "SELECT p.title, p.slug, p.body, p.excerpt, p.cover_image, p.published_at,
            c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     WHERE p.status = 'published' AND p.published_at IS NOT NULL AND p.published_at <= NOW()
     ORDER BY p.published_at DESC, p.id DESC LIMIT :limit OFFSET :offset"
);
$statement->bindValue('limit', $perPage, PDO::PARAM_INT);
$statement->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$statement->execute();
$posts = $statement->fetchAll();

public_header('Latest posts');
?>
<header class="page-heading">
    <p class="eyebrow">Stories, ideas, and updates</p>
    <h1>Latest posts</h1>
</header>
<?php if (!$posts): ?>
    <div class="panel empty-state"><h2>No published posts yet</h2><p>Check back soon for new stories.</p></div>
<?php else: ?>
    <div class="post-grid">
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <?php if ($post['cover_image']): ?><a class="post-card-image" href="/swd/07-php-blog/post/<?= rawurlencode($post['slug']) ?>"><img class="post-cover" src="/swd/07-php-blog/uploads/<?= e($post['cover_image']) ?>" alt=""></a><?php endif; ?>
                <div class="post-card-content">
                    <p class="meta"><a href="/swd/07-php-blog/category/<?= rawurlencode($post['category_slug']) ?>"><?= e($post['category_name']) ?></a></p>
                    <h2><a href="/swd/07-php-blog/post/<?= rawurlencode($post['slug']) ?>"><?= e($post['title']) ?></a></h2>
                    <p class="post-excerpt"><?= e($post['excerpt'] ?: excerpt($post['body'])) ?></p>
                    <p class="post-byline">By <?= e($post['author_name']) ?> <span aria-hidden="true">·</span> <?= e(format_date($post['published_at'])) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if ($count > $perPage): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php if ($page > 1): ?><a class="button button-secondary" href="?page=<?= $page - 1 ?>">Newer posts</a><?php endif; ?>
            <span>Page <?= $page ?> of <?= (int) ceil($count / $perPage) ?></span>
            <?php if ($page < $totalPages): ?><a class="button button-secondary" href="?page=<?= $page + 1 ?>">Older posts</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; public_footer(); ?>
