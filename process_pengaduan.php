<?php

session_start(); 

include 'config.php'; 

// Periksa apakah pengguna sudah login dan role-nya 'siswa'
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    $_SESSION['error_message'] = "Akses ditolak. Mohon login sebagai siswa."; // Pesan kesalahan
    header('location: index.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['id']; 

    $kategori = trim($_POST['kategori']);
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $lampiran_db_name = NULL; 

    
    if (empty($kategori) || empty($judul) || empty($deskripsi)) {
        $_SESSION['pengaduan_error'] = "Kategori, Judul, dan Deskripsi harus diisi.";
        header('location: form_pengaduan.php'); 
        exit;
    }

    
    if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
        $target_dir = "uploads/";
        
        // Buat folder uploads jika belum ada
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Pastikan hak akses (permissions) sudah benar
        }

        $original_file_name = basename($_FILES['lampiran']['name']);
        $file_extension = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));
        $unique_file_name = uniqid('lampiran_', true) . '.' . $file_extension; // Nama unik untuk file
        $target_file = $target_dir . $unique_file_name; // Path lengkap untuk menyimpan file

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf']; // Tambahkan 'gif' jika diizinkan
        
        // Cek tipe file
        if (!in_array($file_extension, $allowed_types)) {
            $_SESSION['pengaduan_error'] = "Format file tidak diizinkan. Hanya JPG, PNG, GIF, PDF.";
            header('location: form_pengaduan.php');
            exit;
        }

        // Cek ukuran file
        if ($_FILES['lampiran']['size'] > 2000000) { // 2MB
            $_SESSION['pengaduan_error'] = "Ukuran file terlalu besar (maks 2MB).";
            header('location: form_pengaduan.php');
            exit;
        }

        // Coba pindahkan file yang diunggah
        if (move_uploaded_file($_FILES['lampiran']['tmp_name'], $target_file)) {
            $lampiran_db_name = $unique_file_name; // Simpan hanya nama unik file ke database
        } else {
            $_SESSION['pengaduan_error'] = "Gagal mengunggah lampiran. Terjadi masalah saat memindahkan file.";
            // Anda bisa tambahkan debug lebih lanjut di sini jika perlu, misal error_get_last()
            header('location: form_pengaduan.php');
            exit;
        }
    }

    $status = 'pending';
    $created_at = date('Y-m-d H:i:s'); // Format tanggal dan waktu MySQL

    $komentar_admin_default = NULL; 

    // Query INSERT ke tabel pengaduan
    // Kolom 'lampiran_path' digunakan di sini
    $stmt = $conn->prepare("INSERT INTO pengaduan (user_id, kategori, judul, deskripsi, lampiran_path, status, created_at, komentar_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === FALSE) {
        $_SESSION['pengaduan_error'] = "Error persiapan statement: " . $conn->error;
        header('location: form_pengaduan.php');
        exit;
    }

  
    $stmt->bind_param("isssssss", $user_id, $kategori, $judul, $deskripsi, $lampiran_db_name, $status, $created_at, $komentar_admin_default);

    if ($stmt->execute()) {
        $_SESSION['pengaduan_success'] = "Pengaduan/Saran berhasil diajukan!";
        header('location: pengaduan_siswa.php'); // Redirect ke halaman cek status
        exit;
    } else {
        $_SESSION['pengaduan_error'] = "Terjadi kesalahan saat menyimpan pengaduan: " . $stmt->error;
        header('location: form_pengaduan.php');
        exit;
    }
    $stmt->close();
}

$conn->close();
?>