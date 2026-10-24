<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/Booking.php';
require_once __DIR__ . '/../app/models/Motor.php';

class BookingUploadException extends RuntimeException
{
}

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode request tidak diizinkan.');
}

$rawMotorId = $_POST['motor_id'] ?? '';
$motorId = is_string($rawMotorId) && ctype_digit($rawMotorId) ? (int) $rawMotorId : 0;
$formUrl = 'booking_form.php?motor_id=' . $motorId;
$csrfToken = $_POST['_csrf_token'] ?? '';
if (!is_string($csrfToken) || empty($_SESSION['_csrf_token'])
    || !hash_equals($_SESSION['_csrf_token'], $csrfToken)) {
    flash_message('error', 'Sesi formulir tidak valid. Silakan coba kembali.', 'danger');
    redirect($formUrl);
}

$motor = $motorId > 0 ? Motor::getMotorById($motorId) : null;
if ($motor === null || $motor['status'] !== 'tersedia') {
    flash_message('error', 'Motor tidak ditemukan atau sedang tidak tersedia.', 'danger');
    redirect('katalog.php');
}

$readPostString = static function (string $key): string {
    $value = $_POST[$key] ?? '';

    return is_string($value) ? trim($value) : '';
};

$startDate = $readPostString('start_date');
$endDate = $readPostString('end_date');
$pickupMethod = $readPostString('pickup_method');
$deliveryAddress = $readPostString('delivery_address');
$notes = $readPostString('notes');
$start = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);
$end = DateTimeImmutable::createFromFormat('!Y-m-d', $endDate);
$today = new DateTimeImmutable('today');

if ($start === false || $start->format('Y-m-d') !== $startDate
    || $end === false || $end->format('Y-m-d') !== $endDate
    || $start < $today || $end < $start) {
    flash_message('error', 'Tanggal sewa tidak valid. Tanggal mulai minimal hari ini dan tanggal selesai tidak boleh sebelum tanggal mulai.', 'danger');
    redirect($formUrl);
}

if (!in_array($pickupMethod, ['ambil_sendiri', 'diantar'], true)) {
    flash_message('error', 'Metode pengambilan tidak valid.', 'danger');
    redirect($formUrl);
}
if ($pickupMethod === 'diantar' && $deliveryAddress === '') {
    flash_message('error', 'Alamat pengantaran wajib diisi.', 'danger');
    redirect($formUrl);
}
if (preg_match_all('/./us', $deliveryAddress) > 500 || preg_match_all('/./us', $notes) > 255
    || preg_match('//u', $deliveryAddress) !== 1 || preg_match('//u', $notes) !== 1) {
    flash_message('error', 'Alamat pengantaran atau catatan melebihi batas karakter.', 'danger');
    redirect($formUrl);
}

try {
    if (!Booking::checkMotorAvailability($motorId, $startDate, $endDate)) {
        flash_message('error', 'Motor sudah dipesan pada rentang tanggal tersebut. Silakan pilih tanggal lain.', 'danger');
        redirect($formUrl);
    }
} catch (InvalidArgumentException $exception) {
    flash_message('error', 'Rentang tanggal booking tidak valid.', 'danger');
    redirect($formUrl);
}

$maxFileSize = 2 * 1024 * 1024;
$allowedFiles = [
    'ktp_file' => [
        'directory' => UPLOAD_KTP_DIR,
        'prefix' => 'ktp',
        'mime_types' => [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
        ],
    ],
    'sim_file' => [
        'directory' => UPLOAD_SIM_DIR,
        'prefix' => 'sim',
        'mime_types' => [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
        ],
    ],
];
$uploadedPaths = [];
$storedFileNames = [];
$userId = (int) currentUser()['id'];
$fileInfo = new finfo(FILEINFO_MIME_TYPE);

