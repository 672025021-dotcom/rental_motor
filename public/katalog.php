<?php

require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/models/Motor.php';

$allowedTypes = ['matic', 'manual', 'sport'];
$rawTypeFilter = $_GET['type'] ?? '';
$typeFilter = is_string($rawTypeFilter) ? trim($rawTypeFilter) : '';
if ($typeFilter !== '' && !in_array($typeFilter, $allowedTypes, true)) {
    $typeFilter = '';
}
$rawSearchKeyword = $_GET['search'] ?? '';
$searchKeyword = is_string($rawSearchKeyword) ? trim($rawSearchKeyword) : '';
$searchKeyword = substr($searchKeyword, 0, 100);
$motors = Motor::getAllAvailableMotors(
    $typeFilter !== '' ? $typeFilter : null,
    $searchKeyword
);
$flashError = flash_message('error');
$flashSuccess = flash_message('success');
$page_title = 'Katalog Motor';
require __DIR__ . '/../app/views/layout/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4">
    <div>
        <h1 class="h2 mb-1">Katalog Motor</h1>
        <p class="text-muted mb-0">Temukan motor yang cocok untuk perjalanan Anda.</p>
    </div>
</div>

<?php if (is_array($flashError)): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($flashError['message'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_array($flashSuccess)): ?>
    <div class="alert alert-success" role="alert"><?= htmlspecialchars($flashSuccess['message'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form class="card card-body bg-light mb-4" method="get" action="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/katalog.php">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label for="type" class="form-label">Tipe Motor</label>
            <select class="form-select" id="type" name="type">
                <option value="" <?= $typeFilter === '' ? 'selected' : '' ?>>Semua Tipe</option>
                <?php foreach ($allowedTypes as $type): ?>
                    <option value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" <?= $typeFilter === $type ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($type), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label for="search" class="form-label">Cari Merk atau Model</label>
            <input type="search" class="form-control" id="search" name="search" maxlength="100"
                   placeholder="Contoh: Honda Beat" value="<?= htmlspecialchars($searchKeyword, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-primary" type="submit">Cari Motor</button>
        </div>
    </div>
</form>

<?php if ($motors === []): ?>
    <div class="alert alert-info" role="status">Motor tidak ditemukan.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($motors as $motor): ?>
            <div class="col-sm-6 col-lg-4">
                <article class="card h-100 shadow-sm">
                    <img src="<?= htmlspecialchars(motor_image_url($motor['image']), ENT_QUOTES, 'UTF-8') ?>"
                         class="card-img-top" alt="<?= htmlspecialchars($motor['brand'] . ' ' . $motor['model'], ENT_QUOTES, 'UTF-8') ?>"
                         style="height: 220px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <span class="badge text-bg-primary align-self-start mb-2"><?= htmlspecialchars(ucfirst($motor['type']), ENT_QUOTES, 'UTF-8') ?></span>
                        <h2 class="h5 card-title"><?= htmlspecialchars($motor['brand'] . ' ' . $motor['model'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="text-muted mb-2"><?= $motor['engine_cc'] !== null ? htmlspecialchars((string) $motor['engine_cc'], ENT_QUOTES, 'UTF-8') . ' cc' : 'Kapasitas mesin tidak tersedia' ?></p>
                        <p class="fw-semibold mt-auto mb-3"><?= format_rupiah($motor['price_per_day']) ?><span class="fw-normal text-muted"> / hari</span></p>
                        <a class="btn btn-outline-primary" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/detail_motor.php?id=<?= (int) $motor['id'] ?>">Lihat Detail</a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../app/views/layout/footer.php'; ?>