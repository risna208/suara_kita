<?php
// login_process.php
include 'config.php'; // Pastikan file koneksi database Anda terhubung dengan benar

// Mulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek jika request bukan POST, redirect kembali ke halaman login
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('location: index.php');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? ''; // Ini adalah password yang diinput user dari form

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = "Username dan password tidak boleh kosong.";
    header('location: index.php');
    exit;
}

// Siapkan query untuk mengambil data pengguna berdasarkan username
// Ganti 'hashed_password' dengan 'password_hash' sesuai struktur tabel Anda
//                         vvvvvvvvvvvvv
$sql = "SELECT id, username, password_hash, role, status FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // Penanganan error jika prepare statement gagal
    $_SESSION['login_error'] = "Terjadi kesalahan sistem. Silakan coba lagi.";
    header('location: index.php');
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verifikasi password (PENTING: Gunakan password_verify() jika password di-hash saat pendaftaran)
    // Ganti $user['hashed_password'] dengan $user['password_hash']
    //                           vvvvvvvvvvvvv
    if (password_verify($password, $user['password_hash'])) {
        // Password benar, sekarang cek STATUS akun
        if ($user['status'] === 'inactive') {
            $_SESSION['login_error'] = "Akun Anda telah dinonaktifkan. Silakan hubungi administrator.";
            header('location: index.php');
            exit;
        } else {
            // Login berhasil dan akun aktif, set session
            $_SESSION['loggedin'] = true;
            $_SESSION['id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['status'] = $user['status']; // Simpan status di sesi

            // Redirect berdasarkan peran
            if ($user['role'] === 'admin') {
                header('location: dashboard_admin.php');
            } elseif ($user['role'] === 'petugas') {
                header('location: dashboard_petugas.php');
            } elseif ($user['role'] === 'siswa') {
                header('location: dashboard_siswa.php');
            } else {
                // Peran tidak dikenal, arahkan ke halaman default atau error
                $_SESSION['login_error'] = "Peran pengguna tidak valid.";
                header('location: index.php');
            }
            exit;
        }
    } else {
        // Password salah
        $_SESSION['login_error'] = "Username atau password salah.";
        header('location: index.php');
        exit;
    }
} else {
    // Username tidak ditemukan
    $_SESSION['login_error'] = "Username atau password salah.";
    header('location: index.php');
    exit;
}

$stmt->close();
$conn->close();
?>