<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$statement = db()->prepare('SELECT cover_image FROM posts WHERE id = :id');
$statement->execute(['id' => $id]);
$post = $statement->fetch();
if ($post) {
    db()->prepare('DELETE FROM posts WHERE id = :id')->execute(['id' => $id]);
    remove_cover_image($post['cover_image']);
    flash('success', 'Post deleted.');
}
redirect('/swd/07-php-blog/admin/posts/');
