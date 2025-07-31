<?php
// delete_user.php
include 'config.php'; // Pastikan file koneksi database Anda terhubung dengan benar

// Mulai sesi jika belum dimulai
// Baris ini redundant jika session_start() sudah di config.php, tapi tidak fatal.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "Akses ditolak. Anda tidak memiliki izin untuk melakukan tindakan ini.";
    header('location: index.php'); // Arahkan ke halaman login atau dashboard jika tidak admin
    exit;
}

// Periksa apakah request adalah POST dan user_id telah diterima
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $user_id_to_delete = (int)$_POST['id']; // Cast ke integer untuk keamanan tambahan

    // Ambil ID pengguna yang sedang login dari sesi
    $current_admin_id = null;
    if (isset($_SESSION['id'])) {
        $current_admin_id = (int)$_SESSION['id'];
    }

    // Pencegahan: Admin tidak boleh menghapus akunnya sendiri
    if ($user_id_to_delete == $current_admin_id) {
        $_SESSION['error_message'] = "❌ Anda tidak bisa menghapus akun Anda sendiri!";
        header('location: kelola_pengguna_view.php');
        exit;
    }

    // Menggunakan Prepared Statements untuk mencegah SQL Injection
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt === FALSE) {
        $_SESSION['error_message'] = "❗ Gagal menyiapkan statement: " . $conn->error;
    } else {
        $stmt->bind_param("i", $user_id_to_delete); // "i" for integer, assuming 'id' is an integer

        if ($stmt->execute()) {
            // Periksa apakah ada baris yang terpengaruh (artinya penghapusan berhasil)
            if ($stmt->affected_rows > 0) {
                $_SESSION['success_message'] = "✅ Pengguna berhasil dihapus!";
            } else {
                $_SESSION['error_message'] = "❗ Gagal menghapus pengguna: Pengguna tidak ditemukan.";
            }
        } else {
            $_SESSION['error_message'] = "❗ Gagal menghapus pengguna: " . $stmt->error;
        }
        $stmt->close();
    }

} else {
    $_SESSION['error_message'] = "🚫 Permintaan tidak valid untuk menghapus pengguna.";
}

// Tutup koneksi database
if (isset($conn) && $conn) {
    $conn->close();
}

// Redirect kembali ke halaman kelola pengguna
header('location: kelola_pengguna_view.php');
exit;
?>