<?php
require_once 'koneksi.php';

if (!sudahLogin()) {
    header("Location: login");
    exit();
}

$user_id_aktif = $_SESSION['user_id'];
$id_laporan = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Prepare statement and handle possible prepare failure
$stmt_cek = mysqli_prepare($conn, "SELECT * FROM items WHERE id = ? AND user_id = ?");
if ($stmt_cek) {
    mysqli_stmt_bind_param($stmt_cek, "ii", $id_laporan, $user_id_aktif);
    mysqli_stmt_execute($stmt_cek);
    $result = mysqli_stmt_get_result($stmt_cek);
    $item_edit = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_cek);
} else {
    // fallback: attempt a safe query if prepare is not available
    $id_safe = intval($id_laporan);
    $user_safe = intval($user_id_aktif);
    $res = mysqli_query($conn, "SELECT * FROM items WHERE id = $id_safe AND user_id = $user_safe");
    $item_edit = $res ? mysqli_fetch_assoc($res) : null;
}

if (!$item_edit) {
    header("Location: index.php?pesan=error&detail=Akses+Ditolak");
    exit();
}

$pesanTipe = '';
$pesanTeks = '';

if (isset($_POST['update_laporan'])) {
    $nama        = trim($_POST['nama_barang'] ?? '');
    $kat         = $_POST['kategori'] ?? 'Hilang';
    $desk        = trim($_POST['deskripsi'] ?? '');
    $gambar_lama = $_POST['gambar_lama'] ?? null;
    $gambar_baru = $gambar_lama;
    $errorUpload = '';

    if (!empty($_FILES['gambar']['name'])) {
        if ($_FILES['gambar']['size'] > 2 * 1024 * 1024) {
            $errorUpload = "Ukuran gambar melebihi 2 MB.";
        } else {
            $uploaded = uploadGambar($_FILES['gambar']);
            if ($uploaded === null) {
                $errorUpload = "Format gambar tidak didukung.";
            } else {
                $gambar_baru = $uploaded;
                if (!empty($gambar_lama) && file_exists("uploads/" . $gambar_lama)) {
                    unlink("uploads/" . $gambar_lama);
                }
            }
        }
    }

    if ($errorUpload) {
        $pesanTipe = 'error';
        $pesanTeks = $errorUpload;
    } elseif (empty($nama) || empty($desk)) {
        $pesanTipe = 'error';
        $pesanTeks = "Nama barang dan deskripsi tidak boleh kosong.";
    } else {
        $stmt_up = mysqli_prepare($conn, "UPDATE items SET nama_barang = ?, kategori = ?, deskripsi = ?, gambar = ? WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt_up, "ssssii", $nama, $kat, $desk, $gambar_baru, $id_laporan, $user_id_aktif);
        if (mysqli_stmt_execute($stmt_up)) {
            header("Location: /?status=edit_sukses");
            exit();
        } else {
            $pesanTipe = 'error';
            $pesanTeks = "Terjadi kesalahan server saat memperbarui data.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Laporan - TemuBarang</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo">TemuBarang</div>
            <div class="nav-menu">
                <a href="/">Beranda</a>
                <a href="profil">Profil</a>
                <a href="panduan">Panduan</a>
                <a href="logout">Logout (<?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>)</a>
            </div>
            <div class="hamburger"><span></span><span></span><span></span></div>
        </nav>
    </header>

    <div class="main-wrapper">
        <div class="container-report-wrapper" style="margin-top: 60px;">
            <?php if ($pesanTeks): ?>
                <div class="alert <?= $pesanTipe === 'sukses' ? 'badge-temu' : 'badge-hilang' ?>" style="padding:15px; margin-bottom:25px; border-radius:8px;">
                    <?= htmlspecialchars($pesanTeks, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="report-card-container">
                <h2 class="report-card-title">✏️ Edit Laporan</h2>
                
                <form action="" method="POST" enctype="multipart/form-data" class="report-form-clean">
                    <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($item_edit['gambar'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="input-row-grid">
                        <div class="custom-form-group">
                            <label>Nama Barang</label>
                            <input type="text" name="nama_barang" value="<?= htmlspecialchars($item_edit['nama_barang'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="custom-form-group">
                            <label>Kategori</label>
                            <select name="kategori">
                                <option value="Hilang" <?= $item_edit['kategori'] === 'Hilang' ? 'selected' : '' ?>>Barang Hilang</option>
                                <option value="Ditemukan" <?= $item_edit['kategori'] === 'Ditemukan' ? 'selected' : '' ?>>Barang Temuan</option>
                            </select>
                        </div>
                    </div>
                    <div class="custom-form-group">
                        <label>Deskripsi Detail</label>
                        <textarea name="deskripsi" rows="4" required><?= htmlspecialchars($item_edit['deskripsi'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="custom-form-group" style="margin-bottom: 30px;">
                        <label>Upload Foto Baru <small class="lbl-optional">(opsional)</small></label>
                        <div class="upload-area enhanced-upload-box">
                            <input type="file" id="imageUpload" name="gambar" accept="image/jpeg,image/png">
                            <div class="upload-text" <?= !empty($item_edit['gambar']) ? 'style="display:none;"' : '' ?>>
                                <p class="upload-text-title">📸 Klik atau Seret File Gambar Ke Sini</p>
                            </div>
                            <img id="previewImage" src="<?= !empty($item_edit['gambar']) ? 'uploads/'.htmlspecialchars($item_edit['gambar'], ENT_QUOTES, 'UTF-8') : '' ?>" alt="Preview" class="upload-preview-img" style="<?= !empty($item_edit['gambar']) ? 'display:block;' : 'display:none;' ?>">
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <button type="submit" name="update_laporan" class="btn enhanced-btn-submit" style="flex: 1;">💾 Simpan</button>
                        <a href="/" style="color: var(--text-muted); font-weight: 600; text-decoration: none; padding: 16px;">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>