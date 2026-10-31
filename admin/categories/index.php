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
<p><a class="button" href="create.php">Add category</a></p>
<div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($categories as $category): ?>
        <tr><td><?= e($category['name']) ?></td><td><?= e($category['slug']) ?></td><td><?= (int) $category['post_count'] ?></td>
        <td class="actions"><a href="edit.php?id=<?= (int) $category['id'] ?>">Edit</a>
            <form method="post" action="delete.php" onsubmit="return confirm('Delete this category? Posts in it must be moved first.')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $category['id'] ?>"><button class="danger" type="submit">Delete</button></form>
        </td></tr>
    <?php endforeach; ?>
    <?php if (!$categories): ?><tr><td colspan="4">No categories yet.</td></tr><?php endif; ?>
    </tbody>
</table></div>
<?php admin_footer(); ?>
