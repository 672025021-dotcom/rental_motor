<?php

require_once __DIR__ . '/config.php';

function format_rupiah(int|float|string $angka): string
{
    if (!is_numeric($angka)) {
        throw new InvalidArgumentException('Nilai yang akan diformat harus berupa angka.');
    }

    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function sanitize(string $data): string
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function motor_image_url(?string $image): string
{
    $imageDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR
        . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'motor' . DIRECTORY_SEPARATOR;
    $imageName = $image !== null ? basename(str_replace('\\', '/', $image)) : '';

    if ($imageName !== '' && is_file($imageDirectory . $imageName)) {
        return rtrim(BASE_URL, '/') . '/assets/img/motor/' . rawurlencode($imageName);
    }

    return rtrim(BASE_URL, '/') . '/assets/img/motor/placeholder.svg';
}

function redirect(string $path): void
{
    header('Location: ' . rtrim(BASE_URL, '/') . '/' . ltrim($path, '/'));
    exit;
}

function generate_booking_code(): string
{
    static $generatedCodes = [];
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';

    do {
        $suffix = '';
        for ($index = 0; $index < 4; $index++) {
            $suffix .= $characters[random_int(0, strlen($characters) - 1)];
        }

        $code = 'RM-' . date('Ymd') . '-' . $suffix;
    } while (isset($generatedCodes[$code]));

    $generatedCodes[$code] = true;

    return $code;
}

function flash_message(string $name, ?string $message = null, string $type = 'success'): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($message === null) {
        $flash = $_SESSION['_flash'][$name] ?? null;
        unset($_SESSION['_flash'][$name]);

        return $flash;
    }

    if (!in_array($type, ['success', 'danger'], true)) {
        throw new InvalidArgumentException('Tipe flash message harus success atau danger.');
    }

    $_SESSION['_flash'][$name] = [
        'message' => $message,
        'type' => $type,
    ];

    return null;
}