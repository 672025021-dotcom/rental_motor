<?php

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/models/Motor.php';

$rawId = $_GET['id'] ?? null;
$motorId = is_string($rawId) && ctype_digit($rawId) ? (int) $rawId : 0;
$motor = $motorId > 0 ? Motor::getMotorById($motorId) : null;

if ($motor === null) {
    flash_message('error', 'Motor tidak ditemukan.', 'danger');
    redirect('katalog.php');
}

$relatedMotors = Motor::getRelatedMotors($motor['type'], (int) $motor['id']);
$page_title = $motor['brand'] . ' ' . $motor['model'];
require __DIR__ . '/../app/views/layout/header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/katalog.php">Katalog</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($motor['brand'] . ' ' . $motor['model'], ENT_QUOTES, 'UTF-8') ?></li>
    </ol>
</nav>
<div class="row g-4">
    <div class="col-lg-6">
        <img src="<?= htmlspecialchars(motor_image_url($motor['image']), ENT_QUOTES, 'UTF-8') ?>"
             class="img-fluid rounded shadow-sm w-100" alt="<?= htmlspecialchars($motor['brand'] . ' ' . $motor['model'], ENT_QUOTES, 'UTF-8') ?>"
             style="max-height: 460px; object-fit: cover;">
    </div>
    <div class="col-lg-6">
        <span class="badge text-bg-primary mb-2"><?= htmlspecialchars(ucfirst($motor['type']), ENT_QUOTES, 'UTF-8') ?></span>
        <h1 class="h2"><?= htmlspecialchars($motor['brand'] . ' ' . $motor['model'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="fs-4 fw-bold text-primary"><?= format_rupiah($motor['price_per_day']) ?><span class="fs-6 fw-normal text-muted"> / hari</span></p>
        <dl class="row">
            <dt class="col-sm-4">Merk</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($motor['brand'], ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-sm-4">Model</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($motor['model'], ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-sm-4">Plat Nomor</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($motor['plate_number'], ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-sm-4">Tipe</dt>
            <dd class="col-sm-8"><?= htmlspecialchars(ucfirst($motor['type']), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-sm-4">Kapasitas Mesin</dt>
            <dd class="col-sm-8"><?= $motor['engine_cc'] !== null ? htmlspecialchars((string) $motor['engine_cc'], ENT_QUOTES, 'UTF-8') . ' cc' : '-' ?></dd>
            <dt class="col-sm-4">Tahun</dt>
            <dd class="col-sm-8"><?= $motor['year'] !== null ? htmlspecialchars((string) $motor['year'], ENT_QUOTES, 'UTF-8') : '-' ?></dd>
            <dt class="col-sm-4">Warna</dt>
            <dd class="col-sm-8"><?= $motor['color'] !== null && $motor['color'] !== '' ? htmlspecialchars($motor['color'], ENT_QUOTES, 'UTF-8') : '-' ?></dd>
            <dt class="col-sm-4">Deskripsi</dt>
            <dd class="col-sm-8"><?= $motor['description'] !== null && $motor['description'] !== '' ? nl2br(htmlspecialchars($motor['description'], ENT_QUOTES, 'UTF-8')) : '-' ?></dd>
            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8"><?= htmlspecialchars(ucfirst($motor['status']), ENT_QUOTES, 'UTF-8') ?></dd>
        </dl>
        <?php if ($motor['status'] === 'tersedia'): ?>
            <a class="btn btn-primary btn-lg" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/booking_form.php?motor_id=<?= (int) $motor['id'] ?>">Lanjut Sewa / Booking</a>
        <?php else: ?>
            <button class="btn btn-secondary btn-lg" type="button" disabled>Motor Tidak Tersedia</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($relatedMotors !== []): ?>
    <section class="mt-5" aria-labelledby="related-motors-title">
        <h2 class="h3 mb-3" id="related-motors-title">Motor Serupa</h2>
        <div class="row g-4">
            <?php foreach ($relatedMotors as $relatedMotor): ?>
                <div class="col-sm-6 col-lg-4">
                    <article class="card h-100 shadow-sm">
                        <img src="<?= htmlspecialchars(motor_image_url($relatedMotor['image']), ENT_QUOTES, 'UTF-8') ?>"
                             class="card-img-top" alt="<?= htmlspecialchars($relatedMotor['brand'] . ' ' . $relatedMotor['model'], ENT_QUOTES, 'UTF-8') ?>"
                             style="height: 190px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h3 class="h5 card-title"><?= htmlspecialchars($relatedMotor['brand'] . ' ' . $relatedMotor['model'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="fw-semibold mt-auto"><?= format_rupiah($relatedMotor['price_per_day']) ?><span class="fw-normal text-muted"> / hari</span></p>
                            <a class="btn btn-outline-primary" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/detail_motor.php?id=<?= (int) $relatedMotor['id'] ?>">Lihat Detail</a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../app/views/layout/footer.php'; ?>