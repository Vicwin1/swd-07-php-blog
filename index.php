<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$count = (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at <= NOW()")->fetchColumn();
$statement = db()->prepare(
    "SELECT p.title, p.slug, p.body, p.excerpt, p.cover_image, p.published_at, c.name AS category_name, c.slug AS category_slug
     FROM posts p JOIN categories c ON c.id = p.category_id
     WHERE p.status = 'published' AND p.published_at <= NOW()
     ORDER BY p.published_at DESC, p.id DESC LIMIT :limit OFFSET :offset"
);
$statement->bindValue('limit', $perPage, PDO::PARAM_INT);
$statement->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$statement->execute();
$posts = $statement->fetchAll();

public_header('Latest posts');
?>
<div class="admin-top">
    <div><h1>Latest posts</h1><p class="muted">Stories, ideas, and updates.</p></div>
    <a href="/swd/07-php-blog/admin/">Admin</a>
</div>
<?php if (!$posts): ?>
    <div class="panel">No published posts yet.</div>
<?php else: ?>
    <div class="post-grid">
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <?php if ($post['cover_image']): ?><a href="/swd/07-php-blog/post.php?slug=<?= e($post['slug']) ?>"><img class="post-cover" src="/swd/07-php-blog/uploads/<?= e($post['cover_image']) ?>" alt=""></a><?php endif; ?>
                <p class="meta"><a href="/swd/07-php-blog/category.php?slug=<?= e($post['category_slug']) ?>"><?= e($post['category_name']) ?></a> · <?= e(format_date($post['published_at'])) ?></p>
                <h2><a href="/swd/07-php-blog/post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h2>
                <p><?= e($post['excerpt'] ?: excerpt($post['body'])) ?></p>
                <a href="/swd/07-php-blog/post.php?slug=<?= e($post['slug']) ?>">Read more</a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if ($count > $perPage): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php if ($page > 1): ?><a class="button" href="?page=<?= $page - 1 ?>">Newer</a><?php endif; ?>
            <span>Page <?= $page ?> of <?= (int) ceil($count / $perPage) ?></span>
            <?php if ($page * $perPage < $count): ?><a class="button" href="?page=<?= $page + 1 ?>">Older</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; public_footer(); ?>
