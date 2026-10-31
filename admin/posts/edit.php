<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$query = db()->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
$query->execute(['id' => $id ?: 0]);
$post = $query->fetch();
if (!$post) {
    http_response_code(404);
    exit('Post not found.');
}
$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$errors = [];
$title = $post['title'];
$excerptText = $post['excerpt'] ?? '';
$body = $post['body'];
$categoryId = (string) $post['category_id'];
$status = $post['status'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = post_value('title');
    $excerptText = post_value('excerpt');
    $body = post_value('body');
    $categoryId = post_value('category_id');
    $status = post_value('status');
    if ($title === '' || mb_strlen($title) > 240) $errors[] = 'Title is required and must be 240 characters or fewer.';
    if ($body === '') $errors[] = 'Post body is required.';
    if (!ctype_digit($categoryId) || !in_array((int) $categoryId, array_map(static fn($c) => (int) $c['id'], $categories), true)) $errors[] = 'Choose a valid category.';
    if (!in_array($status, ['draft', 'published'], true)) $errors[] = 'Choose a valid post status.';
    if (!$errors) {
        $newImage = null;
        try {
            $newImage = save_uploaded_image($_FILES['cover_image'] ?? null);
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
        if (!$errors) {
            $publishedAt = $status === 'published'
                ? ($post['published_at'] ?: date('Y-m-d H:i:s'))
                : null;
            try {
                $update = db()->prepare(
                    'UPDATE posts SET category_id = :category_id, title = :title, slug = :slug,
                     excerpt = :excerpt, body = :body, status = :status, cover_image = :cover_image,
                     published_at = :published_at WHERE id = :id'
                );
                $update->execute([
                    'category_id' => (int) $categoryId,
                    'title' => $title,
                    'slug' => unique_slug('posts', $title, (int) $post['id']),
                    'excerpt' => $excerptText !== '' ? $excerptText : null,
                    'body' => $body,
                    'status' => $status,
                    'cover_image' => $newImage ?? $post['cover_image'],
                    'published_at' => $publishedAt,
                    'id' => (int) $post['id'],
                ]);
                if ($newImage !== null && $post['cover_image']) delete_uploaded_image($post['cover_image']);
                flash('success', 'Post updated.');
                redirect(url('admin/posts/'));
            } catch (Throwable $exception) {
                if ($newImage !== null) delete_uploaded_image($newImage);
                throw $exception;
            }
        }
    }
}
admin_header('Edit post');
?>
<section class="panel form-panel">
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label class="field">Title<input class="form-control" name="title" maxlength="240" required value="<?= e($title) ?>"></label>
        <label class="field">Category<select class="form-control" name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></label>
        <label class="field">Excerpt<textarea class="form-control" name="excerpt" rows="3"><?= e($excerptText) ?></textarea></label>
        <label class="field">Body<textarea class="form-control" name="body" rows="14" required><?= e($body) ?></textarea><span class="field-hint">Separate paragraphs with a blank line. HTML is displayed as plain text.</span></label>
        <?php if ($post['cover_image']): ?><p>Current cover: <a href="<?= e(url($post['cover_image'])) ?>" target="_blank" rel="noopener">View image</a></p><?php endif; ?>
        <label class="field">Replace cover image<input class="form-control" type="file" name="cover_image" accept="image/jpeg,image/png,image/gif,image/webp"><span class="field-hint">Leave blank to keep the current image. Maximum 5 MB.</span></label>
        <label class="field">Status<select class="form-control" name="status"><option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option></select></label>
        <div class="form-actions"><button type="submit">Save changes</button><a class="button-link secondary" href="<?= e(url('admin/posts/')) ?>">Cancel</a></div>
    </form>
</section>
<?php admin_footer(); ?>