<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$query = trim((string) ($_GET['q'] ?? ''));
$posts = [];
if ($query !== '' && mb_strlen($query) <= 100) {
    if (mb_strlen($query) < 3) {
        $likeQuery = strtr($query, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']);
        $statement = db()->prepare(
            "SELECT p.title, p.slug, p.body, p.excerpt, p.cover_image, p.published_at,
                    c.name AS category_name, c.slug AS category_slug, a.name AS author_name
             FROM posts p
             JOIN categories c ON c.id = p.category_id
             JOIN admins a ON a.id = p.author_id
             WHERE p.status = 'published' AND p.published_at IS NOT NULL AND p.published_at <= NOW()
               AND (p.title LIKE :title_query ESCAPE '\\\\' OR p.body LIKE :body_query ESCAPE '\\\\')
             ORDER BY p.published_at DESC, p.id DESC
             LIMIT 50"
        );
        $likePattern = '%' . $likeQuery . '%';
        $statement->execute(['title_query' => $likePattern, 'body_query' => $likePattern]);
    } else {
    $statement = db()->prepare(
        "SELECT p.title, p.slug, p.body, p.excerpt, p.cover_image, p.published_at,
                c.name AS category_name, c.slug AS category_slug, a.name AS author_name
         FROM posts p
         JOIN categories c ON c.id = p.category_id
         JOIN admins a ON a.id = p.author_id
         WHERE p.status = 'published' AND p.published_at IS NOT NULL AND p.published_at <= NOW()
           AND MATCH(p.title, p.body) AGAINST (:query IN NATURAL LANGUAGE MODE)
         ORDER BY p.published_at DESC, p.id DESC LIMIT 50"
    );
    $statement->execute(['query' => $query]);
    }
    $posts = $statement->fetchAll();
}

public_header('Search');
?>
<h1>Search posts</h1>
<?php if ($query === ''): ?><p>Enter a search term to find posts.</p>
<?php elseif (mb_strlen($query) > 100): ?><p>Please limit your search to 100 characters.</p>
<?php elseif (!$posts): ?><p>No results found for “<?= e($query) ?>”.</p>
<?php else: ?>
    <p><?= count($posts) ?> result(s) for “<?= e($query) ?>”.</p>
    <div class="post-grid">
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <?php if ($post['cover_image']): ?><a class="post-card-image" href="/swd/07-php-blog/post/<?= rawurlencode($post['slug']) ?>"><img class="post-cover" src="/swd/07-php-blog/uploads/<?= e($post['cover_image']) ?>" alt=""></a><?php endif; ?>
                <div class="post-card-content">
                    <p class="meta"><a href="/swd/07-php-blog/category/<?= rawurlencode($post['category_slug']) ?>"><?= e($post['category_name']) ?></a> · <?= e(format_date($post['published_at'])) ?></p>
                    <h2><a href="/swd/07-php-blog/post/<?= rawurlencode($post['slug']) ?>"><?= e($post['title']) ?></a></h2>
                    <p class="post-excerpt"><?= e($post['excerpt'] ?: excerpt($post['body'])) ?></p>
                    <p class="post-byline">By <?= e($post['author_name']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; public_footer(); ?>
