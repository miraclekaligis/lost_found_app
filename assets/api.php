<?php
// ============================================================
//  assets/api.php — Chatbot API Proxy untuk LostTrack
//  Menyimpan token API secara AMAN di server-side
//  Tidak ada token yang terekspos ke browser/client
// ============================================================

// --- Headers ---
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ============================================================
//  KONFIGURASI — Silakan ganti token di bawah ini
// ============================================================

// --- Pilih Provider ---
// 'groq'   → Groq (Gratis & cepat, model LLaMA/Mixtral)
// 'openai' → OpenAI (Berbayar, model GPT)
 $API_PROVIDER = 'zhipu';

// --- Token / API Key ---
// ⚠️ GANTI DENGAN TOKEN ASLI ANDA
 $ZHIPU_API_KEY   = '2043ad53fe604f21bb6bd06786ed437e.sUVIFZQLGHkuwptb';
 $OPENAI_API_KEY  = 'sk-GANTIDENGANTOKENANDAxxxxxxxxxxxxxxxxxxxxxxxx';

// --- Endpoint ---
 $ZHIPU_API_URL   = 'https://open.bigmodel.cn/api/paas/v4/chat/completions';
 $OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';

// --- Model ---
 $ZHIPU_MODEL   = 'glm-4';
 $OPENAI_MODEL = 'gpt-3.5-turbo';

// ============================================================
//  SYSTEM PROMPT — Kepribadian & Pengetahuan Bot
// ============================================================

 $SYSTEM_PROMPT = <<<'PROMPT'
Kamu adalah **LostTrack Assistant**, asisten virtual resmi untuk sistem "Lost & Found Integrated System" (LostTrack).

## Identitas
- Nama: LostTrack Assistant
- Sistem: LostTrack — Lost & Found Integrated System
- Dibuat oleh: Tim Ibren, Miracle, Heavenly, dan Anggreini

## Aturan Utama
1. HANYA menjawab pertanyaan yang berkaitan dengan LostTrack / Lost & Found
2. Jika pertanyaan di luar topik, tolak dengan sopan dan arahkan kembali ke topik LostTrack
3. Jawab dalam Bahasa Indonesia yang ramah, sopan, dan mudah dipahami
4. Gunakan emoji secukupnya agar percakapan terasa hidup
5. Jangan pernah mengklaim sebagai manusia — kamu adalah asisten AI
6. JANGAN pernah memberikan informasi palsu. Jika tidak tahu, katakan jujur

## Pengetahuan tentang LostTrack

### Cara Melapor
1. Login terlebih dahulu ke akun
2. Di Beranda, isi form "Buat Laporan Baru"
3. Pilih kategori: Barang Hilang atau Barang Temuan
4. Isi nama barang & deskripsi sedetail mungkin
5. Upload foto pendukung (opsional, maks 2 MB, format JPG/PNG)
6. Klik "Kirim Laporan Sistem"
7. Laporan langsung muncul di Beranda dan ditinjau Admin

### Kategori Laporan
- 🔴 Barang Hilang — Kehilangan sesuatu, jelaskan ciri-ciri dan lokasi terakhir
- 🟢 Barang Ditemukan — Menemukan barang orang lain, jelaskan di mana dan kapan

### Cara Mencari Barang
- Scroll di Beranda untuk melihat seluruh laporan
- Perhatikan badge: Merah = Hilang, Hijau = Ditemukan
- Setiap laporan punya foto, kategori, deskripsi, dan waktu lapor

### Upload Foto
- Format: JPG atau PNG saja
- Ukuran maksimal: 2 MB
- Foto bersifat opsional tapi SANGAT disarankan
- Tips: foto jelas, pencahayaan baik, fokus pada barang

### Tips Deskripsi yang Baik
- Sebutkan ciri-ciri unik (warna, merek, ukuran, stiker)
- Tuliskan lokasi terakhir terlihat/ditemukan
- Cantumkan waktu perkiraan kehilangan/penemuan
- Jika ada nomor seri atau identitas khusus, tuliskan
- Tambahkan kontak yang bisa dihubungi

