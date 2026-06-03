<?php
require 'koneksi.php';
if (!isset($_SESSION['login'])) { header("Location: login.php"); exit; }

// --- PROSES TAMBAH DATA ---
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kat = $_POST['kategori'];
    $desk = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    mysqli_query($conn, "INSERT INTO items (nama_barang, kategori, deskripsi) VALUES ('$nama', '$kat', '$desk')");
    header("Location: dashboard.php"); exit;
}

// --- PROSES HAPUS DATA ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM items WHERE id = $id");
    header("Location: dashboard.php"); exit;
}

// --- PROSES UPDATE DATA ---
if (isset($_POST['update'])) {
    $id = $_POST['id_barang'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kat = $_POST['kategori'];
    $desk = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    mysqli_query($conn, "UPDATE items SET nama_barang='$nama', kategori='$kat', deskripsi='$desk' WHERE id=$id");
    header("Location: dashboard.php"); exit;
}

// Mengecek apakah tombol EDIT ditekan
$isEdit = false;
if (isset($_GET['edit'])) {
    $isEdit = true;
    $id_edit = $_GET['edit'];
    $ambilData = mysqli_query($conn, "SELECT * FROM items WHERE id = $id_edit");
    $dataEdit = mysqli_fetch_assoc($ambilData);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Panel Kendali Admin</h1>
        <nav>
            <a href="index.php">Lihat Web Publik</a>
            <a href="logout.php" class="btn-danger" style="padding: 10px 20px;">Logout</a>
        </nav>
    </header>

    <div class="container">
        <?php if ($isEdit): ?>
            <h2 style="color: #f39c12;">✏️ Edit Data Laporan</h2>
            <form action="" method="POST" style="background: rgba(0,0,0,0.2); padding: 25px; border-radius: 10px; border: 1px dashed #f39c12;">
                <input type="hidden" name="id_barang" value="<?= $dataEdit['id'] ?>">
                
                <label>Nama Barang</label>
                <input type="text" name="nama_barang" value="<?= $dataEdit['nama_barang'] ?>" required>
                
                <label>Kategori</label>
                <select name="kategori">
                    <option value="Hilang" <?= ($dataEdit['kategori'] == 'Hilang') ? 'selected' : '' ?>>Barang Hilang</option>
                    <option value="Ditemukan" <?= ($dataEdit['kategori'] == 'Ditemukan') ? 'selected' : '' ?>>Barang Temuan</option>
                </select>
                
                <label>Deskripsi</label>
                <textarea name="deskripsi" rows="3" required><?= $dataEdit['deskripsi'] ?></textarea>
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" name="update" class="btn" style="flex: 1;">Update Data</button>
                    <a href="dashboard.php" class="btn btn-danger" style="flex: 1; text-align: center;">Batal Edit</a>
                </div>
            </form>
        <?php else: ?>
            <h2>➕ Tambah Laporan Baru</h2>
            <form action="" method="POST" style="background: rgba(0,0,0,0.2); padding: 25px; border-radius: 10px;">
                <label>Nama Barang</label>
                <input type="text" name="nama_barang" placeholder="Masukkan nama barang..." required>
                
                <label>Kategori</label>
                <select name="kategori">
                    <option value="Hilang">Barang Hilang</option>
                    <option value="Ditemukan">Barang Temuan</option>
                </select>
                
                <label>Deskripsi Detail</label>
                <textarea name="deskripsi" rows="3" placeholder="Lokasi, ciri-ciri, kontak yang bisa dihubungi..." required></textarea>
                
                <button type="submit" name="tambah" class="btn">Simpan Laporan</button>
            </form>
        <?php endif; ?>

        <hr style="border: 1px solid #2a3f5f; margin: 40px 0;">

        <h2>⚙️ Kelola Database Laporan</h2>
        <table>
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th>Status</th>
                    <th style="text-align: center;">Aksi (Edit / Hapus)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $q = mysqli_query($conn, "SELECT * FROM items ORDER BY id DESC");
                while ($row = mysqli_fetch_assoc($q)) {
                    $badge = ($row['kategori'] == 'Hilang') ? 'badge-hilang' : 'badge-temu';
                    echo "<tr>
                            <td style='font-weight:bold;'>{$row['nama_barang']}</td>
                            <td><span class='badge {$badge}'>{$row['kategori']}</span></td>
                            <td style='text-align: center;'>
                                <a href='dashboard.php?edit={$row['id']}' class='btn' style='padding: 6px 12px; font-size: 0.8em;'>Edit</a>
                                <a href='dashboard.php?hapus={$row['id']}' class='btn btn-danger' style='padding: 6px 12px; font-size: 0.8em; margin-left: 5px;' onclick='return konfirmasiHapus(\"{$row['nama_barang']}\")'>Hapus</a>
                            </td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>