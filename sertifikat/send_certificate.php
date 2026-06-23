<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

require '../vendor/autoload.php';
include "../db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

header('Content-Type: application/json');

if (!isset($_POST['id'])) {
    echo json_encode(["status" => "error", "message" => "ID tidak valid"]);
    exit;
}

$user_id = intval($_POST['id']);
$tahun = date('Y'); // Atur sesuai tahun acara, atau bisa di-passing dari request

// Dapatkan data user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(["status" => "error", "message" => "Mahasiswa tidak ditemukan"]);
    exit;
}

if (empty($user['email'])) {
    $conn->query("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES ($user_id, 'Sertifikat', 'Gagal', 'Alamat email kosong')");
    echo json_encode(["status" => "error", "message" => "Alamat email mahasiswa kosong"]);
    exit;
}

// Load Settings
$settings_query = $conn->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settings_query->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Validasi Settings
$required_settings = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'b2_key_id', 'b2_application_key', 'b2_bucket_name', 'b2_region'];
foreach ($required_settings as $req) {
    if (empty($settings[$req])) {
        $conn->query("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES ($user_id, 'Sertifikat', 'Gagal', 'Konfigurasi SMTP/B2 belum lengkap')");
        echo json_encode(["status" => "error", "message" => "Konfigurasi Integrasi belum lengkap. Silakan cek menu Pengaturan Integrasi."]);
        exit;
    }
}

try {
    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => $settings['b2_region'],
        'endpoint' => 'https://' . $settings['b2_region'],
        'credentials' => [
            'key'    => $settings['b2_key_id'],
            'secret' => $settings['b2_application_key'],
        ],
    ]);

    // Kemungkinan nama file
    $file_nim = "sertifikat/Sertifikat UNAKI INSIGHT ($tahun) - " . $user['nim'] . ".pdf";
    $file_nama = "sertifikat/Sertifikat UNAKI INSIGHT ($tahun) - " . $user['nama_lengkap'] . ".pdf";
    
    $objectKey = null;

    // Cek file dengan nama NIM
    try {
        $s3->headObject([
            'Bucket' => $settings['b2_bucket_name'],
            'Key'    => $file_nim
        ]);
        $objectKey = $file_nim;
    } catch (AwsException $e) {
        if ($e->getStatusCode() != 404 && $e->getAwsErrorCode() != 'NotFound') {
            throw $e; // Kredensial bermasalah atau error selain file not found
        }
        
        // Cek file dengan nama Nama Lengkap jika NIM gagal
        try {
            $s3->headObject([
                'Bucket' => $settings['b2_bucket_name'],
                'Key'    => $file_nama
            ]);
            $objectKey = $file_nama;
        } catch (AwsException $e2) {
            if ($e2->getStatusCode() != 404 && $e2->getAwsErrorCode() != 'NotFound') {
                throw $e2; // Kredensial bermasalah
            }
            
            // File benar-benar tidak ditemukan
            $err = "Sertifikat tidak ditemukan di B2";
            $stmt_log = $conn->prepare("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES (?, 'Sertifikat', 'Gagal', ?)");
            $stmt_log->bind_param("is", $user_id, $err);
            $stmt_log->execute();
            echo json_encode(["status" => "error", "message" => "Sertifikat untuk mahasiswa ini belum diunggah ke Backblaze."]);
            exit;
        }
    }

    // Jika ditemukan, Generate URL (misal bucket public, jika private butuh presigned url)
    // Di sini kita gunakan Pre-signed URL yang berlaku 7 hari agar aman dan mahasiswa bisa mendownload.
    $cmd = $s3->getCommand('GetObject', [
        'Bucket' => $settings['b2_bucket_name'],
        'Key'    => $objectKey
    ]);
    
    $request = $s3->createPresignedRequest($cmd, '+7 days');
    $b2_url = (string) $request->getUri();

    // 3. Kirim Email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $settings['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $settings['smtp_username'];
    $mail->Password   = $settings['smtp_password'];
    if (!empty($settings['smtp_encryption'])) {
        $mail->SMTPSecure = $settings['smtp_encryption'] == 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    }
    $mail->Port       = $settings['smtp_port'];

    $mail->setFrom($settings['smtp_from_email'] ?: $settings['smtp_username'], $settings['smtp_from_name'] ?: 'Panitia Absensi');
    $mail->addAddress($user['email'], $user['nama_lengkap']);

    $mail->isHTML(true);
    $mail->Subject = 'Sertifikat UNAKI INSIGHT - ' . $user['nama_lengkap'];
    $mail->Body    = '
    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8fafc; border-radius: 10px;">
        <h2 style="color: #4f46e5; text-align: center;">Sertifikat Kehadiran</h2>
        <p>Halo <b>' . htmlspecialchars($user['nama_lengkap']) . '</b>,</p>
        <p>Terima kasih atas partisipasi dan kehadiran Anda pada acara UNAKI INSIGHT. Berikut ini kami lampirkan tautan untuk mengunduh e-sertifikat Anda:</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . $b2_url . '" style="background-color: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Unduh Sertifikat</a>
        </div>
        <p>Tautan ini berlaku selama 7 hari. Harap segera mengunduh dan menyimpannya di perangkat Anda.</p>
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
        <p style="font-size: 12px; color: #64748b; text-align: center;">Salam hangat, Panitia UNAKI INSIGHT</p>
    </div>';

    $mail->send();

    // 4. Log sukses
    $stmt_log = $conn->prepare("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES (?, 'Sertifikat', 'Berhasil', 'Email beserta link B2 sertifikat berhasil terkirim')");
    $stmt_log->bind_param("i", $user_id);
    $stmt_log->execute();

    echo json_encode(["status" => "success", "message" => "Sertifikat berhasil dikirim ke " . $user['email']]);

} catch (Aws\Exception\AwsException $e) {
    $err = "Kredensial atau Konfigurasi Backblaze B2 tidak valid.";
    $stmt_log = $conn->prepare("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES (?, 'Sertifikat', 'Gagal', ?)");
    $stmt_log->bind_param("is", $user_id, $err);
    $stmt_log->execute();
    echo json_encode(["status" => "error", "message" => "Gagal terhubung ke Backblaze B2. Harap periksa kembali Pengaturan Integrasi (Key ID, App Key, Bucket, Region)."]);
} catch (PHPMailerException $e) {
    $err = "Konfigurasi SMTP tidak valid atau email ditolak.";
    $stmt_log = $conn->prepare("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES (?, 'Sertifikat', 'Gagal', ?)");
    $stmt_log->bind_param("is", $user_id, $err);
    $stmt_log->execute();
    echo json_encode(["status" => "error", "message" => "Pengiriman email gagal karena Konfigurasi SMTP tidak tepat. Harap periksa pengaturan SMTP Anda."]);
} catch (Exception $e) {
    $err = "Terjadi kesalahan sistem: " . $e->getMessage();
    $stmt_log = $conn->prepare("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES (?, 'Sertifikat', 'Gagal', ?)");
    $stmt_log->bind_param("is", $user_id, $err);
    $stmt_log->execute();
    echo json_encode(["status" => "error", "message" => "Kesalahan: " . $e->getMessage()]);
}
?>
