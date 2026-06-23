<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = trim($_POST['nim']);
    $nama = trim($_POST['nama']);
    $jalur_program = trim($_POST['jalur_program']);
    $fakultas = trim($_POST['fakultas']);
    $program_studi = trim($_POST['program_studi']);
    $angkatan = trim($_POST['angkatan']);
    $email = trim($_POST['email']);
    $jk = trim($_POST['jk']);
    $alamat = trim($_POST['alamat']);
    
    // DB schema requires agama and nomor_hp to be NOT NULL
    $agama = trim($_POST['agama'] ?? '');
    $nomor_hp = trim($_POST['nomor_hp'] ?? '');
    
    // Check if NIM already exists
    $check = $conn->prepare("SELECT id FROM users WHERE nim = ?");
    $check->bind_param("s", $nim);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'NIM sudah terdaftar!']);
        exit;
    }
    
    $sql = "INSERT INTO users (nim, nama_lengkap, jalur_program, fakultas, program_studi, tahun_masuk, jenis_kelamin, alamat, email, agama, nomor_hp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssss", $nim, $nama, $jalur_program, $fakultas, $program_studi, $angkatan, $jk, $alamat, $email, $agama, $nomor_hp);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil disimpan']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
}
?>
