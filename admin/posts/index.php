<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
require_admin();

$status = $_GET['status'] ?? 'all';
if (!is_string($status) || !in_array($status, ['all', 'draft', 'published'], true)) {
    $status = 'all';
}
$sql = 'SELECT p.id, p.title, p.slug, p.status, p.published_at, p.updated_at,
               c.name AS category_name, a.name AS author_name
        FROM posts p JOIN categories c ON c.id = p.category_id
        JOIN admins a ON a.id = p.author_id';
if ($status === 'all') {
    $posts = db()->query($sql . ' ORDER BY p.updated_at DESC')->fetchAll();
} else {
    $statement = db()->prepare($sql . ' WHERE p.status = :status ORDER BY p.updated_at DESC');
    $statement->execute(['status' => $status]);
    $posts = $statement->fetchAll();
}

admin_header('Posts');
?>
<div class="admin-top">
    <a class="button" href="create.php">Create post</a>
    <form method="get" class="filter-form">
        <label for="status-filter">Filter by status</label>
        <select class="form-control" id="status-filter" name="status" onchange="this.form.submit()">
            <?php foreach (['all' => 'All statuses', 'draft' => 'Drafts', 'published' => 'Published'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
    </form>
</div>
<div class="table-wrap"><table>
    <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($posts as $post): ?>
        <tr>
            <td><?= e($post['title']) ?><br><span class="muted">By <?= e($post['author_name']) ?></span></td>
            <td><?= e($post['category_name']) ?></td>
            <td><span class="status-badge status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td>
            <td><?= e(format_date($post['updated_at'])) ?></td>
            <td class="actions">
                <a href="edit.php?id=<?= (int) $post['id'] ?>">Edit</a>
                <?php if ($post['status'] === 'published'): ?><a href="/swd/07-php-blog/post/<?= rawurlencode($post['slug']) ?>">View</a><?php endif; ?>
                <form method="post" action="delete.php" onsubmit="return confirm('Delete this post?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $post['id'] ?>"><button class="danger" type="submit">Delete</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$posts): ?><tr><td colspan="5">No posts found for this filter.</td></tr><?php endif; ?>
    </tbody>
</table></div>
<?php admin_footer(); ?>
