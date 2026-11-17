<?php
require_once 'includes/functions.php';
require_role(['pembeli', 'admin', 'penjual']);

$cart = $_SESSION['cart'] ?? [];
if (!$cart) { flash('warning', 'Keranjang kosong.'); redirect('keranjang.php'); }

$errors = [];
$form = [
    'nama_penerima' => user()['nama'], 'telepon_penerima' => '', 'alamat_pengiriman' => '', 'catatan' => ''
];
$u = $db->prepare("SELECT alamat, telepon FROM users WHERE id_user = ?");
$u->bind_param('i', $_SESSION['user']['id_user']); $u->execute();
$prof = $u->get_result()->fetch_assoc();
$form['alamat_pengiriman'] = $prof['alamat'] ?? '';
$form['telepon_penerima']  = $prof['telepon'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($form as $k => $_) $form[$k] = trim($_POST[$k] ?? '');
    if ($form['nama_penerima'] === '')     $errors[] = 'Nama penerima wajib diisi.';
    if (!preg_match('/^[0-9+\- ]{8,20}$/', $form['telepon_penerima'])) $errors[] = 'Nomor telepon tidak valid.';
    if ($form['alamat_pengiriman'] === '') $errors[] = 'Alamat pengiriman wajib diisi.';

    if (!$errors) {
        try {
            $db->begin_transaction();
            $ids = array_map('intval', array_keys($cart));
            $in  = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT id_produk, nama_produk, harga, stok FROM produk WHERE id_produk IN ($in) FOR UPDATE");
            $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
            $stmt->execute();
            $produk = [];
            foreach ($stmt->get_result() as $r) $produk[$r['id_produk']] = $r;

            $total = 0; $baris = [];
            foreach ($cart as $idp => $qty) {
                if (!isset($produk[$idp])) throw new RuntimeException('Ada produk yang sudah tidak tersedia.');
                $p = $produk[$idp];
                if ($qty > $p['stok']) throw new RuntimeException('Stok "' . $p['nama_produk'] . '" tidak mencukupi (sisa ' . $p['stok'] . ').');
                $sub = $qty * $p['harga']; $total += $sub;
                $baris[] = [$idp, $qty, $p['harga'], $sub];
            }

            $kode = 'INV-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $idPembeli = (int)$_SESSION['user']['id_user'];
            $ins = $db->prepare("INSERT INTO pesanan (kode_pesanan, id_pembeli, total_harga, nama_penerima, telepon_penerima, alamat_pengiriman, catatan)
                                 VALUES (?,?,?,?,?,?,?)");
            $ins->bind_param('sidssss', $kode, $idPembeli, $total, $form['nama_penerima'], $form['telepon_penerima'], $form['alamat_pengiriman'], $form['catatan']);
            $ins->execute();
            $idPesanan = $db->insert_id;

            $det = $db->prepare("INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan, subtotal) VALUES (?,?,?,?,?)");
            $upd = $db->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
            foreach ($baris as [$idp, $qty, $harga, $sub]) {
                $det->bind_param('iiidd', $idPesanan, $idp, $qty, $harga, $sub); $det->execute();
                $upd->bind_param('ii', $qty, $idp); $upd->execute();
            }
            $db->commit();
            $_SESSION['cart'] = [];
            flash('success', 'Pesanan berhasil dibuat dengan kode ' . $kode . '.');
            redirect('pesanan_detail.php?id=' . $idPesanan);
        } catch (RuntimeException $ex) {
            $db->rollback(); $errors[] = $ex->getMessage();
        } catch (mysqli_sql_exception $ex) {
            $db->rollback(); $errors[] = 'Terjadi kesalahan database. Silakan coba lagi.';
        }
    }
}

$ids = array_map('intval', array_keys($cart));
$in  = implode(',', array_fill(0, count($ids), '?'));
$stmt = $db->prepare("SELECT id_produk, nama_produk, harga FROM produk WHERE id_produk IN ($in)");
$stmt->bind_param(str_repeat('i', count($ids)), ...$ids); $stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total = 0; foreach ($items as &$it) { $it['qty'] = $cart[$it['id_produk']]; $it['sub'] = $it['qty'] * $it['harga']; $total += $it['sub']; } unset($it);

$judul = 'Checkout';
require_once 'includes/header.php';
?>
<h1 class="h3 mb-3">Checkout</h1>
<?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<div class="row g-4">
  <div class="col-lg-7">
    <form method="post" class="card shadow-sm"><div class="card-body">
      <?= csrf_field() ?>
      <h2 class="h5">Data Pengiriman</h2>
      <div class="mb-3"><label class="form-label">Nama Penerima</label><input class="form-control" name="nama_penerima" value="<?= e($form['nama_penerima']) ?>" required></div>
      <div class="mb-3"><label class="form-label">No. Telepon</label><input class="form-control" name="telepon_penerima" value="<?= e($form['telepon_penerima']) ?>" required></div>
      <div class="mb-3"><label class="form-label">Alamat Lengkap</label><textarea class="form-control" name="alamat_pengiriman" rows="3" required><?= e($form['alamat_pengiriman']) ?></textarea></div>
      <div class="mb-3"><label class="form-label">Catatan (opsional)</label><input class="form-control" name="catatan" maxlength="255" value="<?= e($form['catatan']) ?>"></div>
      <button class="btn btn-success w-100"><i class="bi bi-bag-check"></i> Buat Pesanan</button>
    </div></form>
  </div>
  <div class="col-lg-5">
    <div class="card shadow-sm"><div class="card-body">
      <h2 class="h5">Ringkasan Pesanan</h2>
      <ul class="list-group list-group-flush mb-2">
        <?php foreach ($items as $it): ?>
        <li class="list-group-item d-flex justify-content-between px-0"><span><?= e($it['nama_produk']) ?> <small class="text-muted">x<?= $it['qty'] ?></small></span><span><?= rupiah($it['sub']) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <div class="d-flex justify-content-between fw-bold"><span>Total</span><span class="harga"><?= rupiah($total) ?></span></div>
    </div></div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>