document.addEventListener('DOMContentLoaded', () => {
    // 1. Efek Fade Out untuk Alert setelah 3 detik
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });

    // 2. Fitur Responsif Hamburger Menu
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');

    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }

    // 3. Fitur Preview Gambar Sebelum Di-upload (Solusi Gagal Upload)
    const imageUpload = document.getElementById('imageUpload');
    const previewImage = document.getElementById('previewImage');
    const uploadText = document.querySelector('.upload-text');

    if (imageUpload && previewImage) {
        imageUpload.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.addEventListener('load', function() {
                    previewImage.setAttribute('src', this.result);
                    previewImage.style.display = 'block';
                    if (uploadText) uploadText.style.display = 'none';
                });
                reader.readAsDataURL(file);
            }
        });
    }
});

// 4. Konfirmasi Hapus Data Dinamis (Sintaksis Sudah Diperbaiki)
function konfirmasiHapus(namaBarang) {
    return confirm(`⚠️ PERINGATAN!\n\nApakah Anda yakin ingin menghapus data: ${namaBarang}?\nTindakan ini tidak bisa dibatalkan.`);
}