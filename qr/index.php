<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login/index.php");
    exit;
}

include "../db.php";

$sql = "SELECT * FROM users ORDER BY registered_at DESC";
$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola QR Code - Absensi Mahasiswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/sweetalert2/sweetalert2.css">
    <link rel="stylesheet" href="../assets/fontawesome/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                    <h2 class="text-xl font-bold text-slate-800">Kelola QR Code</h2>
                    <p class="text-xs text-slate-500 font-medium">Data Peserta & Generate QR</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="openAddModal()" class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white text-sm font-semibold rounded-xl shadow-md shadow-emerald-200 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Tambah Mahasiswa
                </button>
            </div>
        </header>

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Filter & Search Section -->
            <div class="glass-card rounded-2xl p-6">
                <form id="specificSearchingForm" class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">NIM</label>
                        <div class="relative">
                            <input type="text" id="nim_peserta" placeholder="Cari NIM..."
                                class="w-full pl-4 pr-10 py-2.5 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all">
                            <i class="fa-solid fa-search absolute right-4 top-3.5 text-slate-400 text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Peserta</label>
                        <div class="relative">
                            <input type="text" id="nama_peserta" placeholder="Cari Nama..."
                                class="w-full pl-4 pr-10 py-2.5 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all">
                            <i class="fa-solid fa-search absolute right-4 top-3.5 text-slate-400 text-sm"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Program Studi</label>
                        <div class="relative">
                            <input type="text" id="program_studi" placeholder="Cari Program Studi..."
                                class="w-full pl-4 pr-10 py-2.5 bg-slate-50 border border-slate-200 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all">
                            <i class="fa-solid fa-search absolute right-4 top-3.5 text-slate-400 text-sm"></i>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div id="tableDataContainer" class="glass-card rounded-2xl overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white/50">
                    <div>
                        <h3 class="font-bold text-slate-800">Daftar Mahasiswa</h3>
                        <p class="text-xs text-slate-500">Klik Generate QR pada tiap baris atau semua sekaligus.</p>
                    </div>
                    <button onclick="GenerateAllQR()" class="px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white border border-indigo-100 text-sm font-semibold rounded-xl transition-all shadow-sm flex items-center gap-2">
                        <i class="fa-solid fa-qrcode"></i> Generate All QR
                    </button>
                </div>
                
                <div class="overflow-x-auto" id="tableData">
                    <!-- Tabel default akan dimuat oleh JS -->
                </div>
            </div>
        </div>
    </main>

    <!-- Hidden div for QR Code generation -->
    <div id="qrcodes" style="display:none"></div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-[60]">
        <div class="bg-white p-6 rounded-2xl shadow-2xl flex flex-col items-center gap-4">
            <div class="w-12 h-12 border-4 border-slate-100 border-t-indigo-600 rounded-full animate-spin"></div>
            <span class="text-slate-700 font-medium" id="loadingText">Sedang memproses...</span>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="fixed top-5 right-5 transform translate-x-full transition-transform duration-300 z-[70] bg-emerald-500 text-white px-6 py-3 rounded-xl shadow-lg shadow-emerald-200 flex items-center gap-3">
        <i class="fa-solid fa-circle-check"></i>
        <span id="toastMsg" class="font-medium text-sm">Berhasil!</span>
    </div>

    <!-- Modal Tambah Mahasiswa -->
    <div id="addModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity p-4">
        <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden transform scale-95 transition-transform" id="addModalCard">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-user-plus text-indigo-600 mr-2"></i>Tambah Mahasiswa Baru</h3>
                <button onclick="closeAddModal()" class="text-slate-400 hover:text-red-500 transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            
            <div class="p-6">
                <form id="addUserForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">NIM <span class="text-red-500">*</span></label>
                            <input type="text" name="nim" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="nama" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Jalur Program</label>
                            <input type="text" name="jalur_program" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Fakultas</label>
                            <input type="text" name="fakultas" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Program Studi</label>
                            <input type="text" name="program_studi" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Angkatan</label>
                            <input type="number" name="angkatan" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500" value="<?= date('Y') ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Email</label>
                            <input type="email" name="email" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Jenis Kelamin</label>
                            <select name="jk" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Agama</label>
                            <select name="agama" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                                <option value="">Pilih Agama</option>
                                <option value="Islam">Islam</option>
                                <option value="Kristen Protestan">Kristen Protestan</option>
                                <option value="Katolik">Katolik</option>
                                <option value="Hindu">Hindu</option>
                                <option value="Buddha">Buddha</option>
                                <option value="Khonghucu">Khonghucu</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Nomor HP</label>
                            <input type="text" name="nomor_hp" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Alamat</label>
                            <textarea name="alamat" rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <button onclick="closeAddModal()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-100 transition-colors font-medium text-sm">Batal</button>
                <button onclick="submitUser()" class="px-6 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors font-medium text-sm shadow-md">Simpan Data</button>
            </div>
        </div>
    </div>

    <!-- Modal Edit Mahasiswa -->
    <div id="editModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity p-4">
        <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden transform scale-95 transition-transform" id="editModalCard">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-pen-to-square text-amber-500 mr-2"></i>Edit Data Mahasiswa</h3>
                <button onclick="closeEditModal()" class="text-slate-400 hover:text-red-500 transition-colors">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            
            <div class="p-6">
                <form id="editUserForm" class="space-y-4">
                    <input type="hidden" name="id" id="e_id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">NIM <span class="text-red-500">*</span></label>
                            <input type="text" name="nim" id="e_nim" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="nama" id="e_nama" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Jalur Program</label>
                            <input type="text" name="jalur_program" id="e_jalur_program" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Fakultas</label>
                            <input type="text" name="fakultas" id="e_fakultas" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Program Studi</label>
                            <input type="text" name="program_studi" id="e_program_studi" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Angkatan</label>
                            <input type="number" name="angkatan" id="e_angkatan" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Email</label>
                            <input type="email" name="email" id="e_email" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Jenis Kelamin</label>
                            <select name="jk" id="e_jk" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Agama</label>
                            <select name="agama" id="e_agama" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                                <option value="">Pilih Agama</option>
                                <option value="Islam">Islam</option>
                                <option value="Kristen Protestan">Kristen Protestan</option>
                                <option value="Katolik">Katolik</option>
                                <option value="Hindu">Hindu</option>
                                <option value="Buddha">Buddha</option>
                                <option value="Khonghucu">Khonghucu</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Nomor HP</label>
                            <input type="text" name="nomor_hp" id="e_nomor_hp" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Alamat</label>
                            <textarea name="alamat" id="e_alamat" rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <button onclick="closeEditModal()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-100 transition-colors font-medium text-sm">Batal</button>
                <button onclick="submitEditUser()" class="px-6 py-2 bg-amber-500 text-white rounded-xl hover:bg-amber-600 transition-colors font-medium text-sm shadow-md">Simpan Perubahan</button>
            </div>
        </div>
    </div>

    <!-- Modal Hapus Data -->
    <div id="deleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity p-4">
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl transform transition-transform scale-95" id="deleteModalCard">
            <div class="flex flex-col items-center text-center mb-6">
                <div class="w-12 h-12 bg-red-100 text-red-500 rounded-full flex items-center justify-center mb-4">
                    <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">Hapus Data?</h3>
                <p class="text-sm text-slate-500 mt-1">Anda yakin ingin menghapus data <strong id="del_name" class="text-slate-700"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 transition-colors font-medium">Batal</button>
                <button onclick="prosesDelete()" class="flex-1 px-4 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition-colors font-medium shadow-md shadow-red-200">Ya, Hapus</button>
            </div>
        </div>
    </div>

    <script src="../assets/jquery/jquery-3.7.1.min.js"></script>
    <script src="../assets/qrcodejs/qrcode.min.js"></script>
    <script src="../assets/jszip/dist/jszip.min.js"></script>
    <script src="../assets/FileSaver-2.0.4/dist/FileSaver.min.js"></script>
    <script>
        // Modal logic
        function openAddModal() {
            const modal = document.getElementById('addModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => document.getElementById('addModalCard').classList.remove('scale-95'), 10);
        }

        function closeAddModal() {
            document.getElementById('addModalCard').classList.add('scale-95');
            setTimeout(() => {
                document.getElementById('addModal').classList.add('hidden');
                document.getElementById('addModal').classList.remove('flex');
                document.getElementById('addUserForm').reset();
            }, 150);
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

        function showToast(msg) {
            const toast = document.getElementById("toast");
            document.getElementById("toastMsg").innerText = msg;
            toast.classList.remove("translate-x-full");
            setTimeout(() => {
                toast.classList.add("translate-x-full");
            }, 3000);
        }

        // Infinite Scroll Logic
        let currentPage = 1;
        let isLoading = false;
        let hasMoreData = true;

        function loadData() {
            currentPage = 1;
            hasMoreData = true;
            isLoading = true;

            let nim = $("#nim_peserta").val();
            let nama = $("#nama_peserta").val();
            let prodi = $("#program_studi").val();

            let params = [];
            if (nim) params.push("nim=" + encodeURIComponent(nim));
            if (nama) params.push("nama_peserta=" + encodeURIComponent(nama));
            if (prodi) params.push("program_studi=" + encodeURIComponent(prodi));
            params.push("page=" + currentPage);

            let url = "?" + params.join("&");

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

            $.get("ajaxSearching.php" + url, function(data) {
                $("#tableData").html(data);

                if (!data.includes("Tidak ada data ditemukan")) {
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
        }

        function loadMore() {
            if (isLoading || !hasMoreData) return;
            
            isLoading = true;
            currentPage++;

            let nim = $("#nim_peserta").val();
            let nama = $("#nama_peserta").val();
            let prodi = $("#program_studi").val();

            let params = [];
            if (nim) params.push("nim=" + encodeURIComponent(nim));
            if (nama) params.push("nama_peserta=" + encodeURIComponent(nama));
            if (prodi) params.push("program_studi=" + encodeURIComponent(prodi));
            params.push("page=" + currentPage);

            let url = "?" + params.join("&");

            $("#scrollLoader").removeClass("hidden").addClass("flex");

            $.get("ajaxSearching.php" + url, function(data) {
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

        // Live Search
        let searchTimeout = null;
        $(document).ready(function() {
            $("#specificSearchingForm").on("input change", function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadData();
                }, 400);
            });
            
            // Initial load
            loadData();
        });

        // Add User
        function submitUser() {
            const form = document.getElementById('addUserForm');
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            showLoading("Menyimpan data...");

            fetch('add_user.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if(data.status === 'success') {
                    showToast("Data berhasil ditambahkan!");
                    closeAddModal();
                    // Reload table
                    $("#specificSearchingForm").trigger('change');
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert("Terjadi kesalahan sistem.");
            });
        }

        // Edit User Logic
        function openEditModal(data) {
            document.getElementById('e_id').value = data.id || '';
            document.getElementById('e_nim').value = data.nim || '';
            document.getElementById('e_nama').value = data.nama_lengkap || '';
            document.getElementById('e_jalur_program').value = data.jalur_program || '';
            document.getElementById('e_fakultas').value = data.fakultas || '';
            document.getElementById('e_program_studi').value = data.program_studi || '';
            document.getElementById('e_angkatan').value = data.angkatan || '';
            document.getElementById('e_email').value = data.email || '';
            document.getElementById('e_jk').value = data.jenis_kelamin || 'Laki-laki';
            document.getElementById('e_agama').value = data.agama || '';
            document.getElementById('e_nomor_hp').value = data.nomor_hp || '';
            document.getElementById('e_alamat').value = data.alamat || '';

            const modal = document.getElementById('editModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => document.getElementById('editModalCard').classList.remove('scale-95'), 10);
        }

        function closeEditModal() {
            document.getElementById('editModalCard').classList.add('scale-95');
            setTimeout(() => {
                document.getElementById('editModal').classList.add('hidden');
                document.getElementById('editModal').classList.remove('flex');
                document.getElementById('editUserForm').reset();
            }, 150);
        }

        function submitEditUser() {
            const form = document.getElementById('editUserForm');
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            showLoading("Memperbarui data...");

            fetch('edit_user.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if(data.status === 'success') {
                    showToast("Data berhasil diperbarui!");
                    closeEditModal();
                    $("#specificSearchingForm").trigger('change');
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert("Terjadi kesalahan sistem.");
            });
        }

        // Delete User Logic
        let deleteId = null;
        function confirmDelete(id, nama) {
            deleteId = id;
            document.getElementById('del_name').innerText = nama;
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => document.getElementById('deleteModalCard').classList.remove('scale-95'), 10);
        }

        function closeDeleteModal() {
            document.getElementById('deleteModalCard').classList.add('scale-95');
            setTimeout(() => {
                document.getElementById('deleteModal').classList.add('hidden');
                document.getElementById('deleteModal').classList.remove('flex');
                deleteId = null;
            }, 150);
        }

        function prosesDelete() {
            if (!deleteId) return;
            closeDeleteModal();
            showLoading("Menghapus data...");

            fetch('delete_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: deleteId })
            })
            .then(res => res.json())
            .then(data => {
                hideLoading();
                if(data.status === 'success') {
                    showToast("Data berhasil dihapus!");
                    $("#specificSearchingForm").trigger('change');
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => {
                hideLoading();
                alert("Terjadi kesalahan sistem.");
            });
        }

        // Generate QR Code logic
        function _createCanvasQR(qrCodeContainer, padding = 100) {
            return new Promise(resolve => {
                setTimeout(() => {
                    const canvas = qrCodeContainer.querySelector("canvas");
                    if (canvas) {
                        const qrSize = canvas.width;
                        const newCanvas = document.createElement("canvas");
                        newCanvas.width = qrSize + padding * 2;
                        newCanvas.height = qrSize + padding * 2;

                        const ctx = newCanvas.getContext("2d");
                        ctx.fillStyle = "#ffffff";
                        ctx.fillRect(0, 0, newCanvas.width, newCanvas.height);
                        ctx.drawImage(canvas, padding, padding);

                        const dataURL = newCanvas.toDataURL("image/png");
                        resolve(dataURL);
                    } else {
                        resolve(null);
                    }
                }, 300);
            });
        }

        function GenerateAllQR() {
            showLoading("Generating All QR Codes...");

            let nimParam = $("#nim_peserta").val();
            let namaParam = $("#nama_peserta").val();
            let prodiParam = $("#program_studi").val();

            let params = [];
            if (nimParam) params.push("nim=" + encodeURIComponent(nimParam));
            if (namaParam) params.push("nama_peserta=" + encodeURIComponent(namaParam));
            if (prodiParam) params.push("program_studi=" + encodeURIComponent(prodiParam));

            let url = "get_data.php" + (params.length ? "?" + params.join("&") : "");

            fetch(url)
                .then(res => res.json())
                .then(users => {
                    if (users.length === 0) {
                        hideLoading();
                        showToast("Tidak ada data untuk di-generate!");
                        return;
                    }

                    const container = document.getElementById("qrcodes");
                    container.innerHTML = ''; // reset
                    const zip = new JSZip();

                    let tasks = users.map(user => {
                        return new Promise(resolve => {
                            const qrDiv = document.createElement("div");
                            container.appendChild(qrDiv);

                            new QRCode(qrDiv, {
                                text: user.id_encrypted,
                                width: 1080,
                                height: 1080,
                                correctLevel: QRCode.CorrectLevel.H
                            });

                            _createCanvasQR(qrDiv).then(dataURL => {
                                if(dataURL) {
                                    const base64Data = dataURL.split(",")[1];
                                    zip.file(user.nim + ".png", base64Data, { base64: true });
                                }
                                resolve();
                            });
                        });
                    });

                    Promise.all(tasks).then(() => {
                        zip.generateAsync({ type: "blob" }).then(content => {
                            saveAs(content, "qrcodes.zip");
                            hideLoading();
                            showToast("Semua QR Code berhasil diunduh!");
                        });
                    });
                });
        }

        function GenerateQR(nim, encrypted_id) {
            showLoading("Generating QR Code...");
            
            const container = document.getElementById("qrcodes");
            container.innerHTML = ''; // reset
            const qrDiv = document.createElement("div");
            container.appendChild(qrDiv);

            new QRCode(qrDiv, {
                text: encrypted_id,
                width: 1080,
                height: 1080,
                correctLevel: QRCode.CorrectLevel.H
            });

            _createCanvasQR(qrDiv).then(dataURL => {
                hideLoading();
                if(dataURL) {
                    // Trigger download
                    const a = document.createElement('a');
                    a.href = dataURL;
                    a.download = nim + '_QR.png';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    showToast("QR Code berhasil diunduh!");
                }
            });
        }

        function sendQREmail(id) {
            Swal.fire({
                title: 'Kirim Email QR?',
                text: "Sistem akan membuat QR Code dan mengirimkannya ke email mahasiswa.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Kirim!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading("Mengirim QR via Email...");
                    $.post('send_qr_email.php', { id: id }, function(response) {
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