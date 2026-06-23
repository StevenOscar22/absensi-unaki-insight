<?php
include("../db.php");

$key = "secret_key_1234567890";
$iv = substr(hash('sha256', $key), 0, 16);

$nim = isset($_GET['nim']) ? $_GET['nim'] : "";
$nama_peserta = isset($_GET['nama_peserta']) ? $_GET['nama_peserta'] : "";
$program_studi = isset($_GET['program_studi']) ? $_GET['program_studi'] : "";

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$query = "SELECT * FROM users WHERE 1=1";

if ($nim) {
    $query .= " AND nim LIKE '%$nim%'";
}
if ($nama_peserta) {
    $query .= " AND nama_lengkap LIKE '%$nama_peserta%'";
}
if ($program_studi) {
    $query .= " AND program_studi LIKE '%$program_studi%'";
}

$ambil_data = "$query ORDER BY registered_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($ambil_data);

$output = "";

if ($page == 1) {
    $output .= '
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                <th class="py-4 px-6 font-semibold w-16 text-center">No</th>
                <th class="py-4 px-6 font-semibold">NIM</th>
                <th class="py-4 px-6 font-semibold">Nama & Email</th>
                <th class="py-4 px-6 font-semibold">Fakultas / Prodi</th>
                <th class="py-4 px-6 font-semibold text-center w-32">Aksi</th>
            </tr>
        </thead>
        <tbody id="tableBody" class="divide-y divide-slate-100">';
}

if ($result->num_rows > 0) {
    $no = $offset + 1;
    while ($row = $result->fetch_assoc()) {
        $encrypted_id = openssl_encrypt($row['id'], 'AES-256-CBC', $key, 0, $iv);
        $rowJson = htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8");
        $output .= '
            <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="py-4 px-6 text-sm text-slate-600 text-center">' . $no++ . '</td>
                <td class="py-4 px-6 text-sm font-semibold text-slate-700">' . htmlspecialchars($row['nim'] ?: '-') . '</td>
                <td class="py-4 px-6">
                    <div class="text-sm font-semibold text-slate-800">' . htmlspecialchars($row['nama_lengkap'] ?: '-') . '</div>
                    <div class="text-xs text-slate-500">' . htmlspecialchars($row['email'] ?: '-') . '</div>
                </td>
                <td class="py-4 px-6">
                    <div class="text-sm text-slate-700">' . htmlspecialchars($row['fakultas'] ?: '-') . '</div>
                    <div class="text-xs font-medium text-slate-500">' . htmlspecialchars($row['program_studi'] ?: '-') . '</div>
                </td>
                <td class="py-4 px-6">
                    <div class="flex gap-2 justify-center">
                        <button onclick="GenerateQR(\'' . htmlspecialchars(addslashes($row['nim'])) . '\', \'' . htmlspecialchars(addslashes($encrypted_id)) . '\')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all border border-indigo-100 shadow-sm" title="Download QR">
                            <i class="fa-solid fa-qrcode"></i>
                        </button>
                        <button onclick="sendQREmail(' . $row['id'] . ')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all border border-emerald-100 shadow-sm" title="Kirim QR via Email">
                            <i class="fa-solid fa-envelope"></i>
                        </button>
                        <button onclick=\'openEditModal(' . $rowJson . ')\' class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white transition-all border border-amber-100 shadow-sm" title="Edit Data">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button onclick="confirmDelete(\'' . $row['id'] . '\', \'' . htmlspecialchars(addslashes($row['nama_lengkap'])) . '\')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all border border-red-100 shadow-sm" title="Hapus Data">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </td>
            </tr>';
    }
} else {
    if ($page == 1) {
        $output .= '
            <tr>
                <td colspan="5" class="py-12 text-center">
                    <div class="flex flex-col items-center justify-center text-slate-400">
                        <i class="fa-regular fa-folder-open text-4xl mb-3"></i>
                        <p class="text-sm font-medium">Tidak ada data ditemukan.</p>
                    </div>
                </td>
            </tr>';
    }
}

if ($page == 1) {
    $output .= '
        </tbody>
    </table>';
}

echo $output;
?>