<?php
session_start();

$url = "http://localhost/absensi-ui-reversion/dashboard";

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: " . $url);
    exit;
}

include "./../../db.php";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        // Cek di database
        $stmt = $conn->prepare("SELECT id, username, password, nama_lengkap, role, is_active FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($admin = $result->fetch_assoc()) {
            // Cek status aktif
            if ($admin['is_active'] == 0) {
                $error = "Akun Anda telah dinonaktifkan. Hubungi administrator!";
            }
            // Verifikasi password
            elseif (password_verify($password, $admin['password'])) {
                // Login berhasil
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['nama_lengkap'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_username'] = $admin['username'];

                // Update last login dan IP
                $ip = $_SERVER['REMOTE_ADDR'];
                $update_stmt = $conn->prepare("UPDATE admins SET last_login = NOW(), last_ip = ? WHERE id = ?");
                $update_stmt->bind_param("si", $ip, $admin['id']);
                $update_stmt->execute();

                // Redirect ke dashboard
                header("Location: " . $url);
                exit;
            } else {
                $error = "Password salah!";
                // Bisa ditambahkan percobaan gagal
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Sistem Absensi Ospek</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>

<body class="font-sans">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-8 text-center">
                <div class="inline-block p-3 bg-white/20 rounded-full mb-4">
                    <i class="fas fa-user-shield text-4xl text-white"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">Login Admin</h2>
                <p class="text-blue-100 mt-2">Sistem Absensi Mahasiswa Baru</p>
            </div>

            <!-- Form Login -->
            <div class="px-6 py-8">
                <?php if ($error): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-5">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-user mr-2"></i> Username
                        </label>
                        <input type="text" name="username" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                            placeholder="Masukkan username"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-lock mr-2"></i> Password
                        </label>
                        <div class="relative">
                            <input type="text" name="password" required autocomplete="off" id="password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                placeholder="Masukkan password">
                            <button id="togglePassword" type="button" class="absolute right-3 top-2.5 text-gray-500">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-2 px-4 rounded-lg hover:opacity-90 transition duration-300 transform hover:scale-105">
                        <i class="fas fa-sign-in-alt mr-2"></i> Login
                    </button>
                </form>

                <!-- Info tambahan -->
                <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                    <p class="text-xs text-gray-500">
                        <i class="fas fa-shield-alt mr-1"></i> Sistem Absensi Ospek
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');

        if (togglePasswordBtn && passwordInput) {
            passwordInput.addEventListener('input', function() {
                if (passwordInput.value.length > 0) {
                    passwordInput.type = "password";
                } else {
                    passwordInput.type = "text";
                }
            })

            // passwordInput.type === "text" ? passwordInput.type = "text" : passwordInput.type = "password";
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Update eye icon
                eyeIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
                togglePasswordBtn.setAttribute('title',
                    type === 'password' ? 'Tampilkan Password' : 'Sembunyikan Password'
                );
            });
        }

        // Optional: Auto focus pada field username
        document.querySelector('input[name="username"]').focus();
    </script>
</body>

</html>