try {
    foreach ($allowedFiles as $field => $rules) {
        $upload = $_FILES[$field] ?? null;
        if (!is_array($upload)
            || !isset($upload['error'], $upload['tmp_name'], $upload['size'], $upload['name'])
            || !is_int($upload['error'])
            || !is_string($upload['tmp_name'])
            || !is_int($upload['size'])
            || !is_string($upload['name'])) {
            throw new BookingUploadException('Berkas ' . ($field === 'ktp_file' ? 'KTP' : 'SIM') . ' wajib diunggah.');
        }
        if ($upload['error'] !== UPLOAD_ERR_OK) {
            $message = match ($upload['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran berkas terlalu besar.',
                UPLOAD_ERR_NO_FILE => 'Berkas wajib diunggah.',
                default => 'Berkas gagal diunggah. Silakan coba kembali.',
            };
            throw new BookingUploadException(($field === 'ktp_file' ? 'KTP: ' : 'SIM: ') . $message);
        }
        if (!is_uploaded_file($upload['tmp_name']) || $upload['size'] < 1 || $upload['size'] > $maxFileSize) {
            throw new BookingUploadException(($field === 'ktp_file' ? 'KTP' : 'SIM') . ' harus berukuran maksimal 2 MB.');
        }

        $extension = strtolower(pathinfo((string) $upload['name'], PATHINFO_EXTENSION));
        if (!isset($rules['mime_types'][$extension])) {
            throw new BookingUploadException('Format berkas hanya boleh JPG, JPEG, PNG, atau PDF.');
        }
        $mimeType = $fileInfo->file($upload['tmp_name']);
        if ($mimeType !== $rules['mime_types'][$extension]) {
            throw new BookingUploadException('Isi berkas tidak sesuai dengan ekstensi file.');
        }

        if (!is_dir($rules['directory']) || !is_writable($rules['directory'])) {
            throw new BookingUploadException('Direktori penyimpanan berkas tidak tersedia.');
        }

        $fileName = sprintf(
            '%s_%d_%s_%s.%s',
            $rules['prefix'],
            $userId,
            date('YmdHis'),
            bin2hex(random_bytes(8)),
            $extension
        );
        $destination = $rules['directory'] . $fileName;
        if (!move_uploaded_file($upload['tmp_name'], $destination)) {
            throw new BookingUploadException('Berkas gagal disimpan. Silakan coba kembali.');
        }

        $uploadedPaths[] = $destination;
        $storedFileNames[$field] = $fileName;
    }

    $totalDays = (int) $start->diff($end)->days;
    $pricePerDay = (int) $motor['price_per_day'];
    $totalPrice = $totalDays * $pricePerDay;
    if ($totalDays > 65535 || $totalPrice > 4294967295) {
        throw new RuntimeException('Durasi atau total harga pemesanan melebihi batas yang didukung.');
    }

    $bookingId = Booking::createBooking([
        'booking_code' => generate_booking_code(),
        'user_id' => $userId,
        'motorcycle_id' => $motorId,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'total_days' => $totalDays,
        'price_per_day' => $pricePerDay,
        'total_price' => $totalPrice,
        'pickup_method' => $pickupMethod,
        'delivery_address' => $pickupMethod === 'diantar' ? $deliveryAddress : null,
        'notes' => $notes !== '' ? $notes : null,
        'ktp_file' => $storedFileNames['ktp_file'],
        'sim_file' => $storedFileNames['sim_file'],
    ]);
} catch (BookingUnavailableException $exception) {
    foreach ($uploadedPaths as $path) {
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Booking gagal dan berkas sementara tidak dapat dibersihkan.', 0, $exception);
        }
    }
    flash_message('error', $exception->getMessage(), 'danger');
    redirect($formUrl);
} catch (BookingUploadException $exception) {
    foreach ($uploadedPaths as $path) {
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Pemesanan gagal dan berkas sementara tidak dapat dibersihkan.', 0, $exception);
        }
    }
    flash_message('error', $exception->getMessage(), 'danger');
    redirect($formUrl);
} catch (Throwable $exception) {
    foreach ($uploadedPaths as $path) {
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Pemesanan gagal dan berkas sementara tidak dapat dibersihkan.', 0, $exception);
        }
    }
    throw $exception;
}

unset($_SESSION['_csrf_token']);
flash_message('success', 'Pemesanan berhasil dibuat.', 'success');
redirect('pesanan_detail.php?id=' . $bookingId);