<?php

require_once '[/htdocs/config.php]'; // GANTI DENGAN PATH PROJECT ANDA

$expiry_point = time() - EXPIRY_TIME;

// Ambil semua file yang sudah kedaluwarsa dari database
$stmt = $pdo->prepare("SELECT id, unique_name FROM files WHERE upload_time < ?");
$stmt->execute([$expiry_point]);
$expired_files = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($expired_files) {
    $ids_to_delete = [];
    foreach ($expired_files as $file) {
        $ids_to_delete[] = $file['id'];
        $file_path = UPLOAD_DIR . $file['unique_name'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Hapus semua record yang expired dari database dalam satu query
    $stmt = $pdo->prepare("DELETE FROM files WHERE id IN (" . implode(',', array_fill(0, count($ids_to_delete), '?')) . ")");
    $stmt->execute($ids_to_delete);
}