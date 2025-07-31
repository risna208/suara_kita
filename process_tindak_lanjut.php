<?php
// process_tindak_lanjut.php

// Pastikan session sudah dimulai, jika belum, mulai.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta'); // Pastikan zona waktu diatur

include 'config.php'; // Pastikan file ini ada dan terkonfigurasi dengan benar

// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('location: index.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pengaduan_id = $_POST['pengaduan_id'] ?? null;
    $new_status = $_POST['status'] ?? null;
    $new_komentar_admin = trim($_POST['komentar_admin'] ?? ''); // Komentar baru dari admin
    $current_admin_username = $_SESSION['username'] ?? 'Admin'; // Ambil username admin yang sedang login

    if (empty($pengaduan_id) || empty($new_status)) {
        $_SESSION['action_error'] = "ID Pengaduan atau Status baru tidak boleh kosong.";
        header('location: kelola_pengaduan.php');
        exit;
    }

    // Ambil data pengaduan yang ada, termasuk riwayat komentar
    $stmt = $conn->prepare("SELECT status, komentar_admin_history, lampiran_path FROM pengaduan WHERE id = ?");
    $stmt->bind_param("i", $pengaduan_id);
    $stmt->execute();
    $stmt->bind_result($old_status, $komentar_history_json, $old_lampiran_path);
    $stmt->fetch();
    $stmt->close();

    $komentar_history = json_decode($komentar_history_json, true);
    if (!is_array($komentar_history)) {
        $komentar_history = []; // Inisialisasi jika null atau tidak valid JSON
    }

    // Tambahkan komentar baru ke riwayat jika ada komentar baru atau status berubah
    if (!empty($new_komentar_admin) || ($new_status !== $old_status)) {
        $new_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'admin_username' => $current_admin_username,
            'status' => $new_status, // Simpan status saat komentar ini ditambahkan
            'comment' => $new_komentar_admin
        ];
        $komentar_history[] = $new_entry;
    }

    $updated_komentar_history_json = json_encode($komentar_history);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['action_error'] = "Gagal meng-encode riwayat komentar: " . json_last_error_msg();
        header('location: kelola_pengaduan.php');
        exit;
    }

    $update_lampiran_sql_part = "";
    $lampiran_to_save = $old_lampiran_path; // Default: pertahankan lampiran lama

    // Logika upload lampiran baru jika statusnya "selesai" dan ada file baru
    if ($new_status === 'selesai' && isset($_FILES['new_lampiran']) && $_FILES['new_lampiran']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $original_filename = basename($_FILES["new_lampiran"]["name"]);
        $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        $new_filename = uniqid('lampiran_') . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        $uploadOk = 1;

        // Periksa ukuran file
        if ($_FILES["new_lampiran"]["size"] > 5000000) { // 5MB
            $_SESSION['action_error'] = "Maaf, ukuran file terlalu besar. Maksimal 5MB.";
            $uploadOk = 0;
        }

        // Izinkan format file tertentu
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        if (!in_array($file_extension, $allowed_extensions)) {
            $_SESSION['action_error'] = "Maaf, hanya file JPG, JPEG, PNG, GIF, & PDF yang diizinkan.";
            $uploadOk = 0;
        }

        if ($uploadOk == 0) {
            header('location: kelola_pengaduan.php');
            exit;
        } else {
            if (move_uploaded_file($_FILES["new_lampiran"]["tmp_name"], $target_file)) {
                // Hapus lampiran lama jika ada dan berbeda
                if (!empty($old_lampiran_path) && file_exists($target_dir . $old_lampiran_path) && $old_lampiran_path !== $new_filename) {
                    unlink($target_dir . $old_lampiran_path);
                }
                $lampiran_to_save = $new_filename;
                $update_lampiran_sql_part = ", lampiran_path = ?";
            } else {
                $_SESSION['action_error'] = "Maaf, terjadi kesalahan saat mengunggah file Anda.";
                header('location: kelola_pengaduan.php');
                exit;
            }
        }
    } else if ($new_status !== 'selesai' && !empty($old_lampiran_path) && (isset($_FILES['new_lampiran']) && $_FILES['new_lampiran']['error'] == UPLOAD_ERR_NO_FILE)) {
        // Jika status bukan selesai dan tidak ada file baru diupload,
        // dan sebelumnya ada lampiran, biarkan lampiran lama.
        // Jika Anda ingin menghapus lampiran saat status berubah dari "selesai" ke yang lain,
        // tambahkan logika penghapusan di sini.
        $lampiran_to_save = $old_lampiran_path;
    }


    // Perbarui database
    // Hapus kolom komentar_admin lama, ganti dengan komentar_admin_history
    $sql_update = "UPDATE pengaduan SET status = ?, komentar_admin_history = ?, updated_at = NOW() " . $update_lampiran_sql_part . " WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);

    if ($update_lampiran_sql_part) {
        $stmt_update->bind_param("sssi", $new_status, $updated_komentar_history_json, $lampiran_to_save, $pengaduan_id);
    } else {
        $stmt_update->bind_param("ssi", $new_status, $updated_komentar_history_json, $pengaduan_id);
    }


    if ($stmt_update->execute()) {
        $_SESSION['action_success'] = "Pengaduan berhasil diperbarui!";
    } else {
        $_SESSION['action_error'] = "Gagal memperbarui pengaduan: " . $stmt_update->error;
    }
    $stmt_update->close();

    header('location: kelola_pengaduan.php');
    exit;
}

$conn->close();
?>