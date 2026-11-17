<?php
// GET /api/produk.php               -> daftar produk
// GET /api/produk.php?id=1          -> detail produk
// Filter opsional: ?q=kopi&kategori=2&page=1&limit=10
require_once __DIR__ . '/bootstrap.php';

function format_produk(array $r): array
{
    $gambarUrl = null;
    if (!empty($r['gambar']) && is_file(__DIR__ . '/../uploads/' . basename($r['gambar']))) {
        $gambarUrl = app_base_url() . '/uploads/' . rawurlencode(basename($r['gambar']));
    }
    return [
        'id_produk'   => (int)$r['id_produk'],
        'nama_produk' => $r['nama_produk'],
        'deskripsi'   => $r['deskripsi'],
        'harga'       => (float)$r['harga'],
        'stok'        => (int)$r['stok'],
        'gambar'      => $r['gambar'],
        'gambar_url'  => $gambarUrl,
        'kategori'    => ['id_kategori' => (int)$r['id_kategori'], 'nama_kategori' => $r['nama_kategori']],
        'penjual'     => ['id_user' => (int)$r['id_penjual'], 'nama' => $r['nama_penjual']],
        'created_at'  => $r['created_at'],
    ];
}

run_endpoint(function () {
    allow_methods(['GET']);
    $conn = db();

    $select = "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar, p.created_at,
                      k.id_kategori, k.nama_kategori, u.id_user AS id_penjual, u.nama AS nama_penjual
               FROM produk p
               JOIN kategori k ON k.id_kategori = p.id_kategori
               JOIN users u ON u.id_user = p.id_penjual";

    // ---- Detail ----
    if (isset($_GET['id'])) {
        $id = positive_int($_GET['id'], 'id');
        $stmt = $conn->prepare("$select WHERE p.id_produk = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) throw new ApiException(404, "Produk dengan id $id tidak ditemukan.");
        json_response(200, ['status' => 'success', 'data' => format_produk($row)]);
    }

    // ---- Daftar ----
    $where = []; $types = ''; $params = [];
    if (isset($_GET['kategori']) && $_GET['kategori'] !== '') {
        $where[] = 'p.id_kategori = ?'; $types .= 'i';
        $params[] = positive_int($_GET['kategori'], 'kategori');
    }
    if (isset($_GET['q']) && is_string($_GET['q']) && trim($_GET['q']) !== '') {
        $where[] = 'p.nama_produk LIKE ?'; $types .= 's';
        $params[] = '%' . trim($_GET['q']) . '%';
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $page  = isset($_GET['page'])  ? positive_int($_GET['page'], 'page')   : 1;
    $limit = min(isset($_GET['limit']) ? positive_int($_GET['limit'], 'limit') : 10, 100);
    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM produk p$whereSql");
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("$select$whereSql ORDER BY p.id_produk LIMIT ? OFFSET ?");
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
    $stmt->execute();

    $data = [];
    foreach ($stmt->get_result() as $row) $data[] = format_produk($row);

    json_response(200, [
        'status' => 'success',
        'meta'   => ['page' => $page, 'limit' => $limit, 'total' => $total, 'total_pages' => (int)ceil($total / $limit)],
        'data'   => $data,
    ]);
});