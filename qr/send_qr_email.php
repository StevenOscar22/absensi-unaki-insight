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
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

header('Content-Type: application/json');

if (!isset($_POST['id'])) {
    echo json_encode(["status" => "error", "message" => "ID tidak valid"]);
    exit;
}

$user_id = intval($_POST['id']);

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
    $conn->query("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES ($user_id, 'QR Code', 'Gagal', 'Alamat email kosong')");
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
        $conn->query("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES ($user_id, 'QR Code', 'Gagal', 'Konfigurasi SMTP/B2 belum lengkap')");
        echo json_encode(["status" => "error", "message" => "Konfigurasi Integrasi belum lengkap. Silakan cek menu Pengaturan Integrasi."]);
        exit;
    }
}

try {
    // 1. Generate QR Code
    $qrData = $user['nim'] . " - " . $user['nama_lengkap'];
    $options = new QROptions([
        'version'    => 5,
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'   => QRCode::ECC_L,
    ]);
    $qrcode = new QRCode($options);
    $qrImageBase64 = $qrcode->render($qrData);
    // Remove data:image/png;base64,
    $qrImageBase64 = explode(',', $qrImageBase64)[1];
    $qrImageData = base64_decode($qrImageBase64);

    // Save to temp file
    $tmpFile = tempnam(sys_get_temp_dir(), 'qr_');
    file_put_contents($tmpFile, $qrImageData);

    $fileName = $user['nim'] . "-mahasiswa.png";
    $objectKey = "qr-code/" . $fileName;

    // 2. Upload ke B2
    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => $settings['b2_region'],
        'endpoint' => 'https://' . $settings['b2_region'],
        'credentials' => [
            'key'    => $settings['b2_key_id'],
            'secret' => $settings['b2_application_key'],
        ],
    ]);

    $s3->putObject([
        'Bucket' => $settings['b2_bucket_name'],
        'Key'    => $objectKey,
        'SourceFile' => $tmpFile,
        'ContentType' => 'image/png'
    ]);

    // Hapus temp file
    unlink($tmpFile);

    // Dapatkan URL B2 (Jika public)
    $b2_url = "https://" . $settings['b2_bucket_name'] . "." . $settings['b2_region'] . "/" . $objectKey;

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
    $mail->Subject = 'QR Code Absensi UNAKI INSIGHT - ' . $user['nama_lengkap'];
    $mail->Body    = '
    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8fafc; border-radius: 10px;">
        <h2 style="color: #4f46e5; text-align: center;">QR Code Absensi</h2>
        <p>Halo <b>' . htmlspecialchars($user['nama_lengkap']) . '</b>,</p>
        <p>Berikut adalah tautan QR Code yang akan digunakan untuk absensi kehadiran acara UNAKI INSIGHT.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="' . $b2_url . '" style="background-color: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Lihat QR Code Anda</a>
        </div>
        <p>Atau Anda dapat mengunduhnya melalui tautan berikut: <br><a href="' . $b2_url . '">' . $b2_url . '</a></p>
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
        <p style="font-size: 12px; color: #64748b; text-align: center;">Harap simpan QR Code ini untuk keperluan pemindaian saat acara berlangsung.</p>
    </div>';

    $mail->send();

    // 4. Log sukses
    $stmt_log = $conn->prepare("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES (?, 'QR Code', 'Berhasil', 'Email beserta link B2 berhasil terkirim')");
    $stmt_log->bind_param("i", $user_id);
    $stmt_log->execute();

    echo json_encode(["status" => "success", "message" => "QR Code berhasil diunggah ke B2 dan dikirim ke " . $user['email']]);
} catch (Aws\Exception\AwsException $e) {
    $err = "Kredensial atau Konfigurasi Backblaze B2 tidak valid.";
    $stmt_log = $conn->prepare("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES (?, 'QR Code', 'Gagal', ?)");
    $stmt_log->bind_param("is", $user_id, $err);
    $stmt_log->execute();
    echo json_encode(["status" => "error", "message" => "Gagal terhubung ke Backblaze B2. Harap periksa kembali Pengaturan Integrasi (Key ID, App Key, Bucket, Region)."]);
} catch (PHPMailerException $e) {
    $err = "Konfigurasi SMTP tidak valid atau email ditolak.";
    $stmt_log = $conn->prepare("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES (?, 'QR Code', 'Gagal', ?)");
    $stmt_log->bind_param("is", $user_id, $err);
    $stmt_log->execute();
    echo json_encode(["status" => "error", "message" => "Pengiriman email gagal karena Konfigurasi SMTP tidak tepat. Harap periksa pengaturan SMTP Anda."]);
} catch (Exception $e) {
    $err = "Terjadi kesalahan sistem: " . $e->getMessage();
    $stmt_log = $conn->prepare("INSERT INTO email_logs (user_id, jenis_email, status, pesan_log) VALUES (?, 'QR Code', 'Gagal', ?)");
    $stmt_log->bind_param("is", $user_id, $err);
    $stmt_log->execute();
    echo json_encode(["status" => "error", "message" => "Kesalahan: " . $e->getMessage()]);
}
