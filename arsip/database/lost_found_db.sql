CREATE DATABASE IF NOT EXISTS `lost_found_db`;
USE `lost_found_db`;

-- ============================================================
-- TABEL: users
-- Kolom role: 'admin' bisa CRUD semua, 'member' hanya buat laporan
-- ============================================================
CREATE TABLE `users` (
  `id`             int(11)      NOT NULL AUTO_INCREMENT,
  `username`       varchar(50)  NOT NULL UNIQUE,
  `email`          varchar(100) NOT NULL UNIQUE,
  `password`       varchar(255) NOT NULL,
  `role`           enum('admin','member') NOT NULL DEFAULT 'member',
  `tanggal_daftar` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Generate hash: php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
-- Ganti nilai password di bawah dengan hasil perintah di atas.
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
('admin', 'admin@losttrack.com', '$2y$10$7ipm2Sm/Ck2aNuwRtvG6RekvGOgV/g88C6SbKvrZoEU5MGSM.Ypca', 'admin');

-- ============================================================
-- TABEL: items
-- Kolom user_id: relasi ke users — siapa yang membuat laporan
-- ============================================================
CREATE TABLE `items` (
  `id`           int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`      int(11)      NOT NULL,
  `nama_barang`  varchar(150) NOT NULL,
  `kategori`     enum('Hilang','Ditemukan') NOT NULL,
  `deskripsi`    text         NOT NULL,
  `gambar`       varchar(255) DEFAULT NULL,
  `tanggal_lapor` timestamp   NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `items` (`user_id`, `nama_barang`, `kategori`, `deskripsi`, `tanggal_lapor`) VALUES
(1, 'Kunci Motor Honda', 'Ditemukan', 'Ditemukan di parkiran depan gedung Fakultas Teknik.', '2026-06-02 00:30:00'),
(1, 'Dompet Hitam Kulit', 'Hilang',    'Hilang di sekitar kantin, berisi KTP dan KTM.',        '2026-06-01 02:15:00');
