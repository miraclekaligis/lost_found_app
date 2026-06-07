<?php
session_start();
date_default_timezone_set('Asia/Makassar'); // <-- Tambahkan ini (sesuaikan dengan 'Asia/Jakarta' atau 'Asia/Jayapura' jika perlu)

$host = "sql203.infinityfree.com";
$user = "if0_39547178";
$pass = "dede13042006";
$db   = "if0_39547178_lost_found_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// ... (sisa kode helper ke bawah sudah sempurna, tidak perlu diubah)

// -------------------------------------------------------
// Helper: cek apakah user sudah login
// -------------------------------------------------------
function sudahLogin(): bool {
    return isset($_SESSION['login']) && $_SESSION['login'] === true;
}

// -------------------------------------------------------
// Helper: cek apakah user adalah admin
// -------------------------------------------------------
function isAdmin(): bool {
    return sudahLogin() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// -------------------------------------------------------
// Helper: paksa login — redirect jika belum login
// -------------------------------------------------------
function requireLogin(): void {
    if (!sudahLogin()) {
        header("Location: login");
        exit;
    }
}

// -------------------------------------------------------
// Helper: paksa role admin — redirect jika bukan admin
// -------------------------------------------------------
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header("Location: index");
        exit;
    }
}

// -------------------------------------------------------
// Helper: upload gambar — return nama file atau null
// Validasi: tipe MIME (bukan hanya ekstensi) + ukuran maks 2 MB
// -------------------------------------------------------
function uploadGambar(array $file, string $uploadDir = 'uploads/'): ?string {
    if (empty($file['name'])) return null;

    $maxSize     = 2 * 1024 * 1024; // 2 MB
    $tipeAllowed = ['image/jpeg', 'image/png', 'image/jpg'];
    $extAllowed  = ['jpg', 'jpeg', 'png'];

    // Cek ukuran
    if ($file['size'] > $maxSize) return null;

    // Cek tipe MIME nyata (bukan dari browser)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $tipeAllowed)) return null;

    // Cek ekstensi
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $extAllowed)) return null;

    // Buat nama file unik
    $namaFile = uniqid('img_') . '.' . $ext;

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $namaFile)) {
        return $namaFile;
    }

    return null;
}

// -------------------------------------------------------
// Helper: hapus file gambar dari server
// -------------------------------------------------------
function hapusGambar(?string $namaFile, string $uploadDir = 'uploads/'): void {
    if (!empty($namaFile)) {
        $path = $uploadDir . $namaFile;
        if (file_exists($path)) unlink($path);
    }
}
