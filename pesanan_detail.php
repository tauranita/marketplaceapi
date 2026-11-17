<?php
require_once 'includes/functions.php';
require_role(['pembeli', 'admin', 'penjual']);
$judul = 'Detail Pesanan';
require_once 'includes/header.php';
$id = (int)($_GET['id'] ?? 0);
$uid = (int)$_SESSION['user']['id_user'];
$stmt = $db->prepare("SELECT * FROM pesanan WHERE id_pesanan = ? AND (id_pembeli = ? OR ? = 1)");
$isAdmin = $_SESSION['user']['role'] === 'admin' ? 1 : 0;
$stmt->bind_param('iii', $id, $uid, $isAdmin); $stmt->execute();
$o = $stmt->get_result()->fetch_assoc();
if (!$o) { http_response_code(404); echo '<div class="alert alert-danger">Pesanan tidak ditemukan.</div>'; require 'includes/footer.php'; exit; }
$d = $db->prepare("SELECT d.*, p.nama_produk FROM detail_pesanan d JOIN produk p ON p.id_produk = d.id_produk WHERE d.id_pesanan = ?");
$d->bind_param('i', $id); $d->execute();
$det = $d->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<h1 class="h3 mb-1">Pesanan <?= e($o['kode_pesanan']) ?></h1>
<p class="text-muted"><?= e(date('d/m/Y H:i', strtotime($o['tanggal_pesanan']))) ?> &middot; <span class="badge text-bg-secondary"><?= e($o['status']) ?></span></p>
<div class="row g-4">
  <div class="col-lg-8"><div class="table-responsive bg-white border rounded"><table class="table mb-0">
    <thead class="table-light"><tr><th>Produk</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead><tbody>
    <?php foreach ($det as $r): ?><tr><td><?= e($r['nama_produk']) ?></td><td><?= rupiah($r['harga_satuan']) ?></td><td><?= $r['jumlah'] ?></td><td><?= rupiah($r['subtotal']) ?></td></tr><?php endforeach; ?>
    </tbody><tfoot><tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="harga"><?= rupiah($o['total_harga']) ?></td></tr></tfoot>
  </table></div></div>
  <div class="col-lg-4"><div class="card shadow-sm"><div class="card-body">
    <h2 class="h6">Dikirim ke</h2>
    <strong><?= e($o['nama_penerima']) ?></strong><br><?= e($o['telepon_penerima']) ?><br><?= nl2br(e($o['alamat_pengiriman'])) ?>
    <?php if ($o['catatan']): ?><hr><small class="text-muted">Catatan: <?= e($o['catatan']) ?></small><?php endif; ?>
  </div></div></div>
</div>
<?php require_once 'includes/footer.php'; ?>