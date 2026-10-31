<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['_csrf'] ?? null;
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        exit('The request could not be verified. Please go back and try again.');
    }
}