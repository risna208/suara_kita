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

// =========================================================================
// LOGIKA FILTER STATUS DAN BULAN
// =========================================================================
// Status yang valid untuk filter di tombol: 'semua', 'pending', 'diproses', 'ditolak', 'selesai'
$valid_filter_options = ['semua', 'pending', 'diproses', 'ditolak', 'selesai']; 

// Ambil status dari URL, jika tidak ada atau tidak valid, default ke 'ditolak' untuk tampilan aktif
$filter_status = $_GET['status'] ?? 'ditolak'; // DEFAULT KE 'DITOLAK'

$filter_status_lower = strtolower($filter_status);
// Jika filter yang dipilih dari URL tidak valid, kita defaultkan ke 'ditolak'
if (!in_array($filter_status_lower, $valid_filter_options)) {
    $filter_status = 'ditolak';
    $filter_status_lower = 'ditolak';
}

// Ambil filter bulan dan tahun dari URL
$bulan_pilihan = $_GET['bulan'] ?? ''; // Default: kosong (tidak ada filter bulan)
$tahun_pilihan = $_GET['tahun'] ?? ''; // Default: kosong (tidak ada filter tahun)

// Array untuk nama bulan
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Query dasar untuk mengambil pengaduan
// ************ Perubahan di sini: Menambahkan p.updated_at ************
$sql = "SELECT p.id, u.username AS pengirim, p.kategori, p.judul, p.status, p.created_at, p.updated_at, p.lampiran_path
        FROM pengaduan p
        JOIN users u ON p.user_id = u.id";

$conditions = [];
$params = [];
$param_types = '';

// KONDISI PENTING: PAKSA FILTER HANYA UNTUK 'Ditolak'
$conditions[] = "p.status = ?";
$params[] = 'Ditolak'; // Diubah menjadi 'Ditolak'
$param_types .= 's';

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

// =========================================================================
// AKHIR DARI LOGIKA FILTER
// =========================================================================

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

// Data untuk ditampilkan di laporan
$data_pengaduan = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data_pengaduan[] = $row;
    }
}

