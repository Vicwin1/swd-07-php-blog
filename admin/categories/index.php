<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
require_admin();

$categories = db()->query(
    'SELECT c.id, c.name, c.slug, c.description, COUNT(p.id) AS post_count
     FROM categories c LEFT JOIN posts p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.name'
)->fetchAll();
admin_header('Categories');
?>
<section class="panel">
    <div class="panel-heading"><h2>All categories</h2><a class="button-link" href="<?= e(url('admin/categories/create.php')) ?>">Create category</a></div>
    <?php if (!$categories): ?><p class="empty-state">There are no categories yet.</p><?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= e($category['name']) ?></td><td><?= e($category['slug']) ?></td><td><?= (int) $category['post_count'] ?></td>
                <td class="table-actions">
                    <a href="<?= e(url('admin/categories/edit.php?id=' . $category['id'])) ?>">Edit</a>
                    <form method="post" action="<?= e(url('admin/categories/delete.php')) ?>" onsubmit="return confirm('Delete this category? It cannot be deleted while posts use it.')">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                        <button class="link-button danger-text" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>