<?php
// -- Konfigurasi Database --
define('DB_HOST', 'localhost'); // Ganti dengan host database anda
define('DB_NAME', '[nama db]'); // Ganti dengan nama database anda
define('DB_USER', '[user db]'); // Ganti dengan username database anda
define('DB_PASS', '[pass db]'); // Ganti dengan password database anda

// -- Pengaturan File & Waktu --
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100 MB
define('EXPIRY_TIME', 600); // 10 menit dalam detik

// -- KUNCI GOOGLE RECAPTCHA --
// GANTI DENGAN KUNCI YANG ANDA DAPATKAN DARI GOOGLE
define('RECAPTCHA_SITE_KEY', '[site key]'); // Ganti dengan site key anda
define('RECAPTCHA_SECRET_KEY', '[secret key]'); // Ganti dengan secret key anda


// -- Buat Koneksi ke Database menggunakan PDO --
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Koneksi ke database gagal: " . $e->getMessage());
    die("Tidak dapat terhubung ke server. Silakan coba lagi nanti.");
}

// -- Mulai session jika belum ada --
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}