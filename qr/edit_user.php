<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $nim = trim($_POST['nim']);
    $nama = trim($_POST['nama']);
    $jalur_program = trim($_POST['jalur_program']);
    $fakultas = trim($_POST['fakultas']);
    $program_studi = trim($_POST['program_studi']);
    $angkatan = trim($_POST['angkatan']);
    $email = trim($_POST['email']);
    $jk = trim($_POST['jk']);
    $alamat = trim($_POST['alamat']);

    $agama = trim($_POST['agama'] ?? '');
    $nomor_hp = trim($_POST['nomor_hp'] ?? '');

    // Check if NIM already exists for ANOTHER user
    $check = $conn->prepare("SELECT id FROM users WHERE nim = ? AND id != ?");
    $check->bind_param("si", $nim, $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'NIM sudah digunakan oleh mahasiswa lain!']);
        exit;
    }

    $sql = "UPDATE users SET 
            nim = ?, 
            nama_lengkap = ?, 
            jalur_program = ?, 
            fakultas = ?, 
            program_studi = ?, 
            tahun_masuk = ?, 
            jenis_kelamin = ?, 
            alamat = ?, 
            email = ?,
            agama = ?,
            nomor_hp = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssi", $nim, $nama, $jalur_program, $fakultas, $program_studi, $angkatan, $jk, $alamat, $email, $agama, $nomor_hp, $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil diperbarui']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
}
