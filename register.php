<?php
require 'koneksi.php';

// Jika sudah login, lempar ke halaman index
if (sudahLogin()) { 
    header("Location: ./"); 
    exit; 
}

// Memanggil library PHPMailer secara manual
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$error   = '';
$success = false;

if (isset($_POST['register'])) {
    $username   = trim($_POST['username']   ?? '');
    $email      = trim($_POST['email']      ?? '');
    $password   = $_POST['password']        ?? '';
    $konfirmasi = $_POST['confirm_password'] ?? '';

    if (strlen($username) < 3) {
        $error = "Username minimal 3 karakter.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password) < 5) {
        $error = "Password minimal 5 karakter.";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        $cek = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($cek, "ss", $username, $email);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {
            $error = "Username atau email sudah digunakan.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            // GENERATE OTP
            $otp_code   = (string)rand(100000, 999999);
            $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role, otp_code, otp_expiry, is_verified) VALUES (?, ?, ?, 'member', ?, ?, 0)");
            mysqli_stmt_bind_param($stmt, "sssss", $username, $email, $hash, $otp_code, $otp_expiry);

            if (mysqli_stmt_execute($stmt)) {
                
                // --- PROSES KIRIM EMAIL OTP VIA PHPMailer ---
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'officialmiraclekaligis@gmail.com'; 
                    $mail->Password   = 'bxckkonpoqcvwihl';
                    
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
                    $mail->Timeout    = 30; 

                    // PENTING: Perbaikan Handshake SSL untuk Localhost XAMPP Windows
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ];

                    // Pengaturan Pengirim & Penerima
                    $mail->setFrom('officialmiraclekaligis@gmail.com', 'TemuBarang System');
                    $mail->addAddress($email, $username);

                    // Konten Isi Email
                    $mail->isHTML(true);
                    $mail->Subject = 'Kode OTP Verifikasi Akun - TemuBarang';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #111a2c; color: #fff; border-radius: 10px;'>
                            <h2 style='color: #00f2fe;'>Halo, ".htmlspecialchars($username)."!</h2>
                            <p>Terima kasih telah bergabung di TemuBarang. Gunakan kode OTP di bawah ini untuk memverifikasi pendaftaran akun Anda:</p>
                            <div style='font-size: 28px; font-weight: bold; color: #030914; padding: 15px; background: #00f2fe; display: inline-block; border-radius: 5px; letter-spacing: 5px; margin: 15px 0;'>$otp_code</div>
                            <p style='color: #ff4a5a;'>⚠️ Kode ini hanya berlaku selama 5 menit.</p>
                            <p style='font-size: 0.85em; color: #8fa0b5;'>Jika Anda tidak merasa mendaftar di platform kami, harap abaikan email ini.</p>
                        </div>
                    ";

                    $mail->send();
                    
                    // Simpan email ke session untuk divalidasi di halaman berikutnya
                    $_SESSION['email_verifikasi'] = $email;
                    
                    // Sesuaikan arah redirect ke file php Anda (verifikasi.php)
                    header("Location: verifikasi");
                    exit;

                } catch (Exception $e) {
                    mysqli_query($conn, "DELETE FROM users WHERE email = '$email'");
                    $error = "Gagal mengirimkan kode OTP ke email. Error: {$mail->ErrorInfo}";
                }

            } else {
                $error = "Terjadi kesalahan server. Coba lagi.";
            }
        }
    }
}

$old = [
    'username' => !$success ? htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') : '',
    'email'    => !$success ? htmlspecialchars($_POST['email']    ?? '', ENT_QUOTES, 'UTF-8') : '',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun - TemuBarang</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page-body">
    <div class="auth-container" style="text-align: center;">
        <h2>📝 Buat Akun</h2>
        <p>Silakan isi data berikut untuk mendaftar</p>

        <?php if ($error): ?>
            <div class="alert-box">
                ⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" style="text-align: left;">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Pilih username unik..." value="<?= $old['username'] ?>" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Masukkan email aktif..." value="<?= $old['email'] ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Minimal 5 karakter..." required>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="confirm_password" placeholder="Ulangi password..." required>
            </div>
            <button type="submit" name="register" class="btn auth-btn-submit" style="width: 100%;">Daftar Sekarang</button>
        </form>
        
        <div class="auth-link" style="margin-top: 15px;">
            Sudah punya akun? <a href="login">Login di sini</a>
        </div>

        <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
            <a href="/" class="btn-kembali" style="text-decoration: none; color: #666; font-size: 14px; display: inline-flex; align-items: center; gap: 5px;">
                ⬅️ Kembali ke Beranda
            </a>
        </div>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>