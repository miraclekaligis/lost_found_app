<?php
require 'koneksi.php';

// Jika sudah login, tendang ke halaman yang sesuai dengan role-nya
if (sudahLogin()) {
    if (isAdmin()) {
        header("Location: dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

 $error = false;
 $error_msg = '';

if (isset($_POST['login'])) {
    $usernameInput = trim($_POST['username'] ?? '');
    $passwordInput = $_POST['password'] ?? '';

    // ============================================================
    // TAMBAHKAN email DAN is_verified DI SELECT
    // ============================================================
    $stmt = mysqli_prepare($conn, "SELECT id, username, email, password, role, is_verified FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $usernameInput);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($passwordInput, $row['password'])) {

            // ============================================================
            // CEK APAKAH AKUN SUDAH DIVERIFIKASI OTP
            // ============================================================
            if ($row['is_verified'] != 1) {
                // Simpan email ke session agar halaman verifikasi tahu siapa user-nya
                $_SESSION['email_verifikasi'] = $row['email'];

                // Tendang ke halaman verifikasi OTP
                header("Location: verifikasi");
                exit;
            }

            // ============================================================
            // AKUN TERVERIFIKASI — LANJUT LOGIN
            // ============================================================
            session_regenerate_id(true);

            $_SESSION['login']    = true;
            $_SESSION['user_id']  = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role'];

            if ($row['role'] === 'admin') {
                header("Location: dashboard");
            } else {
                header("Location: /");
            }
            exit;
        }
    }
    $error = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="body-login">
    <div class="container-login" style="max-width:450px; text-align:center;">
        <h2>🔐 Login</h2>
        <?php if ($error): ?>
            <div class="alert badge-hilang" style="padding:15px; margin-bottom:20px; border-radius:8px;">
                ⚠️ Username atau password salah!
            </div>
        <?php endif; ?>
        <form action="" method="POST" style="text-align:left;">
            <label>Username</label>
            <input type="text" name="username" placeholder="Masukkan username..." required autocomplete="off">
            <label>Password</label>
            <input type="password" name="password" placeholder="Masukkan password..." required>
            <button type="submit" name="login" class="btn" style="margin-top:10px; width:100%;">Login</button>
        </form>
        
        <div class="auth-link" style="margin-top: 15px;">
            Belum punya akun? <a href="register">Daftar Sekarang</a>
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