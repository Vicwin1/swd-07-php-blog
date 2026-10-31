<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once __DIR__ . '/layout.php';
require_admin();

$summary = db()->query(
    "SELECT COUNT(*) AS post_count,
            COALESCE(SUM(status = 'published'), 0) AS published_count,
            COALESCE(SUM(status = 'draft'), 0) AS draft_count,
            (SELECT COUNT(*) FROM categories) AS category_count
     FROM posts"
)->fetch();

$latestPosts = db()->query(
    'SELECT p.id, p.title, p.slug, p.status, p.updated_at, c.name AS category_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     ORDER BY p.updated_at DESC, p.id DESC
     LIMIT 5'
)->fetchAll();

$categoryCounts = db()->query(
    'SELECT c.id, c.name, c.slug, COUNT(p.id) AS post_count
     FROM categories c
     LEFT JOIN posts p ON p.category_id = c.id
     GROUP BY c.id, c.name, c.slug
     ORDER BY c.name'
)->fetchAll();

admin_header('Dashboard');
?>
<div class="stats">
    <div class="stat"><strong><?= (int) $summary['post_count'] ?></strong>Posts</div>
    <div class="stat"><strong><?= (int) $summary['published_count'] ?></strong>Published</div>
    <div class="stat"><strong><?= (int) $summary['draft_count'] ?></strong>Drafts</div>
    <div class="stat"><strong><?= (int) $summary['category_count'] ?></strong>Categories</div>
</div>
<p style="margin-top:1.5rem"><a class="button" href="/swd/07-php-blog/admin/posts/create.php">Write a post</a> <a class="button" href="/swd/07-php-blog/admin/categories/create.php">Add a category</a></p>
<div class="dashboard-sections">
    <section>
        <div class="section-heading"><h2>Latest posts</h2><a href="/swd/07-php-blog/admin/posts/">All posts</a></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th></tr></thead>
            <tbody>
            <?php foreach ($latestPosts as $post): ?>
                <tr>
                    <td><a href="/swd/07-php-blog/admin/posts/edit.php?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a></td>
                    <td><?= e($post['category_name']) ?></td>
                    <td><span class="status-badge status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td>
                    <td><?= e(format_date($post['updated_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$latestPosts): ?><tr><td colspan="4">No posts yet. Create your first post to get started.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
    <section>
        <div class="section-heading"><h2>Posts per category</h2><a href="/swd/07-php-blog/admin/categories/">All categories</a></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Category</th><th>Posts</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($categoryCounts as $category): ?>
                <tr>
                    <td><?= e($category['name']) ?></td>
                    <td><?= (int) $category['post_count'] ?></td>
                    <td><a href="/swd/07-php-blog/category/<?= rawurlencode($category['slug']) ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$categoryCounts): ?><tr><td colspan="3">No categories yet.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </section>
</div>
<?php admin_footer(); ?>
