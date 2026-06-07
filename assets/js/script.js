document.addEventListener('DOMContentLoaded', () => {
    
    // ==========================================
    // 1. Efek Fade Out untuk Alert setelah 3 detik
    // ==========================================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });

    // ==========================================
    // 2. Fitur Responsif Hamburger Menu
    // ==========================================
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');

    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }

    // ==========================================
    // 3. Fitur Preview Gambar Sebelum Di-upload
    // ==========================================
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

    // ============================================================
    // 4. CHATBOT ENGINE — AI Powered via assets/api.php
    // ============================================================
    (() => {
        const API_URL    = 'assets/api.php';
        const toggleBtn  = document.getElementById('chatbotToggle');
        const chatWindow = document.getElementById('chatbotWindow');
        const chatBody   = document.getElementById('chatbotBody');
        const chatInput  = document.getElementById('chatInput');
        const chatSend   = document.getElementById('chatSendBtn');
        const chatClear  = document.getElementById('chatClearBtn');

        // Jika element chatbot tidak ada di halaman ini, lewati fungsi ini saja
        if (!toggleBtn || !chatWindow || !chatBody || !chatInput || !chatSend || !chatClear) return;

        let isOpen = false;
        let isBusy = false;

        // --- Toggle buka/tutup ---
        toggleBtn.addEventListener('click', function() {
            isOpen = !isOpen;
            chatWindow.classList.toggle('open', isOpen);
            toggleBtn.classList.toggle('active', isOpen);
            if (isOpen) chatInput.focus();
        });

        // --- Bersihkan chat ---
        chatClear.addEventListener('click', function() {
            chatBody.innerHTML = '';
            fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'clear_history' })
            }).catch(function(){});
        });

        // --- Waktu sekarang ---
        function now() {
            return new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        }

        // --- Format teks AI ---
        function formatText(text) {
            var out = text.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
            out = out.replace(/\n/g, '<br>');
            return out;
        }

        // --- Render pesan ---
        function addMessage(text, sender) {
            var wrapper = document.createElement('div');
            wrapper.classList.add('chat-msg', sender);
            wrapper.innerHTML = formatText(text) + '<span class="msg-time">' + now() + '</span>';
            chatBody.appendChild(wrapper);
            scrollToBottom();
        }

        // --- Typing indicator ---
        function showTyping() {
            var el = document.createElement('div');
            el.classList.add('typing-indicator');
            el.id = 'typingIndicator';
            el.innerHTML = '<span></span><span></span><span></span>';
            chatBody.appendChild(el);
            scrollToBottom();
        }

        // --- Hide Typing ---
        function hideTyping() {
            var el = document.getElementById('typingIndicator');
            if (el) el.remove();
        }

        // --- Error banner ---
        function showError(msg) {
            var el = document.createElement('div');
            el.classList.add('chat-error-banner');
            el.textContent = msg;
            chatBody.appendChild(el);
            scrollToBottom();
            setTimeout(function() { el.remove(); }, 5000);
        }

        function scrollToBottom() {
            requestAnimationFrame(function() {
                chatBody.scrollTop = chatBody.scrollHeight;
            });
        }

        // --- Set busy state ---
        function setBusy(busy) {
            isBusy = busy;
            chatInput.disabled = busy;
            chatSend.disabled  = busy;
            if (!busy) chatInput.focus();
        }

        // --- Handle pesan user ---
        function handleUserMessage(text) {
            if (!text.trim() || isBusy) return;

            addMessage(text, 'user');
            chatInput.value = '';

            setBusy(true);
            showTyping();

            fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            })
            .then(function(res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function(data) {
                hideTyping();
                if (data.reply) {
                    addMessage(data.reply, 'bot');
                } else if (data.error) {
                    addMessage('⚠️ Terjadi kesalahan: ' + data.error, 'bot');
                }
            })
            .catch(function() {
                hideTyping();
                showError('Koneksi gagal. Coba lagi nanti.');
            })
            .finally(function() {
                setBusy(false);
            });
        }

        // --- Event: kirim pesan ---
        chatSend.addEventListener('click', function() {
            handleUserMessage(chatInput.value);
        });
        chatInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleUserMessage(chatInput.value);
            }
        });
    })();
});

// ==========================================
// 5. Konfirmasi Hapus Data Dinamis
// ==========================================
function konfirmasiHapus(namaBarang) {
    return confirm(`⚠️ PERINGATAN!\n\nApakah Anda yakin ingin menghapus data: ${namaBarang}?\nTindakan ini tidak bisa dibatalkan.`);
}