// Tutup statement dan koneksi database
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengaduan Status Ditolak - Sistem Suara Kita</title> <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Gaya CSS lainnya tetap sama */
        :root {
            --primary-blue: #007bff;
            --dark-blue: #0056b3;
            --light-blue: #e0f2ff;
            --text-dark: #343a40;
            --text-light: #FFFFFF;
            --card-bg: #FFFFFF;
            --secondary-accent: #6c757d;
        }

        /* --- MODIFIKASI DIMULAI DI SINI UNTUK SCROLLING --- */
        body {
            font-family: 'Open Sans', sans-serif;
            color: var(--text-dark);
            /* Hapus atau komentari properti yang mencegah scrolling: */
            /* min-height: 100vh; */
            /* display: flex; */
            /* justify-content: center; */
            /* align-items: center; */
            padding: 20px; /* Jaga padding */
            position: relative;
            /* overflow: hidden; */ /* Hapus ini agar body bisa discroll */
            background-color: rgba(255, 255, 255, 0.8); /* Tambahkan background agar teks terlihat jelas di atas gambar blur */
        }

        .background-blur {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('ifsu.jpeg'); /* Pastikan path gambar benar */
            background-size: cover;
            background-position: center;
            filter: blur(8px);
            -webkit-filter: blur(8px);
            z-index: -1;
            transform: scale(1.05);
        }

        .container-report {
            background-color: var(--card-bg);
            padding: 40px 50px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            max-width: 1000px;
            width: 100%;
            border: 1px solid rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
            margin: 20px auto; /* Tambahkan margin auto untuk centering dan ruang atas/bawah */
            /* Jika Anda ingin hanya kontainer ini yang bisa discroll, Anda bisa menambahkan: */
            /* max-height: 90vh; */
            /* overflow-y: auto; */
        }

        /* Untuk membuat tabel bisa di-scroll secara horizontal jika terlalu lebar */
        .table-responsive {
            overflow-x: auto; /* Memungkinkan scroll horizontal */
            -webkit-overflow-scrolling: touch; /* Untuk pengalaman scrolling yang lebih baik di perangkat sentuh */
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            min-width: 700px; /* Opsional: Tetapkan lebar minimum agar scroll horizontal terpicu jika konten melebihi ini */
        }

        .table th, .table td {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: left;
            white-space: nowrap; /* Mencegah teks melilit dalam sel */
        }
        /* --- MODIFIKASI BERAKHIR DI SINI --- */


        .table thead {
            background-color: var(--primary-blue);
            color: var(--text-light);
        }

        .table tbody tr:nth-of-type(even) {
            background-color: #f2f2f2;
        }

        .table tbody tr:nth-of-type(odd) {
            background-color: #ffffff;
        }

        .status-badge {
            display: inline-block;
            padding: .35em .65em;
            font-size: .75em;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: .375rem;
        }
        .status-diproses { background-color: #17a2b8; }
        .status-pending { background-color: #ffc107; }
        .status-ditolak { background-color: #dc3545; }
        .status-selesai { background-color: #28a745; }

        .no-data-message {
            text-align: center;
            color: #555;
            padding: 20px;
            border: 1px dashed #ccc;
            border-radius: 5px;
            margin-top: 20px;
            font-style: italic;
        }

        .btn-primary {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-primary:hover {
            background-color: var(--dark-blue);
            border-color: var(--dark-blue);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background-color: var(--secondary-accent);
            border-color: var(--secondary-accent);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
            transform: translateY(-2px);
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
        }

        .filter-container {
            margin-bottom: 20px;
            text-align: center;
        }

        .filter-container a {
            padding: 8px 15px;
            margin: 0 5px;
            border: 1px solid var(--primary-blue);
            border-radius: 20px;
            text-decoration: none;
            color: var(--primary-blue);
            font-weight: 600;
            transition: background-color 0.3s, color 0.3s;
        }
        
        .filter-container a:hover {
            background-color: var(--light-blue);
        }

        .filter-container a.active {
            background-color: var(--primary-blue);
            color: var(--text-light);
        }

        .filter-bulan-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-bulan-container .form-select {
            width: auto;
            max-width: 120px;
        }

        @media print {
            body {
                background-color: #fff;
                margin: 0;
            }
            .background-blur, .no-print {
                display: none;
            }
            .container-report {
                box-shadow: none;
                padding: 0;
                margin: 0;
                max-width: none;
                width: 100%;
                border: none;
            }
            .table th, .table td {
                border-color: #aaa;
            }
            .table thead {
                background-color: #e9ecef;
                color: #000;
            }
            .report-header {
                display: block;
            }
            .report-header-right {
                display: none;
            }
            /* Pastikan tabel tidak discroll saat dicetak */
            .table-responsive {
                overflow-x: visible;
            }
            .table {
                min-width: auto; /* Reset min-width saat dicetak */
            }
            .table th, .table td {
                white-space: normal; /* Izinkan teks melilit saat dicetak */
            }
        }

        .thumbnail-report {
            max-width: 50px;
            max-height: 50px;
            display: block;
            margin: 0 auto;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .file-icon-report {
            font-size: 1.5em;
            text-align: center;
            display: block;
            margin: 0 auto;
            color: var(--primary-blue);
        }
    </style>
</head>
<body>
    <div class="background-blur"></div>
    <div class="container container-report">
        <h1 class="text-center">Laporan Pengaduan / Saran</h1>
        <div class="report-header no-print">
            <div class="report-header-left">
                <p>Sistem Suara Kita</p>
                <p>Dicetak pada: <?php echo date('d M Y H:i'); ?></p>
                <p>
                    Laporan Pengaduan (Status: **Ditolak**
                    <?php
                    $filter_date_info = [];
                    // Check if both month and year are selected
                    if (!empty($bulan_pilihan) && !empty($tahun_pilihan)) {
                        $filter_date_info[] = "Bulan: " . htmlspecialchars($nama_bulan[(int)$bulan_pilihan]) . " Tahun: " . htmlspecialchars($tahun_pilihan);
                    }
                    // Check if only month is selected
                    elseif (!empty($bulan_pilihan)) {
                        $filter_date_info[] = "Bulan: " . htmlspecialchars($nama_bulan[(int)$bulan_pilihan]);
                    }
                    // Check if only year is selected
                    elseif (!empty($tahun_pilihan)) {
                        $filter_date_info[] = "Tahun: " . htmlspecialchars($tahun_pilihan);
                    }

                    if (!empty($filter_date_info)) {
                        echo " - " . implode(", ", $filter_date_info);
                    }
                    ?>)
                </p>
                </div>
            <?php if ($user_role === 'admin'): ?>
            <div class="report-header-right">
                <div class="filter-bulan-container">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="d-flex">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                        <select name="bulan" id="bulan" class="form-select" onchange="this.form.submit()">
                            <option value="">Pilih Bulan</option>
                            <?php foreach ($nama_bulan as $num => $nama): ?>
                                <option value="<?php echo $num; ?>" <?php echo ($bulan_pilihan == $num) ? 'selected' : ''; ?>>
                                    <?php echo $nama; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="tahun" id="tahun" class="form-select ms-2" onchange="this.form.submit()">
                            <option value="">Pilih Tahun</option>
                            <?php
                            $tahun_sekarang = date('Y');
                            for ($i = $tahun_sekarang; $i >= $tahun_sekarang - 5; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($tahun_pilihan == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($user_role === 'admin'): ?>
        <div class="filter-container no-print mt-4">
            <?php 
            // Loop untuk semua opsi filter yang diinginkan
            $all_filter_options_with_files = [
                'semua' => 'cetak_laporan_pengaduan.php',
                'pending' => 'laporan_pending.php',
                'diproses' => 'laporan_diproses.php',
                'ditolak' => 'laporan_ditolak.php',
                'selesai' => 'laporan_selesai.php'
            ];

            foreach ($all_filter_options_with_files as $status_option => $filename): 
                $current_page_name = basename($_SERVER['PHP_SELF']);
                $is_active = false;

                // Tentukan status aktif berdasarkan nama file yang sedang dibuka
                if ($status_option === 'semua' && $current_page_name === 'cetak_laporan_pengaduan.php') {
                    $is_active = true;
                } elseif ($status_option === 'pending' && $current_page_name === 'laporan_pending.php') {
                    $is_active = true;
                } elseif ($status_option === 'selesai' && $current_page_name === 'laporan_selesai.php') {
                    $is_active = true;
                }
                // Tambahkan kondisi untuk 'diproses' dan 'ditolak' jika Anda membuat file terpisah
                elseif ($status_option === 'diproses' && $current_page_name === 'laporan_diproses.php') {
                    $is_active = true;
                }
                elseif ($status_option === 'ditolak' && $current_page_name === 'laporan_ditolak.php') {
                    $is_active = true;
                }
            ?>
                <a href="<?php echo $filename; ?>?status=<?php echo $status_option; ?>&bulan=<?php echo htmlspecialchars($bulan_pilihan); ?>&tahun=<?php echo htmlspecialchars($tahun_pilihan); ?>" class="<?php echo $is_active ? 'active' : ''; ?>">
                    <?php echo ucwords($status_option); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($data_pengaduan)): ?>
            <div class="table-responsive"> <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <?php if ($user_role === 'admin'): ?>
                                <th>Pengirim</th>
                            <?php endif; ?>
                            <th>Kategori</th>
                            <th>Judul</th>
                            <th>Status</th>
                            <th>Tanggal Kirim</th>
                            <th>Tanggal Update</th> 
                            <th>Lampiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($data_pengaduan as $row): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <?php if ($user_role === 'admin'): ?>
                                    <td><?php echo htmlspecialchars($row['pengirim']); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($row['kategori']); ?></td>
                                <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    // Display updated_at if it's different from created_at, or if it's not the initial creation
                                    if ($row['updated_at'] && strtotime($row['updated_at']) > strtotime($row['created_at'])) {
                                        echo date('d M Y H:i', strtotime($row['updated_at']));
                                    } else {
                                        echo '<span class="text-muted">Belum diupdate</span>'; // Or simply leave blank
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    // Memastikan $row['lampiran_path'] adalah string sebelum di-pass ke htmlspecialchars
                                    $lampiran_path = isset($row['lampiran_path']) ? htmlspecialchars((string)$row['lampiran_path']) : '';
                                    if (!empty($lampiran_path)) {
                                        $file_extension = pathinfo($lampiran_path, PATHINFO_EXTENSION);
                                        $image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                                        $upload_dir = 'uploads/'; // Direktori tempat lampiran disimpan
                                        $full_path = $upload_dir . $lampiran_path;

                                        if (in_array(strtolower($file_extension), $image_extensions)) {
                                            if (file_exists($full_path)) {
                                                echo '<img src="' . $full_path . '" alt="Foto Lampiran" class="thumbnail-report">';
                                            } else {
                                                echo '<span class="text-muted">File tidak ditemukan</span>';
                                            }
                                        } else {
                                            if (strtolower($file_extension) === 'pdf') {
                                                echo '<i class="fas fa-file-pdf file-icon-report"></i>';
                                            } else {
                                                echo '<i class="fas fa-file file-icon-report"></i>';
                                            }
                                            echo '<br><span class="text-muted">' . strtoupper($file_extension) . ' File</span>';
                                        }
                                    } else {
                                        // Mengubah teks menjadi "Tidak ada lampiran"
                                        echo '<span class="text-muted">Tidak ada lampiran</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div> <?php else: ?>
            <p class="no-data-message">Tidak ada data pengaduan dengan status "Ditolak" untuk ditampilkan.</p>
        <?php endif; ?>

        <div class="text-center mt-4 no-print" style="display:flex; justify-content:center; gap:10px;">
            <button class="btn btn-primary" onclick="window.print()">Cetak Laporan</button>
            <a href="export_excel.php?status=ditolak&bulan=<?php echo htmlspecialchars($bulan_pilihan); ?>&tahun=<?php echo htmlspecialchars($tahun_pilihan); ?>" class="btn btn-success">Export ke Excel</a>
            <a href="<?php echo ($user_role === 'admin') ? 'dashboard_admin.php' : 'dashboard_siswa.php'; ?>" class="btn btn-secondary ms-2">Kembali</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>