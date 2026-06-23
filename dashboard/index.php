<?php
session_start();

// Cek autentikasi admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login/index.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');

// Ambil input dropdown (jika ada) untuk initial values
$search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
$search_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$search_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$search_day = isset($_GET['day']) ? $_GET['day'] : date('d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Absensi Mahasiswa Baru</title>
    <!-- Gunakan Tailwind CLI build jika ada, atau fallback CDN untuk konsistensi -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/fontawesome/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        
        /* Custom scrollbar */
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
        
        .skeleton {
            background: #e2e8f0;
            background: linear-gradient(110deg, #ececec 8%, #f5f5f5 18%, #ececec 33%);
            border-radius: 5px;
            background-size: 200% 100%;
            animation: 1.5s shine linear infinite;
        }
        @keyframes shine {
            to { background-position-x: -200%; }
        }
    </style>
</head>

<body class="text-slate-800 antialiased">
    
    <!-- Include Sidebar -->
    <?php include "../components/sidebar.php"; ?>

    <!-- Main Content -->
    <main class="md:ml-64 min-h-screen transition-all duration-300">
        
        <!-- Top Header -->
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-slate-100 shadow-sm px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-slate-500 hover:text-indigo-600 transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Dashboard Kehadiran</h2>
                    <p class="text-xs text-slate-500 font-medium"><?= date('d F Y') ?></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden sm:block text-right">
                    <p class="text-sm font-semibold text-slate-800"><?= isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'Admin' ?></p>
                    <p class="text-xs text-indigo-600 font-medium">Administrator</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold shadow-md">
                    <?= substr(isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'A', 0, 1) ?>
                </div>
            </div>
        </header>

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            
            <!-- Filter Section -->
            <div class="glass-card rounded-2xl p-6">
                <form id="filterForm" class="flex flex-col lg:flex-row gap-5 items-end" onsubmit="event.preventDefault(); loadData();">
                    
                    <div class="w-full lg:w-1/3">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Cari Mahasiswa</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-search text-slate-400"></i>
                            </div>
                            <input type="text" id="search_name" name="search_name" value="<?= htmlspecialchars($search_name) ?>" placeholder="Nama..."
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all">
                        </div>
                    </div>

                    <div class="w-full lg:w-1/2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Pilih Tanggal/Bulan</label>
                        <div class="flex gap-2">
                            <!-- Tanggal -->
                            <select id="sel-day" name="day" class="w-1/3 py-2.5 px-3 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all">
                                <option value="">Tanggal (Semua)</option>
                                <?php for($i=1; $i<=31; $i++): $val = sprintf("%02d", $i); ?>
                                    <option value="<?= $val ?>" <?= $search_day == $val ? 'selected' : '' ?>><?= $val ?></option>
                                <?php endfor; ?>
                            </select>
                            
                            <!-- Bulan -->
                            <select id="sel-month" name="month" class="w-1/3 py-2.5 px-3 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all">
                                <option value="">Bulan (Semua)</option>
                                <?php 
                                $months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                                foreach($months as $idx => $m): 
                                    $val = sprintf("%02d", $idx + 1);
                                ?>
                                    <option value="<?= $val ?>" <?= $search_month == $val ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Tahun -->
                            <select id="sel-year" name="year" class="w-1/3 py-2.5 px-3 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all">
                                <?php 
                                $curY = date('Y');
                                for($y = $curY; $y >= 2020; $y--): 
                                ?>
                                    <option value="<?= $y ?>" <?= $search_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="w-full lg:w-auto flex gap-3">
                        <button type="submit" class="flex-1 lg:flex-none px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-indigo-200 transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-filter"></i> Filter
                        </button>
                        <button type="button" onclick="resetForm()" class="flex-1 lg:flex-none px-6 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-rotate-right"></i> Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info Panel -->
            <div id="infoPanel" class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-4 shadow-sm hidden">
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0 text-amber-600">
                    <i class="fa-solid fa-circle-info text-lg"></i>
                </div>
                <div>
                    <h3 class="text-amber-800 font-semibold mb-1">Mode Rekap Bulanan</h3>
                    <p class="text-sm text-amber-700">Saat ini menampilkan total kehadiran per bulan/tahun. Untuk melakukan <b>Absen Manual</b>, Anda harus memilih Tanggal yang spesifik pada filter di atas.</p>
                </div>
            </div>

            <!-- Table Card -->
            <div id="tableData" class="glass-card rounded-2xl overflow-hidden min-h-[400px]">
                <!-- Skeleton Loader -->
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-white/50">
                    <div class="skeleton h-6 w-48"></div>
                    <div class="skeleton h-6 w-24 rounded-full"></div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="skeleton h-10 w-full"></div>
                    <div class="skeleton h-12 w-full"></div>
                    <div class="skeleton h-12 w-full"></div>
                    <div class="skeleton h-12 w-full"></div>
                    <div class="skeleton h-12 w-full"></div>
                </div>
            </div>
            
        </div>
    </main>

    <!-- Modal Konfirmasi Absen Manual -->
    <div id="confirmModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity">
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl transform transition-transform scale-95" id="modalCard">
            <div class="flex flex-col items-center text-center mb-6">
                <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center mb-4">
                    <i class="fa-solid fa-clipboard-check text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Konfirmasi Absen</h3>
                <p class="text-sm text-slate-500 mt-1">Anda akan melakukan absensi manual untuk:</p>
            </div>
            
            <div class="bg-slate-50 rounded-xl p-4 mb-6 text-sm text-left">
                <p class="flex justify-between mb-2"><span class="text-slate-500">Nama:</span> <strong id="m_nama" class="text-slate-800"></strong></p>
                <p class="flex justify-between mb-2"><span class="text-slate-500">Sesi:</span> <strong id="m_sesi" class="text-slate-800 uppercase"></strong></p>
                <p class="flex justify-between"><span class="text-slate-500">Tanggal:</span> <strong id="m_tgl" class="text-slate-800"></strong></p>
            </div>
            
            <div class="flex gap-3">
                <button onclick="closeModal()" class="flex-1 px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 transition-colors font-medium">Batal</button>
                <button onclick="prosesAbsen()" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors font-medium shadow-md shadow-indigo-200">Ya, Absen</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-[60]">
        <div class="bg-white p-5 rounded-2xl shadow-2xl flex items-center gap-4">
            <i class="fa-solid fa-spinner fa-spin text-2xl text-indigo-600"></i>
            <span class="text-slate-700 font-medium">Memproses...</span>
        </div>
    </div>

    <script src="../assets/jquery/jquery-3.7.1.min.js"></script>
    <script>
        let currentAbsenData = null;
        let currentPage = 1;
        let isLoading = false;
        let hasMoreData = true;

        function loadData() {
            currentPage = 1;
            hasMoreData = true;
            isLoading = true;
            
            let search_name = $("#search_name").val();
            let day = $("#sel-day").val();
            let month = $("#sel-month").val();
            let year = $("#sel-year").val();

            // Tampilkan atau sembunyikan info panel berdasarkan spesifikasi tanggal
            if (day === "") {
                $("#infoPanel").removeClass("hidden").addClass("flex");
            } else {
                $("#infoPanel").addClass("hidden").removeClass("flex");
            }

            // Tampilkan skeleton loader
            $("#tableData").html(`
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-white/50">
                    <div class="skeleton h-6 w-48"></div>
                    <div class="skeleton h-6 w-24 rounded-full"></div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="skeleton h-10 w-full"></div>
                    <div class="skeleton h-12 w-full"></div>
                    <div class="skeleton h-12 w-full"></div>
                    <div class="skeleton h-12 w-full"></div>
                    <div class="skeleton h-12 w-full"></div>
                    <div class="skeleton h-12 w-full"></div>
                    <div class="skeleton h-12 w-full"></div>
                </div>
            `);

            // Fetch data via AJAX
            $.get(`ajaxSearching.php`, { search_name, day, month, year, page: currentPage }, function(data) {
                $("#tableData").html(data);
                
                if (!data.includes("Tidak ada data yang ditemukan")) {
                    $("#tableData").append(`
                        <div id="scrollLoader" class="hidden py-6 flex-col items-center justify-center gap-3 border-t border-slate-100 bg-slate-50/50">
                            <div class="flex gap-2">
                                <div class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 0s"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 0.2s"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 0.4s"></div>
                            </div>
                            <span class="text-xs font-semibold text-slate-500">Memuat data selanjutnya...</span>
                        </div>
                    `);
                } else {
                    hasMoreData = false;
                }
                
                isLoading = false;
            }).fail(function() {
                $("#tableData").html(`
                    <div class="p-12 text-center">
                        <i class="fa-solid fa-triangle-exclamation text-4xl text-red-400 mb-3"></i>
                        <h3 class="text-lg font-bold text-slate-800">Gagal Memuat Data</h3>
                        <p class="text-sm text-slate-500">Terjadi kesalahan pada server saat memuat tabel absensi.</p>
                    </div>
                `);
                isLoading = false;
            });
        }

        function loadMore() {
            if (isLoading || !hasMoreData) return;
            
            isLoading = true;
            currentPage++;
            
            let search_name = $("#search_name").val();
            let day = $("#sel-day").val();
            let month = $("#sel-month").val();
            let year = $("#sel-year").val();

            $("#scrollLoader").removeClass("hidden").addClass("flex");

            $.get(`ajaxSearching.php`, { search_name, day, month, year, page: currentPage }, function(data) {
                $("#scrollLoader").removeClass("flex").addClass("hidden");
                
                if (data.trim() === "") {
                    hasMoreData = false;
                    $("#scrollLoader").after(`
                        <div class="py-4 text-center text-xs font-semibold text-slate-400 bg-slate-50 border-t border-slate-100">
                            -- Semua data telah ditampilkan --
                        </div>
                    `);
                } else {
                    $("#tableBody").append(data);
                }
                isLoading = false;
            }).fail(function() {
                $("#scrollLoader").removeClass("flex").addClass("hidden");
                isLoading = false;
            });
        }

        // Window scroll listener for infinite scrolling
        $(window).scroll(function() {
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 150) {
                loadMore();
            }
        });

        function resetForm() {
            $("#search_name").val("");
            $("#sel-day").val("");
            $("#sel-month").val("");
            // Leave year as current year usually
            loadData();
        }

        // Live search delay
        let timeout = null;
        $("#search_name").on("input", function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                loadData();
            }, 500);
        });

        $("#sel-day, #sel-month, #sel-year").on("change", function() {
            loadData();
        });

        // Load data initially
        $(document).ready(function() {
            loadData();
        });

        function absenManual(userId, sesi, nama) {
            currentAbsenData = { userId, sesi, nama };
            
            document.getElementById('m_nama').innerText = nama;
            document.getElementById('m_sesi').innerText = sesi;
            
            let day = $("#sel-day").val();
            let month = $("#sel-month").val();
            let year = $("#sel-year").val();
            let tglStr = year + "-" + month + "-" + day;
            // Format to basic string for display
            document.getElementById('m_tgl').innerText = tglStr;
            
            const modal = document.getElementById('confirmModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => document.getElementById('modalCard').classList.remove('scale-95'), 10);
        }

        function closeModal() {
            document.getElementById('modalCard').classList.add('scale-95');
            setTimeout(() => {
                document.getElementById('confirmModal').classList.add('hidden');
                document.getElementById('confirmModal').classList.remove('flex');
                currentAbsenData = null;
            }, 150);
        }

        function prosesAbsen() {
            if (!currentAbsenData) return;
            
            closeModal();
            document.getElementById('loadingOverlay').classList.remove('hidden');
            document.getElementById('loadingOverlay').classList.add('flex');

            const { userId, sesi } = currentAbsenData;
            
            let day = $("#sel-day").val();
            let month = $("#sel-month").val();
            let year = $("#sel-year").val();
            const tanggal = year + "-" + month + "-" + day;

            fetch(`absen_process.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, sesi: sesi, tanggal: tanggal })
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                loadData(); // reload the table with AJAX instead of page reload
            })
            .catch(err => {
                alert('Terjadi kesalahan sistem.');
                document.getElementById('loadingOverlay').classList.add('hidden');
            });
        }
    </script>
</body>
</html>