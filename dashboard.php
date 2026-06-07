<?php
require 'koneksi.php';
requireAdmin();

// -------------------------------------------------------
// TAMBAH DATA
// -------------------------------------------------------
if (isset($_POST['tambah'])) {
    $nama   = trim($_POST['nama_barang'] ?? '');
    $kat    = $_POST['kategori'] ?? 'Hilang';
    $desk   = trim($_POST['deskripsi'] ?? '');
    $gambar = null;
    $errorUpload = '';

    if (!empty($_FILES['gambar']['name'])) {
        // Cek ukuran sebelum proses — beri pesan spesifik
        if ($_FILES['gambar']['size'] > 2 * 1024 * 1024) {
            $errorUpload = "Ukuran gambar melebihi 2 MB.";
        } else {
            $gambar = uploadGambar($_FILES['gambar']);
            if ($gambar === null) {
                $errorUpload = "Format gambar tidak didukung. Gunakan JPG atau PNG.";
            }
        }
    }

    if ($errorUpload) {
        header("Location: dashboard.php?pesan=error&detail=" . urlencode($errorUpload));
        exit;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO items (user_id, nama_barang, kategori, deskripsi, gambar) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issss", $_SESSION['user_id'], $nama, $kat, $desk, $gambar);
    mysqli_stmt_execute($stmt);
    header("Location: dashboard.php?pesan=tambah"); 
    exit;
}

// -------------------------------------------------------
// HAPUS DATA — sekaligus hapus file gambar dari server
// -------------------------------------------------------
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    $cek = mysqli_query($conn, "SELECT gambar FROM items WHERE id = $id");
    if ($cek && $row = mysqli_fetch_assoc($cek)) {
        hapusGambar($row['gambar']);
    }

    mysqli_query($conn, "DELETE FROM items WHERE id = $id");
    header("Location: dashboard.php?pesan=hapus"); 
    exit;
}

// -------------------------------------------------------
// UPDATE DATA
// -------------------------------------------------------
if (isset($_POST['update'])) {
    $id   = (int) $_POST['id_barang'];
    $nama = trim($_POST['nama_barang'] ?? '');
    $kat  = $_POST['kategori'] ?? 'Hilang';
    $desk = trim($_POST['deskripsi'] ?? '');
    $errorUpload = '';

    if (!empty($_FILES['gambar']['name'])) {
        if ($_FILES['gambar']['size'] > 2 * 1024 * 1024) {
            $errorUpload = "Ukuran gambar melebihi 2 MB.";
        } else {
            $gambarBaru = uploadGambar($_FILES['gambar']);
            if ($gambarBaru === null) {
                $errorUpload = "Format gambar tidak didukung. Gunakan JPG atau PNG.";
            }
        }

        if ($errorUpload) {
            header("Location: dashboard.php?edit=$id&pesan=error&detail=" . urlencode($errorUpload));
            exit;
        }

        // Hapus gambar lama, simpan gambar baru
        $ambilLama = mysqli_query($conn, "SELECT gambar FROM items WHERE id = $id");
        if ($ambilLama && $rowLama = mysqli_fetch_assoc($ambilLama)) {
            hapusGambar($rowLama['gambar']);
        }

        $stmt = mysqli_prepare($conn, "UPDATE items SET nama_barang=?, kategori=?, deskripsi=?, gambar=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssi", $nama, $kat, $desk, $gambarBaru, $id);
    } else {
        // Tidak ada gambar baru — update teks saja
        $stmt = mysqli_prepare($conn, "UPDATE items SET nama_barang=?, kategori=?, deskripsi=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssi", $nama, $kat, $desk, $id);
    }

    mysqli_stmt_execute($stmt);
    header("Location: dashboard.php?pesan=update"); 
    exit;
}

// -------------------------------------------------------
// BACA DATA UNTUK EDIT
// -------------------------------------------------------
$isEdit   = false;
$dataEdit = null;
if (isset($_GET['edit'])) {
    $id_edit   = (int) $_GET['edit'];
    $ambilData = mysqli_query($conn, "SELECT * FROM items WHERE id = $id_edit");
    if ($ambilData && mysqli_num_rows($ambilData) > 0) {
        $isEdit   = true;
        $dataEdit = mysqli_fetch_assoc($ambilData);
    }
}

// -------------------------------------------------------
// BACA PESAN DARI QUERY STRING (setelah redirect)
// -------------------------------------------------------
$pesanTipe = '';
$pesanTeks = '';
$kode      = $_GET['pesan'] ?? '';