### Akun & Login
- Harus login untuk melapor
- Belum punya akun? Klik "Login" lalu "Daftar Akun Baru"
- Isi username, email, password, lalu klik "Daftar"

### Lupa Password
- Fitur reset mandiri belum tersedia
- Hubungi Admin melalui halaman Panduan
- Admin akan membantu memulihkan akses akun

### Profil
- Melihat data akun (username, email)
- Mengubah informasi profil
- Melihat riwayat laporan yang pernah dibuat
- Akses via menu "Profil" di navbar

### Dashboard Admin (khusus Admin)
- Melihat seluruh laporan masuk
- Menyetujui/menolak laporan
- Menghapus laporan tidak valid
- Melihat statistik sistem
- Menu "Dashboard" muncul otomatis jika login sebagai Admin

### Edit/Hapus Laporan
- Saat ini dilakukan melalui Admin
- Hubungi Admin, sertakan detail laporan yang ingin diubah/dihapus
- Fitur edit mandiri sedang dalam pengembangan

### Proses Laporan
- Laporan yang dikirim langsung muncul di Beranda
- Admin meninjau dan menindaklanjuti
- Semakin lengkap deskripsi & foto, semakin cepat pencocokan

### Keamanan Data
- Password disimpan dalam bentuk hash terenkripsi
- Data pribadi tidak ditampilkan secara publik
- Hanya deskripsi barang yang terlihat di beranda
- Upload file dibatasi format & ukuran
- Admin melakukan moderasi laporan

### Menghubungi Admin
- Gunakan chatbot ini untuk pertanyaan umum
- Halaman Panduan berisi informasi kontak
- Lapor melalui sistem, Admin akan melihat dan menindaklanjuti
- Admin aktif memantau laporan setiap hari

### Navigasi Website
- Beranda: halaman utama, lihat & buat laporan
- Profil: kelola akun dan riwayat laporan
- Panduan: petunjuk penggunaan sistem
- Dashboard: khusus Admin
- Login/Logout: akses akun

## Format Jawaban
- Gunakan formatting yang rapi dengan bold, bullet points
- Berikan jawaban yang terstruktur dan mudah dibaca
- Jika relevan, berikan tips tambahan
- Akhiri dengan pertanyaan atau penawaran bantuan lanjutan
PROMPT;

// ============================================================
//  RATE LIMITING Sederhana (berbasis session)
// ============================================================

session_start();

if (!isset($_SESSION['chat_rate'])) {
    $_SESSION['chat_rate'] = ['count' => 0, 'window' => time()];
}

// Reset counter setiap 60 detik
if (time() - $_SESSION['chat_rate']['window'] > 60) {
    $_SESSION['chat_rate'] = ['count' => 0, 'window' => time()];
}

// Maks 20 pesan per menit
if ($_SESSION['chat_rate']['count'] >= 20) {
    http_response_code(429);
    echo json_encode([
        'reply' => '⚠️ Anda mengirim pesan terlalu cepat. Silakan tunggu beberapa saat sebelum mencoba lagi.',
        'source' => 'rate_limit'
    ]);
    exit;
}

 $_SESSION['chat_rate']['count']++;

// ============================================================
//  AMBIL & VALIDASI INPUT
// ============================================================

 $rawInput = file_get_contents('php://input');
 $data     = json_decode($rawInput, true);

 $message = trim($data['message'] ?? '');

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Pesan tidak boleh kosong']);
    exit;
}

if (mb_strlen($message) > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Pesan terlalu panjang (maks 1000 karakter)']);
    exit;
}

// ============================================================
//  RIWAYAT PERCAKAPAN (disimpan di session)
// ============================================================

if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Simpan pesan user ke history
 $_SESSION['chat_history'][] = [
    'role'    => 'user',
    'content' => $message
];

