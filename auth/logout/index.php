<?php
session_start();

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke halaman login
$url = "http://localhost/absensi-ui-reversion/auth/login/index.php";
header("Location: " . $url);
exit;
