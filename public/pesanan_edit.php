<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/Booking.php';

requireLogin();
$rawId = $_GET['id'] ?? $_POST['booking_id'] ?? '';
$bookingId = is_string($rawId) && ctype_digit($rawId) ? (int) $rawId : 0;
$user = currentUser();
$booking = Booking::getBookingDetailForUser($bookingId, $user['id']);

if ($booking === null) {
    flash_message('error', 'Pesanan tidak ditemukan.', 'danger');
    redirect('pesanan.php');
}
if ($booking['status'] !== 'pending') {
    flash_message('error', 'Pesanan hanya dapat diedit selama berstatus menunggu.', 'danger');
    redirect('pesanan_detail.php?id=' . $bookingId);
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Metode request tidak diizinkan.');
}

if (empty($_SESSION['_csrf_token'])) {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$startDate = $booking['start_date'];
$endDate = $booking['end_date'];
$pickupMethod = $booking['pickup_method'];
$deliveryAddress = $booking['delivery_address'] ?? '';
$notes = $booking['notes'] ?? '';
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['_csrf_token'] ?? '';
    if (!is_string($csrfToken) || !hash_equals($_SESSION['_csrf_token'], $csrfToken)) {
        $errors[] = 'Sesi formulir tidak valid. Silakan muat ulang halaman.';
    }

    $readString = static function (string $key): string {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    };
    $startDate = $readString('start_date');
    $endDate = $readString('end_date');
    $pickupMethod = $readString('pickup_method');
    $deliveryAddress = $readString('delivery_address');
    $notes = $readString('notes');

    $start = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);
    $end = DateTimeImmutable::createFromFormat('!Y-m-d', $endDate);
    $todayDate = new DateTimeImmutable('today');
    if ($start === false || $start->format('Y-m-d') !== $startDate
        || $end === false || $end->format('Y-m-d') !== $endDate
        || $start < $todayDate || $end < $start) {
        $errors[] = 'Tanggal tidak valid. Tanggal mulai minimal hari ini dan tanggal selesai tidak boleh sebelum tanggal mulai.';
    }
    if (!in_array($pickupMethod, ['ambil_sendiri', 'diantar'], true)) {
        $errors[] = 'Metode pengambilan tidak valid.';
    }
    if ($pickupMethod === 'diantar' && $deliveryAddress === '') {
        $errors[] = 'Alamat pengantaran wajib diisi.';
    }
    if (strlen($deliveryAddress) > 500 || strlen($notes) > 255) {
        $errors[] = 'Alamat pengantaran atau catatan melebihi batas karakter.';
    }
    if ($errors === []) {
        $totalDays = (int) $start->diff($end)->days;
        $totalPrice = $totalDays * (int) $booking['price_per_day'];
        try {
            if (Booking::updateBookingCustomer($bookingId, $user['id'], [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'total_price' => $totalPrice,
                'pickup_method' => $pickupMethod,
                'delivery_address' => $pickupMethod === 'diantar' ? $deliveryAddress : null,
                'notes' => $notes !== '' ? $notes : null,
            ])) {
                flash_message('success', 'Pesanan berhasil diperbarui.', 'success');
                redirect('pesanan_detail.php?id=' . $bookingId);
            }

            $errors[] = 'Pesanan tidak lagi dapat diedit.';
        } catch (BookingUnavailableException $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}

$page_title = 'Edit Pesanan';
require __DIR__ . '/../app/views/layout/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h2 mb-0">Edit Pesanan</h1>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/pesanan_detail.php?id=<?= $bookingId ?>">Kembali</a>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <p class="text-muted"><?= htmlspecialchars($booking['booking_code'], ENT_QUOTES, 'UTF-8') ?> &middot;
                    <?= htmlspecialchars($booking['brand'] . ' ' . $booking['model'], ENT_QUOTES, 'UTF-8') ?>
                    &middot; <?= format_rupiah($booking['price_per_day']) ?> / hari
                </p>
                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/pesanan_edit.php">
                    <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Tanggal Mulai</label>
                            <input class="form-control" type="date" id="start_date" name="start_date" min="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>" required value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">Tanggal Selesai</label>
                            <input class="form-control" type="date" id="end_date" name="end_date" min="<?= htmlspecialchars(max($today, $startDate), ENT_QUOTES, 'UTF-8') ?>" required value="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <fieldset class="col-12">
                            <legend class="form-label fs-6">Metode Pengambilan</legend>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="pickup_method" id="pickup_self" value="ambil_sendiri" <?= $pickupMethod === 'ambil_sendiri' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pickup_self">Ambil sendiri</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="pickup_method" id="pickup_delivery" value="diantar" <?= $pickupMethod === 'diantar' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pickup_delivery">Diantar</label>
                            </div>
                        </fieldset>
                        <div class="col-12" id="delivery-address-group">
                            <label for="delivery_address" class="form-label">Alamat Pengantaran</label>
                            <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" maxlength="500"><?= htmlspecialchars($deliveryAddress, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Catatan Tambahan</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="255"><?= htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <span class="text-muted">Total mengikuti durasi x <?= format_rupiah($booking['price_per_day']) ?> / hari.</span>
                            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const start = document.querySelector('#start_date');
    const end = document.querySelector('#end_date');
    const group = document.querySelector('#delivery-address-group');
    const address = document.querySelector('#delivery_address');
    const updateFields = () => {
        end.min = start.value || end.min;
        const delivery = document.querySelector('#pickup_delivery').checked;
        group.classList.toggle('d-none', !delivery);
        address.disabled = !delivery;
        address.required = delivery;
    };
    start.addEventListener('change', updateFields);
    document.querySelectorAll('input[name="pickup_method"]').forEach((input) => input.addEventListener('change', updateFields));
    updateFields();
});
</script>
<?php require __DIR__ . '/../app/views/layout/footer.php'; ?>