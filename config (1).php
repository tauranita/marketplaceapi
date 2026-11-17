<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$local = __DIR__ . '/config.local.php';
if (!file_exists($local)) {
    die('File includes/config.local.php belum dibuat.');
}
require_once $local;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die('Koneksi database gagal. Periksa konfigurasi includes/config.local.php.');
}