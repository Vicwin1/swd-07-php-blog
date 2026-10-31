<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$query = db()->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
$query->execute(['id' => $id ?: 0]);
$category = $query->fetch();
if (!$category) {
    http_response_code(404);
    exit('Category not found.');
}
$errors = [];
$name = $category['name'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = post_value('name');
    if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Name is required and must be 120 characters or fewer.';
    if (!$errors) {
        $update = db()->prepare('UPDATE categories SET name = :name, slug = :slug WHERE id = :id');
        try {
            $update->execute([
                'name' => $name,
                'slug' => unique_slug('categories', $name, (int) $category['id']),
                'id' => (int) $category['id'],
            ]);
            flash('success', 'Category updated.');
            redirect(url('admin/categories/'));
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') throw $exception;
            $errors[] = 'A category with this name or slug already exists.';
        }
    }
}
admin_header('Edit category');
?>
<section class="panel form-panel">
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label class="field">Name<input class="form-control" name="name" maxlength="120" required value="<?= e($name) ?>"></label>
        <div class="form-actions"><button type="submit">Save changes</button><a class="button-link secondary" href="<?= e(url('admin/categories/')) ?>">Cancel</a></div>
    </form>
</section>
<?php admin_footer(); ?>