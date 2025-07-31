<?php

include 'config.php';

// Pastikan session sudah dimulai, jika belum, mulai.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    // HAPUS BARIS INI: $role = $_POST['role']; // <--- Ambil nilai role dari form

    // Tambahkan baris ini untuk menetapkan 'role' secara otomatis sebagai 'siswa'
    $role = 'siswa'; // <--- PERUBAHAN PENTING DI SINI!

    // --- Validasi Username dan Password ---
    if (strlen($username) < 6) {
        $_SESSION['register_error'] = "Username harus minimal 6 karakter.";
        header('location: register.php');
        exit;
    }

    if (strlen($password) < 8) {
        $_SESSION['register_error'] = "Password harus minimal 8 karakter.";
        header('location: register.php');
        exit;
    }
    // --- Akhir Validasi Panjang ---

    // Ubah validasi empty agar tidak memeriksa $role dari $_POST
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $_SESSION['register_error'] = "Semua kolom harus diisi.";
        header('location: register.php');
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = "Password dan Konfirmasi Password tidak cocok.";
        header('location: register.php');
        exit;
    }

    // Baris ini menjadi tidak perlu lagi karena peran sudah ditetapkan secara otomatis
    /*
    $allowed_roles = ['siswa', 'petugas']; // Menambahkan 'petugas' jika Anda ingin mengizinkan pendaftaran petugas juga
    if (!in_array($role, $allowed_roles)) {
        $_SESSION['register_error'] = "Pilihan peran tidak valid.";
        header('location: register.php');
        exit;
    }
    */

    // Periksa apakah username sudah ada di database
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = "Username sudah terdaftar. Silakan pilih username lain.";
        header('location: register.php');
        exit;
    }
    $stmt->close();

    // Hash password sebelum menyimpan
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);

    if ($stmt->execute()) {
        $_SESSION['register_success'] = "Pendaftaran berhasil! Silakan login.";
        header('location: index.php'); // Arahkan ke halaman login
        exit;
    } else {
        $_SESSION['register_error'] = "Terjadi kesalahan saat pendaftaran. Silakan coba lagi.";
        // Untuk debugging, bisa tampilkan error database:
        // $_SESSION['register_error'] = "Terjadi kesalahan: " . $conn->error;
        header('location: register.php');
        exit;
    }
    $stmt->close();
}

$conn->close();
?>