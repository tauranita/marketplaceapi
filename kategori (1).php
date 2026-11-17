<?php
// GET /api/kategori.php -> daftar kategori + jumlah produk
require_once __DIR__ . '/bootstrap.php';

run_endpoint(function () {
    allow_methods(['GET']);
    $res = db()->query(
        "SELECT k.id_kategori, k.nama_kategori, COUNT(p.id_produk) AS jumlah_produk
         FROM kategori k LEFT JOIN produk p ON p.id_kategori = k.id_kategori
         GROUP BY k.id_kategori, k.nama_kategori ORDER BY k.nama_kategori"
    );
    $data = [];
    foreach ($res as $r) {
        $data[] = [
            'id_kategori'   => (int)$r['id_kategori'],
            'nama_kategori' => $r['nama_kategori'],
            'jumlah_produk' => (int)$r['jumlah_produk'],
        ];
    }
    json_response(200, ['status' => 'success', 'total' => count($data), 'data' => $data]);
});