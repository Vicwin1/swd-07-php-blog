<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$query = trim((string) ($_GET['q'] ?? ''));
$posts = [];
if (mb_strlen($query) >= 3 && mb_strlen($query) <= 100) {
    $statement = db()->prepare(
        "SELECT p.title, p.slug, p.body, p.cover_image, p.published_at, c.name AS category_name
         FROM posts p JOIN categories c ON c.id = p.category_id
         WHERE p.status = 'published' AND p.published_at <= NOW()
           AND MATCH(p.title, p.body) AGAINST (:query IN NATURAL LANGUAGE MODE)
         ORDER BY p.published_at DESC LIMIT 50"
    );
    $statement->execute(['query' => $query]);
    $posts = $statement->fetchAll();
}

public_header('Search');
?>
<h1>Search posts</h1>
<?php if ($query === ''): ?><p>Enter a search term to find posts.</p>
<?php elseif (mb_strlen($query) < 3): ?><p>Please enter at least 3 characters.</p>
<?php elseif (mb_strlen($query) > 100): ?><p>Please limit your search to 100 characters.</p>
<?php elseif (!$posts): ?><p>No results found for “<?= e($query) ?>”.</p>
<?php else: ?>
    <p><?= count($posts) ?> result(s) for “<?= e($query) ?>”.</p>
    <div class="post-grid">
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <p class="meta"><?= e($post['category_name']) ?> · <?= e(format_date($post['published_at'])) ?></p>
                <h2><a href="/swd/07-php-blog/post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h2>
                <p><?= e(excerpt($post['body'])) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; public_footer(); ?>
