<?php
include "db.php"; // koneksi database

$key = "secret_key_1234567890";
$iv = substr(hash('sha256', $key), 0, 16);

// Validasi token
if (!isset($_GET['token'])) {
    echo json_encode(["status" => 400, "message" => "Token tidak ditemukan!"]);
    exit;
}

$encrypted_id = $_GET['token'];

// Dekripsi
$id = openssl_decrypt($encrypted_id, 'AES-256-CBC', $key, 0, $iv);

if ($id === false || $id == '') {
    echo json_encode(["status" => 400, "message" => "Token tidak valid!"]);
    exit;
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Sanitasi input untuk mencegah SQL injection
$id = mysqli_real_escape_string($conn, $id);

// Query untuk mengecek kehadiran pagi
$cek_kehadiran_pagi_sql = "SELECT * 
    FROM kehadiran_user 
    WHERE user_id = '$id' 
      AND DATE(waktu_kehadiran) = CURDATE() 
      AND TIME(waktu_kehadiran) BETWEEN '06:00:00' AND '10:00:00'
    LIMIT 1";

// Query untuk mengecek kehadiran sore
$cek_kehadiran_sore_sql = "SELECT * 
    FROM kehadiran_user 
    WHERE user_id = '$id' 
      AND DATE(waktu_kehadiran) = CURDATE() 
      AND TIME(waktu_kehadiran) BETWEEN '15:00:00' AND '18:00:00'
    LIMIT 1";

$cek_kehadiran_pagi_result = $conn->query($cek_kehadiran_pagi_sql);
$cek_kehadiran_sore_result = $conn->query($cek_kehadiran_sore_sql);

// Cek apakah query berhasil
if (!$cek_kehadiran_pagi_result || !$cek_kehadiran_sore_result) {
    echo json_encode(["status" => 500, "message" => "Error database: " . $conn->error]);
    exit;
}

// Cek apakah sudah absen pagi atau sore
$sudah_absen_pagi = $cek_kehadiran_pagi_result->num_rows > 0;
$sudah_absen_sore = $cek_kehadiran_sore_result->num_rows > 0;

// Dapatkan waktu sekarang
$waktu_sekarang = date('H:i:s');
$jam_sekarang = (int) date('H');

// Logika pengecekan berdasarkan waktu dan status absensi
if ($sudah_absen_pagi && $sudah_absen_sore) {
    echo json_encode(["status" => 400, "title" => "Sudah Absen!", "message" => "Anda sudah absen untuk hari ini (pagi dan sore)!"]);
    exit;
}

// Jika masih dalam rentang waktu pagi (06:00-10:00)
if ($waktu_sekarang >= '06:00:00' && $waktu_sekarang <= '10:00:00') {
    if ($sudah_absen_pagi) {
        echo json_encode(["status" => 400, "title" => "Sudah Absen!", "message" => "Anda sudah absen pagi hari ini!"]);
        exit;
    }
    // Jika belum absen pagi dan masih dalam waktu, lanjutkan proses absensi
}
// Jika masih dalam rentang waktu sore (15:00-18:00)
elseif ($waktu_sekarang >= '15:00:00' && $waktu_sekarang <= '18:00:00') {
    if ($sudah_absen_sore) {
        echo json_encode(["status" => 400, "title" => "Sudah Absen!", "message" => "Anda sudah absen sore hari ini!"]);
        exit;
    }
    // Jika belum absen sore dan masih dalam waktu, lanjutkan proses absensi
}
// Jika di luar jam absensi
else {
    // Cek apakah terlambat dari jam pagi atau sore
    if ($jam_sekarang > 10 && $jam_sekarang < 15) {
        echo json_encode(["status" => 400, "title" => "Gagal Absen!", "message" => "Anda terlambat absen pagi! Silakan absen pada sesi pagi (06:00-10:00)."]);
    } elseif ($jam_sekarang > 18 && $jam_sekarang < 24) {
        if (!$sudah_absen_pagi && !$sudah_absen_sore) {
            echo json_encode(["status" => 400, "title" => "Gagal Absen!", "message" => "Anda terlambat absen! Silakan absen pada sesi pagi (06:00-10:00) atau sore (15:00-18:00)."]);
        } elseif (!$sudah_absen_sore) {
            echo json_encode(["status" => 400, "title" => "Gagal Absen!", "message" => "Anda terlambat absen sore!"]);
        }
    } else {
        echo json_encode(["status" => 400, "title" => "Belum bisa absen!", "message" => "Belum waktunya absen! Silakan absen pada sesi pagi (06:00-10:00) atau sore (15:00-18:00)."]);
    }
    exit;
}

// Cek user di database dengan prepared statement untuk keamanan
$sql = "SELECT id, nama_lengkap FROM users WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => 500, "message" => "Error prepare statement: " . $conn->error]);
    exit;
}

$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Catat absensi dengan prepared statement
    $insert_sql = "INSERT INTO kehadiran_user (user_id, waktu_kehadiran) VALUES (?, NOW())";
    $insert_stmt = $conn->prepare($insert_sql);

    if (!$insert_stmt) {
        echo json_encode(["status" => 500, "message" => "Error prepare insert statement: " . $conn->error]);
        exit;
    }

    $insert_stmt->bind_param("s", $user['id']);

    if ($insert_stmt->execute()) {
        echo json_encode([
            "status" => 200,
            "message" => "Absensi berhasil",
            "nama_lengkap" => $user['nama_lengkap'] // Diperbaiki dari $$user['nama_lengkap'] menjadi $user['nama_lengkap']
        ]);
    } else {
        echo json_encode(["status" => 500, "message" => "Gagal menyimpan absensi: " . $insert_stmt->error]);
    }

    $insert_stmt->close();
} else {
    echo json_encode(["status" => 400, "message" => "User tidak ditemukan!"]);
}

$stmt->close();
$conn->close();
?>