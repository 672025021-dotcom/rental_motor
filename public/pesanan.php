<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/Booking.php';

requireLogin();
$user = currentUser();
foreach (Booking::getUserBookingIds($user['id']) as $bookingId) {
    Booking::autoUpdateBookingStatus($bookingId);
}
$bookings = Booking::getUserBookings($user['id']);
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
    'belum_bayar' => ['Belum Bayar', 'warning'],
    'lunas' => ['Lunas', 'success'],
    'dikembalikan' => ['Dikembalikan', 'info'],
];
$page_title = 'Riwayat Pesanan';
require __DIR__ . '/../app/views/layout/header.php';
?>
<h1 class="h2 mb-4">Riwayat Pesanan</h1>
<?php if (is_array($successMessage)): ?>
    <div class="alert alert-success" role="alert"><?= htmlspecialchars($successMessage['message'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_array($errorMessage)): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage['message'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($bookings === []): ?>
    <div class="card card-body text-center py-5">
        <p class="mb-3">Anda belum memiliki pesanan.</p>
        <a class="btn btn-primary align-self-center" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/katalog.php">Lihat Katalog Motor</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
            <tr>
                <th>Kode Booking</th>
                <th>Motor</th>
                <th>Tanggal Sewa</th>
                <th>Total Harga</th>
                <th>Status Pesanan</th>
                <th>Pembayaran</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $booking): ?>
                <?php
                [$statusLabel, $statusClass] = $statusLabels[$booking['status']] ?? [$booking['status'], 'status-dibatalkan'];
                [$paymentLabel, $paymentColor] = $paymentLabels[$booking['payment_status']] ?? [$booking['payment_status'], 'secondary'];
                ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($booking['booking_code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= htmlspecialchars(motor_image_url($booking['image']), ENT_QUOTES, 'UTF-8') ?>"
                                 alt="" width="64" height="48" class="rounded" style="object-fit: cover;">
                            <span><?= htmlspecialchars($booking['brand'] . ' ' . $booking['model'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($booking['start_date'])), ENT_QUOTES, 'UTF-8') ?>
                        &ndash; <?= htmlspecialchars(date('d/m/Y', strtotime($booking['end_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= format_rupiah($booking['total_price']) ?></td>
                    <td><span class="badge status-badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><span class="badge text-bg-<?= htmlspecialchars($paymentColor, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/pesanan_detail.php?id=<?= (int) $booking['id'] ?>">Detail</a>
                            <?php if ($booking['status'] === 'pending'): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/pesanan_edit.php?id=<?= (int) $booking['id'] ?>">Edit</a>
                                <form method="post" action="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/pesanan_batal.php"
                                      onsubmit="return confirm('Batalkan pesanan ini?');">
                                    <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ??= bin2hex(random_bytes(32)), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="cancel_reason" value="Dibatalkan oleh pelanggan.">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Batalkan</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../app/views/layout/footer.php'; ?>