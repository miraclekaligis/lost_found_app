lost_found_app/
│
├── assets/                  --> Folder untuk menyimpan file pendukung (statis)
│   ├── css/
│   │   └── style.css        --> File CSS murni untuk mengatur seluruh tampilan web
│   ├── js/
│   │   └── script.js        --> File JavaScript murni untuk validasi form/interaksi
│   └── images/              --> Folder untuk menyimpan foto (misal: foto profil anggota kelompok)
│
├── database/                --> Folder khusus untuk menyimpan rancangan database
│   └── lost_found_db.sql    --> File backup/export database MySQL (penting untuk deployment nanti)
│
├── index.php                --> (Halaman Dinamis) Halaman utama, menampilkan daftar barang
├── profil_tim.php           --> (Halaman Statis) Menampilkan informasi Ibren, Miracle, & Heavenly
├── panduan.php              --> (Halaman Statis) Konten bebas terkait prosedur barang hilang
├── login.php                --> (Halaman Dinamis) Form untuk admin masuk ke sistem
├── dashboard.php            --> (Halaman Dinamis) Panel untuk Tambah, Edit, Hapus (CRUD) laporan
├── koneksi.php              --> (Skrip Backend) Skrip PHP untuk menghubungkan web ke database MySQL
└── logout.php               --> (Skrip Backend) Skrip PHP untuk mengakhiri sesi (session) login admin