<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Verifikasi Google reCAPTCHA
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret'   => RECAPTCHA_SECRET_KEY,
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
        // 2. Jika reCAPTCHA berhasil, lanjutkan proses registrasi
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $error = "Username dan password tidak boleh kosong.";
        } elseif (strlen($password) < 6) {
            $error = "Password minimal harus 6 karakter.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username sudah digunakan. Silakan pilih yang lain.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
                if ($stmt->execute([$username, $password_hash])) {
                    $success = "Registrasi berhasil! Silakan <a href='login.php'>login</a>.";
                } else {
                    $error = "Terjadi kesalahan pada server. Silakan coba lagi.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Upload File Temporary</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="login-container">
        <form action="register.php" method="post" class="login-form">
            <h2>Buat Akun Baru 📝</h2>
            <p>Buat akun anda untuk mulai mengunggah file.</p>

            <?php if($error): ?><div class="message error"><?php echo $error; ?></div><?php endif; ?>
            <?php if($success): ?><div class="message success"><?php echo $success; ?></div><?php endif; ?>

            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="6" required>
            </div>
            
            <div class="recaptcha-container">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>  <!-- Tidak perlu diubah -->
            </div>
            
            <button type="submit" class="btn">Daftar</button>
            <div class="form-footer">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </form>
    </div>
</body>
</html>