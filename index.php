<?php require 'koneksi.php'; ?>
<?php
// Proses submit laporan oleh user biasa
$pesanTipe = '';
$pesanTeks = '';

if (isset($_POST['kirim_laporan']) && sudahLogin() && !isAdmin()) {
    $nama  = trim($_POST['nama_barang'] ?? '');
    $kat   = $_POST['kategori'] ?? 'Hilang';
    $desk  = trim($_POST['deskripsi'] ?? '');
    $gambar = null;
    $errorUpload = '';

    if (!empty($_FILES['gambar']['name'])) {
        if ($_FILES['gambar']['size'] > 2 * 1024 * 1024) {
            $errorUpload = "Ukuran gambar melebihi 2 MB.";
        } else {
            $gambar = uploadGambar($_FILES['gambar']);
            if ($gambar === null) {
                $errorUpload = "Format tidak didukung. Gunakan JPG atau PNG.";
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
        $stmt = mysqli_prepare($conn,
            "INSERT INTO items (user_id, nama_barang, kategori, deskripsi, gambar) VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "issss",
            $_SESSION['user_id'], $nama, $kat, $desk, $gambar
        );
        if (mysqli_stmt_execute($stmt)) {
            $pesanTipe = 'sukses';
            $pesanTeks = "Laporan berhasil dikirim! Admin akan segera menindaklanjuti.";
        } else {
            $pesanTipe = 'error';
            $pesanTeks = "Terjadi kesalahan server. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found - Beranda</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo">LOSTTRACK</div>
            <div class="nav-menu">
                <a class="nav_btn" href="/">Beranda</a>
                <a class="nav_btn" href="profil">Profil</a>
                <a class="nav_btn" href="panduan">Panduan</a>
                <?php if (isAdmin()): ?>
                    <a class="nav_btn" href="dashboard">Dashboard</a>
                <?php endif; ?>
                <?php if (sudahLogin()): ?>
                    <a class="nav_btn" href="logout">
                        Logout (<?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>)
                    </a>
                <?php else: ?>
                    <a class="nav_btn" href="login">Login</a>
                <?php endif; ?>
            </div>
            <div class="hamburger"><span></span><span></span><span></span></div>
        </nav>
    </header>

    <div class="main-wrapper">
        
        <?php
        // ── FORM BUAT LAPORAN — hanya tampil untuk user biasa yang sudah login ──
        if (sudahLogin() && !isAdmin()):
        ?>
        <div class="container-report-wrapper">

            <?php if ($pesanTeks): ?>
                <div class="alert <?= $pesanTipe === 'sukses' ? 'badge-temu' : 'badge-hilang' ?>"
                     style="padding:15px; margin-bottom:25px; border-radius:8px;">
                    <?= $pesanTipe === 'sukses' ? '✅' : '⚠️' ?>
                    <?= htmlspecialchars($pesanTeks, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="report-card-container">
                <h2 class="report-card-title">📋 Buat Laporan Baru</h2>
                <p class="report-card-subtitle">Isi detail data dengan benar agar proses pencarian menjadi lebih mudah.</p>
                
                <form action="" method="POST" enctype="multipart/form-data" class="report-form-clean">

                    <div class="input-row-grid">
                        <div class="custom-form-group">
                            <label>Nama Barang</label>
                            <input type="text" name="nama_barang" placeholder="Contoh: Dompet hitam, Kunci motor..." required>
                        </div>

                        <div class="custom-form-group">
                            <label>Kategori</label>
                            <select name="kategori">
                                <option value="Hilang">Barang Hilang</option>
                                <option value="Ditemukan">Barang Temuan</option>
                            </select>
                        </div>
                    </div>

                    <div class="custom-form-group">
                        <label>Deskripsi Detail</label>
                        <textarea name="deskripsi" rows="3" placeholder="Sebutkan ciri-ciri unik barang, lokasi terakhir terlihat, atau kontak yang bisa dihubungi..." required></textarea>
                    </div>

                    <div class="custom-form-group" style="margin-bottom: 30px;">
                        <label>Upload Foto Pendukung <small class="lbl-optional">(opsional, maks 2 MB)</small></label>
                        <div class="upload-area enhanced-upload-box">
                            <input type="file" id="imageUpload" name="gambar" accept="image/jpeg,image/png">
                            <div class="upload-text">
                                <p class="upload-text-title">📸 Klik atau Seret File Gambar Ke Sini</p>
                                <span class="upload-text-sub">Format: JPG, PNG — Ukuran maksimal: 2 MB</span>
                            </div>
                            <img id="previewImage" src="" alt="Preview" class="upload-preview-img">
                        </div>
                    </div>

                    <button type="submit" name="kirim_laporan" class="btn enhanced-btn-submit">
                        🚀 Kirim Laporan Sistem
                    </button>
                </form>
            </div>

            <hr class="report-divider">
        </div>
        <?php endif; ?>

        <div class="posts-container">
            <?php
            $query = mysqli_query($conn, "SELECT * FROM items ORDER BY id DESC");

            if (mysqli_num_rows($query) === 0): ?>
                <p style="text-align:center; color:#a0b2c6; margin-top:60px;">
                    Belum ada laporan. Cek kembali nanti.
                </p>
            <?php else:
                while ($row = mysqli_fetch_assoc($query)):
                    $badgeClass = ($row['kategori'] === 'Hilang') ? 'badge-hilang' : 'badge-temu';
                    $tanggal    = date('d M Y, H:i', strtotime($row['tanggal_lapor']));
            ?>
            <div class="post-card">
                <div class="post-header">
                    <div class="profile-pic">📦</div>
                    <div class="post-user">
                        <h4><?= htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8') ?></h4>
                        <span><?= $tanggal ?></span>
                    </div>
                </div>

                <?php if (!empty($row['gambar'])): ?>
                    <div class="post-image">
                        <img src="uploads/<?= htmlspecialchars($row['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                             alt="Foto <?= htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                <?php endif; ?>

                <div class="post-content">
                    <span class="badge <?= $badgeClass ?>">
                        <?= htmlspecialchars($row['kategori'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <p><?= nl2br(htmlspecialchars($row['deskripsi'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            </div>
            <?php   endwhile;
            endif; ?>
        </div>

        <footer class="footer-copyright">
            <p>&copy; 2026 <span class="brand-glow">LostTrack</span> System. All Rights Reserved.</p>
            <p class="footer-sub">Designed & Engineered by Ibren, Miracle, Heavenly, and Anggreini</p>
        </footer>

    </div>

    <script src="assets/js/script.js"></script>
    <script>
        const imgInput = document.getElementById('imageUpload');
        const imgPreview = document.getElementById('previewImage');
        if(imgInput && imgPreview) {
            imgInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.addEventListener('load', function() {
                        imgPreview.setAttribute('src', this.result);
                        imgPreview.style.display = 'block';
                    });
                    reader.readAsDataURL(file);
                }
            });
        }
    </script>
</body>
</html>