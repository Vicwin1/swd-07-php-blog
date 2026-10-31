<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/app/db.php';

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php create-admin.php \"Your Name\" you@example.com\n");
    exit(1);
}
$name = trim($argv[1]);
$email = strtolower(trim($argv[2]));
if ($name === '' || mb_strlen($name) > 120 || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Provide a name (maximum 120 characters) and a valid email address (maximum 190 characters).\n");
    exit(1);
}

$existing = (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($existing > 0) {
    fwrite(STDERR, "An administrator already exists. The first-admin script cannot create additional accounts.\n");
    exit(1);
}
$password = getenv('ADMIN_PASSWORD') ?: '';
if ($password === '') {
    fwrite(STDOUT, "Admin password (minimum 12 characters): ");
    if (PHP_OS_FAMILY === 'Windows') {
        $secureInput = shell_exec(
            'powershell.exe -NoProfile -Command "$s=Read-Host -AsSecureString; $b=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); [Runtime.InteropServices.Marshal]::PtrToStringBSTR($b)"'
        );
        if (is_string($secureInput)) {
            $password = rtrim($secureInput, "\r\n");
        }
    } else {
        $terminal = shell_exec('stty -g 2>/dev/null');
        if (is_string($terminal) && trim($terminal) !== '') {
            shell_exec('stty -echo');
            $password = rtrim((string) fgets(STDIN), "\r\n");
            shell_exec('stty ' . escapeshellarg(trim($terminal)));
            fwrite(STDOUT, PHP_EOL);
        }
    }
    if ($password === '') {
        fwrite(STDERR, "Could not read the password securely. Set ADMIN_PASSWORD and rerun the command.\n");
        exit(1);
    }
}
if (strlen($password) < 12) {
    fwrite(STDERR, "Password must be at least 12 characters long.\n");
    exit(1);
}
try {
    $insert = db()->prepare('INSERT INTO admins (name, email, password_hash) VALUES (:name, :email, :hash)');
    $insert->execute([
        'name' => $name,
        'email' => $email,
        'hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "First admin account created for {$email}.\n");
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        fwrite(STDERR, "An admin with that email address already exists.\n");
        exit(1);
    }
    throw $exception;
}