<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = [
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_email', 'smtp_from_name',
        'b2_key_id', 'b2_application_key', 'b2_bucket_name', 'b2_region'
    ];
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $val = $_POST[$key];
                $stmt->bind_param("sss", $key, $val, $val);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Pengaturan berhasil disimpan"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan: " . $e->getMessage()]);
    }
}
?>
