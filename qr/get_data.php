<?php
include "../absensi/../db.php";

// Key & IV untuk enkripsi (harus konsisten)
$key = "secret_key_1234567890";
$iv = substr(hash('sha256', $key), 0, 16); // ambil 16 karakter

$nim = isset($_GET['nim']) ? $_GET['nim'] : "";
$nama_peserta = isset($_GET['nama_peserta']) ? $_GET['nama_peserta'] : "";
$program_studi = isset($_GET['program_studi']) ? $_GET['program_studi'] : "";

$query = "SELECT id, nim FROM users WHERE 1=1";

if ($nim) {
    $query .= " AND nim LIKE '%$nim%'";
}
if ($nama_peserta) {
    $query .= " AND nama_lengkap LIKE '%$nama_peserta%'";
}
if ($program_studi) {
    $query .= " AND program_studi LIKE '%$program_studi%'";
}

$sql = "$query ORDER BY registered_at DESC";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    // Enkripsi ID
    $encrypted_id = openssl_encrypt($row['id'], 'AES-256-CBC', $key, 0, $iv);
    $data[] = [
        "id" => $row['id'],
        "nim" => $row['nim'],
        "id_encrypted" => $encrypted_id
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
