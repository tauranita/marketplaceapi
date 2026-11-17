<?php
// POST /api/pesanan.php                 -> buat pesanan baru (body JSON)
// GET  /api/pesanan.php[?id_pembeli=3]  -> daftar pesanan
// GET  /api/pesanan.php?id=1            -> detail pesanan + item
require_once __DIR__ . '/bootstrap.php';

function validate_pesanan(array $in): array
{
    $err = [];
    $idPembeli = $in['id_pembeli'] ?? null;
    if (!is_int($idPembeli) || $idPembeli < 1) $err['id_pembeli'] = 'Wajib bilangan bulat positif (id user pembeli).';

    $nama   = trim((string)($in['nama_penerima'] ?? ''));
    $telp   = trim((string)($in['telepon_penerima'] ?? ''));
    $alamat = trim((string)($in['alamat_pengiriman'] ?? ''));
    $catat  = trim((string)($in['catatan'] ?? ''));

    if ($nama === '' || mb_strlen($nama) > 100)         $err['nama_penerima'] = 'Wajib diisi, maksimal 100 karakter.';
    if (!preg_match('/^[0-9+\- ]{8,20}$/', $telp))       $err['telepon_penerima'] = 'Nomor telepon tidak valid (8-20 digit).';
    if ($alamat === '')                                  $err['alamat_pengiriman'] = 'Wajib diisi.';
    if (mb_strlen($catat) > 255)                         $err['catatan'] = 'Maksimal 255 karakter.';

    $items = $in['items'] ?? null;
    $merged = [];
    if (!is_array($items) || !$items) {
        $err['items'] = 'Minimal satu item: [{"id_produk":1,"jumlah":2}].';
    } else {
        foreach ($items as $i => $it) {
            $pid = is_array($it) ? ($it['id_produk'] ?? null) : null;
            $qty = is_array($it) ? ($it['jumlah'] ?? null) : null;
            if (!is_int($pid) || $pid < 1 || !is_int($qty) || $qty < 1) {
                $err["items[$i]"] = 'id_produk dan jumlah harus bilangan bulat positif.';
                continue;
            }
            $merged[$pid] = ($merged[$pid] ?? 0) + $qty;
        }
    }
    if ($err) throw new ApiException(422, 'Validasi gagal.', $err);

    return [$idPembeli, $nama, $telp, $alamat, $catat === '' ? null : $catat, $merged];
}

