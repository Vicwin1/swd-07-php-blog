<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
require_admin();

$posts = db()->query(
    'SELECT p.id, p.title, p.status, p.updated_at, c.name AS category_name, a.name AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     ORDER BY p.updated_at DESC, p.id DESC'
)->fetchAll();
admin_header('Posts');
?>
<section class="panel">
    <div class="panel-heading"><h2>All posts</h2><a class="button-link" href="<?= e(url('admin/posts/create.php')) ?>">Create post</a></div>
    <?php if (!$posts): ?><p class="empty-state">There are no posts yet.</p><?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($posts as $post): ?>
            <tr>
                <td><?= e($post['title']) ?></td><td><?= e($post['category_name']) ?></td><td><?= e($post['author_name']) ?></td>
                <td><span class="status <?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td><td><?= e(format_date($post['updated_at'])) ?></td>
                <td class="table-actions">
                    <a href="<?= e(url('admin/posts/edit.php?id=' . $post['id'])) ?>">Edit</a>
                    <form method="post" action="<?= e(url('admin/posts/delete.php')) ?>" onsubmit="return confirm('Delete this post?')">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                        <button class="link-button danger-text" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>