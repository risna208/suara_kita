<?php
// Pastikan file config.php ada dan terkonfigurasi dengan benar
include 'config.php';

// Mulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Periksa apakah pengguna sudah login, jika tidak, arahkan kembali ke halaman login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('location: index.php'); // Redirect ke halaman login jika belum login
    exit;
}

// Periksa koneksi database
if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

// Ambil peran pengguna dari sesi
$user_role = $_SESSION['role'];
// Menggunakan $_SESSION['id'] untuk ID pengguna yang login
$current_user_id = $_SESSION['id'];

// Ambil status, bulan, dan tahun dari URL
$filter_status = $_GET['status'] ?? '';
$bulan_pilihan = $_GET['bulan'] ?? '';
$tahun_pilihan = $_GET['tahun'] ?? '';

// Array untuk nama bulan
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Query dasar untuk mengambil pengaduan
$sql = "SELECT p.id, u.username AS pengirim, p.kategori, p.judul, p.status, p.created_at, p.updated_at, p.lampiran_path
        FROM pengaduan p
        JOIN users u ON p.user_id = u.id";

$conditions = [];
$params = [];
$param_types = '';

// Tambahkan kondisi filter status jika ada
if (!empty($filter_status) && $filter_status !== 'semua') {
    $conditions[] = "p.status = ?";
    $params[] = ucwords($filter_status); // Ubah ke format 'Pending', 'Diproses', dll.
    $param_types .= 's';
}

// Tambahkan kondisi filter bulan dan tahun jika ada
if (!empty($bulan_pilihan) && !empty($tahun_pilihan)) {
    $conditions[] = "YEAR(p.created_at) = ? AND MONTH(p.created_at) = ?";
    $params[] = $tahun_pilihan;
    $params[] = $bulan_pilihan;
    $param_types .= 'ii';
} elseif (!empty($bulan_pilihan)) { // Hanya filter bulan
    $conditions[] = "MONTH(p.created_at) = ?";
    $params[] = $bulan_pilihan;
    $param_types .= 'i';
} elseif (!empty($tahun_pilihan)) { // Hanya filter tahun
    $conditions[] = "YEAR(p.created_at) = ?";
    $params[] = $tahun_pilihan;
    $param_types .= 'i';
}

// Tambahkan kondisi untuk peran pengguna (siswa hanya bisa melihat pengaduannya sendiri)
if ($user_role === 'siswa') {
    $conditions[] = "p.user_id = ?";
    $params[] = $current_user_id;
    $param_types .= 'i';
}

// Gabungkan semua kondisi dengan WHERE jika ada
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY p.created_at DESC"; // Urutkan berdasarkan tanggal terbaru

// Prepared statement untuk keamanan
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

// Bind parameter jika ada
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

// Eksekusi statement
$stmt->execute();
$result = $stmt->get_result();

// Set header untuk download file Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Laporan_Pengaduan_'. ucwords($filter_status) .'_'. date('Ymd_His') .'.xls"');
header('Cache-Control: max-age=0');

// Buat file pointer yang terhubung ke output
$output = fopen('php://output', 'w');

// Tulis header kolom ke file CSV/Excel
if ($user_role === 'admin') {
    fputcsv($output, ['No', 'Pengirim', 'Kategori', 'Judul', 'Status', 'Tanggal Dibuat', 'Tanggal Update', 'Lampiran']);
} else {
    fputcsv($output, ['No', 'Kategori', 'Judul', 'Status', 'Tanggal Dibuat', 'Tanggal Update', 'Lampiran']);
}


$no = 1;
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $created_at_formatted = date('d M Y H:i', strtotime($row['created_at']));
        $updated_at_formatted = (!empty($row['updated_at']) && strtotime($row['updated_at']) > strtotime($row['created_at'])) ? date('d M Y H:i', strtotime($row['updated_at'])) : 'Belum Diupdate';
        $lampiran_info = !empty($row['lampiran_path']) ? 'Ada Lampiran' : 'Tidak ada lampiran';

        if ($user_role === 'admin') {
            fputcsv($output, [
                $no++,
                $row['pengirim'],
                $row['kategori'],
                $row['judul'],
                $row['status'],
                $created_at_formatted,
                $updated_at_formatted,
                $lampiran_info
            ]);
        } else {
            fputcsv($output, [
                $no++,
                $row['kategori'],
                $row['judul'],
                $row['status'],
                $created_at_formatted,
                $updated_at_formatted,
                $lampiran_info
            ]);
        }
    }
}

// Tutup file pointer
fclose($output);

// Tutup statement dan koneksi database
$stmt->close();
$conn->close();
exit;
?>