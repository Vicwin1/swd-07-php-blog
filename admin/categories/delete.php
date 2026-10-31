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
try {
    db()->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $id]);
    flash('success', 'Category deleted.');
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        flash('error', 'This category still has posts. Move or delete those posts before deleting the category.');
    } else {
        throw $exception;
    }
}
redirect('/swd/07-php-blog/admin/categories/');
