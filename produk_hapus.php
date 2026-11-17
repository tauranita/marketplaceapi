<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin', 'penjual']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('dashboard/produk.php');
csrf_check();
$id = (int)($_POST['id'] ?? 0);
$uid = (int)$_SESSION['user']['id_user'];
$isAdmin = $_SESSION['user']['role'] === 'admin';

$s = $db->prepare("SELECT id_penjual, gambar FROM produk WHERE id_produk = ?"); $s->bind_param('i', $id); $s->execute();
$row = $s->get_result()->fetch_assoc();
if (!$row || (!$isAdmin && (int)$row['id_penjual'] !== $uid)) { flash('danger', 'Produk tidak ditemukan.'); redirect('dashboard/produk.php'); }

try {
    $d = $db->prepare("DELETE FROM produk WHERE id_produk = ?"); $d->bind_param('i', $id); $d->execute();
    if ($row['gambar']) @unlink(__DIR__ . '/../uploads/' . basename($row['gambar']));
    flash('success', 'Produk berhasil dihapus.');
} catch (mysqli_sql_exception $ex) {
    $u = $db->prepare("UPDATE produk SET stok = 0 WHERE id_produk = ?"); $u->bind_param('i', $id); $u->execute();
    flash('warning', 'Produk sudah pernah dipesan sehingga tidak dapat dihapus. Stok diset menjadi 0.');
}
redirect('dashboard/produk.php');