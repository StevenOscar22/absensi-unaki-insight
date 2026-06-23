<?php
// session_start();
// if (!isset($_SESSION['admin_id'])) {
//     exit;
// }

include("../db.php");

$nim = isset($_GET['nim']) ? $_GET['nim'] : "";
$nama_peserta = isset($_GET['nama_peserta']) ? $_GET['nama_peserta'] : "";
$program_studi = isset($_GET['program_studi']) ? $_GET['program_studi'] : "";
$tanggalKehadiran = isset($_GET['tanggal_kehadiran']) ? $_GET['tanggal_kehadiran'] : "";
$bulanKehadiran = isset($_GET['bulan_kehadiran']) ? $_GET['bulan_kehadiran'] : date('m');
$tahunKehadiran = isset($_GET['tahun_kehadiran']) ? $_GET['tahun_kehadiran'] : date('Y');
$sesi_kehadiran = isset($_GET['sesi']) ? $_GET['sesi'] : "pagi";

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$query = "SELECT users.nim, users.nama_lengkap, users.fakultas, users.program_studi , users.email, users.alamat, kehadiran_user.waktu_kehadiran FROM users JOIN kehadiran_user ON users.id = kehadiran_user.user_id WHERE 1=1";

if ($nim) {
    $query .= " AND nim LIKE '%$nim%'";
}
if ($nama_peserta) {
    $query .= " AND nama_lengkap LIKE '%$nama_peserta%'";
}
if ($program_studi) {
    $query .= " AND program_studi LIKE '%$program_studi%'";
}
if ($tanggalKehadiran) {
    $query .= " AND DAY(kehadiran_user.waktu_kehadiran) = $tanggalKehadiran";
}
if ($bulanKehadiran) {
    $query .= " AND MONTH(kehadiran_user.waktu_kehadiran) = $bulanKehadiran";
}
if ($tahunKehadiran) {
    $query .= " AND YEAR(kehadiran_user.waktu_kehadiran) = $tahunKehadiran";
}
if ($sesi_kehadiran === 'pagi') {
    $query .= " AND TIME(kehadiran_user.waktu_kehadiran) BETWEEN '06:00:00' AND '10:00:00'";
} elseif ($sesi_kehadiran === 'sore') {
    $query .= " AND TIME(kehadiran_user.waktu_kehadiran) BETWEEN '12:00:00' AND '18:00:00'";
}

$ambil_data = "$query ORDER BY waktu_kehadiran DESC LIMIT $limit OFFSET $offset";
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
                <th class="py-4 px-6 font-semibold text-center">Sesi</th>
                <th class="py-4 px-6 font-semibold text-center">Waktu Kehadiran</th>
            </tr>
        </thead>
        <tbody id="tableBody" class="divide-y divide-slate-100">';
}

if ($result->num_rows > 0) {
    $no = $offset + 1;
    while ($row = $result->fetch_assoc()) {
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
                <td class="py-4 px-6 text-center">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ' . ($sesi_kehadiran == 'pagi' ? 'bg-indigo-50 text-indigo-700' : 'bg-orange-50 text-orange-600') . ' uppercase">
                        ' . $sesi_kehadiran . '
                    </span>
                </td>
                <td class="py-4 px-6 text-center text-sm font-medium text-slate-600">
                    ' . htmlspecialchars($row['waktu_kehadiran'] ? date('d-m-Y | H:i', strtotime($row['waktu_kehadiran'])) : '-') . '
                </td>
            </tr>';
    }
} else {
    if ($page == 1) {
        $output .= '
            <tr>
                <td colspan="6" class="py-12 text-center">
                    <div class="flex flex-col items-center justify-center text-slate-400">
                        <i class="fa-regular fa-folder-open text-4xl mb-3"></i>
                        <p class="text-sm font-medium">Tidak ada data kehadiran ditemukan.</p>
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
