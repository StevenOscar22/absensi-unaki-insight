<?php
include "db.php";

$queries = [
    "CREATE TABLE IF NOT EXISTS `settings` (
        `id` int NOT NULL AUTO_INCREMENT,
        `setting_key` varchar(100) NOT NULL,
        `setting_value` text NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    "CREATE TABLE IF NOT EXISTS `email_logs` (
        `id` int NOT NULL AUTO_INCREMENT,
        `user_id` int NOT NULL,
        `jenis_email` varchar(50) NOT NULL,
        `status` enum('Berhasil', 'Gagal') NOT NULL,
        `pesan_log` text NOT NULL,
        `waktu_kirim` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
];

foreach ($queries as $q) {
    if (!$conn->query($q)) {
        echo "Error: " . $conn->error . "\n";
    }
}
echo "Migration Success\n";
?>
