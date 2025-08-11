<?php
require_once 'config.php';

// Wajib Login
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    http_response_code(403); // Forbidden
    die('Akses ditolak. Silakan login terlebih dahulu.');
}

// Validasi Input
$file_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$file_id) {
    http_response_code(400); // Bad Request
    die('ID file tidak valid.');
}

$user_id = $_SESSION['user_id'];

// Cek Kepemilikan File di Database
$stmt = $pdo->prepare("SELECT unique_name, original_name FROM files WHERE id = ? AND user_id = ?");
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404); // Not Found
    die('File tidak ditemukan atau Anda tidak memiliki izin untuk mengaksesnya.');
}

// Kirim File ke Browser
$file_path = UPLOAD_DIR . $file['unique_name'];

if (file_exists($file_path)) {
    // Hentikan output buffering jika ada
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Atur header untuk download/tampil
    header('Content-Type: ' . mime_content_type($file_path));
    header('Content-Disposition: inline; filename="' . basename($file['original_name']) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: private');
    header('Pragma: private');

    // Baca dan kirim isi file
    flush(); // Memastikan header terkirim
    readfile($file_path);
    exit;
} else {
    http_response_code(404);
    die('File fisik tidak ditemukan di server.');
}