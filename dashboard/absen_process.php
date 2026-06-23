<?php
include "../db.php";

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Set header untuk JSON response
header('Content-Type: application/json');

// Cek method request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 405,
        'message' => 'Method tidak diizinkan'
    ]);
    exit;
}

// Ambil data JSON dari request body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validasi data input
if (!isset($data['user_id']) || !isset($data['sesi']) || !isset($data['tanggal'])) {
    echo json_encode([
        'status' => 400,
        'message' => 'Data tidak lengkap'
    ]);
    exit;
}

$user_id = $data['user_id'];
$sesi = $data['sesi'];
$tanggal = $data['tanggal'];

// Validasi sesi
if (!in_array($sesi, ['pagi', 'sore'])) {
    echo json_encode([
        'status' => 400,
        'message' => 'Sesi tidak valid'
    ]);
    exit;
}

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    echo json_encode([
        'status' => 400,
        'message' => 'Format tanggal tidak valid'
    ]);
    exit;
}

try {
    // Cek apakah user exists
    $check_user = $conn->prepare("SELECT id, nama_lengkap FROM users WHERE id = ?");
    $check_user->bind_param("i", $user_id);
    $check_user->execute();
    $user_result = $check_user->get_result();

    if ($user_result->num_rows === 0) {
        echo json_encode([
            'status' => 404,
            'message' => 'User tidak ditemukan'
        ]);
        exit;
    }

    $user_data = $user_result->fetch_assoc();
    $nama_user = $user_data['nama_lengkap'];

    // Tentukan waktu dan rentang waktu berdasarkan sesi
    if ($sesi === 'pagi') {
        $waktu_absen = $tanggal . ' 07:00:00';
        $start_time = '06:00:00';
        $end_time = '10:00:00';
    } else {
        $waktu_absen = $tanggal . ' 16:00:00';
        $start_time = '15:00:00';
        $end_time = '18:00:00';
    }

    // Cek apakah sudah absen pada sesi dan tanggal tersebut
    $check_absen = $conn->prepare("
        SELECT id FROM kehadiran_user 
        WHERE user_id = ? 
        AND DATE(waktu_kehadiran) = ? 
        AND TIME(waktu_kehadiran) BETWEEN ? AND ?
    ");
    $check_absen->bind_param("isss", $user_id, $tanggal, $start_time, $end_time);
    $check_absen->execute();
    $absen_result = $check_absen->get_result();

    if ($absen_result->num_rows > 0) {
        echo json_encode([
            'status' => 409,
            'message' => "Peserta {$nama_user} sudah melakukan absen {$sesi} pada tanggal " . date('d F Y', strtotime($tanggal))
        ]);
        exit;
    }

    // Insert data absensi manual
    $insert_absen = $conn->prepare("
        INSERT INTO kehadiran_user (user_id, waktu_kehadiran) 
        VALUES (?, ?)
    ");
    $insert_absen->bind_param("is", $user_id, $waktu_absen);

    if ($insert_absen->execute()) {
        // Log aktivitas (opsional, jika ada tabel log)
        $log_message = "Absen manual {$sesi} untuk {$nama_user} pada " . date('d F Y H:i', strtotime($waktu_absen));

        // Insert ke tabel log jika ada
        // $log_stmt = $conn->prepare("INSERT INTO activity_log (message, created_at) VALUES (?, NOW())");
        // $log_stmt->bind_param("s", $log_message);
        // $log_stmt->execute();

        echo json_encode([
            'status' => 200,
            'message' => "Berhasil melakukan absen manual {$sesi} untuk {$nama_user}",
            'data' => [
                'user_id' => $user_id,
                'nama_lengkap' => $nama_user,
                'sesi' => $sesi,
                'waktu_absen' => $waktu_absen,
                'tanggal' => $tanggal
            ]
        ]);
    } else {
        throw new Exception("Gagal menyimpan data absensi");
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 500,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
} finally {
    // Tutup prepared statements
    if (isset($check_user))
        $check_user->close();
    if (isset($check_absen))
        $check_absen->close();
    if (isset($insert_absen))
        $insert_absen->close();

    // Tutup koneksi database
    $conn->close();
}
?>