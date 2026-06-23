<?php
// File untuk membuat admin baru (hapus setelah digunakan)
include "./../db.php";

$password = "admininsight"; // Ganti dengan password yang diinginkan
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO admins (username, email, password, nama_lengkap, role, is_active) 
        VALUES ('admin', 'admin@example.com', '$hashed_password', 'Administrator', 'super_admin', 1)";

if ($conn->query($sql)) {
    echo "Admin berhasil dibuat!<br>";
    echo "Username: admin<br>";
    echo "Password: admininsight<br>";
    echo "Hash password: " . $hashed_password;
} else {
    echo "Error: " . $conn->error;
}
?>