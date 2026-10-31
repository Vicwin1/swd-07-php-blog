<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
require_admin();

$posts = db()->query(
    'SELECT p.id, p.title, p.slug, p.status, p.published_at, p.updated_at, c.name AS category_name, a.name AS author_name
     FROM posts p JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id
     ORDER BY p.updated_at DESC'
)->fetchAll();

admin_header('Posts');
?>
<p><a class="button" href="create.php">Create post</a></p>
<div class="table-wrap"><table>
    <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($posts as $post): ?>
        <tr>
            <td><?= e($post['title']) ?><br><span class="muted">By <?= e($post['author_name']) ?></span></td>
            <td><?= e($post['category_name']) ?></td><td><?= e(ucfirst($post['status'])) ?></td><td><?= e(format_date($post['updated_at'])) ?></td>
            <td class="actions">
                <a href="edit.php?id=<?= (int) $post['id'] ?>">Edit</a>
                <?php if ($post['status'] === 'published'): ?><a href="/swd/07-php-blog/post.php?slug=<?= e($post['slug']) ?>">View</a><?php endif; ?>
                <form method="post" action="delete.php" onsubmit="return confirm('Delete this post?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $post['id'] ?>"><button class="danger" type="submit">Delete</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$posts): ?><tr><td colspan="5">No posts yet.</td></tr><?php endif; ?>
    </tbody>
</table></div>
<?php admin_footer(); ?>
