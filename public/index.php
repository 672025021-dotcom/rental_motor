<?php

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/models/Motor.php';

$motors = Motor::getAllAvailableMotors(limit: 6);
$page_title = 'Beranda';
require __DIR__ . '/../app/views/layout/header.php';
?>
<section class="home-hero rounded-4 p-4 p-md-5 mb-5">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <p class="text-uppercase text-primary fw-semibold mb-2">Rental Motor Mudah & Nyaman</p>
            <h1 class="display-5 fw-bold">Jelajahi perjalananmu dengan motor yang tepat.</h1>
            <p class="lead text-secondary">Pilih motor yang tersedia dan pesan dengan mudah untuk kebutuhan perjalanan Anda.</p>
            <a class="btn btn-primary btn-lg" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/katalog.php">Lihat Semua Katalog</a>
        </div>
    </div>
</section>

<section aria-labelledby="latest-motors-title">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h3 mb-1" id="latest-motors-title">Motor Tersedia</h2>
            <p class="text-muted mb-0">Pilihan motor terbaru untuk perjalanan Anda.</p>
        </div>
        <a class="btn btn-outline-primary d-none d-sm-inline-block" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/katalog.php">Lihat Semua</a>
    </div>
    <?php if ($motors === []): ?>
        <div class="alert alert-info">Belum ada motor yang tersedia saat ini.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($motors as $motor): ?>
                <div class="col-sm-6 col-lg-4">
                    <article class="card h-100 shadow-sm">
                        <img src="<?= htmlspecialchars(motor_image_url($motor['image']), ENT_QUOTES, 'UTF-8') ?>"
                             class="card-img-top" alt="<?= htmlspecialchars($motor['brand'] . ' ' . $motor['model'], ENT_QUOTES, 'UTF-8') ?>"
                             style="height: 210px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <span class="badge text-bg-primary align-self-start mb-2"><?= htmlspecialchars(ucfirst($motor['type']), ENT_QUOTES, 'UTF-8') ?></span>
                            <h3 class="h5 card-title"><?= htmlspecialchars($motor['brand'] . ' ' . $motor['model'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="text-muted mb-2"><?= $motor['engine_cc'] !== null ? htmlspecialchars((string) $motor['engine_cc'], ENT_QUOTES, 'UTF-8') . ' cc' : 'Kapasitas mesin tidak tersedia' ?></p>
                            <p class="fw-semibold mt-auto mb-3"><?= format_rupiah($motor['price_per_day']) ?><span class="fw-normal text-muted"> / hari</span></p>
                            <a class="btn btn-outline-primary" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/detail_motor.php?id=<?= (int) $motor['id'] ?>">Lihat Detail</a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="text-center mt-4 d-sm-none">
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/katalog.php">Lihat Semua Katalog</a>
    </div>
</section>
<?php require __DIR__ . '/../app/views/layout/footer.php'; ?>