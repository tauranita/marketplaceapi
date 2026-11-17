<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'penjual']);
$uid = (int)$_SESSION['user']['id_user'];
$isAdmin = $_SESSION['user']['role'] === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    csrf_check();
    $status = $_POST['status'] ?? '';
    if (in_array($status, ['menunggu', 'dibayar', 'dikirim', 'selesai', 'batal'], true)) {
        $idp = (int)($_POST['id'] ?? 0);
        $s = $db->prepare("UPDATE pesanan SET status = ? WHERE id_pesanan = ?"); $s->bind_param('si', $status, $idp); $s->execute();
        flash('success', 'Status pesanan diperbarui.');
    }
    redirect('dashboard/pesanan.php');
}

$judul = 'Pesanan Masuk';
require_once __DIR__ . '/../includes/header.php';

$sql = "SELECT o.id_pesanan, o.kode_pesanan, o.tanggal_pesanan, o.status, o.nama_penerima, u.nama AS pembeli,
               SUM(d.subtotal) AS nilai, GROUP_CONCAT(CONCAT(p.nama_produk, ' x', d.jumlah) SEPARATOR ', ') AS item
        FROM pesanan o JOIN users u ON u.id_user = o.id_pembeli
        JOIN detail_pesanan d ON d.id_pesanan = o.id_pesanan JOIN produk p ON p.id_produk = d.id_produk";
if (!$isAdmin) $sql .= " WHERE p.id_penjual = ?";
$sql .= " GROUP BY o.id_pesanan ORDER BY o.tanggal_pesanan DESC";
$stmt = $db->prepare($sql);
if (!$isAdmin) $stmt->bind_param('i', $uid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<h1 class="h3 mb-3">Pesanan Masuk</h1>
<div class="table-responsive bg-white border rounded"><table class="table align-middle mb-0">
<thead class="table-light"><tr><th>Kode</th><th>Tanggal</th><th>Pembeli</th><th>Item</th><th>Nilai</th><th>Status</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada pesanan.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?>
<tr><td><a href="<?= url('pesanan_detail.php?id=' . $r['id_pesanan']) ?>"><?= e($r['kode_pesanan']) ?></a></td>
<td><?= e(date('d/m/Y H:i', strtotime($r['tanggal_pesanan']))) ?></td><td><?= e($r['pembeli']) ?></td>
<td class="small"><?= e($r['item']) ?></td><td><?= rupiah($r['nilai']) ?></td>
<td><?php if ($isAdmin): ?>
  <form method="post" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id_pesanan'] ?>">
    <select name="status" class="form-select form-select-sm">
      <?php foreach (['menunggu','dibayar','dikirim','selesai','batal'] as $st): ?><option <?= $r['status']===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?>
    </select><button class="btn btn-sm btn-outline-success"><i class="bi bi-check2"></i></button></form>
  <?php else: ?><span class="badge text-bg-secondary"><?= e($r['status']) ?></span><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>