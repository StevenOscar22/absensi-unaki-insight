<?php
// db.php - koneksi database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "absensi_qr";


// $servername = "ftp.rizalscompanylab.my.id";
// $username = "rizp3541_absen_mahasiswa";
// $password = "absen_mahasiswa";
// $dbname = "rizp3541_absen_mahasiswa";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
