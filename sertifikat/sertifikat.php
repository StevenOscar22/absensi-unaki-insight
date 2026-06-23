<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login/index.php");
    exit;
}

include "../db.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelayakan Sertifikat - Absensi Mahasiswa Baru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/fontawesome/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/sweetalert2/sweetalert2.css">
    <script src="../assets/sweetalert2/sweetalert2.js"></script>
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
                    <h2 class="text-xl font-bold text-slate-800">Kelayakan Sertifikat</h2>
                    <p class="text-xs text-slate-500 font-medium">Filter Mahasiswa Berdasarkan Kehadiran Wajib</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="index.php" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-sm font-semibold rounded-xl transition-all flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Data
                </a>
            </div>
        </header>

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Filter Section -->
            <div class="glass-card rounded-2xl p-6">
                <div class="mb-4">
                    <h3 class="font-bold text-slate-800 text-lg">Tentukan Tanggal Wajib Hadir</h3>
                    <p class="text-sm text-slate-500">Mahasiswa harus hadir pada <b>semua</b> tanggal yang Anda tambahkan di bawah ini agar layak mendapatkan sertifikat.</p>
                </div>

                <form id="sertifikatForm" class="space-y-4">
                    <div id="datesContainer" class="flex flex-wrap gap-3">
                        <div class="flex items-center gap-2 date-group">
                            <input type="date" name="dates[]" required class="px-4 py-2 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                            <button type="button" onclick="removeDate(this)" class="w-9 h-9 rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center opacity-50 cursor-not-allowed" disabled>
                                <i class="fa-solid fa-minus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                        <button type="button" onclick="addDate()" class="px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border border-indigo-100 text-sm font-semibold rounded-xl transition-all flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i> Tambah Tanggal
                        </button>
                        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white text-sm font-semibold rounded-xl shadow-md shadow-amber-200 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                            <i class="fa-solid fa-search"></i> Cek Kelayakan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Results Container -->
            <div id="resultContainer"></div>
        </div>
    </main>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-[60]">
        <div class="bg-white p-6 rounded-2xl shadow-2xl flex flex-col items-center gap-4">
            <div class="w-12 h-12 border-4 border-slate-100 border-t-indigo-600 rounded-full animate-spin"></div>
            <span class="text-slate-700 font-medium" id="loadingText">Sedang memproses...</span>
        </div>
    </div>

    <script src="../assets/jquery/jquery-3.7.1.min.js"></script>
    <script>
        function addDate() {
            const container = document.getElementById('datesContainer');
            const newGroup = document.createElement('div');
            newGroup.className = 'flex items-center gap-2 date-group';
            newGroup.innerHTML = `
                <input type="date" name="dates[]" required class="px-4 py-2 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                <button type="button" onclick="removeDate(this)" class="w-9 h-9 rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center">
                    <i class="fa-solid fa-minus"></i>
                </button>
            `;
            container.appendChild(newGroup);
            updateRemoveButtons();
        }

        function showLoading(msg = "Sedang memproses...") {
            document.getElementById("loadingText").innerText = msg;
            document.getElementById("loadingOverlay").classList.remove("hidden");
            document.getElementById("loadingOverlay").classList.add("flex");
        }

        function hideLoading() {
            document.getElementById("loadingOverlay").classList.add("hidden");
            document.getElementById("loadingOverlay").classList.remove("flex");
        }

        function removeDate(btn) {
            const groups = document.querySelectorAll('.date-group');
            if (groups.length > 1) {
                btn.closest('.date-group').remove();
                updateRemoveButtons();
            }
        }

        function updateRemoveButtons() {
            const groups = document.querySelectorAll('.date-group');
            const btns = document.querySelectorAll('.date-group button');
            if (groups.length === 1) {
                btns[0].disabled = true;
                btns[0].classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                btns.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                });
            }
        }

        let currentPage = 1;
        let isLoading = false;
        let hasMoreData = true;

        $(document).ready(function() {
            $("#sertifikatForm").on("submit", function(e) {
                e.preventDefault();
                currentPage = 1;
                hasMoreData = true;
                isLoading = true;

                let formData = $(this).serializeArray();
                formData.push({name: "page", value: currentPage});

                // Skeleton Loader
                $("#resultContainer").html(`
                    <div class="glass-card rounded-2xl overflow-hidden animate-fade-in">
                        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-white/50">
                            <div class="skeleton h-6 w-48"></div>
                            <div class="skeleton h-6 w-24 rounded-full"></div>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="skeleton h-10 w-full"></div>
                            <div class="skeleton h-12 w-full"></div>
                            <div class="skeleton h-12 w-full"></div>
                            <div class="skeleton h-12 w-full"></div>
                        </div>
                    </div>
                `);

                $.post("sertifikat_ajax.php", $.param(formData), function(data) {
                    $("#resultContainer").html(data);

                    if (!data.includes("Tidak ada mahasiswa yang memenuhi syarat kehadiran") && !data.includes("Harap pilih setidaknya satu tanggal wajib hadir")) {
                        $("#resultContainer").append(`
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
                    $("#resultContainer").html(`
                        <div class="glass-card rounded-2xl p-6 text-center text-red-500">
                            <i class="fa-solid fa-triangle-exclamation text-4xl mb-3"></i>
                            <p class="text-sm font-medium">Terjadi kesalahan saat memuat data.</p>
                        </div>
                    `);
                    isLoading = false;
                });
            });
        });

        function loadMore() {
            if (isLoading || !hasMoreData || $("#resultContainer").is(':empty')) return;
            
            isLoading = true;
            currentPage++;

            let formData = $("#sertifikatForm").serializeArray();
            formData.push({name: "page", value: currentPage});

            $("#scrollLoader").removeClass("hidden").addClass("flex");

            $.post("sertifikat_ajax.php", $.param(formData), function(data) {
                $("#scrollLoader").removeClass("flex").addClass("hidden");
                
                if (data.trim() === "") {
                    hasMoreData = false;
                    $("#scrollLoader").after(`
                        <div class="py-4 text-center text-xs font-semibold text-slate-400 bg-slate-50 border-t border-slate-100">
                            -- Semua data telah ditampilkan --
                        </div>
                    `);
                } else {
                    $("#certTableBody").append(data);
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

        function sendCertificateEmail(id) {
            Swal.fire({
                title: 'Kirim Email Sertifikat?',
                text: "Sistem akan mengecek sertifikat di Backblaze dan mengirimkannya ke email mahasiswa.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Kirim!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading("Memeriksa B2 dan mengirim email...");
                    $.post('send_certificate.php', { id: id }, function(response) {
                        hideLoading();
                        if (response.status === 'success') {
                            Swal.fire('Berhasil', response.message, 'success');
                        } else {
                            Swal.fire('Gagal', response.message, 'error');
                        }
                    }, 'json').fail(function() {
                        hideLoading();
                        Swal.fire('Error', 'Kesalahan sistem saat mengirim email', 'error');
                    });
                }
            });
        }
    </script>
</body>
</html>
