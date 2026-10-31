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
$values = ['title' => $post['title'], 'excerpt' => $post['excerpt'] ?? '', 'body' => $post['body'], 'category_id' => (string) $post['category_id'], 'status' => $post['status']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (array_keys($values) as $key) $values[$key] = post_value($key, $values[$key]);
    if ($values['title'] === '' || mb_strlen($values['title']) > 240) $errors[] = 'Title is required and must be 240 characters or fewer.';
    if (mb_strlen($values['excerpt']) > 1000) $errors[] = 'Excerpt must be 1000 characters or fewer.';
    if ($values['body'] === '') $errors[] = 'Post body is required.';
    if (!in_array($values['status'], ['draft', 'published'], true)) $errors[] = 'Choose a valid status.';
    $categoryCheck = db()->prepare('SELECT id FROM categories WHERE id = :id');
    $categoryCheck->execute(['id' => $values['category_id']]);
    if (!$categoryCheck->fetch()) $errors[] = 'Choose a category.';

    if (!$errors) {
        $cover = $post['cover_image'];
        $newCoverUploaded = false;
        $savedToDatabase = false;
        try {
            $cover = store_cover_image($_FILES['cover_image'] ?? null, $post['cover_image']);
            $newCoverUploaded = $cover !== $post['cover_image'];
            $slug = unique_post_slug($values['title'], (int) $id);
            $statement = db()->prepare(
                'UPDATE posts SET category_id = :category_id, title = :title, slug = :slug, excerpt = :excerpt,
                 body = :body, status = :status, cover_image = :cover_image,
                 published_at = CASE WHEN :published = \'published\' AND published_at IS NULL
                                     THEN NOW() ELSE published_at END
                 WHERE id = :id'
            );
            $statement->execute([
                'category_id' => $values['category_id'],
                'title' => $values['title'],
                'slug' => $slug,
                'excerpt' => $values['excerpt'] !== '' ? $values['excerpt'] : null,
                'body' => $values['body'],
                'status' => $values['status'],
                'cover_image' => $cover,
                'published' => $values['status'],
                'id' => $id,
            ]);
            $savedToDatabase = true;
            if ($newCoverUploaded) remove_cover_image($post['cover_image']);
            flash('success', 'Post updated.');
            redirect('/swd/07-php-blog/admin/posts/');
        } catch (RuntimeException $exception) {
            if (!$savedToDatabase && $newCoverUploaded && $cover !== $post['cover_image']) {
                remove_cover_image($cover);
            }
            $errors[] = $exception->getMessage();
        } catch (PDOException $exception) {
            if (!$savedToDatabase && $newCoverUploaded && $cover !== $post['cover_image']) {
                remove_cover_image($cover);
            }
            if ($exception->getCode() === '23000') {
                $errors[] = 'A post with that generated slug already exists. Try changing the title.';
            } else {
                throw $exception;
            }
        }
    }
}

admin_header('Edit post');
?>
<form class="form-card" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <label class="field">Title<input class="form-control" name="title" maxlength="240" required value="<?= e($values['title']) ?>"></label>
    <label class="field">Category<select class="form-control" name="category_id" required><option value="">Select category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) $category['id'] === $values['category_id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></label>
    <label class="field">Excerpt<textarea class="form-control" name="excerpt" maxlength="1000" rows="3"><?= e($values['excerpt']) ?></textarea></label>
    <label class="field">Body<textarea class="form-control" name="body" required><?= e($values['body']) ?></textarea></label>
    <?php if ($post['cover_image']): ?><p>Current cover: <img src="/swd/07-php-blog/uploads/<?= e($post['cover_image']) ?>" alt="" style="max-width:180px;display:block"></p><?php endif; ?>
    <label class="field">Replace cover image (JPEG, PNG, WebP; up to 2 MB)<input class="form-control" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp"></label>
    <label class="field">Status<select class="form-control" name="status"><option value="draft" <?= $values['status'] === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $values['status'] === 'published' ? 'selected' : '' ?>>Published</option></select></label>
    <button type="submit">Update post</button> <a href="/swd/07-php-blog/admin/posts/">Cancel</a>
</form>
<?php admin_footer(); ?>
