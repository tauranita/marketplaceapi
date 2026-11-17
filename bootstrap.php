<?php
// Bootstrap REST API: helper JSON + koneksi MySQLi.
// Memakai kredensial yang sama dengan aplikasi (includes/config.local.php).

class ApiException extends Exception
{
    public int $status;
    public $errors;
    public function __construct(int $status, string $message, $errors = null)
    {
        parent::__construct($message);
        $this->status = $status;
        $this->errors = $errors;
    }
}

function json_response(int $status, array $payload, array $headers = []): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    foreach ($headers as $h) header($h);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function json_error(int $status, string $message, $errors = null): void
{
    $body = ['status' => 'error', 'message' => $message];
    if ($errors !== null) $body['errors'] = $errors;
    json_response($status, $body);
}

function allow_methods(array $methods): string
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: ' . implode(', ', $methods) . ', OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'OPTIONS') { http_response_code(204); exit; }
    if (!in_array($method, $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        throw new ApiException(405, "Method $method tidak diizinkan. Gunakan: " . implode(', ', $methods));
    }
    return $method;
}

function get_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        throw new ApiException(400, 'Body request kosong. Kirim JSON dengan header Content-Type: application/json.');
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) throw new ApiException(400, 'Format JSON tidak valid.');
    return $data;
}

function positive_int($value, string $field): int
{
    if (!is_string($value) || !ctype_digit($value) || (int)$value < 1) {
        throw new ApiException(400, "Parameter $field harus berupa bilangan bulat positif.");
    }
    return (int)$value;
}

function run_endpoint(callable $handler): void
{
    try {
        $handler();
    } catch (ApiException $e) {
        json_error($e->status, $e->getMessage(), $e->errors);
    } catch (Throwable $e) {
        error_log($e->getMessage());
        json_error(500, 'Terjadi kesalahan pada server.');
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) return $conn;

    foreach ([__DIR__ . '/../includes/config.local.php', __DIR__ . '/config.local.php'] as $f) {
        if (is_file($f)) { require_once $f; break; }
    }
    if (!defined('DB_HOST')) {
        throw new ApiException(500, 'Konfigurasi database belum dibuat (includes/config.local.php).');
    }
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        throw new ApiException(500, 'Tidak dapat terhubung ke database.');
    }
    return $conn;
}

// URL dasar folder aplikasi (untuk membentuk URL gambar produk)
function app_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir;
}