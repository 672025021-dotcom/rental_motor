<?php

require_once __DIR__ . '/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function loginUser(array $user): void
{
    if (!isset($user['id'], $user['full_name'], $user['email'], $user['role'])) {
        throw new InvalidArgumentException('Data pengguna untuk login tidak lengkap.');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['full_name'] = (string) $user['full_name'];
    $_SESSION['email'] = (string) $user['email'];
    $_SESSION['role'] = (string) $user['role'];
}

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookieParameters = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookieParameters['path'],
            'domain' => $cookieParameters['domain'],
            'secure' => $cookieParameters['secure'],
            'httponly' => $cookieParameters['httponly'],
            'samesite' => $cookieParameters['samesite'] ?? 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    session_id('');
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'full_name' => (string) ($_SESSION['full_name'] ?? ''),
        'email' => (string) ($_SESSION['email'] ?? ''),
        'role' => (string) ($_SESSION['role'] ?? 'customer'),
    ];
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash_message('error', 'Silakan login terlebih dahulu.', 'danger');
        redirect('login.php');
    }
}

function requireGuest(): void
{
    if (isLoggedIn()) {
        redirect('katalog.php');
    }
}