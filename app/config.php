<?php

function loadEnv(string $path): void
{
    if (!file_exists($path) || !is_readable($path)) {
        throw new RuntimeException('File .env tidak ditemukan atau tidak dapat dibaca: ' . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('Gagal membaca file .env: ' . $path);
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if ($name === '') {
            continue;
        }

        if (
            strlen($value) >= 2
            && (($value[0] === '"' && $value[strlen($value) - 1] === '"')
                || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$name] = $value;
        putenv($name . '=' . $value);
    }
}

loadEnv(__DIR__ . '/../.env');

$getEnvValue = static fn (string $name, string $default = ''): string => $_ENV[$name] ?? $default;
$projectRoot = dirname(__DIR__);
$resolveStorageDirectory = static function (string $path) use ($projectRoot): string {
    $path = trim($path);
    $isWindowsAbsolutePath = preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1;
    $isAbsolutePath = str_starts_with($path, DIRECTORY_SEPARATOR) || $isWindowsAbsolutePath;
    $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

    if (!$isAbsolutePath) {
        $normalizedPath = $projectRoot . DIRECTORY_SEPARATOR . $normalizedPath;
    }

    return rtrim($normalizedPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
};

if (!defined('APP_NAME')) {
    define('APP_NAME', $getEnvValue('APP_NAME', 'Rental Motor'));
}

if (!defined('APP_ENV')) {
    define('APP_ENV', $getEnvValue('APP_ENV', 'local'));
}

if (!defined('BASE_URL')) {
    define('BASE_URL', $getEnvValue('BASE_URL', 'http://localhost/rental_motor/public'));
}

if (!defined('DB_HOST')) {
    define('DB_HOST', $getEnvValue('DB_HOST', '127.0.0.1'));
}

if (!defined('DB_PORT')) {
    define('DB_PORT', $getEnvValue('DB_PORT', '3306'));
}

if (!defined('DB_NAME')) {
    define('DB_NAME', $getEnvValue('DB_NAME', 'rental_motor'));
}

if (!defined('DB_USER')) {
    define('DB_USER', $getEnvValue('DB_USER', 'root'));
}

if (!defined('DB_PASS')) {
    define('DB_PASS', $getEnvValue('DB_PASS'));
}

if (!defined('UPLOAD_KTP_DIR')) {
    define(
        'UPLOAD_KTP_DIR',
        $resolveStorageDirectory($getEnvValue('UPLOAD_KTP_DIR', 'storage/uploads/ktp/'))
    );
}

if (!defined('UPLOAD_SIM_DIR')) {
    define(
        'UPLOAD_SIM_DIR',
        $resolveStorageDirectory($getEnvValue('UPLOAD_SIM_DIR', 'storage/uploads/sim/'))
    );
}

if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', (int) $getEnvValue('MAX_FILE_SIZE', '2097152'));
}
