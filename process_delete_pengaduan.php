<?php
// process_delete_pengaduan.php

session_start();
include 'config.php'; // Pastikan file ini ada dan terkonfigurasi dengan benar

// Pastikan hanya admin yang bisa mengakses skrip ini
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    $_SESSION['action_error'] = "Akses tidak sah.";
    header('location: index.php');
    exit;
}

// Periksa apakah request adalah POST dan ada ID pengaduan yang dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pengaduan_id'])) {
    $pengaduan_id = $_POST['pengaduan_id'];

    // Mulai transaksi untuk memastikan integritas data
    $conn->begin_transaction();

    try {
        // 1. Ambil path lampiran sebelum menghapus record pengaduan
        $stmt_select_lampiran = $conn->prepare("SELECT lampiran_path FROM pengaduan WHERE id = ?");
        $stmt_select_lampiran->bind_param("i", $pengaduan_id);
        $stmt_select_lampiran->execute();
        $result_lampiran = $stmt_select_lampiran->get_result();
        $lampiran_row = $result_lampiran->fetch_assoc();
        $lampiran_path_to_delete = $lampiran_row['lampiran_path'] ?? null;
        $stmt_select_lampiran->close();

        // 2. Hapus record dari database
        $stmt_delete = $conn->prepare("DELETE FROM pengaduan WHERE id = ?");
        $stmt_delete->bind_param("i", $pengaduan_id);

        if ($stmt_delete->execute()) {
            // 3. Jika penghapusan dari DB berhasil, hapus file lampiran jika ada
            if ($lampiran_path_to_delete && $lampiran_path_to_delete !== 'null' && $lampiran_path_to_delete !== '') {
                $full_file_path = 'uploads/' . $lampiran_path_to_delete;
                if (file_exists($full_file_path)) {
                    unlink($full_file_path); // Hapus file dari server
                }
            }
            $conn->commit();
            $_SESSION['action_success'] = "Pengaduan berhasil dihapus.";
        } else {
            throw new Exception("Gagal menghapus pengaduan dari database.");
        }
        $stmt_delete->close();

    } catch (Exception $e) {
        $conn->rollback(); // Batalkan transaksi jika ada error
        $_SESSION['action_error'] = "Terjadi kesalahan: " . $e->getMessage();
    }

    header('location: kelola_pengaduan.php');
    exit;
} else {
    $_SESSION['action_error'] = "Permintaan tidak valid.";
    header('location: kelola_pengaduan.php');
    exit;
}
?>