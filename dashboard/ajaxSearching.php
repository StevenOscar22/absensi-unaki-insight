<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    exit;
}

include "../db.php";
date_default_timezone_set('Asia/Jakarta');

$search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
$search_year = isset($_GET['year']) ? $_GET['year'] : '';
$search_month = isset($_GET['month']) ? $_GET['month'] : '';
$search_day = isset($_GET['day']) ? $_GET['day'] : '';

$is_specific_date = false;
$search_date_string = "";

if (!empty($search_year) && !empty($search_month) && !empty($search_day)) {
    $search_date_string = sprintf("%04d-%02d-%02d", $search_year, $search_month, $search_day);
    $is_specific_date = true;
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

if ($is_specific_date) {
    $sql = "SELECT 
        u.id,
        u.nama_lengkap,
        CASE WHEN kp.user_id IS NOT NULL THEN 1 ELSE 0 END AS sudah_absen_pagi,
        CASE WHEN ks.user_id IS NOT NULL THEN 1 ELSE 0 END AS sudah_absen_sore,
        kp.waktu_kehadiran AS waktu_absen_pagi,
        ks.waktu_kehadiran AS waktu_absen_sore
    FROM users u
    LEFT JOIN kehadiran_user kp ON u.id = kp.user_id 
        AND DATE(kp.waktu_kehadiran) = ? 
        AND TIME(kp.waktu_kehadiran) BETWEEN '06:00:00' AND '10:00:00'
    LEFT JOIN kehadiran_user ks ON u.id = ks.user_id 
        AND DATE(ks.waktu_kehadiran) = ? 
        AND TIME(ks.waktu_kehadiran) BETWEEN '15:00:00' AND '18:00:00'";

    if (!empty($search_name)) {
        $sql .= " WHERE u.nama_lengkap LIKE ?";
    }
    $sql .= " ORDER BY u.nama_lengkap ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);

    if (!empty($search_name)) {
        $like = "%{$search_name}%";
        $stmt->bind_param("sssii", $search_date_string, $search_date_string, $like, $limit, $offset);
    } else {
        $stmt->bind_param("ssii", $search_date_string, $search_date_string, $limit, $offset);
    }
} else {
    $sql = "SELECT 
        u.id,
        u.nama_lengkap,
        SUM(CASE WHEN TIME(k.waktu_kehadiran) BETWEEN '06:00:00' AND '10:00:00' THEN 1 ELSE 0 END) as total_pagi,
        SUM(CASE WHEN TIME(k.waktu_kehadiran) BETWEEN '15:00:00' AND '18:00:00' THEN 1 ELSE 0 END) as total_sore
    FROM users u
    LEFT JOIN kehadiran_user k ON u.id = k.user_id 
        AND YEAR(k.waktu_kehadiran) = ? ";
    
    if (!empty($search_month)) {
        $sql .= " AND MONTH(k.waktu_kehadiran) = ? ";
    }
    if (!empty($search_name)) {
        $sql .= " WHERE u.nama_lengkap LIKE ?";
    }
    
    $sql .= " GROUP BY u.id, u.nama_lengkap ORDER BY u.nama_lengkap ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);

    if (!empty($search_month)) {
        if (!empty($search_name)) {
            $like = "%{$search_name}%";
            $stmt->bind_param("sssii", $search_year, $search_month, $like, $limit, $offset);
        } else {
            $stmt->bind_param("ssii", $search_year, $search_month, $limit, $offset);
        }
    } else {
        if (!empty($search_name)) {
            $like = "%{$search_name}%";
            $stmt->bind_param("ssii", $search_year, $like, $limit, $offset);
        } else {
            $stmt->bind_param("sii", $search_year, $limit, $offset);
        }
    }
}

$stmt->execute();
$result = $stmt->get_result();
$num_rows = $result->num_rows;

// Fetch total count for display only on page 1
if ($page == 1) {
    if ($is_specific_date) {
        $count_sql = "SELECT COUNT(*) as total FROM users u";
        if (!empty($search_name)) $count_sql .= " WHERE u.nama_lengkap LIKE ?";
        $count_stmt = $conn->prepare($count_sql);
        if (!empty($search_name)) {
            $count_stmt->bind_param("s", $like);
        }
    } else {
        $count_sql = "SELECT COUNT(*) as total FROM users u";
        if (!empty($search_name)) $count_sql .= " WHERE u.nama_lengkap LIKE ?";
        $count_stmt = $conn->prepare($count_sql);
        if (!empty($search_name)) {
            $count_stmt->bind_param("s", $like);
        }
    }
    $count_stmt->execute();
    $total_count = $count_stmt->get_result()->fetch_assoc()['total'];
    $months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    $title = $is_specific_date ? "Kehadiran Tanggal: " . date('d F Y', strtotime($search_date_string)) : "Rekap Kehadiran Bulan " . $months[(int)$search_month - 1] . " " . $search_year;
}

$output = "";

if ($page == 1) {
    $output .= '
    <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-white/50">
        <h3 class="font-bold text-slate-800">' . htmlspecialchars($title) . '</h3>
        <div class="text-xs font-semibold px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full">
            ' . $total_count . ' Mahasiswa (Hasil Filter)
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                    <th class="py-4 px-6 font-semibold w-16 text-center">No</th>
                    <th class="py-4 px-6 font-semibold">Mahasiswa</th>';
    if ($is_specific_date) {
        $output .= '
                    <th class="py-4 px-6 font-semibold text-center">Absen Pagi</th>
                    <th class="py-4 px-6 font-semibold text-center">Waktu Pagi</th>
                    <th class="py-4 px-6 font-semibold text-center">Absen Sore</th>
                    <th class="py-4 px-6 font-semibold text-center">Waktu Sore</th>';
    } else {
        $output .= '
                    <th class="py-4 px-6 font-semibold text-center">Total Hadir Pagi</th>
                    <th class="py-4 px-6 font-semibold text-center">Total Hadir Sore</th>';
    }
    $output .= '
                </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-slate-100">';
}

if ($num_rows > 0) {
    $no = $offset + 1;
    while ($row = $result->fetch_assoc()) {
        $output .= '<tr class="hover:bg-slate-50/50 transition-colors">
            <td class="py-4 px-6 text-sm text-slate-600 text-center">' . $no++ . '</td>
            <td class="py-4 px-6">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm border border-indigo-200">
                        ' . strtoupper(substr($row['nama_lengkap'], 0, 1)) . '
                    </div>
                    <div class="text-sm font-semibold text-slate-800">
                        ' . htmlspecialchars($row['nama_lengkap']) . '
                    </div>
                </div>
            </td>';
            
        if ($is_specific_date) {
            $output .= '<td class="py-4 px-6 text-center">';
            if ($row['sudah_absen_pagi']) {
                $output .= '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700"><i class="fa-solid fa-check"></i> Hadir</span>';
            } else {
                $output .= '<button onclick="absenManual(\'' . $row['id'] . '\', \'pagi\', \'' . htmlspecialchars(addslashes($row['nama_lengkap'])) . '\')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all border border-indigo-200 hover:border-transparent"><i class="fa-solid fa-hand-pointer"></i> Manual Pagi</button>';
            }
            $output .= '</td><td class="py-4 px-6 text-center text-sm">';
            if ($row['waktu_absen_pagi']) {
                $output .= '<span class="text-slate-600 font-medium">' . date('H:i:s', strtotime($row['waktu_absen_pagi'])) . '</span>';
            } else {
                $output .= '<span class="text-slate-400 italic">-</span>';
            }
            $output .= '</td><td class="py-4 px-6 text-center">';
            if ($row['sudah_absen_sore']) {
                $output .= '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700"><i class="fa-solid fa-check"></i> Hadir</span>';
            } else {
                $output .= '<button onclick="absenManual(\'' . $row['id'] . '\', \'sore\', \'' . htmlspecialchars(addslashes($row['nama_lengkap'])) . '\')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-orange-50 text-orange-600 hover:bg-orange-500 hover:text-white transition-all border border-orange-200 hover:border-transparent"><i class="fa-solid fa-hand-pointer"></i> Manual Sore</button>';
            }
            $output .= '</td><td class="py-4 px-6 text-center text-sm">';
            if ($row['waktu_absen_sore']) {
                $output .= '<span class="text-slate-600 font-medium">' . date('H:i:s', strtotime($row['waktu_absen_sore'])) . '</span>';
            } else {
                $output .= '<span class="text-slate-400 italic">-</span>';
            }
            $output .= '</td>';
        } else {
            $output .= '
            <td class="py-4 px-6 text-center">
                <div class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 font-bold border border-indigo-100">
                    ' . $row['total_pagi'] . '
                </div>
            </td>
            <td class="py-4 px-6 text-center">
                <div class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-orange-50 text-orange-600 font-bold border border-orange-100">
                    ' . $row['total_sore'] . '
                </div>
            </td>';
        }
        $output .= '</tr>';
    }
} else {
    if ($page == 1) {
        $output .= '
        <tr>
            <td colspan="' . ($is_specific_date ? 6 : 4) . '" class="py-12 text-center">
                <div class="flex flex-col items-center justify-center text-slate-400">
                    <i class="fa-regular fa-folder-open text-4xl mb-3"></i>
                    <p class="text-sm font-medium">Tidak ada data yang ditemukan.</p>
                </div>
            </td>
        </tr>';
    }
}

if ($page == 1) {
    $output .= '
            </tbody>
        </table>
    </div>';
}

echo $output;