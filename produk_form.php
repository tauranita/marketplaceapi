<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'penjual']);

$uid = (int)$_SESSION['user']['id_user'];
$isAdmin = $_SESSION['user']['role'] === 'admin';
$id = (int)($_GET['id'] ?? 0);
$p = ['nama_produk' => '', 'deskripsi' => '', 'harga' => '', 'stok' => '', 'id_kategori' => '', 'gambar' => ''];

if ($id) {
    $s = $db->prepare("SELECT * FROM produk WHERE id_produk = ?"); $s->bind_param('i', $id); $s->execute();
    $row = $s->get_result()->fetch_assoc();
    if (!$row || (!$isAdmin && (int)$row['id_penjual'] !== $uid)) { http_response_code(404); die('Produk tidak ditemukan.'); }
    $p = $row;
}
$kategori = $db->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $p['nama_produk'] = trim($_POST['nama_produk'] ?? '');
    $p['deskripsi']   = trim($_POST['deskripsi'] ?? '');
    $p['harga']       = $_POST['harga'] ?? '';
    $p['stok']        = $_POST['stok'] ?? '';
    $p['id_kategori'] = (int)($_POST['id_kategori'] ?? 0);

    if ($p['nama_produk'] === '') $errors[] = 'Nama produk wajib diisi.';
    if (!is_numeric($p['harga']) || $p['harga'] < 0) $errors[] = 'Harga tidak valid.';
    if (!ctype_digit((string)$p['stok'])) $errors[] = 'Stok harus bilangan bulat >= 0.';
    if ($p['id_kategori'] <= 0) $errors[] = 'Pilih kategori.';

    $gambarBaru = null;
    if (!empty($_FILES['gambar']['name'])) {
        $f = $_FILES['gambar'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if ($f['error'] !== UPLOAD_ERR_OK) $errors[] = 'Upload gambar gagal.';
        elseif ($f['size'] > 2 * 1024 * 1024) $errors[] = 'Ukuran gambar maksimal 2 MB.';
        elseif (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || @getimagesize($f['tmp_name']) === false)
            $errors[] = 'File harus berupa gambar JPG/PNG/WEBP.';
        else {
            $gambarBaru = bin2hex(random_bytes(8)) . '.' . $ext;
            if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/../uploads/' . $gambarBaru)) { $errors[] = 'Gagal menyimpan gambar.'; $gambarBaru = null; }
        }
    }

    if (!$errors) {
        $harga = (float)$p['harga']; $stok = (int)$p['stok'];
        if ($id) {
            $gambar = $gambarBaru ?? $p['gambar'];
            $s = $db->prepare("UPDATE produk SET id_kategori=?, nama_produk=?, deskripsi=?, harga=?, stok=?, gambar=? WHERE id_produk=?");
            $s->bind_param('issdisi', $p['id_kategori'], $p['nama_produk'], $p['deskripsi'], $harga, $stok, $gambar, $id);
            $s->execute();
            if ($gambarBaru && !empty($row['gambar'])) @unlink(__DIR__ . '/../uploads/' . basename($row['gambar']));
            flash('success', 'Produk berhasil diperbarui.');
        } else {
            $s = $db->prepare("INSERT INTO produk (id_penjual, id_kategori, nama_produk, deskripsi, harga, stok, gambar) VALUES (?,?,?,?,?,?,?)");
            $s->bind_param('iissdis', $uid, $p['id_kategori'], $p['nama_produk'], $p['deskripsi'], $harga, $stok, $gambarBaru);
            $s->execute();
            flash('success', 'Produk berhasil ditambahkan.');
        }
        redirect('dashboard/produk.php');
    }
}
$judul = $id ? 'Edit Produk' : 'Tambah Produk';
require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3"><?= e($judul) ?></h1>
<?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="card shadow-sm"><div class="card-body row g-3">
  <?= csrf_field() ?>
  <div class="col-md-8"><label class="form-label">Nama Produk</label><input name="nama_produk" class="form-control" value="<?= e($p['nama_produk']) ?>" required></div>
  <div class="col-md-4"><label class="form-label">Kategori</label>
    <select name="id_kategori" class="form-select" required><option value="">- pilih -</option>
    <?php foreach ($kategori as $k): ?><option value="<?= $k['id_kategori'] ?>" <?= (int)$p['id_kategori'] === (int)$k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-6"><label class="form-label">Harga (Rp)</label><input type="number" step="1" min="0" name="harga" class="form-control" value="<?= e($p['harga'] !== '' ? (int)$p['harga'] : '') ?>" required></div>
  <div class="col-md-6"><label class="form-label">Stok</label><input type="number" min="0" name="stok" class="form-control" value="<?= e($p['stok']) ?>" required></div>
  <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="deskripsi" rows="4" class="form-control"><?= e($p['deskripsi']) ?></textarea></div>
  <div class="col-12"><label class="form-label">Gambar (JPG/PNG/WEBP, maks 2 MB)</label><input type="file" name="gambar" accept="image/*" class="form-control">
    <?php if ($p['gambar']): ?><small class="text-muted">Gambar saat ini: <?= e($p['gambar']) ?></small><?php endif; ?></div>
  <div class="col-12 d-flex gap-2"><button class="btn btn-success">Simpan</button><a class="btn btn-outline-secondary" href="<?= url('dashboard/produk.php') ?>">Batal</a></div>
</div></form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>