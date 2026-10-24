<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/User.php';

requireLogin();
$user = currentUser();
$profile = User::getUserById($user['id']);
if ($profile === null) {
    logoutUser();
    flash_message('error', 'Akun tidak ditemukan. Silakan login kembali.', 'danger');
    redirect('login.php');
}

if (empty($_SESSION['_csrf_token'])) {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}

$profileErrors = [];
$passwordErrors = [];
$successMessage = flash_message('success');
$errorMessage = flash_message('error');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Metode request tidak diizinkan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['_csrf_token'] ?? '';
    if (!is_string($csrfToken) || !hash_equals($_SESSION['_csrf_token'], $csrfToken)) {
        $profileErrors[] = 'Sesi formulir tidak valid. Silakan muat ulang halaman.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'profile') {
            $fullName = $_POST['full_name'] ?? '';
            $phone = $_POST['phone_wa'] ?? '';
            $address = $_POST['address'] ?? '';
            if (!is_string($fullName) || !is_string($phone) || !is_string($address)) {
                $profileErrors[] = 'Data profil tidak valid.';
            } else {
                $fullName = trim($fullName);
                $phone = trim($phone);
                $address = trim($address);
                if ($fullName === '' || strlen($fullName) > 100) {
                    $profileErrors[] = 'Nama wajib diisi dan maksimal 100 karakter.';
                }
                if ($phone === '' || strlen($phone) > 20) {
                    $profileErrors[] = 'Nomor WhatsApp wajib diisi dan maksimal 20 karakter.';
                }
                if (strlen($address) > 65535) {
                    $profileErrors[] = 'Alamat terlalu panjang.';
                }
                if ($profileErrors === []) {
                    User::updateUserProfile($user['id'], $fullName, $phone, $address !== '' ? $address : null);
                    $_SESSION['full_name'] = $fullName;
                    flash_message('success', 'Profil berhasil diperbarui.', 'success');
                    redirect('profil.php');
                }
                $profile['full_name'] = $fullName;
                $profile['phone_wa'] = $phone;
                $profile['address'] = $address;
            }
        } elseif ($action === 'password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            if (!is_string($currentPassword) || !is_string($newPassword) || !is_string($confirmPassword)) {
                $passwordErrors[] = 'Data password tidak valid.';
            } else {
                $passwordHash = User::getUserPasswordHash($user['id']);
                if ($passwordHash === null || !password_verify($currentPassword, $passwordHash)) {
                    $passwordErrors[] = 'Password lama tidak sesuai.';
                }
                if (strlen($newPassword) < 8 || strlen($newPassword) > 72) {
                    $passwordErrors[] = 'Password baru harus berukuran 8 hingga 72 byte.';
                }
                if ($newPassword !== $confirmPassword) {
                    $passwordErrors[] = 'Konfirmasi password baru tidak sama.';
                }
                if ($passwordErrors === []) {
                    User::updateUserPassword($user['id'], password_hash($newPassword, PASSWORD_BCRYPT));
                    flash_message('success', 'Password berhasil diubah.', 'success');
                    redirect('profil.php');
                }
            }
        } else {
            $profileErrors[] = 'Jenis perubahan tidak dikenal.';
        }
    }
}

$page_title = 'Profil Saya';
require __DIR__ . '/../app/views/layout/header.php';
?>
<h1 class="h2 mb-4">Profil Saya</h1>
<?php if (is_array($successMessage)): ?>
    <div class="alert alert-success" role="alert"><?= htmlspecialchars($successMessage['message'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_array($errorMessage)): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage['message'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<div class="row g-4">
    <section class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Informasi Pribadi</div>
            <div class="card-body">
                <?php if ($profileErrors !== []): ?>
                    <div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($profileErrors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/profil.php">
                    <input type="hidden" name="action" value="profile">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nama Lengkap</label>
                        <input class="form-control" id="full_name" name="full_name" maxlength="100" required value="<?= htmlspecialchars($profile['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input class="form-control" id="email" type="email" readonly value="<?= htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="phone_wa" class="form-label">Nomor WhatsApp</label>
                        <input class="form-control" id="phone_wa" name="phone_wa" maxlength="20" required value="<?= htmlspecialchars($profile['phone_wa'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat</label>
                        <textarea class="form-control" id="address" name="address" rows="4"><?= htmlspecialchars($profile['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Simpan Profil</button>
                </form>
            </div>
        </div>
    </section>
    <section class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Ganti Password</div>
            <div class="card-body">
                <?php if ($passwordErrors !== []): ?>
                    <div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($passwordErrors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/profil.php">
                    <input type="hidden" name="action" value="password">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Lama</label>
                        <input class="form-control" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru</label>
                        <input class="form-control" type="password" id="new_password" name="new_password" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                        <input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </div>
                    <button class="btn btn-outline-primary" type="submit">Ubah Password</button>
                </form>
            </div>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../app/views/layout/footer.php'; ?>