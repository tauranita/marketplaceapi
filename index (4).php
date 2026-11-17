<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'name' => 'Pasar Nusantara REST API',
    'endpoints' => [
        'GET  /api/produk.php[?id=1&q=&kategori=&page=&limit=]',
        'GET  /api/kategori.php',
        'GET  /api/pesanan.php[?id=1|id_pembeli=3]',
        'POST /api/pesanan.php',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);