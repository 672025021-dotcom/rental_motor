<?php

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/models/User.php';

requireGuest();

$error = null;
$email = '';
$successMessage = flash_message('success');

if (empty($_SESSION['_csrf_token'])) {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $csrfToken = (string) ($_POST['_csrf_token'] ?? '');

    if (!hash_equals($_SESSION['_csrf_token'], $csrfToken)) {
        $error = 'Sesi formulir tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Email atau password tidak valid.';
    } else {
        $user = User::getUserByEmail($email);
        if ($user !== null && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            unset($_SESSION['_csrf_token']);
            redirect('katalog.php');
        }

        $error = 'Email atau password salah.';
    }
}

$flashError = flash_message('error');
$page_title = 'Login';
require __DIR__ . '/../app/views/layout/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 card-title mb-4">Login</h1>
                <?php if (is_array($successMessage)): ?>
                    <div class="alert alert-success" role="alert"><?= htmlspecialchars($successMessage['message'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (is_array($flashError)): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($flashError['message'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($error !== null): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/login.php">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" maxlength="100"
                               autocomplete="email" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password"
                               autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Masuk</button>
                </form>
                <p class="text-center mt-3 mb-0">Belum punya akun? <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/register.php">Daftar</a></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../app/views/layout/footer.php'; ?>