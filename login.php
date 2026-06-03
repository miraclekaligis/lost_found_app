<?php
require 'koneksi.php';
if (isset($_SESSION['login'])) { header("Location: dashboard.php"); exit; }

$error = false;
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username' AND password = '$password'");
    if (mysqli_num_rows($result) === 1) {
        $_SESSION['login'] = true;
        header("Location: dashboard.php"); exit;
    }
    $error = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistem Web</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Otentikasi Sistem</h1>
        <nav><a href="index.php">Kembali ke Beranda</a></nav>
    </header>
    <div class="container" style="max-width: 450px; text-align: center;">
        <h2>Area Admin</h2>
        <?php if ($error) : ?>
            <div class="alert badge-hilang" style="padding:15px; margin-bottom:20px;">Username atau Password salah!</div>
        <?php endif; ?>
        <form action="" method="POST" style="text-align: left;">
            <label>Username</label>
            <input type="text" name="username" placeholder="Masukkan username admin..." required>
            <label>Password</label>
            <input type="password" name="password" placeholder="Masukkan password..." required>
            <button type="submit" name="login" class="btn" style="margin-top: 10px;">Login Dashboard</button>
        </form>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>