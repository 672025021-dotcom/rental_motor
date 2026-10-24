<?php

require_once __DIR__ . '/config.php';

function getDBConnection(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST
        . ';port=' . DB_PORT
        . ';dbname=' . DB_NAME
        . ';charset=utf8mb4';

    try {
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        throw new RuntimeException(
            'Gagal terhubung ke database "' . DB_NAME . '" pada ' . DB_HOST . ':' . DB_PORT
                . '. Periksa konfigurasi koneksi database.',
            0,
            $exception
        );
    }
}