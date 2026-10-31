<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
verify_csrf();
logout_admin();
redirect(url('admin/login.php'));