function buat_pesanan(mysqli $conn): void
{
    [$idPembeli, $nama, $telp, $alamat, $catatan, $items] = validate_pesanan(get_json_body());

    $conn->begin_transaction();
    try {
        $s = $conn->prepare("SELECT id_user FROM users WHERE id_user = ? AND role = 'pembeli'");
        $s->bind_param('i', $idPembeli);
        $s->execute();
        if (!$s->get_result()->fetch_assoc()) {
            throw new ApiException(422, 'Validasi gagal.', ['id_pembeli' => "User pembeli dengan id $idPembeli tidak ditemukan."]);
        }

        $lock = $conn->prepare("SELECT id_produk, nama_produk, harga, stok FROM produk WHERE id_produk = ? FOR UPDATE");
        $lines = []; $total = 0.0;
        foreach ($items as $pid => $qty) {
            $lock->bind_param('i', $pid);
            $lock->execute();
            $p = $lock->get_result()->fetch_assoc();
            if (!$p) throw new ApiException(404, "Produk dengan id $pid tidak ditemukan.");
            if ((int)$p['stok'] < $qty) {
                throw new ApiException(409, "Stok '{$p['nama_produk']}' tidak mencukupi (tersedia {$p['stok']}, diminta $qty).");
            }
            $harga = (float)$p['harga'];
            $sub = $harga * $qty;
            $total += $sub;
            $lines[] = ['id' => $pid, 'nama' => $p['nama_produk'], 'qty' => $qty, 'harga' => $harga, 'sub' => $sub];
        }

        $kode = 'INV-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $ins = $conn->prepare(
            "INSERT INTO pesanan (kode_pesanan, id_pembeli, total_harga, nama_penerima, telepon_penerima, alamat_pengiriman, catatan)
             VALUES (?,?,?,?,?,?,?)"
        );
        $ins->bind_param('sidssss', $kode, $idPembeli, $total, $nama, $telp, $alamat, $catatan);
        $ins->execute();
        $idPesanan = $conn->insert_id;

        $det = $conn->prepare("INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan, subtotal) VALUES (?,?,?,?,?)");
        $upd = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
        foreach ($lines as $l) {
            $det->bind_param('iiidd', $idPesanan, $l['id'], $l['qty'], $l['harga'], $l['sub']);
            $det->execute();
            $upd->bind_param('ii', $l['qty'], $l['id']);
            $upd->execute();
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    json_response(201, [
        'status'  => 'success',
        'message' => 'Pesanan berhasil dibuat.',
        'data'    => [
            'id_pesanan'        => $idPesanan,
            'kode_pesanan'      => $kode,
            'id_pembeli'        => $idPembeli,
            'status'            => 'menunggu',
            'total_harga'       => $total,
            'nama_penerima'     => $nama,
            'telepon_penerima'  => $telp,
            'alamat_pengiriman' => $alamat,
            'catatan'           => $catatan,
            'items'             => array_map(fn($l) => [
                'id_produk' => $l['id'], 'nama_produk' => $l['nama'], 'jumlah' => $l['qty'],
                'harga_satuan' => $l['harga'], 'subtotal' => $l['sub'],
            ], $lines),
        ],
    ], ['Location: pesanan.php?id=' . $idPesanan]);
}

function detail_pesanan(mysqli $conn, int $id): void
{
    $s = $conn->prepare(
        "SELECT o.id_pesanan, o.kode_pesanan, o.id_pembeli, u.nama AS nama_pembeli, o.tanggal_pesanan, o.total_harga,
                o.status, o.nama_penerima, o.telepon_penerima, o.alamat_pengiriman, o.catatan
         FROM pesanan o JOIN users u ON u.id_user = o.id_pembeli WHERE o.id_pesanan = ?"
    );
    $s->bind_param('i', $id);
    $s->execute();
    $o = $s->get_result()->fetch_assoc();
    if (!$o) throw new ApiException(404, "Pesanan dengan id $id tidak ditemukan.");

    $s = $conn->prepare(
        "SELECT d.id_produk, p.nama_produk, d.jumlah, d.harga_satuan, d.subtotal
         FROM detail_pesanan d JOIN produk p ON p.id_produk = d.id_produk WHERE d.id_pesanan = ?"
    );
    $s->bind_param('i', $id);
    $s->execute();
    $items = [];
    foreach ($s->get_result() as $r) {
        $items[] = [
            'id_produk' => (int)$r['id_produk'], 'nama_produk' => $r['nama_produk'], 'jumlah' => (int)$r['jumlah'],
            'harga_satuan' => (float)$r['harga_satuan'], 'subtotal' => (float)$r['subtotal'],
        ];
    }
    $o['id_pesanan'] = (int)$o['id_pesanan'];
    $o['id_pembeli'] = (int)$o['id_pembeli'];
    $o['total_harga'] = (float)$o['total_harga'];
    $o['items'] = $items;
    json_response(200, ['status' => 'success', 'data' => $o]);
}

function daftar_pesanan(mysqli $conn): void
{
    $sql = "SELECT id_pesanan, kode_pesanan, id_pembeli, tanggal_pesanan, total_harga, status FROM pesanan";
    $stmt = null;
    if (isset($_GET['id_pembeli'])) {
        $idp = positive_int($_GET['id_pembeli'], 'id_pembeli');
        $stmt = $conn->prepare("$sql WHERE id_pembeli = ? ORDER BY id_pesanan DESC");
        $stmt->bind_param('i', $idp);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query("$sql ORDER BY id_pesanan DESC");
    }
    $data = [];
    foreach ($res as $r) {
        $r['id_pesanan'] = (int)$r['id_pesanan'];
        $r['id_pembeli'] = (int)$r['id_pembeli'];
        $r['total_harga'] = (float)$r['total_harga'];
        $data[] = $r;
    }
    json_response(200, ['status' => 'success', 'total' => count($data), 'data' => $data]);
}

run_endpoint(function () {
    $method = allow_methods(['GET', 'POST']);
    $conn = db();
    if ($method === 'POST') buat_pesanan($conn);
    if (isset($_GET['id'])) detail_pesanan($conn, positive_int($_GET['id'], 'id'));
    daftar_pesanan($conn);
});