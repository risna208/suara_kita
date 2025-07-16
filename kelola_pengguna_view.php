<?php
// config.php (pastikan file ini ada dan terkonfigurasi dengan benar)
include 'config.php';

// Mulai sesi jika belum dimulai (penting untuk $_SESSION)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('location: index.php');
    exit;
}

// Periksa koneksi database
if ($conn->connect_error) {
    die("<div class='container' style='color: red; text-align: center; margin-top: 100px;'><h2>Koneksi Database Gagal!</h2><p>Mohon maaf, kami tidak dapat terhubung ke database saat ini. Silakan coba lagi nanti atau hubungi administrator.</p><a href='dashboard_admin.php' class='back-button'>⬅️ Kembali ke Dashboard Admin</a></div>");
}

// Ambil semua pengguna dari database
// Kita hanya akan menampilkan id, username, dan role
$sql = "SELECT id, username, role FROM users ORDER BY username ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Suara Kita - Admin | Kelola Pengguna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            /* Warna yang sesuai dengan tema admin dashboard dan kelola_pengaduan */
            --primary-blue-dark: #4a69bd; /* Biru gelap untuk navbar dan elemen utama */
            --primary-blue-light: #6a89cc; /* Sedikit lebih terang dari dark blue */
            --secondary-orange: #f59e00; /* Oranye/Kuning untuk aksen seperti tombol logout */
            --danger-red: #dc3545; /* Merah untuk status ditolak atau logout */
            --success-green: #28a745; /* Hijau untuk status selesai */
            --info-blue: #17a2b8; /* Biru muda untuk status diproses */
            --text-dark: #343a40;
            --text-light: #FFFFFF;
            --card-bg: #FFFFFF;
            --table-header-bg: #E8F0F7; /* Warna header tabel lebih terang, sesuai kelola_pengaduan */
            --table-header-text: var(--text-dark); /* Warna teks header tabel gelap */
            --table-border: #dee2e6;
            --body-bg-gradient: linear-gradient(135deg, #e0f2f7, #c1e4f4); /* Gradien biru muda */
            --font-poppins: 'Poppins', sans-serif;
            --font-open-sans: 'Open Sans', sans-serif;

            /* Warna spesifik untuk status pengaduan */
            --status-diproses: #17a2b8; /* Info blue */
            --status-pending: #ffc107; /* Warning yellow */
            --status-ditolak: #dc3545; /* Danger red */
            --status-selesai: #28a745; /* Success green */

            /* Warna untuk tombol Detail */
            --detail-button-bg: #6c757d; /* Gray */
            --detail-button-hover: #5a6268; /* Darker gray */
        }

        body {
            font-family: var(--font-open-sans);
            background: var(--body-bg-gradient); /* Menggunakan gradien sebagai latar belakang */
            background-attachment: fixed; /* Penting agar gradien tidak ikut scroll */
            color: var(--text-dark);
            margin: 0;
            padding-top: 0;
            padding-bottom: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Latar belakang dengan gambar ifsu.jpeg dan blur (seperti kelola pengaduan) */
        .background-blur {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('ifsu.jpeg'); /* <-- PASTIKAN PATH GAMBAR BENAR! */
            background-size: cover;
            background-position: center;
            filter: blur(8px);
            -webkit-filter: blur(8px);
            z-index: -1;
            transform: scale(1.05); /* Sedikit zoom untuk mengisi blur */
            display: block; /* Pastikan ini terlihat */
        }

        /* Navbar sesuai dengan screenshot admin dashboard/kelola_pengaduan */
        .navbar-custom {
            background-color: var(--primary-blue-dark); /* Warna biru gelap */
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding-top: 15px;
            padding-bottom: 15px;
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
            color: var(--secondary-orange) !important; /* Warna hover oranye/kuning */
        }
        .navbar-custom .nav-item .nav-link.active {
            color: var(--secondary-orange) !important;
            font-weight: 600;
            border-bottom: 2px solid var(--secondary-orange);
            padding-bottom: 5px;
        }

        /* Tombol logout di navbar */
        .logout-btn {
            background-color: var(--danger-red); /* Merah sesuai tombol logout di screenshot */
            color: var(--text-light);
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .logout-btn:hover {
            background-color: #bd2130; /* Sedikit lebih gelap saat hover */
            transform: translateY(-1px);
        }

        /* Container utama untuk konten halaman */
        .container-main {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Konten diatur dari atas */
            padding: 40px 15px; /* Padding atas dan bawah untuk container utama */
        }

        .card-custom {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 900px; /* Batasi lebar card agar tidak terlalu lebar */
            text-align: center;
            border: none; /* Border dihapus agar sama dengan kelola pengaduan card */
        }
        h2 {
            font-family: var(--font-poppins);
            margin-bottom: 30px;
            color: var(--primary-blue-dark); /* Judul menggunakan warna biru gelap */
            font-weight: 700;
            font-size: 2.2rem;
            line-height: 1.2;
        }

        /* Gaya Tabel Pengguna (disamakan dengan kelola pengaduan) */
        .table-responsive {
            margin-top: 20px;
        }
        .table {
            border-radius: 10px;
            overflow: hidden; /* Penting untuk border-radius di tabel */
            border: 1px solid var(--table-border);
            /* border-collapse: separate; /* Dihapus karena Bootstrap 5 sudah menanganinya dengan baik */
            /* border-spacing: 0; */
        }
        .table thead {
            background-color: var(--table-header-bg); /* Header tabel sesuai screenshot kelola pengaduan */
            color: var(--table-header-text); /* Teks header tabel gelap */
        }
        .table th, .table td {
            vertical-align: middle;
            padding: 12px;
            border-color: var(--table-border);
        }
        .table-hover tbody tr:hover {
            background-color: #e9f5ff; /* Hover background biru sangat muda, sedikit berbeda */
        }
        /* Striping tabel untuk kelola pengaduan */
        .table tbody tr:nth-of-type(even) {
            background-color: #f8f9fa; /* Warna abu-abu sangat muda untuk baris genap */
        }
        .table tbody tr:nth-of-type(odd) {
            background-color: var(--card-bg); /* Putih untuk baris ganjil */
        }

        /* Tombol Kembali (disamakan dengan tombol "Detail" di kelola pengaduan, tapi labelnya "Kembali") */
        .back-button {
            background-color: var(--detail-button-bg); /* Abu-abu */
            color: var(--text-light);
            padding: 8px 15px; /* Ukuran tombol Detail */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 20px;
            display: inline-block;
            font-size: 0.9em;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: var(--detail-button-hover);
        }
        .no-users-message {
            color: #555;
            padding: 20px;
            border: 1px dashed #ccc;
            border-radius: 5px;
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="background-blur"></div>

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Sistem Suara Kita - Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="kelola_pengaduan.php">Kelola Pengaduan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_admin.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="kelola_pengguna.php">Kelola Pengguna</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="logout-btn">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-main">
        <div class="card-custom">
            <h2>Daftar Pengguna Sistem</h2>

            <?php
            if ($result->num_rows > 0) {
                echo "<div class='table-responsive'>";
                echo "<table class='table table-hover table-bordered'>";
                echo "<thead>";
                echo "<tr>";
                echo "<th>No</th>";
                echo "<th>Username</th>";
                echo "<th>Role</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                $no = 1;
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $no++ . "</td>";
                    echo "<td>" . htmlspecialchars($row["username"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["role"]) . "</td>";
                    echo "</tr>";
                }
                echo "</tbody>";
                echo "</table>";
                echo "</div>"; // Close table-responsive
            } else {
                echo "<p class='no-users-message'>😔 Tidak ada pengguna terdaftar di sistem ini.</p>";
            }
            // Pastikan koneksi ditutup hanya jika berhasil dibuka
            if (isset($conn) && $conn) {
                $conn->close();
            }
            ?>
            <a href="dashboard_admin.php" class="back-button">⬅️ Kembali ke Dashboard Admin</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>