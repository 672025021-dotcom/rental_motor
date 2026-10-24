<?php

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/rental_motor/public');
}

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'rental_motor');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

if (!defined('UPLOAD_KTP_DIR')) {
    define('UPLOAD_KTP_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ktp' . DIRECTORY_SEPARATOR);
}

if (!defined('UPLOAD_SIM_DIR')) {
    define('UPLOAD_SIM_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sim' . DIRECTORY_SEPARATOR);
}