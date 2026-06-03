<?php require 'koneksi.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lost & Found - Beranda</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>Sistem Barang Hilang & Temuan</h1>
        <nav>
            <a href="index.php" style="border-color:#4da8da; color:#fff;">Beranda</a>
            <a href="profil_tim.php">Profil Tim</a>
            <a href="panduan.php">Panduan</a>
            <a href="login.php">Login Admin</a>
        </nav>
    </header>
    <div class="container">
        <h2>Daftar Laporan Terbaru</h2>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Barang</th>
                    <th>Status</th>
                    <th>Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = mysqli_query($conn, "SELECT * FROM items ORDER BY id DESC");
                while ($row = mysqli_fetch_assoc($query)) {
                    $badgeClass = ($row['kategori'] == 'Hilang') ? 'badge-hilang' : 'badge-temu';
                    // Format tanggal agar lebih rapi
                    $tanggal = date('d M Y, H:i', strtotime($row['tanggal_lapor']));
                    
                    echo "<tr>
                            <td style='color:#a0b2c6; font-size:0.9em;'>{$tanggal}</td>
                            <td style='font-weight:bold;'>{$row['nama_barang']}</td>
                            <td><span class='badge {$badgeClass}'>{$row['kategori']}</span></td>
                            <td style='color:#a0b2c6;'>{$row['deskripsi']}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>