<?php
require_once 'includes/functions.php';
require_role(['pembeli', 'admin', 'penjual']);
$judul = 'Pesanan Saya';
require_once 'includes/header.php';
$id = (int)$_SESSION['user']['id_user'];
$stmt = $db->prepare("SELECT * FROM pesanan WHERE id_pembeli = ? ORDER BY tanggal_pesanan DESC");
$stmt->bind_param('i', $id); $stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<h1 class="h3 mb-3">Pesanan Saya</h1>
<?php if (!$rows): ?><div class="alert alert-info">Belum ada pesanan.</div><?php else: ?>
<div class="table-responsive bg-white border rounded"><table class="table table-hover align-middle mb-0">
<thead class="table-light"><tr><th>Kode</th><th>Tanggal</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['kode_pesanan']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($r['tanggal_pesanan']))) ?></td>
<td><?= rupiah($r['total_harga']) ?></td><td><span class="badge text-bg-secondary"><?= e($r['status']) ?></span></td>
<td><a class="btn btn-sm btn-outline-success" href="<?= url('pesanan_detail.php?id=' . $r['id_pesanan']) ?>">Detail</a></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>