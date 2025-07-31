<?php
// config.php (pastikan file ini ada dan terkonfigurasi dengan benar)
include 'config.php';

// Mulai sesi jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Pastikan pengguna sudah login dan role-nya 'siswa'
// Jika tidak, redirect ke halaman login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header('location: index.php'); // Redirect ke halaman login jika bukan siswa atau belum login
    exit;
}

// Periksa koneksi database
if ($conn->connect_error) {
    die("<div class='container' style='color: red; text-align: center; margin-top: 100px;'><h2>Koneksi Database Gagal!</h2><p>Mohon maaf, kami tidak dapat terhubung ke database saat ini. Silakan coba lagi nanti atau hubungi administrator.</p><a href='dashboard_siswa.php' class='back-button'>⬅️ Kembali ke Dashboard</a></div>");
}

// Ambil ID pengguna yang sedang login
$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0);

// Query untuk mengambil semua pengaduan yang diajukan oleh pengguna yang sedang login
// Menambahkan kolom updated_at ke dalam SELECT
$sql = "SELECT p.id, p.kategori, p.judul, p.status, p.created_at, p.updated_at, p.lampiran_path, p.komentar_admin
        FROM pengaduan p
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC";

// Gunakan prepared statement untuk keamanan
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // Tangani error jika prepared statement gagal
    die("<div class='container' style='color: red; text-align: center; margin-top: 100px;'><h2>Error Prepare Statement:</h2><p>" . htmlspecialchars($conn->error) . "</p><a href='dashboard_siswa.php' class='back-button'>⬅️ Kembali ke Dashboard Siswa</a></div>");
}

$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

