<?php
$judul = 'Daftar Produk';
require_once 'includes/header.php';

$q   = trim($_GET['q'] ?? '');
$kat = (int)($_GET['kategori'] ?? 0);

$sql = "SELECT p.*, k.nama_kategori, u.nama AS nama_penjual
        FROM produk p
        JOIN kategori k ON k.id_kategori = p.id_kategori
        JOIN users u ON u.id_user = p.id_penjual
        WHERE 1=1";
$types = ''; $params = [];
if ($q !== '')  { $sql .= " AND p.nama_produk LIKE ?"; $types .= 's'; $params[] = '%' . $q . '%'; }
if ($kat > 0)   { $sql .= " AND p.id_kategori = ?";    $types .= 'i'; $params[] = $kat; }
$sql .= " ORDER BY p.created_at DESC, p.id_produk DESC";

$stmt = $db->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$produk = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$kategori = $db->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);
?>
<div class="p-4 mb-4 bg-white rounded-3 border">
  <h1 class="h3 mb-1">Belanja Produk Lokal Nusantara</h1>
  <p class="text-muted mb-3">Temukan produk dari penjual terpercaya di seluruh Indonesia.</p>
  <form class="row g-2" method="get">
    <div class="col-md-6"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari produk..."></div>
    <div class="col-md-4">
      <select class="form-select" name="kategori">
        <option value="0">Semua Kategori</option>
        <?php foreach ($kategori as $k): ?>
          <option value="<?= $k['id_kategori'] ?>" <?= $kat == $k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-grid"><button class="btn btn-success"><i class="bi bi-search"></i> Cari</button></div>
  </form>
</div>

<?php if (!$produk): ?>
  <div class="alert alert-info">Produk tidak ditemukan.</div>
<?php endif; ?>
<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
<?php foreach ($produk as $p): $img = gambar_produk($p); ?>
  <div class="col">
    <div class="card card-produk h-100 shadow-sm">
      <a href="<?= url('produk.php?id=' . $p['id_produk']) ?>" class="ratio ratio-4x3 d-block">
        <?= $img ? '<img src="' . e($img) . '" class="card-img-top" alt="' . e($p['nama_produk']) . '">' : placeholder_img($p['nama_produk']) ?>
      </a>
      <div class="card-body d-flex flex-column">
        <span class="badge text-bg-light border align-self-start mb-1"><?= e($p['nama_kategori']) ?></span>
        <h2 class="h6 card-title"><a class="text-decoration-none text-dark" href="<?= url('produk.php?id=' . $p['id_produk']) ?>"><?= e($p['nama_produk']) ?></a></h2>
        <div class="harga mb-1"><?= rupiah($p['harga']) ?></div>
        <small class="text-muted mb-2"><i class="bi bi-person"></i> <?= e($p['nama_penjual']) ?> &middot; Stok <?= (int)$p['stok'] ?></small>
        <div class="mt-auto d-flex gap-2">
          <a class="btn btn-outline-success btn-sm flex-fill" href="<?= url('produk.php?id=' . $p['id_produk']) ?>">Detail</a>
          <?php if ($p['stok'] > 0): ?>
          <form method="post" action="<?= url('keranjang.php') ?>" class="flex-fill">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
            <input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>"><input type="hidden" name="jumlah" value="1">
            <button class="btn btn-success btn-sm w-100"><i class="bi bi-cart-plus"></i></button>
          </form>
          <?php else: ?><span class="btn btn-secondary btn-sm flex-fill disabled">Habis</span><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php require_once 'includes/footer.php'; ?>