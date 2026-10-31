<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$term = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$posts = [];
if (mb_strlen($term) >= 3 && mb_strlen($term) <= 100) {
    $query = db()->prepare(
        "SELECT p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at,
                c.name AS category_name, c.slug AS category_slug, a.name AS author_name,
                MATCH(p.title, p.body) AGAINST (:term IN NATURAL LANGUAGE MODE) AS relevance
         FROM posts p
         JOIN categories c ON c.id = p.category_id
         JOIN admins a ON a.id = p.author_id
         WHERE p.status = 'published' AND p.published_at <= NOW()
           AND MATCH(p.title, p.body) AGAINST (:term_search IN NATURAL LANGUAGE MODE)
         ORDER BY relevance DESC, p.published_at DESC
         LIMIT 50"
    );
    $query->execute(['term' => $term, 'term_search' => $term]);
    $posts = $query->fetchAll();
}
public_header('Search');
?>
<section class="page-heading">
    <p class="eyebrow">Search</p>
    <h1>Search posts</h1>
    <p><?= $term !== '' ? 'Results for “' . e($term) . '”' : 'Enter a search term of at least three characters.' ?></p>
</section>
<?php if ($term !== '' && mb_strlen($term) < 3): ?>
    <p class="empty-state">Please enter at least three characters.</p>
<?php elseif (!$posts): ?>
    <p class="empty-state">No matching published posts found.</p>
<?php else: ?>
    <div class="post-grid">
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <?php if ($post['cover_image']): ?><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><img class="post-cover" src="<?= e(url($post['cover_image'])) ?>" alt=""></a><?php endif; ?>
                <div class="post-card-content">
                    <a class="eyebrow" href="<?= e(url('category.php?slug=' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
                    <h2><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2>
                    <p><?= e($post['excerpt'] ?: excerpt($post['body'])) ?></p>
                    <p class="post-meta">By <?= e($post['author_name']) ?> · <?= e(format_date($post['published_at'])) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php public_footer(); ?>