if ($kode === 'tambah') {
    $pesanTipe = 'sukses';
    $pesanTeks = 'Laporan berhasil ditambahkan.';
} elseif ($kode === 'update') {
    $pesanTipe = 'sukses';
    $pesanTeks = 'Laporan berhasil diperbarui.';
} elseif ($kode === 'hapus') {
    $pesanTipe = 'sukses';
    $pesanTeks = 'Laporan berhasil dihapus.';
} elseif ($kode === 'error') {
    $pesanTipe = 'error';
    $pesanTeks = htmlspecialchars($_GET['detail'] ?? 'Terjadi kesalahan.', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - LostTrack</title>
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
                <a class="nav_btn" href="logout">
                    Logout (<?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>)
                </a>
            </div>
            <div class="hamburger"><span></span><span></span><span></span></div>
        </nav>
    </header>

    <div class="container">

        <?php if ($pesanTeks): ?>
            <div class="alert <?= $pesanTipe === 'sukses' ? 'badge-temu' : 'badge-hilang' ?>"
                 style="padding:15px; margin-bottom:20px; border-radius:8px;">
                <?= $pesanTipe === 'sukses' ? '✅' : '⚠️' ?>
                <?= $pesanTeks ?>
            </div>
        <?php endif; ?>

        <?php if ($isEdit && $dataEdit): ?>
            <h2 style="color:#f39c12;">Edit Data Laporan</h2>
            <form action="" method="POST" enctype="multipart/form-data"
                  style="background:rgba(0,0,0,0.2); padding:25px; border-radius:10px; border:1px dashed #f39c12;">
                <input type="hidden" name="id_barang" value="<?= (int) $dataEdit['id'] ?>">

                <label>Nama Barang</label>
                <input type="text" name="nama_barang"
                       value="<?= htmlspecialchars($dataEdit['nama_barang'], ENT_QUOTES, 'UTF-8') ?>" required>

                <label>Kategori</label>
                <select name="kategori">
                    <option value="Hilang"    <?= $dataEdit['kategori'] === 'Hilang'    ? 'selected' : '' ?>>Barang Hilang</option>
                    <option value="Ditemukan" <?= $dataEdit['kategori'] === 'Ditemukan' ? 'selected' : '' ?>>Barang Temuan</option>
                </select>

                <label>Deskripsi</label>
                <textarea name="deskripsi" rows="3" required><?= htmlspecialchars($dataEdit['deskripsi'], ENT_QUOTES, 'UTF-8') ?></textarea>

                <div class="form-group">
                    <label>UPLOAD FOTO BARU <small style="color:#aaa;">(opsional, maks 2 MB)</small></label>
                    <?php if (!empty($dataEdit['gambar'])): ?>
                        <p style="color:#aaa; font-size:0.85em; margin-bottom:8px;">
                            Foto saat ini:
                            <img src="uploads/<?= htmlspecialchars($dataEdit['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                                 style="max-height:60px; vertical-align:middle; border-radius:4px; margin-left:6px;" alt="">
                        </p>
                    <?php endif; ?>
                    <div class="upload-area">
                        <input type="file" id="imageUpload" name="gambar" accept="image/jpeg,image/png">
                        <div class="upload-text">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Klik atau pilih gambar baru</p>
                            <span>JPG, PNG — maks 2 MB</span>
                        </div>
                        <img id="previewImage" src="" alt="Preview" style="display:none; max-height:150px; border-radius:8px; margin-top:10px;">
                    </div>
                </div>

                <div style="display:flex; gap:10px; margin-top:10px;">
                    <button type="submit" name="update" class="btn" style="flex:1;">Update Data</button>
                    <a href="dashboard.php" class="btn btn-danger" style="flex:1; text-align:center;">Batal</a>
                </div>
            </form>

        <?php else: ?>
            <h2>Buat Laporan Baru</h2>
            <form action="" method="POST" enctype="multipart/form-data"
                  style="background:rgba(0,0,0,0.2); padding:25px; border-radius:10px;">
                <label>Nama Barang</label>
                <input type="text" name="nama_barang" placeholder="Masukkan nama barang..." required>

                <label>Kategori</label>
                <select name="kategori">
                    <option value="Hilang">Barang Hilang</option>
                    <option value="Ditemukan">Barang Temuan</option>
                </select>

                <label>Deskripsi Detail</label>
                <textarea name="deskripsi" rows="3"
                          placeholder="Lokasi, ciri-ciri, kontak yang bisa dihubungi..." required></textarea>

                <div class="form-group">
                    <label>UPLOAD FOTO <small style="color:#aaa;">(opsional, maks 2 MB)</small></label>
                    <div class="upload-area">
                        <input type="file" id="imageUpload" name="gambar" accept="image/jpeg,image/png">
                        <div class="upload-text">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Klik atau pilih gambar</p>
                            <span>JPG, PNG — maks 2 MB</span>
                        </div>
                        <img id="previewImage" src="" alt="Preview" style="display:none; max-height:150px; border-radius:8px; margin-top:10px;">
                    </div>
                </div>

                <button type="submit" name="tambah" class="btn">Simpan Laporan</button>
            </form>
        <?php endif; ?>

        <hr style="border:1px solid #2a3f5f; margin:40px 0;">

        <h2>Kelola Laporan</h2>
        <table>
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th>Status</th>
                    <th>Dilaporkan</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $q = mysqli_query($conn, "SELECT * FROM items ORDER BY id DESC");
            while ($row = mysqli_fetch_assoc($q)):
                $badge    = ($row['kategori'] === 'Hilang') ? 'badge-hilang' : 'badge-temu';
                $namaAman = htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8');
                $katAman  = htmlspecialchars($row['kategori'],    ENT_QUOTES, 'UTF-8');
                $tgl      = date('d M Y', strtotime($row['tanggal_lapor']));
                $idInt    = (int) $row['id'];
            ?>
                <tr>
                    <td style="font-weight:bold;"><?= $namaAman ?></td>
                    <td><span class="badge <?= $badge ?>"><?= $katAman ?></span></td>
                    <td><?= $tgl ?></td>
                    <td style="text-align:center;">
                        <a href="dashboard.php?edit=<?= $idInt ?>" class="btn"
                           style="padding:6px 12px; font-size:0.8em;">Edit</a>
                        <a href="dashboard.php?hapus=<?= $idInt ?>" class="btn btn-danger"
                           style="padding:6px 12px; font-size:0.8em; margin-left:5px;"
                           onclick="return konfirmasiHapus('<?= addslashes($namaAman) ?>')">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <footer class="footer-copyright">
            <p>&copy; 2026 <span class="brand-glow">LostTrack</span> System. All Rights Reserved.</p>
        </footer>

    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>