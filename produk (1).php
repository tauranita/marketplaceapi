<?php
$judul = 'Detail Produk';
require_once 'includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT p.*, k.nama_kategori, u.nama AS nama_penjual
                      FROM produk p JOIN kategori k ON k.id_kategori = p.id_kategori
                      JOIN users u ON u.id_user = p.id_penjual WHERE p.id_produk = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if (!$p) { http_response_code(404); echo '<div class="alert alert-danger">Produk tidak ditemukan.</div>'; require 'includes/footer.php'; exit; }
$img = gambar_produk($p);
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Produk</a></li>
  <li class="breadcrumb-item"><a href="<?= url('index.php?kategori=' . $p['id_kategori']) ?>"><?= e($p['nama_kategori']) ?></a></li>
  <li class="breadcrumb-item active"><?= e($p['nama_produk']) ?></li>
</ol></nav>
<div class="row g-4">
  <div class="col-md-5"><div class="ratio ratio-1x1 rounded overflow-hidden border">
    <?= $img ? '<img src="' . e($img) . '" style="object-fit:cover" alt="">' : placeholder_img($p['nama_produk']) ?>
  </div></div>
  <div class="col-md-7">
    <span class="badge text-bg-success"><?= e($p['nama_kategori']) ?></span>
    <h1 class="h3 mt-2"><?= e($p['nama_produk']) ?></h1>
    <div class="display-6 harga mb-3"><?= rupiah($p['harga']) ?></div>
    <p><?= nl2br(e($p['deskripsi'])) ?></p>
    <ul class="list-unstyled text-muted">
      <li><i class="bi bi-person"></i> Penjual: <strong><?= e($p['nama_penjual']) ?></strong></li>
      <li><i class="bi bi-box-seam"></i> Stok tersedia: <strong><?= (int)$p['stok'] ?></strong></li>
    </ul>
    <?php if ($p['stok'] > 0): ?>
    <form method="post" action="<?= url('keranjang.php') ?>" class="row g-2 align-items-center">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="tambah"><input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>">
      <div class="col-auto"><input type="number" name="jumlah" value="1" min="1" max="<?= (int)$p['stok'] ?>" class="form-control" style="width:90px"></div>
      <div class="col-auto"><button class="btn btn-success"><i class="bi bi-cart-plus"></i> Tambah ke Keranjang</button></div>
    </form>
    <?php else: ?><div class="alert alert-secondary">Stok habis.</div><?php endif; ?>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>