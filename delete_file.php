<?php
require_once 'config.php';

// Wajib Login
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Akses ditolak.";
    exit;
}

$file_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if ($file_id) {
    // Cari file di database, pastikan pemiliknya benar
    $stmt = $pdo->prepare("SELECT unique_name FROM files WHERE id = ? AND user_id = ?");
    $stmt->execute([$file_id, $user_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        // Hapus file
        $file_path = UPLOAD_DIR . $file['unique_name'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Hapus record dari database
        $stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
        $stmt->execute([$file_id]);
    }
}

// Redirect kembali ke halaman utama
header("Location: index.php");
exit;