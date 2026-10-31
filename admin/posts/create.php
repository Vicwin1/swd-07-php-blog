<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
require_admin();

$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$errors = [];
$title = $excerptText = $body = '';
$categoryId = '';
$status = 'draft';
$image = null;
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
        $image = null;
        try {
            $image = save_uploaded_image($_FILES['cover_image'] ?? null);
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
        if (!$errors) {
            $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
            try {
                $insert = db()->prepare(
                    'INSERT INTO posts (category_id, author_id, title, slug, excerpt, body, status, cover_image, published_at)
                     VALUES (:category_id, :author_id, :title, :slug, :excerpt, :body, :status, :cover_image, :published_at)'
                );
                $insert->execute([
                    'category_id' => (int) $categoryId,
                    'author_id' => current_admin()['id'],
                    'title' => $title,
                    'slug' => unique_slug('posts', $title),
                    'excerpt' => $excerptText !== '' ? $excerptText : null,
                    'body' => $body,
                    'status' => $status,
                    'cover_image' => $image,
                    'published_at' => $publishedAt,
                ]);
                flash('success', 'Post created.');
                redirect(url('admin/posts/'));
            } catch (Throwable $exception) {
                if ($image !== null) delete_uploaded_image($image);
                throw $exception;
            }
        }
    }
}
admin_header('Create post');
?>
<section class="panel form-panel">
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <?php if (!$categories): ?><div class="alert error">Create a category before adding posts.</div><?php else: ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label class="field">Title<input class="form-control" name="title" maxlength="240" required value="<?= e($title) ?>"></label>
        <label class="field">Category<select class="form-control" name="category_id" required><option value="">Choose category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></label>
        <label class="field">Excerpt<textarea class="form-control" name="excerpt" rows="3"><?= e($excerptText) ?></textarea></label>
        <label class="field">Body<textarea class="form-control" name="body" rows="14" required><?= e($body) ?></textarea><span class="field-hint">Separate paragraphs with a blank line. HTML is displayed as plain text.</span></label>
        <label class="field">Cover image<input class="form-control" type="file" name="cover_image" accept="image/jpeg,image/png,image/gif,image/webp"><span class="field-hint">JPG, PNG, GIF, or WebP; maximum 5 MB.</span></label>
        <label class="field">Status<select class="form-control" name="status"><option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option></select></label>
        <div class="form-actions"><button type="submit">Save post</button><a class="button-link secondary" href="<?= e(url('admin/posts/')) ?>">Cancel</a></div>
    </form>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>