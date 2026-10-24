<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/Motor.php';

requireLogin();

$rawMotorId = $_GET['motor_id'] ?? '';
$motorId = is_string($rawMotorId) && ctype_digit($rawMotorId) ? (int) $rawMotorId : 0;
$motor = $motorId > 0 ? Motor::getMotorById($motorId) : null;

if ($motor === null || $motor['status'] !== 'tersedia') {
    flash_message('error', 'Motor tidak ditemukan atau sedang tidak tersedia.', 'danger');
    redirect('katalog.php');
}

if (empty($_SESSION['_csrf_token'])) {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = flash_message('error');
$user = currentUser();
$today = date('Y-m-d');
$page_title = 'Form Pemesanan';
require __DIR__ . '/../app/views/layout/header.php';
?>
<h1 class="h2 mb-4">Form Pemesanan Motor</h1>
<?php if (is_array($errorMessage)): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage['message'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <article class="card shadow-sm">
            <img src="<?= htmlspecialchars(motor_image_url($motor['image']), ENT_QUOTES, 'UTF-8') ?>"
                 class="card-img-top" alt="<?= htmlspecialchars($motor['brand'] . ' ' . $motor['model'], ENT_QUOTES, 'UTF-8') ?>"
                 style="height: 260px; object-fit: cover;">
            <div class="card-body">
                <h2 class="h4 card-title"><?= htmlspecialchars($motor['brand'] . ' ' . $motor['model'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-muted mb-2"><?= htmlspecialchars(ucfirst($motor['type']), ENT_QUOTES, 'UTF-8') ?>
                    <?= $motor['engine_cc'] !== null ? '&middot; ' . htmlspecialchars((string) $motor['engine_cc'], ENT_QUOTES, 'UTF-8') . ' cc' : '' ?>
                </p>
                <p class="fs-5 fw-semibold mb-0" id="price-per-day" data-price-per-day="<?= (int) $motor['price_per_day'] ?>">
                    <?= format_rupiah($motor['price_per_day']) ?> <span class="fs-6 fw-normal text-muted">/ hari</span>
                </p>
            </div>
        </article>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/booking_simpan.php"
                      enctype="multipart/form-data" id="booking-form">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="motor_id" value="<?= (int) $motor['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Tanggal Mulai Sewa</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" min="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">Tanggal Selesai Sewa</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" min="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-12">
                            <div class="rounded bg-light p-3" aria-live="polite">
                                <div>Durasi: <strong id="total-days">-</strong></div>
                                <div>Estimasi total: <strong id="total-price">-</strong></div>
                            </div>
                        </div>
                        <fieldset class="col-12">
                            <legend class="form-label fs-6">Metode Pengambilan</legend>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="pickup_method" id="pickup_self"
                                       value="ambil_sendiri" checked>
                                <label class="form-check-label" for="pickup_self">Ambil sendiri</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="pickup_method" id="pickup_delivery"
                                       value="diantar">
                                <label class="form-check-label" for="pickup_delivery">Diantar</label>
                            </div>
                        </fieldset>
                        <div class="col-12 d-none" id="delivery-address-group">
                            <label for="delivery_address" class="form-label">Alamat Pengantaran</label>
                            <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3"
                                      maxlength="500" disabled></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="ktp_file" class="form-label">Upload KTP</label>
                            <input class="form-control" type="file" id="ktp_file" name="ktp_file"
                                   accept="image/*,.pdf" required>
                            <div class="form-text">JPG, JPEG, PNG, atau PDF. Maksimal 2 MB.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="sim_file" class="form-label">Upload SIM</label>
                            <input class="form-control" type="file" id="sim_file" name="sim_file"
                                   accept="image/*,.pdf" required>
                            <div class="form-text">JPG, JPEG, PNG, atau PDF. Maksimal 2 MB.</div>
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Catatan Tambahan (opsional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="255"></textarea>
                        </div>
                        <div class="col-12 d-grid">
                            <button class="btn btn-primary btn-lg" type="submit">Kirim Pemesanan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../app/views/layout/footer.php'; ?>