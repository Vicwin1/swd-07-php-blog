<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once __DIR__ . '/layout.php';
require_admin();

$stats = [
    'posts' => (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'published' => (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
    'drafts' => (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn(),
    'categories' => (int) db()->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
];
$recent = db()->query(
    'SELECT p.id, p.title, p.status, p.updated_at, c.name AS category_name
     FROM posts p JOIN categories c ON c.id = p.category_id
     ORDER BY p.updated_at DESC LIMIT 8'
)->fetchAll();
admin_header('Dashboard');
?>
<section class="stats-grid">
    <div class="stat-card"><span>All posts</span><strong><?= $stats['posts'] ?></strong></div>
    <div class="stat-card"><span>Published</span><strong><?= $stats['published'] ?></strong></div>
    <div class="stat-card"><span>Drafts</span><strong><?= $stats['drafts'] ?></strong></div>
    <div class="stat-card"><span>Categories</span><strong><?= $stats['categories'] ?></strong></div>
</section>
<section class="panel">
    <div class="panel-heading"><h2>Recent posts</h2><a class="button-link" href="<?= e(url('admin/posts/create.php')) ?>">New post</a></div>
    <?php if (!$recent): ?><p class="empty-state">No posts yet. Create a post to get started.</p><?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th></th></tr></thead><tbody>
        <?php foreach ($recent as $post): ?>
            <tr><td><?= e($post['title']) ?></td><td><?= e($post['category_name']) ?></td><td><span class="status <?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td><td><?= e(format_date($post['updated_at'])) ?></td><td><a href="<?= e(url('admin/posts/edit.php?id=' . $post['id'])) ?>">Edit</a></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>