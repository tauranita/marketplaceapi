<?php
require_once 'includes/functions.php';
$errors = []; $f = ['nama' => '', 'email' => '', 'role' => 'pembeli', 'telepon' => '', 'alamat' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($f as $k => $_) $f[$k] = trim($_POST[$k] ?? $f[$k]);
    $pass = $_POST['password'] ?? '';
    if ($f['nama'] === '') $errors[] = 'Nama wajib diisi.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if (strlen($pass) < 6) $errors[] = 'Password minimal 6 karakter.';
    if (!in_array($f['role'], ['pembeli', 'penjual'], true)) $f['role'] = 'pembeli';
    if (!$errors) {
        $c = $db->prepare("SELECT 1 FROM users WHERE email = ?"); $c->bind_param('s', $f['email']); $c->execute();
        if ($c->get_result()->num_rows) $errors[] = 'Email sudah terdaftar.';
    }
    if (!$errors) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $s = $db->prepare("INSERT INTO users (nama, email, password, role, alamat, telepon) VALUES (?,?,?,?,?,?)");
        $s->bind_param('ssssss', $f['nama'], $f['email'], $hash, $f['role'], $f['alamat'], $f['telepon']);
        $s->execute();
        flash('success', 'Pendaftaran berhasil. Silakan masuk.');
        redirect('login.php');
    }
}
$judul = 'Daftar';
require_once 'includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-6">
<div class="card shadow-sm"><div class="card-body p-4">
  <h1 class="h4 mb-3">Daftar Akun</h1>
  <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Nama</label><input name="nama" class="form-control" value="<?= e($f['nama']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($f['email']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" minlength="6" required></div>
    <div class="mb-3"><label class="form-label">Daftar sebagai</label>
      <select name="role" class="form-select"><option value="pembeli" <?= $f['role']==='pembeli'?'selected':'' ?>>Pembeli</option><option value="penjual" <?= $f['role']==='penjual'?'selected':'' ?>>Penjual</option></select></div>
    <div class="mb-3"><label class="form-label">Telepon</label><input name="telepon" class="form-control" value="<?= e($f['telepon']) ?>"></div>
    <div class="mb-3"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2"><?= e($f['alamat']) ?></textarea></div>
    <button class="btn btn-success w-100">Daftar</button>
  </form>
</div></div></div></div>
<?php require_once 'includes/footer.php'; ?>