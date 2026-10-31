<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$getPost = db()->prepare('SELECT * FROM posts WHERE id = :id');
$getPost->execute(['id' => $id]);
$post = $getPost->fetch();
if (!$post) {
    http_response_code(404);
    exit('Post not found.');
}
$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$errors = [];
$values = ['title' => $post['title'], 'slug' => $post['slug'], 'body' => $post['body'], 'category_id' => (string) $post['category_id'], 'status' => $post['status']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (array_keys($values) as $key) $values[$key] = post_value($key, $values[$key]);
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
            $cover = store_cover_image($_FILES['cover_image'] ?? null, $post['cover_image']);
            $publishedAt = $values['status'] === 'published'
                ? ($post['published_at'] ?: date('Y-m-d H:i:s'))
                : null;
            $statement = db()->prepare(
                'UPDATE posts SET category_id = :category_id, title = :title, slug = :slug, body = :body,
                 status = :status, cover_image = :cover_image, published_at = :published_at WHERE id = :id'
            );
            $statement->execute([
                'category_id' => $values['category_id'],
                'title' => $values['title'],
                'slug' => $values['slug'],
                'body' => $values['body'],
                'status' => $values['status'],
                'cover_image' => $cover,
                'published_at' => $publishedAt,
                'id' => $id,
            ]);
            if ($cover !== $post['cover_image']) remove_cover_image($post['cover_image']);
            flash('success', 'Post updated.');
            redirect('/swd/07-php-blog/admin/posts/');
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') $errors[] = 'That slug is already in use.';
            else throw $exception;
        }
    }
}

admin_header('Edit post');
?>
<form class="form-card" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <label class="field">Title<input class="form-control" name="title" maxlength="240" required value="<?= e($values['title']) ?>"></label>
    <label class="field">Slug<input class="form-control" name="slug" maxlength="260" required value="<?= e($values['slug']) ?>"></label>
    <label class="field">Category<select class="form-control" name="category_id" required><option value="">Select category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) $category['id'] === $values['category_id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></label>
    <label class="field">Body<textarea class="form-control" name="body" required><?= e($values['body']) ?></textarea></label>
    <?php if ($post['cover_image']): ?><p>Current cover: <img src="/swd/07-php-blog/uploads/<?= e($post['cover_image']) ?>" alt="" style="max-width:180px;display:block"></p><?php endif; ?>
    <label class="field">Replace cover image (JPG, PNG, GIF, WebP; up to 5 MB)<input class="form-control" type="file" name="cover_image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
    <label class="field">Status<select class="form-control" name="status"><option value="draft" <?= $values['status'] === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $values['status'] === 'published' ? 'selected' : '' ?>>Published</option></select></label>
    <button type="submit">Update post</button> <a href="/swd/07-php-blog/admin/posts/">Cancel</a>
</form>
<?php admin_footer(); ?>
