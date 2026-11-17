<?php
require_once 'includes/functions.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? ''); $pass = $_POST['password'] ?? '';
    $stmt = $db->prepare("SELECT id_user, nama, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param('s', $email); $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    if ($u && password_verify($pass, $u['password'])) {
        session_regenerate_id(true);
        unset($u['password']);
        $_SESSION['user'] = $u;
        flash('success', 'Selamat datang, ' . $u['nama'] . '!');
        redirect(in_array($u['role'], ['admin', 'penjual']) ? 'dashboard/produk.php' : 'index.php');
    }
    $error = 'Email atau password salah.';
}
$judul = 'Masuk';
require_once 'includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-5">
<div class="card shadow-sm"><div class="card-body p-4">
  <h1 class="h4 mb-3">Masuk</h1>
  <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <button class="btn btn-success w-100">Masuk</button>
  </form>
  <p class="small text-muted mt-3 mb-0">Belum punya akun? <a href="<?= url('register.php') ?>">Daftar</a></p>
</div></div></div></div>
<?php require_once 'includes/footer.php'; ?>