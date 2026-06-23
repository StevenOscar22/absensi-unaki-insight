<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($data['id']) ? intval($data['id']) : 0;
    
    if ($id > 0) {
        // Option 1: Hard delete
        // Option 2: Delete from kehadiran_user first to avoid foreign key constraint error if exists
        $del_kehadiran = $conn->prepare("DELETE FROM kehadiran_user WHERE user_id = ?");
        $del_kehadiran->bind_param("i", $id);
        $del_kehadiran->execute();

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
    }
}
?>