// Batasi history agar tidak melebihi token limit (simpan 10 pesan terakhir)
if (count($_SESSION['chat_history']) > 10) {
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -10);
}

// ============================================================
//  CEK APAKAH TOKEN SUDAH DIKONFIGURASI
// ============================================================

 $isTokenConfigured = false;
 $apiKey   = '';
 $apiUrl   = '';
 $model    = '';

if ($API_PROVIDER === 'zhipu') {
    $apiKey = $ZHIPU_API_KEY;
    $apiUrl = $ZHIPU_API_URL;
    $model  = $ZHIPU_MODEL;
    $isTokenConfigured = (strpos($apiKey, 'GANTIDENGANTOKENANDA') === false && strlen($apiKey) > 10);
} elseif ($API_PROVIDER === 'openai') {
    $apiKey = $OPENAI_API_KEY;
    $apiUrl = $OPENAI_API_URL;
    $model  = $OPENAI_MODEL;
    $isTokenConfigured = (strpos($apiKey, 'GANTIDENGANTOKENANDA') === false && strlen($apiKey) > 10);
}

// ============================================================
//  JIKA TOKEN BELUM DIKONFIGURASI → GUNAKAN FALLBACK LOKAL
// ============================================================

if (!$isTokenConfigured) {
    $fallbackReply = getLocalResponse($message);

    $_SESSION['chat_history'][] = [
        'role'    => 'assistant',
        'content' => $fallbackReply
    ];

    echo json_encode([
        'reply'   => $fallbackReply,
        'source'  => 'fallback_local'
    ]);
    exit;
}

// ============================================================
//  PANGGIL AI API (Groq / OpenAI)
// ============================================================

// Susun messages untuk API
 $apiMessages = [
    ['role' => 'system', 'content' => $SYSTEM_PROMPT]
];

foreach ($_SESSION['chat_history'] as $msg) {
    $apiMessages[] = [
        'role'    => $msg['role'],
        'content' => $msg['content']
    ];
}

 $payload = [
    'model'       => $model,
    'messages'    => $apiMessages,
    'temperature' => 0.7,
    'max_tokens'  => 800,
    'top_p'       => 0.9
];

 $ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ],
    CURLOPT_SSL_VERIFYPEER => true
]);

 $response = curl_exec($ch);
 $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 $curlErr  = curl_error($ch);
curl_close($ch);

// ============================================================
//  PROSES RESPON API
// ============================================================

if ($curlErr) {
    // cURL error (timeout, koneksi gagal, dll)
    $fallbackReply = getLocalResponse($message);
    $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $fallbackReply];

    echo json_encode([
        'reply'   => $fallbackReply,
        'source'  => 'fallback_curl_error'
    ]);
    exit;
}

if ($httpCode === 200) {
    $result = json_decode($response, true);
    $aiReply = $result['choices'][0]['message']['content'] ?? '';

    if (!empty($aiReply)) {
        // Simpan ke history
        $_SESSION['chat_history'][] = [
            'role'    => 'assistant',
            'content' => $aiReply
        ];

        echo json_encode([
            'reply'  => $aiReply,
            'source' => 'ai_' . $API_PROVIDER
        ]);
        exit;
    }
}

// Jika API gagal → fallback ke respons lokal
 $fallbackReply = getLocalResponse($message);
 $_SESSION['chat_history'][] = ['role' => 'assistant', 'content' => $fallbackReply];

echo json_encode([
    'reply'   => $fallbackReply,
    'source'  => 'fallback_api_error'
]);
exit;

// ============================================================
//  FALLBACK: RESPONS LOKAL (Keyword Matching)
//  Digunakan saat token belum dikonfigurasi ATAU API gagal
// ============================================================

function getLocalResponse($input) {
    $lower = strtolower($input);
    $best  = null;
    $score = 0;

    $kb = getKnowledgeBase();

    foreach ($kb as $entry) {
        $s = 0;
        foreach ($entry['kw'] as $kw) {
            if (strpos($lower, $kw) !== false) {
                $s += strlen($kw);
            }
        }
        if ($s > $score) {
            $score = $s;
            $best  = $entry;
        }
    }

    return $best ? $best['reply'] : getKnowledgeBase()['default']['reply'];
}

