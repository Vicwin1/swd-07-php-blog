<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
require_admin();

$errors = [];
$values = ['name' => '', 'slug' => '', 'description' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (array_keys($values) as $key) $values[$key] = post_value($key);
    $values['slug'] = slugify($values['slug'] !== '' ? $values['slug'] : $values['name']);
    if ($values['name'] === '' || mb_strlen($values['name']) > 120) $errors[] = 'Name is required and must be 120 characters or fewer.';
    if (mb_strlen($values['slug']) > 160) $errors[] = 'Slug must be 160 characters or fewer.';
    if (!$errors) {
        try {
            $statement = db()->prepare('INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :description)');
            $statement->execute(['name' => $values['name'], 'slug' => $values['slug'], 'description' => $values['description'] ?: null]);
            flash('success', 'Category created.');
            redirect('/swd/07-php-blog/admin/categories/');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') $errors[] = 'That slug is already in use.';
            else throw $exception;
        }
    }
}
admin_header('Create category');
?>
<form class="form-card" method="post">
    <?= csrf_field() ?><?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <label class="field">Name<input class="form-control" name="name" maxlength="120" required value="<?= e($values['name']) ?>"></label>
    <label class="field">Slug<input class="form-control" name="slug" maxlength="160" value="<?= e($values['slug']) ?>" placeholder="Generated from name if empty"></label>
    <label class="field">Description<textarea class="form-control" name="description"><?= e($values['description']) ?></textarea></label>
    <button type="submit">Save category</button> <a href="/swd/07-php-blog/admin/categories/">Cancel</a>
</form>
<?php admin_footer(); ?>
