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
    exit('Invalid category.');
}
try {
    $delete = db()->prepare('DELETE FROM categories WHERE id = :id');
    $delete->execute(['id' => $id]);
    flash($delete->rowCount() ? 'success' : 'error', $delete->rowCount() ? 'Category deleted.' : 'Category not found.');
} catch (PDOException $exception) {
    if ($exception->getCode() !== '23000') throw $exception;
    flash('error', 'This category cannot be deleted because posts still use it.');
}
redirect(url('admin/categories/'));