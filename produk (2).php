<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'penjual']);
$judul = 'Kelola Produk';
require_once __DIR__ . '/../includes/header.php';

$uid = (int)$_SESSION['user']['id_user'];
$isAdmin = $_SESSION['user']['role'] === 'admin';
$sql = "SELECT p.*, k.nama_kategori, u.nama AS nama_penjual FROM produk p
        JOIN kategori k ON k.id_kategori = p.id_kategori JOIN users u ON u.id_user = p.id_penjual";
if (!$isAdmin) $sql .= " WHERE p.id_penjual = ?";
$sql .= " ORDER BY p.id_produk DESC";
$stmt = $db->prepare($sql);
if (!$isAdmin) $stmt->bind_param('i', $uid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Kelola Produk</h1>
  <a class="btn btn-success" href="<?= url('dashboard/produk_form.php') ?>"><i class="bi bi-plus-lg"></i> Tambah Produk</a>
</div>
<div class="table-responsive bg-white border rounded"><table class="table table-hover align-middle mb-0">
<thead class="table-light"><tr><th>#</th><th>Produk</th><th>Kategori</th><?= $isAdmin ? '<th>Penjual</th>' : '' ?><th>Harga</th><th>Stok</th><th style="width:150px">Aksi</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada produk.</td></tr><?php endif; ?>
<?php foreach ($rows as $i => $r): ?>
<tr><td><?= $i + 1 ?></td><td><?= e($r['nama_produk']) ?></td><td><?= e($r['nama_kategori']) ?></td>
<?= $isAdmin ? '<td>' . e($r['nama_penjual']) . '</td>' : '' ?>
<td><?= rupiah($r['harga']) ?></td><td><?= (int)$r['stok'] ?></td>
<td class="d-flex gap-1">
  <a class="btn btn-sm btn-outline-primary" href="<?= url('dashboard/produk_form.php?id=' . $r['id_produk']) ?>"><i class="bi bi-pencil"></i></a>
  <form method="post" action="<?= url('dashboard/produk_hapus.php') ?>" onsubmit="return confirm('Hapus produk ini?')">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id_produk'] ?>">
    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
</td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>