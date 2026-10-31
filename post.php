<?php
declare(strict_types=1);
require_once __DIR__ . '/app/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$statement = db()->prepare(
    "SELECT p.id, p.category_id, p.title, p.slug, p.body, p.cover_image, p.published_at,
            c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     WHERE p.slug = :slug AND p.status = 'published'
       AND p.published_at IS NOT NULL AND p.published_at <= NOW()
     LIMIT 1"
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

$wordCount = str_word_count(strip_tags($post['body']));
$readingMinutes = max(1, (int) ceil($wordCount / 200));
$relatedStatement = db()->prepare(
    "SELECT p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at, a.name AS author_name
     FROM posts p
     JOIN admins a ON a.id = p.author_id
     WHERE p.category_id = :category_id AND p.id <> :post_id
       AND p.status = 'published' AND p.published_at IS NOT NULL AND p.published_at <= NOW()
     ORDER BY p.published_at DESC, p.id DESC
     LIMIT 3"
);
$relatedStatement->execute(['category_id' => $post['category_id'], 'post_id' => $post['id']]);
$relatedPosts = $relatedStatement->fetchAll();

public_header($post['title']);
?>
<div class="article-layout">
    <article class="article">
        <p class="article-category"><a href="/swd/07-php-blog/category/<?= rawurlencode($post['category_slug']) ?>"><?= e($post['category_name']) ?></a></p>
        <h1><?= e($post['title']) ?></h1>
        <p class="article-byline">By <strong><?= e($post['author_name']) ?></strong> <span aria-hidden="true">·</span> <?= e(format_date($post['published_at'])) ?> <span aria-hidden="true">·</span> <?= $readingMinutes ?> min read</p>
        <?php if ($post['cover_image']): ?><img class="article-cover" src="/swd/07-php-blog/uploads/<?= e($post['cover_image']) ?>" alt=""><?php endif; ?>
        <div class="article-body"><?= render_body_paragraphs($post['body']) ?></div>
    </article>
    <?php if ($relatedPosts): ?>
        <aside class="related-posts" aria-labelledby="related-heading">
            <h2 id="related-heading">Related posts</h2>
            <div class="related-grid">
                <?php foreach ($relatedPosts as $related): ?>
                    <article class="related-card">
                        <?php if ($related['cover_image']): ?><a href="/swd/07-php-blog/post/<?= rawurlencode($related['slug']) ?>"><img src="/swd/07-php-blog/uploads/<?= e($related['cover_image']) ?>" alt=""></a><?php endif; ?>
                        <p class="meta"><?= e(format_date($related['published_at'])) ?></p>
                        <h3><a href="/swd/07-php-blog/post/<?= rawurlencode($related['slug']) ?>"><?= e($related['title']) ?></a></h3>
                    </article>
                <?php endforeach; ?>
            </div>
        </aside>
    <?php endif; ?>
</div>
<?php public_footer(); ?>
