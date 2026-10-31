<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
verify_csrf();
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid post.');
}
$query = db()->prepare('SELECT cover_image FROM posts WHERE id = :id');
$query->execute(['id' => $id]);
$image = $query->fetchColumn();
$delete = db()->prepare('DELETE FROM posts WHERE id = :id');
$delete->execute(['id' => $id]);
if ($delete->rowCount()) {
    delete_uploaded_image(is_string($image) ? $image : null);
    flash('success', 'Post deleted.');
} else {
    flash('error', 'Post not found.');
}
redirect(url('admin/posts/'));