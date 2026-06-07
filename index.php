<?php 
require 'koneksi.php'; 

// Inisialisasi variabel notifikasi
 $pesanTipe = '';
 $pesanTeks = '';

// =======================================================
// SYSTEM PROTEKSI: PROSES HAPUS LAPORAN (Hanya Pemilik Data)
// =======================================================
if (isset($_POST['hapus_laporan_user']) && sudahLogin()) {
    $id_laporan = intval($_POST['id_laporan'] ?? 0);
    $user_id_aktif = $_SESSION['user_id'];

    $stmt_cek = mysqli_prepare($conn, "SELECT gambar FROM items WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt_cek, "ii", $id_laporan, $user_id_aktif);
    mysqli_stmt_execute($stmt_cek);
    $result_cek = mysqli_stmt_get_result($stmt_cek);

    if ($row_cek = mysqli_fetch_assoc($result_cek)) {
        if (!empty($row_cek['gambar']) && file_exists("uploads/" . $row_cek['gambar'])) {
            unlink("uploads/" . $row_cek['gambar']);
        }
        $stmt_hapus = mysqli_prepare($conn, "DELETE FROM items WHERE id = ?");
        mysqli_stmt_bind_param($stmt_hapus, "i", $id_laporan);
        mysqli_stmt_execute($stmt_hapus);

        $pesanTipe = 'sukses';
        $pesanTeks = "Laporan Anda berhasil dihapus dari sistem.";
    } else {
        $pesanTipe = 'error';
        $pesanTeks = "Akses ditolak! Anda tidak berwenang menghapus laporan ini.";
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'edit_sukses') {
    $pesanTipe = 'sukses';
    $pesanTeks = "Laporan Anda berhasil diperbarui.";
}

// =======================================================
// PROSES TAMBAH DATA LAPORAN BARU
// =======================================================
if (isset($_POST['kirim_laporan']) && sudahLogin() && !isAdmin()) {
    $nama   = trim($_POST['nama_barang'] ?? '');
    $kat    = $_POST['kategori'] ?? 'Hilang';
    $desk   = trim($_POST['deskripsi'] ?? '');
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
        $stmt = mysqli_prepare($conn, "INSERT INTO items (user_id, nama_barang, kategori, deskripsi, gambar) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issss", $_SESSION['user_id'], $nama, $kat, $desk, $gambar);
        if (mysqli_stmt_execute($stmt)) {
            $pesanTipe = 'sukses';
            $pesanTeks = "Laporan berhasil dikirim dan langsung diterbitkan ke sistem!";
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
    <title>TemuBarang - Beranda</title>
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
                <?php if (isAdmin()): ?>
                    <a href="dashboard.php">Dashboard</a>
                <?php endif; ?>
                <?php if (sudahLogin()): ?>
                    <a href="logout">Logout (<?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>)</a>
                <?php else: ?>
                    <a href="login">Login</a>
                <?php endif; ?>
            </div>
            <div class="hamburger"><span></span><span></span><span></span></div>
        </nav>
    </header>

    <div class="main-wrapper">
        
        <?php if (sudahLogin() && !isAdmin()): ?>
        <div class="container-report-wrapper">

            <?php if ($pesanTeks): ?>
                <div class="alert <?= $pesanTipe === 'sukses' ? 'badge-temu' : 'badge-hilang' ?>" style="padding:15px; margin-bottom:25px; border-radius:8px;">
                    <?= $pesanTipe === 'sukses' ? '✅' : '⚠️' ?>
                    <?= htmlspecialchars($pesanTeks, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <button id="toggleFormBtn" class="btn" style="width: 100%; margin-bottom: 30px; display: flex; justify-content: center; align-items: center; gap: 10px; font-size: 1rem;">
                ➕ Buat Laporan Kehilangan / Penemuan
            </button>

            <div id="formReportWrapper" style="display: <?= ($pesanTipe === 'error') ? 'block' : 'none' ?>;">
                <div class="report-card-container">
                    <h2 class="report-card-title">📋 Buat Laporan Baru</h2>
                    <p class="report-card-subtitle">Isi detail data dengan benar agar proses pencarian menjadi lebih mudah.</p>
                    
                    <form action="" method="POST" enctype="multipart/form-data" class="report-form-clean">
                        <div class="input-row-grid">
                            <div class="custom-form-group">
                                <label>Nama Barang</label>
                                <input type="text" name="nama_barang" placeholder="Contoh: Dompet hitam..." required>
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
                            <textarea name="deskripsi" rows="3" placeholder="Sebutkan ciri-ciri unik barang..." required></textarea>
                        </div>
                        <div class="custom-form-group" style="margin-bottom: 30px;">
                            <label>Upload Foto Pendukung <small class="lbl-optional">(opsional, maks 2 MB)</small></label>
                            <div class="upload-area enhanced-upload-box">
                                <input type="file" id="imageUpload" name="gambar" accept="image/jpeg,image/png">
                                <div class="upload-text">
                                    <p class="upload-text-title">📸 Klik atau Seret Gambar Ke Sini</p>
                                    <span class="upload-text-sub">Format: JPG, PNG — Maks: 2 MB</span>
                                </div>
                                <img id="previewImage" src="" alt="Preview" class="upload-preview-img">
                            </div>
                        </div>
                        <button type="submit" name="kirim_laporan" class="btn enhanced-btn-submit">🚀 Kirim Laporan Sistem</button>
                    </form>
                </div>
                <hr class="report-divider">
            </div>

        </div>
        <?php endif; ?>

        <div class="posts-container">
            <?php
            // =======================================================
            // LOGIKA FILTER TAMPILAN BERDASARKAN ROLE USER
            // =======================================================
            // Semua orang (Admin, User, Tamu) bisa melihat semua laporan
            $query = mysqli_query($conn, "SELECT * FROM items ORDER BY id DESC");

            if (!$query || mysqli_num_rows($query) === 0): ?>
                <p style="text-align:center; color:#a0b2c6; margin-top:60px;">
                    Belum ada laporan apapun di sistem saat ini.
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
                        <a href="uploads/<?= htmlspecialchars($row['gambar'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" title="Klik untuk memperbesar gambar">
                            <img src="uploads/<?= htmlspecialchars($row['gambar'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto <?= htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8') ?>" style="cursor: zoom-in; transition: opacity 0.3s;" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                        </a>
                    </div>
                <?php endif; ?>
                <div class="post-content">
                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['kategori'], ENT_QUOTES, 'UTF-8') ?></span>
                    <p><?= nl2br(htmlspecialchars($row['deskripsi'], ENT_QUOTES, 'UTF-8')) ?></p>
                    
                    <?php if (sudahLogin() && $_SESSION['user_id'] == $row['user_id']): ?>
                        <div class="post-actions-wrapper">
                            <a href="edit_laporan.php?id=<?= (int)$row['id'] ?>" class="btn-action-edit">✏️ Edit</a>
                            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?')">
                                <input type="hidden" name="id_laporan" value="<?= (int)$row['id'] ?>">
                                <button type="submit" name="hapus_laporan_user" class="btn-action-delete">🗑️ Hapus</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; endif; ?>
        </div>

        <footer class="footer-copyright">
            <p>&copy; 2026 <span class="brand-glow">TemuBarang</span> System. All Rights Reserved.</p>
        </footer>
    </div>

    <button class="chatbot-toggle" id="chatbotToggle" aria-label="Buka chatbot">
        <span class="toggle-icon toggle-open">💬</span>
        <span class="toggle-close">✕</span>
        <span class="chatbot-badge">1</span>
    </button>

    <div class="chatbot-window" id="chatbotWindow">
        <div class="chatbot-header">
            <div class="chatbot-avatar">🤖</div>
            <div class="chatbot-header-info">
                <h4>TemuBarang Assistant</h4>
                <div class="chatbot-status">Online — AI Powered</div>
            </div>
            <div class="chatbot-header-actions">
                <button id="chatClearBtn" title="Clean Chat" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:16px;">🔄</button>
            </div>
        </div>

        <div class="chatbot-messages" id="chatbotBody">
            <div class="chat-msg incoming">Halo! Ada yang bisa saya bantu terkait pelacakan atau pelaporan barang hilang?</div>
        </div>

        <form class="chatbot-input-form" onsubmit="return false;">
            <input type="text" id="chatInput" placeholder="Ketik pesan..." autocomplete="off" maxlength="1000">
            <button id="chatSendBtn" class="chatbot-send-btn" aria-label="Kirim pesan">➤</button>
        </form>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>