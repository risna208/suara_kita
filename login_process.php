<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Siapkan statement SQL untuk mencegah SQL Injection
    $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); // "s" menunjukkan parameter adalah string
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $db_username, $password_hash, $role);
        $stmt->fetch();

        // Verifikasi password yang dimasukkan dengan hash di database
        if (password_verify($password, $password_hash)) {
            // Password benar, buat sesi
            $_SESSION['loggedin'] = true;
            $_SESSION['id'] = $id;
            $_SESSION['username'] = $db_username;
            $_SESSION['role'] = $role;

            // Redireksi ke halaman dashboard sesuai peran
            if ($role == 'admin') {
                header('location: dashboard_admin.php');
            } elseif ($role == 'siswa') {
                header('location: dashboard_siswa.php');
            }
            exit; // Penting untuk menghentikan eksekusi skrip setelah redirect
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
}

$conn->close();
?>