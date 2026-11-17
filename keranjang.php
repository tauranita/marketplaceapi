<?php
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    $id   = (int)($_POST['id_produk'] ?? 0);
    $_SESSION['cart'] = $_SESSION['cart'] ?? [];

    if ($aksi === 'tambah' || $aksi === 'ubah') {
        $jumlah = max(1, (int)($_POST['jumlah'] ?? 1));
        $stmt = $db->prepare("SELECT nama_produk, stok FROM produk WHERE id_produk = ?");
        $stmt->bind_param('i', $id); $stmt->execute();
        $p = $stmt->get_result()->fetch_assoc();
        if (!$p) { flash('danger', 'Produk tidak ditemukan.'); redirect('keranjang.php'); }
        $baru = ($aksi === 'tambah') ? ($_SESSION['cart'][$id] ?? 0) + $jumlah : $jumlah;
        if ($baru > $p['stok']) { $baru = (int)$p['stok']; flash('warning', 'Jumlah disesuaikan dengan stok tersedia (' . $p['stok'] . ').'); }
        else flash('success', $aksi === 'tambah' ? '"' . $p['nama_produk'] . '" ditambahkan ke keranjang.' : 'Keranjang diperbarui.');
        if ($baru > 0) $_SESSION['cart'][$id] = $baru; else unset($_SESSION['cart'][$id]);
        redirect('keranjang.php');
    }
    if ($aksi === 'hapus') { unset($_SESSION['cart'][$id]); flash('info', 'Produk dihapus dari keranjang.'); redirect('keranjang.php'); }
    if ($aksi === 'kosongkan') { $_SESSION['cart'] = []; redirect('keranjang.php'); }
}

$judul = 'Keranjang Belanja';
require_once 'includes/header.php';

$items = []; $total = 0;
$cart = $_SESSION['cart'] ?? [];
if ($cart) {
    $ids = array_map('intval', array_keys($cart));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM produk WHERE id_produk IN ($in)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    foreach ($stmt->get_result() as $p) {
        $p['qty'] = $cart[$p['id_produk']];
        $p['subtotal'] = $p['qty'] * $p['harga'];
        $total += $p['subtotal'];
        $items[] = $p;
    }
}
?>
<h1 class="h3 mb-3"><i class="bi bi-cart3"></i> Keranjang Belanja</h1>
<?php if (!$items): ?>
  <div class="alert alert-info">Keranjang masih kosong. <a href="<?= url('index.php') ?>">Mulai belanja</a></div>
<?php else: ?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="table-responsive bg-white border rounded">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>Produk</th><th>Harga</th><th style="width:150px">Jumlah</th><th>Subtotal</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><a class="text-decoration-none" href="<?= url('produk.php?id=' . $it['id_produk']) ?>"><?= e($it['nama_produk']) ?></a></td>
          <td><?= rupiah($it['harga']) ?></td>
          <td>
            <form method="post" class="input-group input-group-sm">
              <?= csrf_field() ?><input type="hidden" name="aksi" value="ubah"><input type="hidden" name="id_produk" value="<?= $it['id_produk'] ?>">
              <input type="number" name="jumlah" min="1" max="<?= (int)$it['stok'] ?>" value="<?= $it['qty'] ?>" class="form-control">
              <button class="btn btn-outline-secondary" title="Perbarui"><i class="bi bi-arrow-repeat"></i></button>
            </form>
          </td>
          <td class="fw-semibold"><?= rupiah($it['subtotal']) ?></td>
          <td>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_produk" value="<?= $it['id_produk'] ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm"><div class="card-body">
      <h2 class="h5">Ringkasan</h2>
      <div class="d-flex justify-content-between"><span>Total</span><strong class="harga fs-5"><?= rupiah($total) ?></strong></div>
      <hr>
      <a href="<?= url('checkout.php') ?>" class="btn btn-success w-100 mb-2">Checkout</a>
      <a href="<?= url('index.php') ?>" class="btn btn-outline-secondary w-100 mb-2">Lanjut Belanja</a>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="kosongkan"><button class="btn btn-link text-danger w-100">Kosongkan keranjang</button></form>
    </div></div>
  </div>
</div>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>