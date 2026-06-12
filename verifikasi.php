<?php
require 'koneksi.php';

// Cegah akses langsung jika session email verifikasi tidak ada
if (!isset($_SESSION['email_verifikasi'])) {
    header("Location: register");
    exit;
}

$email = $_SESSION['email_verifikasi'];
$error = '';
$success_msg = '';

// --- LOGIKA VERIFIKASI OTP ---
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
            // unset($_SESSION['last_otp_time']); // Opsional: bersihkan session waktu
            header("Location: login");
            exit;
        }
    } else {
        $error = "Kode OTP salah atau telah kedaluwarsa! Silakan periksa kembali.";
    }
}

// --- LOGIKA KIRIM ULANG OTP (RESEND) ---
// Set waktu pertama kali OTP dikirim jika belum ada di session
if (!isset($_SESSION['last_otp_time'])) {
    $_SESSION['last_otp_time'] = time(); 
}

if (isset($_POST['resend_otp'])) {
    $time_passed = time() - $_SESSION['last_otp_time'];
    
    // Cek apakah sudah lewat 60 detik
    if ($time_passed >= 60) {
        // Generate OTP Baru
        $new_otp = sprintf("%06d", mt_rand(1, 999999));
        $new_expiry = date("Y-m-d H:i:s", strtotime('+15 minutes')); // Masa berlaku 15 menit

        // Update OTP di database
        $update_otp = mysqli_prepare($conn, "UPDATE users SET otp_code = ?, otp_expiry = ? WHERE email = ?");
        mysqli_stmt_bind_param($update_otp, "sss", $new_otp, $new_expiry, $email);
        
        if (mysqli_stmt_execute($update_otp)) {
            // TODO: Masukkan logika pengiriman email Anda di sini (misal menggunakan PHPMailer)
            // mail($email, "Kode OTP Baru", "Kode OTP Anda: " . $new_otp);
            
            $_SESSION['last_otp_time'] = time(); // Reset waktu di session
            $success_msg = "Kode OTP baru berhasil dikirim ke email Anda!";
        } else {
            $error = "Gagal membuat OTP baru. Silakan coba lagi.";
        }
    } else {
        $error = "Harap tunggu sebelum meminta kode OTP lagi.";
    }
}

// Hitung sisa waktu untuk JavaScript
$cooldown = 60;
$time_passed = time() - $_SESSION['last_otp_time'];
$remaining_time = $time_passed >= $cooldown ? 0 : ($cooldown - $time_passed);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - TemuBarang</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .btn-resend {
            background-color: transparent;
            color: #a0b2c6;
            border: 1px solid #a0b2c6;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
            width: 100%;
            transition: 0.3s;
        }
        .btn-resend:hover:not(:disabled) {
            background-color: #a0b2c6;
            color: #fff;
        }
        .btn-resend:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
    </style>
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;" class="auth-page-body">
    <div class="auth-container" style="text-align: center;">
        <h2>🔐 Verifikasi Akun</h2>
        <p style="color: #a0b2c6; font-size: 0.95em; margin-bottom: 20px;">
            Kami telah mengirimkan 6 digit kode OTP ke email <strong style="color:#fff;"><?= htmlspecialchars($email) ?></strong>.
        </p>

        <?php if ($error): ?>
            <div class="alert-box" style="margin-bottom:20px; text-align: left; color: #ff6b6b;">
                ⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_msg): ?>
            <div class="alert-box" style="margin-bottom:20px; text-align: left; color: #51cf66;">
                ✅ <?= htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <label style="text-align: left; display: block;">Masukkan Kode OTP</label>
            <input type="text" name="otp_code" placeholder="------" required maxlength="6" 
                   style="text-align: center; font-size: 1.8em; letter-spacing: 8px; font-weight: bold;" autocomplete="off">

            <button type="submit" name="verify" class="btn auth-btn-submit" style="margin-top:20px; width:100%; font-size:1.1em;">Verifikasi Sekarang</button>
        </form>
        
        <form action="" method="POST" id="formResend">
            <button type="submit" name="resend_otp" id="btnResend" class="btn-resend" disabled>
                Kirim Ulang OTP
            </button>
        </form>
        
        <div class="auth-link" style="margin-top: 20px;">
            Salah memasukkan email? <a href="register" style="text-decoration: underline;">Kembali</a>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let timeLeft = <?= $remaining_time ?>;
            const btnResend = document.getElementById("btnResend");

            function updateTimer() {
                if (timeLeft > 0) {
                    btnResend.disabled = true;
                    btnResend.innerText = `Kirim Ulang OTP (${timeLeft}s)`;
                    timeLeft--;
                } else {
                    btnResend.disabled = false;
                    btnResend.innerText = "Kirim Ulang OTP";
                    clearInterval(timerInterval);
                }
            }

            // Jalankan fungsi segera saat dimuat
            updateTimer();
            
            // Set interval jika masih ada waktu
            let timerInterval;
            if (timeLeft > 0) {
                timerInterval = setInterval(updateTimer, 1000);
            }
        });
    </script>
</body>
</html>