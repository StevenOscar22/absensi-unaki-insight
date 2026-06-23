<?php
include("../db.php");

$nama_peserta = isset($_GET['nama_peserta']) ? $_GET['nama_peserta'] : "";
$tanggalKehadiran = isset($_GET['tanggal_kehadiran']) ? $_GET['tanggal_kehadiran'] : "";

if (!$tanggalKehadiran) {
    $tanggal_xlsx = date('d-m-Y');
} else {
    $tanggal_xlsx = date('d-m-Y', strtotime($tanggalKehadiran));
}

$sesi_kehadiran = isset($_GET['sesi']) ? $_GET['sesi'] : "pagi";

$query = "SELECT users.nim, users.nama_lengkap, users.jalur_program, users.fakultas, users.program_studi , users.email, users.alamat, kehadiran_user.waktu_kehadiran FROM users JOIN kehadiran_user ON users.id = kehadiran_user.user_id WHERE 1=1";

if ($nama_peserta) {
    $query .= " AND nama_lengkap LIKE '%$nama_peserta%'";
}

if ($tanggalKehadiran) {
    $query .= " AND DATE(kehadiran_user.waktu_kehadiran) = '$tanggalKehadiran'";
}

if ($sesi_kehadiran === 'pagi') {
    $query .= " AND TIME(kehadiran_user.waktu_kehadiran) BETWEEN '06:00:00' AND '10:00:00'";
} elseif ($sesi_kehadiran === 'sore') {
    $query .= " AND TIME(kehadiran_user.waktu_kehadiran) BETWEEN '12:00:00' AND '18:00:00'";
}

// $ambil_data = "$query AND TIME(kehadiran_user.waktu_kehadiran) >= '15:00:00' ORDER BY nim";
$ambil_data = "$query ORDER BY nim";
$result = $conn->query($ambil_data);

header("Content-type: application/vnd-ms-excel");
header("Content-disposition: attachment; filename=Data Kehadiran Peserta UI " . strtoupper($sesi_kehadiran) . " " . $tanggal_xlsx . ".xls");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <th>No</th>
            <th>NIM</th>
            <th>Nama Mahasiswa</th>
            <th>Jalur Program</th>
            <th>Fakultas</th>
            <th>Program Studi</th>
            <th>Email</th>
            <th>Alamat</th>
            <th>Waktu Kehadiran</th>
        </tr>
        <?php
        $num = 1;
        while ($data = mysqli_fetch_array($result)) {
            echo "<tr>";
            echo "<td>" . $num++ . "</td>";
            echo "<td>" . $data['nim'] . "</td>";
            echo "<td>" . $data['nama_lengkap'] . "</td>";
            echo "<td>" . $data['jalur_program'] . "</td>";
            echo "<td>" . $data["fakultas"] . "</td>";
            echo "<td>" . $data["program_studi"] . "</td>";
            echo "<td>" . $data["email"] . "</td>";
            echo "<td>" . $data["alamat"] . "</td>";
            echo "<td>" . $data["waktu_kehadiran"] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</body>

</html>