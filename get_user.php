<?php
include "db.php"; // koneksi database

header('Content-Type: application/json');

// $token = $_GET['token'] ?? '';
$key = "secret_key_1234567890";
$iv = substr(hash('sha256', $key), 0, 16);

if (isset($_GET['token'])) {
    $encrypted_id = $_GET['token'];

    // Dekripsi
    $id = openssl_decrypt($encrypted_id, 'AES-256-CBC', $key, 0, $iv);
} else {
    echo json_encode(["status" => 400, "message" => "ID tidak ditemukan"]);
    exit;
}

if ($id == '') {
    echo json_encode(["status" => 400, "message" => "Token tidak valid"]);
    exit;
}

$sql = "SELECT * FROM users WHERE id = '$id'";
$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $data[] = [
        "id" => $row['id'],
        "nama_lengkap" => $row['nama_lengkap']
    ];
} else {
    $data[] = [
        "status" => 404,
        "message" => "User tidak ditemukan"
    ];
}

echo json_encode($data[0]);

?>