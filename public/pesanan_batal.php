<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/Booking.php';

requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode request tidak diizinkan.');
}

$rawId = $_POST['booking_id'] ?? '';
$bookingId = is_string($rawId) && ctype_digit($rawId) ? (int) $rawId : 0;
$reason = $_POST['cancel_reason'] ?? '';
$csrfToken = $_POST['_csrf_token'] ?? '';

if (!is_string($csrfToken) || empty($_SESSION['_csrf_token'])
    || !hash_equals($_SESSION['_csrf_token'], $csrfToken)) {
    flash_message('error', 'Sesi formulir tidak valid. Silakan coba kembali.', 'danger');
    redirect('pesanan.php');
}
if ($bookingId < 1 || !is_string($reason) || trim($reason) === '' || strlen($reason) > 255) {
    flash_message('error', 'Data pembatalan tidak valid.', 'danger');
    redirect('pesanan.php');
}

$user = currentUser();
$booking = Booking::getBookingDetailForUser($bookingId, $user['id']);
if ($booking === null) {
    flash_message('error', 'Pesanan tidak ditemukan.', 'danger');
    redirect('pesanan.php');
}
if ($booking['status'] !== 'pending') {
    flash_message('error', 'Hanya pesanan berstatus menunggu yang dapat dibatalkan.', 'danger');
    redirect('pesanan_detail.php?id=' . $bookingId);
}

if (!Booking::cancelBookingCustomer($bookingId, $user['id'], trim($reason))) {
    flash_message('error', 'Pesanan tidak lagi dapat dibatalkan.', 'danger');
    redirect('pesanan.php');
}

flash_message('success', 'Pesanan berhasil dibatalkan.', 'success');
redirect('pesanan.php');