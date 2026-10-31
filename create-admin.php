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

if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    fwrite(STDERR, "Provide a name (maximum 120 characters) and a valid email address (maximum 190 characters).\n");
    exit(1);
}

$password = getenv('ADMIN_PASSWORD') ?: '';
if ($password === '') {
    fwrite(STDOUT, 'Admin password (minimum 12 characters): ');
    if (PHP_OS_FAMILY === 'Windows' && function_exists('shell_exec')) {
        $password = trim((string) shell_exec(
            'powershell.exe -NoProfile -Command "$p=Read-Host -AsSecureString; $b=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($p); [Runtime.InteropServices.Marshal]::PtrToStringBSTR($b)"'
        ));
    } elseif (PHP_OS_FAMILY !== 'Windows' && function_exists('shell_exec')) {
        $terminalMode = shell_exec('stty -g 2>/dev/null');
        if (is_string($terminalMode) && trim($terminalMode) !== '') {
            shell_exec('stty -echo');
            $password = trim((string) fgets(STDIN));
            shell_exec('stty ' . escapeshellarg(trim($terminalMode)));
            fwrite(STDOUT, PHP_EOL);
        }
    }

    if ($password === '') {
        $password = trim((string) fgets(STDIN));
    }
}

if (strlen($password) < 12) {
    fwrite(STDERR, "Password must be at least 12 characters long.\n");
    exit(1);
}

try {
    $statement = db()->prepare(
        'INSERT INTO admins (name, email, password_hash) VALUES (:name, :email, :password_hash)'
    );
    $statement->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Admin account created for {$email}.\n");
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        fwrite(STDERR, "An admin with that email address already exists.\n");
        exit(1);
    }
    throw $exception;
}
