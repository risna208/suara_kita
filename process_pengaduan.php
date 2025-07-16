<?php
include 'config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header('location: index.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['id'];
    $kategori = trim($_POST['kategori']);
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $lampiran_path = NULL;

    if (empty($kategori) || empty($judul) || empty($deskripsi)) {
        $_SESSION['pengaduan_error'] = "Kategori, Judul, dan Deskripsi harus diisi.";
        header('location: form_pengaduan.php');
        exit;
    }

    if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = uniqid() . '-' . basename($_FILES['lampiran']['name']);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($imageFileType, $allowed_types)) {
            $_SESSION['pengaduan_error'] = "Format file tidak diizinkan. Hanya JPG, PNG, PDF.";
            header('location: form_pengaduan.php');
            exit;
        }
        if ($_FILES['lampiran']['size'] > 2000000) {
            $_SESSION['pengaduan_error'] = "Ukuran file terlalu besar (maks 2MB).";
            header('location: form_pengaduan.php');
            exit;
        }

        if (move_uploaded_file($_FILES['lampiran']['tmp_name'], $target_file)) {
            $lampiran_path = $target_file;
        } else {
            $_SESSION['pengaduan_error'] = "Gagal mengunggah lampiran.";
            header('location: form_pengaduan.php');
            exit;
        }
    }

    // Perhatikan: Hanya kolom yang diisi saat pengajuan awal yang ada di sini.
    // 'status' akan default ke 'pending'. 'komentar_admin' akan default ke NULL.
  $stmt = $conn->prepare("INSERT INTO pengaduan (user_id, kategori, judul, deskripsi, lampiran_path, komentar_admin) VALUES (?, ?, ?, ?, ?, ?)");
$komentar_admin_default = NULL; // Secara eksplisit definisikan NULL
$stmt->bind_param("isssss", $user_id, $kategori, $judul, $deskripsi, $lampiran_path, $komentar_admin_default);


    if ($stmt->execute()) {
        $_SESSION['pengaduan_success'] = "Pengaduan/Saran berhasil diajukan!";
        header('location: dashboard_siswa.php');
        exit;
    } else {
        $_SESSION['pengaduan_error'] = "Terjadi kesalahan saat menyimpan pengaduan: " . $conn->error;
        header('location: form_pengaduan.php');
        exit;
    }
    $stmt->close();
}

$conn->close();
?>