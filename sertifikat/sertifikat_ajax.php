<?php
include "../db.php";

$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$output = "";

if (isset($_POST['dates']) && is_array($_POST['dates'])) {
    $required_dates = [];
    foreach ($_POST['dates'] as $d) {
        $d = trim($d);
        if (!empty($d)) {
            $required_dates[] = $d;
        }
    }
    
    $required_dates = array_unique($required_dates);
    
    if (count($required_dates) > 0) {
        $in_clause = implode("','", array_map(function($d) use ($conn) {
            return mysqli_real_escape_string($conn, $d);
        }, $required_dates));
        
        $total_required = count($required_dates);
        
        // Count total for the header
        $sql_count = "
            SELECT COUNT(*) as total_lulus FROM (
                SELECT u.id
                FROM users u
                JOIN kehadiran_user k ON u.id = k.user_id
                WHERE DATE(k.waktu_kehadiran) IN ('$in_clause')
                GROUP BY u.id
                HAVING COUNT(DISTINCT DATE(k.waktu_kehadiran)) = $total_required
            ) AS subquery
        ";
        $count_res = $conn->query($sql_count);
        $total_lulus = $count_res ? $count_res->fetch_assoc()['total_lulus'] : 0;

        $sql = "
            SELECT u.id, u.nim, u.nama_lengkap, u.fakultas, u.program_studi, COUNT(DISTINCT DATE(k.waktu_kehadiran)) as total_hadir
            FROM users u
            JOIN kehadiran_user k ON u.id = k.user_id
            WHERE DATE(k.waktu_kehadiran) IN ('$in_clause')
            GROUP BY u.id, u.nim, u.nama_lengkap, u.fakultas, u.program_studi
            HAVING total_hadir = $total_required
            ORDER BY u.nama_lengkap ASC
            LIMIT $limit OFFSET $offset
        ";
        
        $result = $conn->query($sql);
        
        if ($page == 1) {
            $formatted_dates = implode(", ", array_map(function($d){ return date('d M Y', strtotime($d)); }, $required_dates));
            $output .= '
            <div class="glass-card rounded-2xl overflow-hidden animate-fade-in" id="certificateResultContainer">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-emerald-50">
                    <div>
                        <h3 class="font-bold text-emerald-800">Daftar Penerima Sertifikat</h3>
                        <p class="text-xs text-emerald-600 font-medium">Memenuhi kehadiran pada: ' . $formatted_dates . '</p>
                    </div>
                    <div class="text-xs font-semibold px-4 py-1.5 bg-emerald-200 text-emerald-800 rounded-full shadow-sm">
                        ' . $total_lulus . ' Lulus Syarat
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                                <th class="py-4 px-6 font-semibold w-16 text-center">No</th>
                                <th class="py-4 px-6 font-semibold">NIM</th>
                                <th class="py-4 px-6 font-semibold">Nama Lengkap</th>
                                <th class="py-4 px-6 font-semibold">Fakultas / Prodi</th>
                                <th class="py-4 px-6 font-semibold text-center">Kehadiran (Hari)</th>
                                <th class="py-4 px-6 font-semibold text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="certTableBody" class="divide-y divide-slate-100 bg-white">';
        }

        if ($result && $result->num_rows > 0) {
            $no = $offset + 1;
            while ($row = $result->fetch_assoc()) {
                $output .= '
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="py-4 px-6 text-sm text-slate-600 text-center">' . $no++ . '</td>
                        <td class="py-4 px-6 text-sm font-semibold text-slate-700">' . htmlspecialchars($row['nim'] ?: '-') . '</td>
                        <td class="py-4 px-6 text-sm font-semibold text-slate-800">' . htmlspecialchars($row['nama_lengkap'] ?: '-') . '</td>
                        <td class="py-4 px-6">
                            <div class="text-sm text-slate-700">' . htmlspecialchars($row['fakultas'] ?: '-') . '</div>
                            <div class="text-xs font-medium text-slate-500">' . htmlspecialchars($row['program_studi'] ?: '-') . '</div>
                        </td>
                        <td class="py-4 px-6 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 font-bold text-sm">
                                ' . $row['total_hadir'] . '
                            </span>
                        </td>
                        <td class="py-4 px-6 text-center">
                            <button onclick="sendCertificateEmail(' . $row['id'] . ')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all border border-indigo-100 shadow-sm" title="Kirim Sertifikat via Email">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </td>
                    </tr>';
            }
        } else {
            if ($page == 1) {
                $output .= '
                    <tr>
                        <td colspan="5" class="py-12 text-center bg-white">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <i class="fa-solid fa-users-slash text-4xl mb-3"></i>
                                <p class="text-sm font-medium">Tidak ada mahasiswa yang memenuhi syarat kehadiran.</p>
                            </div>
                        </td>
                    </tr>';
            }
        }

        if ($page == 1) {
            $output .= '
                        </tbody>
                    </table>
                </div>
            </div>';
        }
    }
} else {
    // Missing dates
    if ($page == 1) {
        $output .= '
        <div class="glass-card rounded-2xl p-6 text-center animate-fade-in">
            <div class="flex flex-col items-center justify-center text-slate-400">
                <i class="fa-regular fa-calendar-xmark text-4xl mb-3"></i>
                <p class="text-sm font-medium">Harap pilih setidaknya satu tanggal wajib hadir.</p>
            </div>
        </div>';
    }
}

echo $output;
?>
