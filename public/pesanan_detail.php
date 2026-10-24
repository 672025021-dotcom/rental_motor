<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/Booking.php';

requireLogin();
$rawId = $_GET['id'] ?? '';
$bookingId = is_string($rawId) && ctype_digit($rawId) ? (int) $rawId : 0;
$user = currentUser();
$booking = Booking::getBookingDetailForUser($bookingId, $user['id']);

if ($booking === null) {
    flash_message('error', 'Pesanan tidak ditemukan.', 'danger');
    redirect('pesanan.php');
}

$logs = Booking::getBookingStatusLogs($bookingId, $user['id']);
$successMessage = flash_message('success');
$errorMessage = flash_message('error');
$statusLabels = [
    'pending' => ['Menunggu', 'status-pending'],
    'dikonfirmasi' => ['Dikonfirmasi', 'status-dikonfirmasi'],
    'berjalan' => ['Berjalan', 'status-berjalan'],
    'selesai' => ['Selesai', 'status-selesai'],
    'dibatalkan' => ['Dibatalkan', 'status-dibatalkan'],
];
$paymentLabels = [
    'belum_bayar' => 'Belum Bayar',
    'lunas' => 'Lunas',
    'dikembalikan' => 'Dikembalikan',
];
[$statusLabel, $statusClass] = $statusLabels[$booking['status']] ?? [$booking['status'], 'status-dibatalkan'];
$page_title = 'Detail Pesanan';
require __DIR__ . '/../app/views/layout/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/pesanan.php" class="text-decoration-none">&larr; Riwayat Pesanan</a>
        <h1 class="h2 mb-0 mt-2">Detail Pesanan</h1>
    </div>
    <span class="badge fs-6 status-badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
</div>
<?php if (is_array($successMessage)): ?>
    <div class="alert alert-success" role="alert"><?= htmlspecialchars($successMessage['message'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_array($errorMessage)): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage['message'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="row g-4">
    <section class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Informasi Motor</div>
            <div class="card-body">
                <img src="<?= htmlspecialchars(motor_image_url($booking['image']), ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($booking['brand'] . ' ' . $booking['model'], ENT_QUOTES, 'UTF-8') ?>"
                     class="img-fluid rounded mb-3" style="max-height: 260px; width: 100%; object-fit: cover;">
                <h2 class="h4"><?= htmlspecialchars($booking['brand'] . ' ' . $booking['model'], ENT_QUOTES, 'UTF-8') ?></h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Plat Nomor</dt><dd class="col-sm-7"><?= htmlspecialchars($booking['plate_number'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-5">Tipe</dt><dd class="col-sm-7"><?= htmlspecialchars(ucfirst($booking['type']), ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-5">Kapasitas Mesin</dt><dd class="col-sm-7"><?= $booking['engine_cc'] !== null ? (int) $booking['engine_cc'] . ' cc' : '-' ?></dd>
                </dl>
            </div>
        </div>
    </section>
    <section class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Rincian Pemesanan</div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Kode Booking</dt><dd class="col-sm-7"><?= htmlspecialchars($booking['booking_code'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-5">Tanggal Mulai</dt><dd class="col-sm-7"><?= htmlspecialchars(date('d/m/Y', strtotime($booking['start_date'])), ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-5">Tanggal Selesai</dt><dd class="col-sm-7"><?= htmlspecialchars(date('d/m/Y', strtotime($booking['end_date'])), ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-5">Durasi</dt><dd class="col-sm-7"><?= (int) $booking['total_days'] ?> hari</dd>
                    <dt class="col-sm-5">Harga per Hari</dt><dd class="col-sm-7"><?= format_rupiah($booking['price_per_day']) ?></dd>
                    <dt class="col-sm-5">Total Harga</dt><dd class="col-sm-7 fw-semibold"><?= format_rupiah($booking['total_price']) ?></dd>
                    <dt class="col-sm-5">Status Pembayaran</dt><dd class="col-sm-7"><?= htmlspecialchars($paymentLabels[$booking['payment_status']] ?? $booking['payment_status'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-5">Metode Pengambilan</dt><dd class="col-sm-7"><?= $booking['pickup_method'] === 'diantar' ? 'Diantar' : 'Ambil sendiri' ?></dd>
                    <?php if ($booking['pickup_method'] === 'diantar'): ?>
                        <dt class="col-sm-5">Alamat Pengantaran</dt><dd class="col-sm-7"><?= nl2br(htmlspecialchars($booking['delivery_address'] ?? '', ENT_QUOTES, 'UTF-8')) ?></dd>
                    <?php endif; ?>
                    <dt class="col-sm-5">Catatan</dt><dd class="col-sm-7"><?= $booking['notes'] !== null && $booking['notes'] !== '' ? nl2br(htmlspecialchars($booking['notes'], ENT_QUOTES, 'UTF-8')) : '-' ?></dd>
                    <?php if ($booking['cancel_reason'] !== null): ?>
                        <dt class="col-sm-5">Alasan Pembatalan</dt><dd class="col-sm-7"><?= htmlspecialchars($booking['cancel_reason'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <?php endif; ?>
                </dl>
                <h3 class="h6">Berkas yang Diunggah</h3>
                <ul class="mb-0">
                    <li>KTP: <?= htmlspecialchars(basename($booking['ktp_file']), ENT_QUOTES, 'UTF-8') ?></li>
                    <li>SIM: <?= htmlspecialchars(basename($booking['sim_file']), ENT_QUOTES, 'UTF-8') ?></li>
                </ul>
            </div>
        </div>
    </section>
    <section class="col-12">
        <div class="card">
            <div class="card-header fw-semibold">Riwayat Status</div>
            <?php if ($logs === []): ?>
                <div class="card-body text-muted">Belum ada riwayat perubahan status.</div>
            <?php else: ?>
                <ul class="list-group list-group-flush status-timeline">
                    <?php foreach ($logs as $log): ?>
                        <li class="list-group-item status-timeline-item">
                            <strong><?= $log['old_status'] === null ? 'Pesanan dibuat' : htmlspecialchars(ucfirst($log['old_status']), ENT_QUOTES, 'UTF-8') ?></strong>
                            &rarr; <?= htmlspecialchars(ucfirst($log['new_status']), ENT_QUOTES, 'UTF-8') ?>
                            <span class="text-muted"> &middot; <?= htmlspecialchars(date('d/m/Y H:i', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($log['note'] !== null && $log['note'] !== ''): ?>
                                <div><?= htmlspecialchars($log['note'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../app/views/layout/footer.php'; ?>