<?php
require_once __DIR__ . '/config.php';

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

function base_url() {
    $root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    $app  = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    return rtrim(str_replace($root, '', $app), '/');
}
function url($path = '') { return base_url() . '/' . ltrim($path, '/'); }

function redirect($path) { header('Location: ' . url($path)); exit; }

function flash($type = null, $msg = null) {
    if ($type !== null) { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; return; }
    if (!empty($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}

function user() { return $_SESSION['user'] ?? null; }
function is_login() { return isset($_SESSION['user']); }
function require_role(array $roles) {
    if (!is_login()) { flash('warning', 'Silakan login terlebih dahulu.'); redirect('login.php'); }
    if (!in_array($_SESSION['user']['role'], $roles, true)) { http_response_code(403); die('Akses ditolak.'); }
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check() {
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(400); die('Token keamanan tidak valid. Muat ulang halaman.');
    }
}

function cart_count() {
    return array_sum($_SESSION['cart'] ?? []);
}

function gambar_produk($row) {
    if (!empty($row['gambar']) && file_exists(__DIR__ . '/../uploads/' . $row['gambar'])) {
        return url('uploads/' . $row['gambar']);
    }
    return null;
}
function placeholder_img($nama) {
    $huruf = e(mb_strtoupper(mb_substr($nama, 0, 1)));
    return '<div class="img-placeholder d-flex align-items-center justify-content-center">' . $huruf . '</div>';
}