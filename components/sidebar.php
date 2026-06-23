<?php
// Tentukan URL saat ini untuk active state
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!-- Sidebar -->
<aside class="fixed inset-y-0 left-0 bg-white w-64 shadow-xl z-50 transform transition-transform duration-300 ease-in-out md:translate-x-0 -translate-x-full" id="sidebar">
    <div class="flex items-center justify-center h-20 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-200">
                <i class="fa-solid fa-qrcode text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600">Absensi UI</h1>
        </div>
    </div>
    
    <div class="px-4 py-6 space-y-2">
        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Menu Utama</p>
        
        <a href="../dashboard/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= ($current_dir == 'dashboard') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' ?>">
            <i class="fa-solid fa-chart-pie w-5 text-center <?= ($current_dir == 'dashboard') ? 'text-indigo-600' : 'text-gray-400' ?>"></i>
            <span>Dashboard</span>
        </a>

        <a href="../qr/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= ($current_dir == 'qr') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' ?>">
            <i class="fa-solid fa-id-badge w-5 text-center <?= ($current_dir == 'qr') ? 'text-indigo-600' : 'text-gray-400' ?>"></i>
            <span>Kelola QR Code</span>
        </a>

        <a href="../sertifikat/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= ($current_dir == 'sertifikat' && $current_page == 'index.php') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' ?>">
            <i class="fa-solid fa-users w-5 text-center <?= ($current_dir == 'sertifikat' && $current_page == 'index.php') ? 'text-indigo-600' : 'text-gray-400' ?>"></i>
            <span>Data Kehadiran</span>
        </a>

        <a href="../sertifikat/sertifikat.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= ($current_dir == 'sertifikat' && $current_page == 'sertifikat.php') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' ?>">
            <i class="fa-solid fa-award w-5 text-center <?= ($current_dir == 'sertifikat' && $current_page == 'sertifikat.php') ? 'text-indigo-600' : 'text-gray-400' ?>"></i>
            <span>Sertifikat</span>
        </a>

        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-6 mb-2">Sistem</p>

        <a href="../settings/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= ($current_dir == 'settings') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600' ?>">
            <i class="fa-solid fa-sliders w-5 text-center <?= ($current_dir == 'settings') ? 'text-indigo-600' : 'text-gray-400' ?>"></i>
            <span>Pengaturan Integrasi</span>
        </a>
    </div>

    <div class="absolute bottom-0 w-full p-4 border-t border-gray-100">
        <a href="../auth/logout/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-50 transition-all duration-200">
            <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>
            <span class="font-medium">Keluar</span>
        </a>
    </div>
</aside>

<!-- Overlay for mobile -->
<div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden md:hidden" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }
</script>
