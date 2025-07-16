<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // <--- Ambil nilai role dari form

    // Validasi input
    if (empty($username) || empty($password) || empty($confirm_password) || empty($role)) { // <--- Tambahkan validasi role
        $_SESSION['register_error'] = "Semua kolom harus diisi.";
        header('location: register.php');
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = "Password dan Konfirmasi Password tidak cocok.";
        header('location: register.php');
        exit;
    }

    // Validasi role yang dipilih (pastikan hanya nilai yang diizinkan oleh ENUM)
    $allowed_roles = ['admin', 'petugas', 'siswa'];
    if (!in_array($role, $allowed_roles)) {
        $_SESSION['register_error'] = "Pilihan peran tidak valid.";
        header('location: register.php');
        exit;
    }

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

    // Masukkan data pengguna baru ke database, termasuk kolom 'role'
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)"); // <--- Tambahkan 'role' di query
    $stmt->bind_param("sss", $username, $hashed_password, $role); // <--- Tambahkan 's' untuk role

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