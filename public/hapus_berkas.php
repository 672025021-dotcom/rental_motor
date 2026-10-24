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
$fileType = $_POST['file_type'] ?? '';
$csrfToken = $_POST['_csrf_token'] ?? '';

if (!is_string($csrfToken) || empty($_SESSION['_csrf_token'])
    || !hash_equals($_SESSION['_csrf_token'], $csrfToken)) {
    flash_message('error', 'Sesi formulir tidak valid. Silakan coba kembali.', 'danger');
    redirect($bookingId > 0 ? 'pesanan_detail.php?id=' . $bookingId : 'pesanan.php');
}

if ($bookingId < 1 || !is_string($fileType) || !in_array($fileType, ['ktp', 'sim'], true)) {
    flash_message('error', 'Data berkas yang akan dihapus tidak valid.', 'danger');
    redirect($bookingId > 0 ? 'pesanan_detail.php?id=' . $bookingId : 'pesanan.php');
}

$user = currentUser();
try {
    if (!Booking::deleteBookingFile($bookingId, $user['id'], $fileType)) {
        flash_message('error', 'Berkas tidak ditemukan atau sudah dihapus.', 'danger');
    } else {
        $fileLabel = $fileType === 'ktp' ? 'KTP' : 'SIM';
        flash_message('success', 'Berkas ' . $fileLabel . ' berhasil dihapus.', 'success');
    }
} catch (BookingStateException $exception) {
    flash_message('error', $exception->getMessage(), 'danger');
} catch (Throwable $exception) {
    error_log('Gagal menghapus berkas booking ' . $bookingId . ': ' . $exception->getMessage());
    flash_message('error', 'Terjadi kesalahan saat menghapus berkas. Silakan coba kembali.', 'danger');
}

redirect('pesanan_detail.php?id=' . $bookingId);
