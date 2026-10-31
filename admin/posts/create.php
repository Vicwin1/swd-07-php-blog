<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
require_admin();

$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$errors = [];
$values = ['title' => '', 'slug' => '', 'body' => '', 'category_id' => '', 'status' => 'draft'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (array_keys($values) as $key) {
        $values[$key] = post_value($key, $values[$key]);
    }
    $values['slug'] = slugify($values['slug'] !== '' ? $values['slug'] : $values['title']);
    if ($values['title'] === '' || mb_strlen($values['title']) > 240) $errors[] = 'Title is required and must be 240 characters or fewer.';
    if (mb_strlen($values['slug']) > 260) $errors[] = 'Slug must be 260 characters or fewer.';
    if ($values['body'] === '') $errors[] = 'Post body is required.';
    if (!in_array($values['status'], ['draft', 'published'], true)) $errors[] = 'Choose a valid status.';
    $categoryCheck = db()->prepare('SELECT id FROM categories WHERE id = :id');
    $categoryCheck->execute(['id' => $values['category_id']]);
    if (!$categoryCheck->fetch()) $errors[] = 'Choose a category.';

    if (!$errors) {
        try {
            $cover = store_cover_image($_FILES['cover_image'] ?? null);
            $statement = db()->prepare(
                'INSERT INTO posts (category_id, author_id, title, slug, body, status, cover_image, published_at)
                 VALUES (:category_id, :author_id, :title, :slug, :body, :status, :cover_image, :published_at)'
            );
            $statement->execute([
                'category_id' => $values['category_id'],
                'author_id' => current_admin()['id'],
                'title' => $values['title'],
                'slug' => $values['slug'],
                'body' => $values['body'],
                'status' => $values['status'],
                'cover_image' => $cover,
                'published_at' => $values['status'] === 'published' ? date('Y-m-d H:i:s') : null,
            ]);
            flash('success', 'Post created.');
            redirect('/swd/07-php-blog/admin/posts/');
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') $errors[] = 'That slug is already in use.';
            else throw $exception;
        }
    }
}

admin_header('Create post');
?>
<form class="form-card" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="MAX_FILE_SIZE" value="5242880">
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <label class="field">Title<input class="form-control" name="title" maxlength="240" required value="<?= e($values['title']) ?>"></label>
    <label class="field">Slug<input class="form-control" name="slug" maxlength="260" value="<?= e($values['slug']) ?>" placeholder="Generated from title if empty"></label>
    <label class="field">Category<select class="form-control" name="category_id" required><option value="">Select category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) $category['id'] === $values['category_id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></label>
    <label class="field">Body<textarea class="form-control" name="body" required><?= e($values['body']) ?></textarea></label>
    <label class="field">Cover image (JPG, PNG, GIF, WebP; up to 5 MB)<input class="form-control" type="file" name="cover_image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
    <label class="field">Status<select class="form-control" name="status"><option value="draft" <?= $values['status'] === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $values['status'] === 'published' ? 'selected' : '' ?>>Published</option></select></label>
    <button type="submit">Save post</button> <a href="/swd/07-php-blog/admin/posts/">Cancel</a>
</form>
<?php admin_footer(); ?>