// Data untuk ditampilkan di tabel
$data_pengaduan = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data_pengaduan[] = $row;
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pengaduan Anda - Sistem Suara Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS yang sama seperti sebelumnya */
        :root {
            --primary-blue: #007bff;
            --dark-blue: #0056b3;
            --light-blue: #e0f2ff;
            --text-dark: #343a40;
            --text-light: #FFFFFF;
            --card-bg: #FFFFFF;
            --secondary-accent: #6c757d;
            --table-header-bg: var(--primary-blue);
            --table-header-text: var(--text-light);
            --table-border: #adb5bd;
            --font-poppins: 'Poppins', sans-serif;
            --font-open-sans: 'Open Sans', sans-serif;
            --status-diproses: #17a2b8;
            --status-pending: #ffc107;
            --status-ditolak: #dc3545;
            --status-selesai: #28a745;
        }
        body {
            font-family: var(--font-open-sans);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0;
            position: relative;
            overflow-x: hidden;
            margin: 0;
        }
        .background-blur {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('ifsu.jpeg'); /* Pastikan path gambar ini benar */
            background-size: cover;
            background-position: center;
            filter: blur(8px);
            -webkit-filter: blur(8px);
            z-index: -1;
            transform: scale(1.05);
        }
        .navbar-custom {
            background-color: var(--primary-blue);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding-top: 15px;
            padding-bottom: 15px;
            width: 100%;
            z-index: 10;
        }
        .navbar-custom .navbar-brand {
            font-family: var(--font-poppins);
            font-weight: 700;
            color: var(--text-light) !important;
            font-size: 1.5rem;
        }
        .navbar-custom .nav-link {
            color: var(--text-light) !important;
            font-weight: 400;
            margin-right: 15px;
            transition: color 0.3s ease;
        }
        .navbar-custom .nav-link:hover {
            color: var(--light-blue) !important;
        }
        .navbar-custom .nav-item .nav-link.active {
            color: var(--light-blue) !important;
            font-weight: 600;
            border-bottom: 2px solid var(--light-blue);
            padding-bottom: 5px;
        }
        .logout-btn {
            background-color: #dc3545; /* Menggunakan warna langsung karena variabel tidak didefinisikan */
            color: var(--text-light);
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .logout-btn:hover {
            background-color: #bd2130; /* Warna hover untuk tombol logout */
            transform: translateY(-1px);
        }
        .container-main {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 15px;
            width: 100%;
        }
        .card-custom {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 1200px; /* Lebarkan card untuk menampung kolom baru */
            text-align: center;
            border: none;
            position: relative;
            z-index: 1;
        }
        h2 {
            font-family: var(--font-poppins);
            margin-bottom: 30px;
            color: var(--primary-blue);
            font-weight: 700;
            font-size: 2.2rem;
            line-height: 1.2;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--table-border);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table thead {
            background-color: var(--table-header-bg);
            color: var(--table-header-text);
        }
        .table th, .table td {
            vertical-align: middle;
            padding: 12px;
            border-color: var(--table-border);
            border-top: none;
            text-align: left;
        }
        .table th {
            border-bottom: 2px solid var(--table-border);
        }
        .table td {
            border-bottom: 1px solid var(--table-border);
        }
        /* Menambahkan border kanan kecuali untuk kolom terakhir */
        .table th:not(:last-child),
        .table td:not(:last-child) {
            border-right: 1px solid var(--table-border);
        }
        .table th:first-child, .table td:first-child {
            text-align: center;
        }
        .table-hover tbody tr:hover {
            background-color: var(--light-blue);
        }
        .table tbody tr:nth-of-type(even) {
            background-color: #f8f9fa;
        }
        .table tbody tr:nth-of-type(odd) {
            background-color: var(--card-bg);
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
        .status-diproses { background-color: var(--status-diproses); }
        .status-pending { background-color: var(--status-pending); }
        .status-ditolak { background-color: var(--status-ditolak); }
        .status-selesai { background-color: var(--status-selesai); }
        .table .photo-col img {
            max-width: 80px;
            height: auto;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .file-icon {
            font-size: 2.5em;
            color: var(--primary-blue);
            display: block;
            margin: 0 auto;
            text-align: center;
        }
        .file-icon a {
            text-decoration: none;
            color: inherit;
        }
        .back-button {
            background-color: var(--secondary-accent);
            color: var(--text-light);
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 30px;
            display: inline-block;
            font-size: 1em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .back-button:hover {
            background-color: var(--dark-blue);
            transform: translateY(-2px);
        }
        .no-data-message {
            color: #555;
            padding: 20px;
            border: 1px dashed #ccc;
            border-radius: 5px;
            margin-top: 20px;
            font-style: italic;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="background-blur"></div>

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Sistem Suara Kita - Siswa</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_siswa.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="form_pengaduan.php">Ajukan Pengaduan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="pengaduan_siswa.php">Cek Status Pengaduan</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="logout-btn">Logout (<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Siswa'; ?>)</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-main">
        <div class="card-custom">
            <h2>Cek Status Pengaduan Anda</h2>

            <?php if (!empty($data_pengaduan)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kategori</th>
                                <th>Judul</th>
                                <th>Lampiran</th>
                                <th>Status</th>
                                <th>Komentar Admin</th>
                                <th>Tanggal Dibuat</th>
                                <th>Tanggal Edit</th> </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($data_pengaduan as $row): ?>
                                <tr>
                                    <td style="text-align: center;"><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['kategori']); ?></td>
                                    <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                    <td class="photo-col">
                                        <?php if (!empty($row['lampiran_path'])): ?>
                                            <?php
                                                $file_extension = strtolower(pathinfo($row['lampiran_path'], PATHINFO_EXTENSION));
                                                $full_path = 'uploads/' . htmlspecialchars($row['lampiran_path']);
                                            ?>
                                            <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                <img src="<?php echo $full_path; ?>" alt="Lampiran Bukti">
                                            <?php elseif ($file_extension == 'pdf'): ?>
                                                <a href="<?php echo $full_path; ?>" target="_blank" title="Lihat PDF">
                                                    <i class="fas fa-file-pdf file-icon"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo $full_path; ?>" target="_blank" title="Lihat Lampiran">
                                                    <i class="fas fa-file file-icon"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Tidak ada lampiran
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['komentar_admin'] ?: 'Belum Ada Komentar'); ?></td>
                                    <td>
                                        <?php
                                            echo date('d M Y H:i', strtotime($row['created_at']));
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            // Tampilkan tanggal update jika ada, jika tidak tampilkan '-'
                                            if (!empty($row['updated_at']) && $row['updated_at'] !== '0000-00-00 00:00:00') {
                                                echo date('d M Y H:i', strtotime($row['updated_at']));
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data-message">Belum ada pengaduan yang Anda ajukan.</p>
            <?php endif; ?>

            <a href="dashboard_siswa.php" class="back-button">Kembali Ke Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>