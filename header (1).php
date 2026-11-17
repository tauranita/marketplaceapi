<?php require_once __DIR__ . '/functions.php'; $flash = flash(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul ?? 'Pasar Nusantara') ?> - Pasar Nusantara</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= url('assets/style.css') ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= url('index.php') ?>"><i class="bi bi-shop"></i> Pasar Nusantara</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?= url('index.php') ?>">Produk</a></li>
        <?php if (is_login() && in_array(user()['role'], ['admin','penjual'])): ?>
          <li class="nav-item"><a class="nav-link" href="<?= url('dashboard/produk.php') ?>">Kelola Produk</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('dashboard/pesanan.php') ?>">Pesanan Masuk</a></li>
        <?php endif; ?>
        <?php if (is_login() && user()['role'] === 'pembeli'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= url('pesanan.php') ?>">Pesanan Saya</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav align-items-lg-center">
        <li class="nav-item">
          <a class="nav-link" href="<?= url('keranjang.php') ?>"><i class="bi bi-cart3"></i> Keranjang
            <span class="badge bg-warning text-dark"><?= cart_count() ?></span></a>
        </li>
        <?php if (is_login()): ?>
          <li class="nav-item"><span class="nav-link disabled text-white-50"><?= e(user()['nama']) ?> (<?= e(user()['role']) ?>)</span></li>
          <li class="nav-item"><a class="nav-link" href="<?= url('logout.php') ?>">Keluar</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= url('login.php') ?>">Masuk</a></li>
          <li class="nav-item"><a class="btn btn-warning btn-sm ms-lg-2" href="<?= url('register.php') ?>">Daftar</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main class="container py-4 flex-grow-1">
<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show"><?= e($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>