<?php
require 'koneksi.php';

// Cegah akses langsung jika session email verifikasi tidak ada
if (!isset($_SESSION['email_verifikasi'])) {
    header("Location: register.php");
    exit;
}

$email = $_SESSION['email_verifikasi'];
$error = '';

if (isset($_POST['verify'])) {
    $otp_input = trim($_POST['otp_code'] ?? '');
    $now       = date("Y-m-d H:i:s");

    // Cari user yang email, otp cocok, dan masa berlakunya belum melewati waktu sekarang
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND otp_code = ? AND otp_expiry >= ?");
    mysqli_stmt_bind_param($stmt, "sss", $email, $otp_input, $now);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $update = mysqli_prepare($conn, "UPDATE users SET is_verified = 1, otp_code = NULL, otp_expiry = NULL WHERE email = ?");
        mysqli_stmt_bind_param($update, "s", $email);
        
        if (mysqli_stmt_execute($update)) {
            unset($_SESSION['email_verifikasi']); // Hapus session sementara
            header("Location: login.php?status=sukses_verifikasi");
            exit;
        }
    } else {
        $error = "Kode OTP salah atau telah kedaluwarsa! Silakan periksa kembali.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - LostTrack</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;" class="auth-page-body">
    <div class="auth-container" style="text-align: center;">
        <h2>🔐 Verifikasi Akun</h2>
        <p style="color: #a0b2c6; font-size: 0.95em; margin-bottom: 20px;">
            Kami telah mengirimkan 6 digit kode OTP ke email <strong style="color:#fff;"><?= htmlspecialchars($email) ?></strong>.
        </p>

        <?php if ($error): ?>
            <div class="alert-box" style="margin-bottom:20px; text-align: left;">
                ⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <label style="text-align: left; display: block;">Masukkan Kode OTP</label>
            <input type="text" name="otp_code" placeholder="------" required maxlength="6" 
                   style="text-align: center; font-size: 1.8em; letter-spacing: 8px; font-weight: bold;" autocomplete="off">

            <button type="submit" name="verify" class="btn auth-btn-submit" style="margin-top:20px; width:100%; font-size:1.1em;">Verifikasi Sekarang</button>
        </form>
        
        <div class="auth-link" style="margin-top: 20px;">
            Salah memasukkan email? <a href="register.php" style="text-decoration: underline;">Kembali</a>
        </div>
    </div>
</body>
</html>