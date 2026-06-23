<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login/index.php");
    exit;
}

include "../db.php";

// Load settings
$settings_query = $conn->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settings_query->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Load email logs
$logs_query = $conn->query("
    SELECT el.*, u.nama_lengkap, u.nim, u.email 
    FROM email_logs el
    LEFT JOIN users u ON el.user_id = u.id
    ORDER BY el.waktu_kirim DESC LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Integrasi - Absensi UI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/fontawesome/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/sweetalert2/sweetalert2.css">
    <script src="../assets/sweetalert2/sweetalert2.js"></script>
    <script src="../assets/jquery/jquery-3.7.1.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="text-slate-800 antialiased">
    
    <?php include "../components/sidebar.php"; ?>

    <main class="md:ml-64 min-h-screen transition-all duration-300">
        <!-- Top Header -->
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-slate-100 shadow-sm px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-slate-500 hover:text-indigo-600 transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Pengaturan Sistem</h2>
                    <p class="text-xs text-slate-500 font-medium">Manajemen Integrasi Email & Penyimpanan Cloud</p>
                </div>
            </div>
        </header>

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Tabs -->
            <div class="flex space-x-1 bg-slate-100 p-1 rounded-xl w-fit">
                <button onclick="switchTab('config')" id="tab-config" class="px-6 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 bg-white shadow text-indigo-600">
                    <i class="fa-solid fa-sliders mr-2"></i> Konfigurasi
                </button>
                <button onclick="switchTab('logs')" id="tab-logs" class="px-6 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 text-slate-500 hover:text-slate-700">
                    <i class="fa-solid fa-clock-rotate-left mr-2"></i> Riwayat Pengiriman
                </button>
            </div>

            <!-- Tab 1: Configuration -->
            <div id="content-config" class="tab-content active">
                <form id="settingsForm" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- SMTP Config -->
                    <div class="glass-card rounded-2xl p-6">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center text-lg">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800">Kredensial SMTP</h3>
                                <p class="text-xs text-slate-500">Konfigurasi server pengirim email.</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">SMTP Host <span class="text-pink-500">*</span></label>
                                <input type="text" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 text-sm" placeholder="smtp.gmail.com">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Port <span class="text-pink-500">*</span></label>
                                    <input type="number" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Enkripsi</label>
                                    <select name="smtp_encryption" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 text-sm appearance-none">
                                        <option value="tls" <?= ($settings['smtp_encryption'] ?? '') == 'tls' ? 'selected' : '' ?>>TLS</option>
                                        <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Username / Email <span class="text-pink-500">*</span></label>
                                <input type="text" name="smtp_username" value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 text-sm" placeholder="email@gmail.com">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Password / App Password <span class="text-pink-500">*</span></label>
                                <input type="password" name="smtp_password" value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 text-sm" placeholder="••••••••••••••••">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">From Name <span class="text-pink-500">*</span></label>
                                    <input type="text" name="smtp_from_name" value="<?= htmlspecialchars($settings['smtp_from_name'] ?? 'Panitia Absensi') ?>" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">From Email <span class="text-pink-500">*</span></label>
                                    <input type="email" name="smtp_from_email" value="<?= htmlspecialchars($settings['smtp_from_email'] ?? '') ?>" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/50 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backblaze Config -->
                    <div class="glass-card rounded-2xl p-6">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                            <div class="w-10 h-10 rounded-lg bg-red-50 text-red-500 flex items-center justify-center text-lg">
                                <i class="fa-solid fa-cloud"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800">Kredensial Backblaze B2</h3>
                                <p class="text-xs text-slate-500">Konfigurasi penyimpanan cloud S3-Compatible.</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Key ID <span class="text-pink-500">*</span></label>
                                <input type="text" name="b2_key_id" value="<?= htmlspecialchars($settings['b2_key_id'] ?? '') ?>" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/50 text-sm" placeholder="Contoh: 003b8c...">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Application Key <span class="text-pink-500">*</span></label>
                                <input type="password" name="b2_application_key" value="<?= htmlspecialchars($settings['b2_application_key'] ?? '') ?>" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/50 text-sm" placeholder="Contoh: K003Yy...">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Bucket Name <span class="text-pink-500">*</span></label>
                                <input type="text" name="b2_bucket_name" value="<?= htmlspecialchars($settings['b2_bucket_name'] ?? '') ?>" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/50 text-sm" placeholder="Contoh: absensi-unaki">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Region / Endpoint S3 <span class="text-pink-500">*</span></label>
                                <input type="text" name="b2_region" value="<?= htmlspecialchars($settings['b2_region'] ?? '') ?>" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/50 text-sm" placeholder="Contoh: s3.us-west-004.backblazeb2.com">
                            </div>
                        </div>

                        <div class="mt-8 pt-4 border-t border-slate-100 flex justify-end">
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                                <i class="fa-solid fa-save"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tab 2: Logs -->
            <div id="content-logs" class="tab-content">
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h3 class="font-bold text-slate-800">Riwayat Pengiriman Email Terakhir</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                                    <th class="py-4 px-6 font-semibold w-16 text-center">Waktu</th>
                                    <th class="py-4 px-6 font-semibold">Tujuan</th>
                                    <th class="py-4 px-6 font-semibold">Tipe</th>
                                    <th class="py-4 px-6 font-semibold text-center">Status</th>
                                    <th class="py-4 px-6 font-semibold">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <?php if ($logs_query->num_rows > 0): ?>
                                    <?php while ($log = $logs_query->fetch_assoc()): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="py-3 px-6 text-xs text-slate-500 whitespace-nowrap">
                                                <?= date('d/m/Y H:i', strtotime($log['waktu_kirim'])) ?>
                                            </td>
                                            <td class="py-3 px-6">
                                                <div class="text-sm font-semibold text-slate-700"><?= htmlspecialchars($log['nama_lengkap'] ?: 'Anonim') ?></div>
                                                <div class="text-xs text-slate-500"><?= htmlspecialchars($log['email'] ?: '-') ?></div>
                                            </td>
                                            <td class="py-3 px-6">
                                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?= $log['jenis_email'] == 'QR Code' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700' ?>">
                                                    <?= htmlspecialchars($log['jenis_email']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-6 text-center">
                                                <?php if ($log['status'] == 'Berhasil'): ?>
                                                    <span class="text-emerald-500" title="Berhasil"><i class="fa-solid fa-check-circle text-lg"></i></span>
                                                <?php else: ?>
                                                    <span class="text-red-500" title="Gagal"><i class="fa-solid fa-circle-xmark text-lg"></i></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-6 text-sm text-slate-600">
                                                <?= htmlspecialchars($log['pesan_log']) ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="py-12 text-center">
                                            <div class="text-slate-400">
                                                <i class="fa-solid fa-clock-rotate-left text-3xl mb-2"></i>
                                                <p class="text-sm">Belum ada riwayat pengiriman email.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function switchTab(tabId) {
            $('.tab-content').removeClass('active');
            $('#content-' + tabId).addClass('active');
            
            // Reset buttons styling
            $('#tab-config, #tab-logs').removeClass('bg-white shadow text-indigo-600').addClass('text-slate-500 hover:text-slate-700');
            
            // Active button styling
            $('#tab-' + tabId).removeClass('text-slate-500 hover:text-slate-700').addClass('bg-white shadow text-indigo-600');
        }

        $(document).ready(function() {
            $('#settingsForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                let btn = form.find('button[type="submit"]');
                let originalText = btn.html();
                
                btn.html('<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);
                
                $.ajax({
                    url: 'save_settings.php',
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        btn.html(originalText).prop('disabled', false);
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                confirmButtonColor: '#6366f1'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: response.message,
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    },
                    error: function() {
                        btn.html(originalText).prop('disabled', false);
                        Swal.fire({
                            icon: 'error',
                            title: 'Kesalahan Sistem',
                            text: 'Terjadi kesalahan saat memproses permintaan.',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