function getKnowledgeBase() {
    return [
        [
            'kw' => ['halo','hai','hi','hello','hey','selamat','pagi','siang','sore','malam'],
            'reply' => "Halo! 👋 Saya LostTrack Assistant, siap membantu Anda terkait sistem Lost & Found. Silakan tanyakan apa saja!"
        ],
        [
            'kw' => ['lapor','melapor','buat laporan','report','posting','post','kirim laporan'],
            'reply' => "📝 <b>Cara Melapor di LostTrack:</b>\n\n1. Login terlebih dahulu ke akun Anda\n2. Di Beranda, isi form \"Buat Laporan Baru\"\n3. Pilih kategori: Barang Hilang atau Barang Temuan\n4. Isi nama barang & deskripsi sedetail mungkin\n5. Upload foto pendukung (opsional, maks 2 MB)\n6. Klik \"Kirim Laporan Sistem\"\n\nLaporan Anda akan langsung muncul di beranda dan ditinjau oleh Admin."
        ],
        [
            'kw' => ['cari','mencari','search','pencarian','temukan','menemukan','cek barang','cek laporan'],
            'reply' => "🔍 <b>Cara Mencari Barang:</b>\n\nSaat ini Anda bisa langsung scroll di Beranda untuk melihat seluruh laporan. Perhatikan badge:\n\n🔴 Merah = Barang Hilang\n🟢 Hijau = Barang Ditemukan\n\nSetiap laporan dilengkapi foto, kategori, deskripsi, dan waktu lapor."
        ],
        [
            'kw' => ['foto','gambar','upload','format foto','jpg','png','ukuran','size','format'],
            'reply' => "🖼️ <b>Ketentuan Upload Foto:</b>\n\n• Format: JPG atau PNG\n• Ukuran maksimal: 2 MB\n• Foto bersifat opsional tapi sangat disarankan\n\n💡 Tips: Pastikan foto jelas, pencahayaan baik, dan fokus pada barang."
        ],
        [
            'kw' => ['akun','daftar','register','signup','belum punya','belum login','login','masuk'],
            'reply' => "🔑 <b>Tentang Akun LostTrack:</b>\n\nUntuk melapor, Anda harus login terlebih dahulu.\n\n1. Klik \"Login\" di navbar\n2. Pilih \"Daftar Akun Baru\"\n3. Isi username, email, dan password\n4. Klik \"Daftar\"\n5. Langsung login dan mulai melapor!"
        ],
        [
            'kw' => ['fitur','panduan','menu','apa saja','bisa apa','fasilitas','fitur losttrack'],
            'reply' => "📚 <b>Fitur LostTrack:</b>\n\n🔹 Buat Laporan — Laporkan barang hilang atau ditemukan\n🔹 Beranda — Lihat seluruh laporan terbaru\n🔹 Profil — Kelola data akun Anda\n🔹 Panduan — Petunjuk penggunaan sistem\n🔹 Dashboard Admin — Kelola laporan (khusus Admin)\n🔹 Chatbot — Asisten virtual 24/7 🤖"
        ],
        [
            'kw' => ['profil','akun saya','data diri','edit profil','ubah profil'],
            'reply' => "👤 <b>Halaman Profil:</b>\n\n• Melihat data akun (username, email)\n• Mengubah informasi profil\n• Melihat riwayat laporan yang pernah dibuat\n\nKlik \"Profil\" di navbar untuk mengaksesnya."
        ],
        [
            'kw' => ['admin','dashboard','kelola','moderasi','manage','tinjau'],
            'reply' => "👨‍💼 <b>Dashboard Admin:</b>\n\nHanya bisa diakses oleh akun Admin. Di sana bisa:\n\n• Melihat seluruh laporan masuk\n• Menyetujui/menolak laporan\n• Menghapus laporan yang tidak valid\n• Melihat statistik sistem\n\nMenu \"Dashboard\" muncul otomatis jika login sebagai Admin."
        ],
        [
            'kw' => ['proses','lama','waktu','berapa lama','durasi','ditinjau','review','pending'],
            'reply' => "⏰ <b>Proses Laporan:</b>\n\nLaporan yang sudah dikirim langsung muncul di Beranda. Admin akan meninjau dan menindaklanjuti.\n\n💡 Tips: Semakin lengkap deskripsi dan foto, semakin cepat proses pencocokan barang!"
        ],
        [
            'kw' => ['deskripsi','tips deskripsi','cara isi deskripsi','detail','ciri-ciri'],
            'reply' => "✍️ <b>Tips Mengisi Deskripsi:</b>\n\n• Sebutkan ciri-ciri unik (warna, merek, ukuran, stiker)\n• Tuliskan lokasi terakhir terlihat/ditemukan\n• Cantumkan waktu perkiraan kehilangan/penemuan\n• Jika ada nomor seri, tuliskan\n• Tambahkan kontak yang bisa dihubungi"
        ],
        [
            'kw' => ['aman','keamanan','privasi','data','password','proteksi','secure'],
            'reply' => "🛡️ <b>Keamanan Data:</b>\n\n• Password disimpan dalam bentuk hash terenkripsi\n• Data pribadi tidak ditampilkan secara publik\n• Hanya deskripsi barang yang terlihat di beranda\n• Upload file dibatasi format & ukuran\n• Admin melakukan moderasi terhadap laporan"
        ],
        [
            'kw' => ['lupa password','forgot','reset password','ubah password','ganti password'],
            'reply' => "🔑 <b>Jika Lupa Password:</b>\n\nFitur reset password belum tersedia secara mandiri. Silakan hubungi Admin melalui halaman Panduan, atau buat akun baru sementara."
        ],
        [
            'kw' => ['kategori','hilang','ditemukan','temuan','jenis laporan','tipe'],
            'reply' => "🏷️ <b>Kategori Laporan:</b>\n\n🔴 Barang Hilang — Kehilangan sesuatu, jelaskan ciri-ciri dan lokasi terakhir.\n🟢 Barang Ditemukan — Menemukan barang orang lain, jelaskan di mana dan kapan.\n\nPilih kategori yang tepat agar proses pencocokan lebih efektif!"
        ],
        [
            'kw' => ['hapus','edit laporan','ubah laporan','koreksi','update laporan'],
            'reply' => "✏️ <b>Mengubah/Menghapus Laporan:</b>\n\nSaat ini dilakukan melalui Admin. Hubungi Admin, sertakan detail laporan yang ingin diubah/dihapus. Fitur edit mandiri sedang dalam pengembangan."
        ],
        [
            'kw' => ['kontak','hubungi','bantuan','help','contact'],
            'reply' => "📞 <b>Menghubungi Admin:</b>\n\n1. Gunakan chatbot ini untuk pertanyaan umum\n2. Halaman Panduan berisi informasi kontak\n3. Lapor melalui sistem, Admin akan menindaklanjuti\n\nAdmin aktif memantau laporan setiap hari."
        ],
        [
            'kw' => ['terima kasih','thanks','makasih','thx','thank'],
            'reply' => "Sama-sama! 😊 Senang bisa membantu. Jika ada pertanyaan lain, jangan ragu untuk bertanya ya. Semoga barang Anda segera ditemukan! 🍀"
        ],
        'default' => [
            'reply' => "🤔 Maaf, saya belum bisa memahami pertanyaan tersebut. Coba tanyakan tentang:\n\n• 📝 Cara melapor barang hilang/temuan\n• 🔍 Cara mencari barang\n• 📚 Fitur-fitur LostTrack\n• 🔑 Akun dan login\n• 👨‍💼 Dashboard Admin"
        ]
    ];
}