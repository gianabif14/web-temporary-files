<?php
require_once 'config.php';

// Wajib Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Menampilkan flash message dari session
$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

include 'cleanup.php';

$user_id = $_SESSION['user_id'];

// PROSES UPLOAD FILE (POST-REDIRECT-GET)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["filesToUpload"])) {
    $message = '';
    $error = '';
    
    // 1. Verifikasi Google reCAPTCHA
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret'   => RECAPTCHA_SECRET_KEY, // Tidak perlu diubah
        'response' => $recaptcha_response
    ];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response_keys = json_decode($result, true);

    if (!$response_keys["success"]) {
        $error = "Verifikasi reCAPTCHA gagal. Silakan centang kotak 'Saya bukan robot'.";
    } else {
        // 2. Jika reCAPTCHA berhasil, lanjutkan proses upload
        foreach ($_FILES['filesToUpload']['name'] as $key => $name) {
            if ($_FILES['filesToUpload']['error'][$key] == UPLOAD_ERR_OK) {
                if ($_FILES['filesToUpload']['size'][$key] > MAX_FILE_SIZE) {
                    $error .= "File '". htmlspecialchars($name) ."' melebihi batas 100 MB.<br>";
                    continue;
                }
                $original_name = basename($name);
                $unique_name = uniqid('file_', true) . '.' . pathinfo($original_name, PATHINFO_EXTENSION);
                $target_file = UPLOAD_DIR . $unique_name;
                if (move_uploaded_file($_FILES["filesToUpload"]["tmp_name"][$key], $target_file)) {
                    $stmt = $pdo->prepare("INSERT INTO files (user_id, unique_name, original_name, upload_time) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $unique_name, $original_name, time()]);
                    $message .= "File '". htmlspecialchars($original_name) ."' berhasil diunggah.<br>";
                } else {
                    $error .= "Gagal mengunggah file '". htmlspecialchars($original_name) ."'.<br>";
                }
            }
        }
    }

    // Simpan pesan ke session dan lakukan redirect
    if (!empty($error)) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => $error];
    } else {
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => $message];
    }

    header("Location: index.php");
    exit;
}

// Ambil daftar file milik pengguna
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$active_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Upload File Temporary</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <header class="main-header">
        <h1>Dashboard</h1>
        <div class="user-info">
            <a href="logout.php" class="btn btn-logout">Logout</a>
        </div>
    </header>
    <h2 style="text-align: center;">Halo, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b>!</h2>
    <main class="container">
        <div class="main-grid">
            <div class="card upload-card">
                <h2>Unggah File Baru</h2>
                <p>Pilih file (Max 100MB). File akan terhapus setelah 10 menit.</p>
                
                <form action="index.php" method="post" enctype="multipart/form-data" class="upload-form">
                    <div class="upload-area">
                        <input type="file" name="filesToUpload[]" id="filesToUpload" multiple required>
                        <label for="filesToUpload" class="upload-label">
                            <span>Pilih File atau Seret ke Sini</span>
                        </label>
                    </div>
                    <div id="file-list-preview"></div>
                    
                    <div class="recaptcha-container">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>

                    <button type="submit" class="btn btn-upload">Unggah Sekarang</button>
                </form>

                <?php if ($flash_message): ?>
                    <div class="message <?php echo htmlspecialchars($flash_message['type']); ?>">
                        <?php echo $flash_message['text']; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card preview-card">
                <h2>File Anda</h2>
                <div class="file-list">
                    <?php if (empty($active_files)): ?>
                        <div class="empty-message">
                            
                            <p><b>Belum ada file yang terupload.</b></p>
                            <span>Gunakan form di samping untuk mengupload file.</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($active_files as $file): ?>
                            <div class="file-item" id="file-item-<?php echo $file['id']; ?>">
                                <div class="file-info">
                                    <span class="file-name"><?php echo htmlspecialchars($file['original_name']); ?></span>
                                    <span class="file-expiry">
                                        Kedaluwarsa: 
                                        <span class="countdown" data-expiry-timestamp="<?php echo $file['upload_time'] + EXPIRY_TIME; ?>">Menghitung...</span>
                                    </span>
                                </div>
                                <div class="file-actions">
                                    <a href="download.php?id=<?php echo $file['id']; ?>" target="_blank" class="btn-preview">Lihat</a>
                                    <a href="delete_file.php?id=<?php echo $file['id']; ?>" class="btn-delete" onclick="return confirm('Anda yakin ingin menghapus file ini?')">Hapus</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <footer class="site-footer">
        <p>
            Made with ❤️ by 
            <a href="https://github.com/gianabif14" target="_blank" rel="noopener noreferrer">@gianabif14</a>
        </p>
    </footer>
    <script src="script.js"></script>
</body>
</html>