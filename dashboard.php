<?php
require 'koneksi.php';

// PROTEKSI GERBANG UTAMA: Cek Login & Level Admin
if (!sudahLogin() || !isAdmin()) {
    header("Location: index");
    exit();
}

if (function_exists('requireAdmin')) {
    requireAdmin();
}

if (isset($_POST['tambah'])) {
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
            if ($gambar === null) $errorUpload = "Format gambar tidak didukung.";
        }
    }

    if ($errorUpload) {
        header("Location: dashboard?pesan=error&detail=" . urlencode($errorUpload));
        exit;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO items (user_id, nama_barang, kategori, deskripsi, gambar) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issss", $_SESSION['user_id'], $nama, $kat, $desk, $gambar);
    if (mysqli_stmt_execute($stmt)) {
        header("Location: dashboard?pesan=sukses_tambah");
    }
    exit;
}

if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    $stmt_img = mysqli_prepare($conn, "SELECT gambar FROM items WHERE id = ?");
    mysqli_stmt_bind_param($stmt_img, "i", $id_hapus);
    mysqli_stmt_execute($stmt_img);
    $res_img = mysqli_stmt_get_result($stmt_img);
    if ($row_img = mysqli_fetch_assoc($res_img)) {
        if (!empty($row_img['gambar']) && file_exists("uploads/" . $row_img['gambar'])) {
            unlink("uploads/" . $row_img['gambar']);
        }
    }
    $stmt_del = mysqli_prepare($conn, "DELETE FROM items WHERE id = ?");
    mysqli_stmt_bind_param($stmt_del, "i", $id_hapus);
    if (mysqli_stmt_execute($stmt_del)) {
        header("Location: dashboard?pesan=sukses_hapus");
    }
    exit;
}

$edit_mode = false;
$item_edit = [];

if (isset($_GET['edit'])) {
    $id_edit = intval($_GET['edit']);
    $stmt_sel = mysqli_prepare($conn, "SELECT * FROM items WHERE id = ?");
    mysqli_stmt_bind_param($stmt_sel, "i", $id_edit);
    mysqli_stmt_execute($stmt_sel);
    $res_sel = mysqli_stmt_get_result($stmt_sel);
    if ($row_edit = mysqli_fetch_assoc($res_sel)) {
        $edit_mode = true;
        $item_edit = $row_edit;
    }
}

if (isset($_POST['update'])) {
    $id_update   = intval($_POST['id_barang']);
    $nama        = trim($_POST['nama_barang'] ?? '');
    $kat         = $_POST['kategori'] ?? 'Hilang';
    $desk        = trim($_POST['deskripsi'] ?? '');
    $gambar_lama = $_POST['gambar_lama'] ?? null;
    $gambar_baru = $gambar_lama;

    if (!empty($_FILES['gambar']['name'])) {
        $uploaded = uploadGambar($_FILES['gambar']);
        if ($uploaded !== null) {
            $gambar_baru = $uploaded;
            if (!empty($gambar_lama) && file_exists("uploads/" . $gambar_lama)) {
                unlink("uploads/" . $gambar_lama);
            }
        }
    }

    $stmt_up = mysqli_prepare($conn, "UPDATE items SET nama_barang = ?, kategori = ?, deskripsi = ?, gambar = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt_up, "ssssi", $nama, $kat, $desk, $gambar_baru, $id_update);
    if (mysqli_stmt_execute($stmt_up)) {
        header("Location: dashboard?pesan=sukses_update");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - TemuBarang</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo">TemuBarang <span style="font-size: 0.5em; color: var(--danger);">[ADMIN]</span></div>
            <div class="nav-menu">
                <a href="/">Beranda</a>
                <a href="profil">Profil</a>
                <a href="panduan">Panduan</a>
                <a href="logout" style="color: var(--danger);">Logout</a>
            </div>
            <div class="hamburger"><span></span><span></span><span></span></div>
        </nav>
    </header>

    <div class="main-wrapper">
        <div class="container">
            <h2>🛡️ Dashboard Administrator</h2>
            
            <div class="report-card-container" style="padding: 30px; border-radius: 16px; margin-bottom: 40px;">
                <h3 style="border-bottom: none; margin-bottom: 20px; padding-bottom: 0;">
                    <?= $edit_mode ? '✏️ Edit Laporan Sistem' : '➕ Tambah Laporan Baru' ?>
                </h3>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="id_barang" value="<?= $item_edit['id'] ?>">
                        <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($item_edit['gambar'] ?? '') ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Nama Barang</label>
                        <input type="text" name="nama_barang" value="<?= $edit_mode ? htmlspecialchars($item_edit['nama_barang']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Status Kategori</label>
                        <select name="kategori">
                            <option value="Hilang" <?= $edit_mode && $item_edit['kategori'] == 'Hilang' ? 'selected' : '' ?>>Barang Hilang</option>
                            <option value="Ditemukan" <?= $edit_mode && $item_edit['kategori'] == 'Ditemukan' ? 'selected' : '' ?>>Barang Ditemukan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi & Detail Informasi</label>
                        <textarea name="deskripsi" rows="4" required><?= $edit_mode ? htmlspecialchars($item_edit['deskripsi']) : '' ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Foto Barang (Maks 2MB, JPG/PNG)</label>
                        <input type="file" name="gambar" style="background: rgba(5, 11, 22, 0.4);">
                    </div>
                    <div style="margin-top: 15px; display: flex; gap: 15px; align-items: center;">
                        <button type="submit" name="<?= $edit_mode ? 'update' : 'tambah' ?>" class="btn">
                            <?= $edit_mode ? 'Simpan Perubahan' : 'Publish Laporan' ?>
                        </button>
                        <?php if ($edit_mode): ?>
                            <a href="dashboard" style="color: var(--text-muted); text-decoration: none; font-size: 0.9em; font-weight: 600;">Batal Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <h2>Kelola Laporan Sistem</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th>Status</th>
                            <th>Pelapor</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $q = mysqli_query($conn, "SELECT items.*, users.username FROM items LEFT JOIN users ON items.user_id = users.id ORDER BY items.id DESC");
                    while ($row = mysqli_fetch_assoc($q)):
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><span class="badge <?= $row['kategori'] == 'Hilang' ? 'badge-hilang' : 'badge-temu' ?>"><?= htmlspecialchars($row['kategori'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars($row['username'] ?? 'Anonim', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a href="dashboard?edit=<?= $row['id'] ?>" class="btn-action-edit" style="padding: 6px 12px; font-size: 0.75rem;">Edit</a>
                                <a href="dashboard?hapus=<?= $row['id'] ?>" class="btn-action-delete" style="padding: 6px 12px; font-size: 0.75rem;" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
</body>
</html>