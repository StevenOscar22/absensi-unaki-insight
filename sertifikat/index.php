<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login/index.php");
    exit;
}

$tahunKehadiran = date('Y');
$bulanKehadiran = date('m');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kehadiran - Absensi Mahasiswa Baru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" src="../assets/sweetalert2/sweetalert2.css">
    </link>
    <script src="../assets/sweetalert2/sweetalert2.js"></script>
    <link rel="stylesheet" href="../assets/fontawesome/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
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
                    <h2 class="text-xl font-bold text-slate-800">Data Kehadiran</h2>
                    <p class="text-xs text-slate-500 font-medium">Log Absensi Harian Mahasiswa</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="sertifikat.php" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white text-sm font-semibold rounded-xl shadow-md shadow-amber-200 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                    <i class="fa-solid fa-award"></i> Kelayakan Sertifikat
                </a>
            </div>
        </header>

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Filter & Search Section -->
            <div class="glass-card rounded-2xl p-6">
                <form id="specificSearchingForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5 items-end">

                    <div class="lg:col-span-1">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">NIM</label>
                        <input type="text" id="nim_peserta" placeholder="Cari NIM..."
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all">
                    </div>

                    <div class="lg:col-span-1">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama</label>
                        <input type="text" id="nama_peserta" placeholder="Cari Nama..."
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all">
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Waktu Kehadiran</label>
                        <div class="flex gap-2">
                            <!-- Tanggal -->
                            <select id="sel-date" class="w-1/3 px-3 py-2.5 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all">
                                <option value="" disabled selected hidden>Tanggal</option>
                            </select>
                            <!-- Bulan -->
                            <select id="sel-month" onchange="onMonthChange(this.value)" class="w-1/3 px-3 py-2.5 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all">
                                <option value="" disabled selected hidden>Bulan</option>
                            </select>
                            <!-- Tahun -->
                            <select id="sel-year" onchange="onYearChange(this.value)" class="w-1/3 px-3 py-2.5 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all">
                                <option value="" disabled selected hidden>Tahun</option>
                            </select>
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Sesi</label>
                        <select id="sesi_kehadiran" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all">
                            <option value="pagi">Pagi</option>
                            <option value="sore">Sore</option>
                        </select>
                    </div>

                </form>
            </div>

            <!-- Table Card -->
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white/50">
                    <div>
                        <h3 class="font-bold text-slate-800">Log Kehadiran Mahasiswa</h3>
                    </div>
                    <a id="cetakExcel" href="cetak_excel.php" class="px-4 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-100 text-sm font-semibold rounded-xl transition-all shadow-sm flex items-center gap-2">
                        <i class="fa-solid fa-file-excel"></i> Export Excel
                    </a>
                </div>

                <div class="overflow-x-auto" id="tableData">
                    <!-- Table dimuat via AJAX -->
                    <?php include "ajaxSearching.php"; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/jquery/jquery-3.7.1.min.js"></script>
    <script>
        const MONTHS = [
            "Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember"
        ];

        const selYear = document.getElementById('sel-year');
        const selMonth = document.getElementById('sel-month');
        const selDay = document.getElementById('sel-date');

        selMonth.disabled = true;
        selDay.disabled = true;

        (function populateYears() {
            const currentYear = new Date().getFullYear();
            for (let y = currentYear; y >= 2020; y--) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                selYear.appendChild(opt);
            }
        })();

        function onYearChange(year) {
            resetSelect(selMonth, 'Bulan');
            resetSelect(selDay, 'Tanggal');
            selMonth.disabled = true;
            selDay.disabled = true;

            if (!year || year === "") return;

            selMonth.disabled = false;
            MONTHS.forEach((name, i) => {
                const opt = document.createElement('option');
                const val = (i + 1).toString().padStart(2, '0');
                opt.value = val;
                opt.textContent = name;
                selMonth.appendChild(opt);
            });
            // trigger change event to reload table based on year
            $("#specificSearchingForm").trigger('change');
        }

        function onMonthChange(month) {
            resetSelect(selDay, 'Tanggal');
            selDay.disabled = true;

            if (!month || month === "") return;

            const year = parseInt(selYear.value);
            const daysInMonth = new Date(year, parseInt(month), 0).getDate();

            selDay.disabled = false;
            const optAll = document.createElement('option');
            optAll.value = "";
            optAll.textContent = "Semua Tanggal";
            selDay.appendChild(optAll);

            for (let d = 1; d <= daysInMonth; d++) {
                const opt = document.createElement('option');
                opt.value = d < 10 ? '0' + d : d;
                opt.textContent = d;
                selDay.appendChild(opt);
            }

            // Trigger
            $("#specificSearchingForm").trigger('change');
        }

        function resetSelect(sel, placeholder) {
            sel.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = placeholder;
            // opt.disabled = true;
            // opt.selected = true;
            sel.appendChild(opt);
        }

        let currentPage = 1;
        let isLoading = false;
        let hasMoreData = true;

        function loadData() {
            currentPage = 1;
            hasMoreData = true;
            isLoading = true;

            let nim = $("#nim_peserta").val();
            let nama_peserta = $("#nama_peserta").val();
            let tanggalKehadiran = $("#sel-date").val();
            let bulanKehadiran = $("#sel-month").val();
            let tahunKehadiran = $("#sel-year").val();
            let sesi = $("#sesi_kehadiran").val();

            let urlParams = {
                sesi: sesi,
                nim: nim,
                nama_peserta: nama_peserta,
                tanggal_kehadiran: tanggalKehadiran,
                bulan_kehadiran: bulanKehadiran,
                tahun_kehadiran: tahunKehadiran,
                page: currentPage
            };

            // Skeleton Loader
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
                </div>
            `);

            $.get('ajaxSearching.php', urlParams, function(data) {
                $("#tableData").html(data);

                if (!data.includes("Tidak ada data kehadiran ditemukan")) {
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
                        <p class="text-sm text-slate-500">Terjadi kesalahan pada server.</p>
                    </div>
                `);
                isLoading = false;
            });

            // Update Excel Link
            let paramString = $.param(urlParams).replace(/&page=1/, '');
            $("#cetakExcel").attr("href", `cetak_excel.php?${paramString}`);
        }

        function loadMore() {
            if (isLoading || !hasMoreData) return;

            isLoading = true;
            currentPage++;

            let nim = $("#nim_peserta").val();
            let nama_peserta = $("#nama_peserta").val();
            let tanggalKehadiran = $("#sel-date").val();
            let bulanKehadiran = $("#sel-month").val();
            let tahunKehadiran = $("#sel-year").val();
            let sesi = $("#sesi_kehadiran").val();

            let urlParams = {
                sesi: sesi,
                nim: nim,
                nama_peserta: nama_peserta,
                tanggal_kehadiran: tanggalKehadiran,
                bulan_kehadiran: bulanKehadiran,
                tahun_kehadiran: tahunKehadiran,
                page: currentPage
            };

            $("#scrollLoader").removeClass("hidden").addClass("flex");

            $.get('ajaxSearching.php', urlParams, function(data) {
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

        $(window).scroll(function() {
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 150) {
                loadMore();
            }
        });

        $(document).ready(function() {
            let sesi_kehadiran = "pagi";
            let tahunKehadiran = "<?= $tahunKehadiran ?>";
            let bulanKehadiran = "<?= $bulanKehadiran ?>";

            $("#sesi_kehadiran").val(sesi_kehadiran);
            $("#sel-year").val(tahunKehadiran);
            onYearChange(tahunKehadiran);
            $("#sel-month").val(bulanKehadiran);
            onMonthChange(bulanKehadiran);

            let timeout = null;
            $("#specificSearchingForm").on("input change", function(e) {
                // Prevent infinite loop if triggered by onYearChange programmatically during setup
                // Wait, we don't want to loadData immediately on typing every char without debounce
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    loadData();
                }, 400);
            });

            // Initial Load
            loadData();
        });
    </script>
</body>

</html>