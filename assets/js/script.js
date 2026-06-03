document.addEventListener('DOMContentLoaded', () => {
    // Efek fade out untuk alert setelah 3 detik
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });
});

function konfirmasiHapus(namaBarang) {
    return confirm(`⚠️ PERINGATAN!\n\nApakah Anda yakin ingin menghapus data: "${namaBarang}"?\nTindakan ini tidak bisa dibatalkan.`);
}