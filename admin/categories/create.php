<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
require_admin();

$errors = [];
$name = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = post_value('name');
    if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Name is required and must be 120 characters or fewer.';
    if (!$errors) {
        $insert = db()->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug)');
        try {
            $insert->execute(['name' => $name, 'slug' => unique_slug('categories', $name)]);
            flash('success', 'Category created.');
            redirect(url('admin/categories/'));
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') throw $exception;
            $errors[] = 'A category with this name or slug already exists.';
        }
    }
}
admin_header('Create category');
?>
<section class="panel form-panel">
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label class="field">Name<input class="form-control" name="name" maxlength="120" required value="<?= e($name) ?>"></label>
        <div class="form-actions"><button type="submit">Create category</button><a class="button-link secondary" href="<?= e(url('admin/categories/')) ?>">Cancel</a></div>
    </form>
</section>
<?php admin_footer(); ?>