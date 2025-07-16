<?php
include 'config.php';

// Pastikan hanya admin yang bisa melakukan tindakan ini
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('location: index.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pengaduan_id = $_POST['pengaduan_id'];
    $status = $_POST['status'];
    $komentar_admin = trim($_POST['komentar_admin']);

    // Validasi input
    if (empty($pengaduan_id) || empty($status)) {
        $_SESSION['action_error'] = "ID pengaduan dan status tidak boleh kosong.";
        header('location: kelola_pengaduan.php');
        exit;
    }

    // Validasi status yang diizinkan
    $allowed_statuses = ['pending', 'diproses', 'selesai', 'ditolak'];
    if (!in_array($status, $allowed_statuses)) {
        $_SESSION['action_error'] = "Status tidak valid.";
        header('location: kelola_pengaduan.php');
        exit;
    }

    // Update status pengaduan di database
    $stmt = $conn->prepare("UPDATE pengaduan SET status = ?, komentar_admin = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $status, $komentar_admin, $pengaduan_id);

    if ($stmt->execute()) {
        $_SESSION['action_success'] = "Status pengaduan berhasil diperbarui!";
        header('location: kelola_pengaduan.php');
        exit;
    } else {
        $_SESSION['action_error'] = "Gagal memperbarui status pengaduan: " . $conn->error;
        header('location: kelola_pengaduan.php');
        exit;
    }
    $stmt->close();
}

$conn->close();
?>