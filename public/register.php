<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/User.php';

requireGuest();

$errors = [];
$formData = [
    'full_name' => '',
    'email' => '',
    'phone_wa' => '',
    'address' => '',
];

if (empty($_SESSION['_csrf_token'])) {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $formData['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
    $formData['phone_wa'] = trim((string) ($_POST['phone_wa'] ?? ''));
    $formData['address'] = trim((string) ($_POST['address'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $csrfToken = (string) ($_POST['_csrf_token'] ?? '');

    if (!hash_equals($_SESSION['_csrf_token'], $csrfToken)) {
        $errors[] = 'Sesi formulir tidak valid. Silakan muat ulang halaman dan coba lagi.';
    }
    if ($formData['full_name'] === '' || $formData['email'] === '' || $password === ''
        || $formData['phone_wa'] === '' || $formData['address'] === '') {
        $errors[] = 'Semua kolom wajib diisi.';
    }
    if ($formData['email'] !== '' && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    }
    if (strlen($password) > 72) {
        $errors[] = 'Password tidak boleh lebih dari 72 byte.';
    }

    if ($errors === [] && User::isEmailRegistered($formData['email'])) {
        $errors[] = 'Email sudah terdaftar. Gunakan email lain atau silakan login.';
    }

    if ($errors === []) {
        try {
            User::registerUser(
                $formData['full_name'],
                $formData['email'],
                $password,
                $formData['phone_wa'],
                $formData['address']
            );
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'Email sudah terdaftar. Gunakan email lain atau silakan login.';
            } else {
                throw $exception;
            }
        }

        if ($errors === []) {
            unset($_SESSION['_csrf_token']);
            flash_message('success', 'Pendaftaran berhasil. Silakan login.', 'success');
            redirect('login.php');
        }
    }
}

$page_title = 'Daftar';
require __DIR__ . '/../app/views/layout/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 card-title mb-4">Buat Akun</h1>
                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/register.php">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" maxlength="100"
                               autocomplete="name" required value="<?= htmlspecialchars($formData['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" maxlength="100"
                               autocomplete="email" required value="<?= htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password"
                               minlength="8" autocomplete="new-password" required>
                        <div class="form-text">Minimal 8 karakter.</div>
                    </div>
                    <div class="mb-3">
                        <label for="phone_wa" class="form-label">Nomor WhatsApp</label>
                        <input type="tel" class="form-control" id="phone_wa" name="phone_wa" maxlength="20"
                               autocomplete="tel" required value="<?= htmlspecialchars($formData['phone_wa'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required><?= htmlspecialchars($formData['address'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Daftar</button>
                </form>
                <p class="text-center mt-3 mb-0">Sudah punya akun? <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/login.php">Login</a></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../app/views/layout/footer.php'; ?>