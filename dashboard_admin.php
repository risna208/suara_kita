<?php
// session_start(); // HAPUS BARIS INI - sudah dipanggil di config.php atau koneksi.php

// Sertakan file konfigurasi Anda (misalnya koneksi.php atau config.php)
// Asumsi 'config.php' berisi koneksi database dan sudah memanggil session_start()
include 'config.php'; // Pastikan path ini benar

// LOGIKA PENCEGAHAN AKSES TIDAK SAH
// Jika pengguna belum login, atau role-nya bukan 'admin', arahkan kembali ke halaman login.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('location: index.php'); // Arahkan ke halaman login Anda (misal: index.php atau login.php)
    exit; // Sangat penting untuk menghentikan eksekusi skrip
}

// Data pengguna yang sudah login (misalnya username) bisa diakses dari $_SESSION
$username_admin = $_SESSION['username'] ?? 'Admin'; // Gunakan 'Admin' sebagai default jika username tidak diset
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Suara Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #007bff; /* Biru standar Bootstrap, bisa diganti */
            --dark-blue: #0056b3;    /* Biru lebih gelap untuk hover */
            --light-blue: #e0f2ff;   /* Biru sangat terang untuk background */
            --text-dark: #343a40;    /* Hampir hitam untuk teks */
            --text-light: #FFFFFF;
            --card-bg: #FFFFFF;
            --secondary-accent: #6c757d; /* Abu-abu untuk teks subtitle */
        }

        body {
            font-family: 'Open Sans', sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative; /* Penting untuk positioning background-blur */
            overflow: hidden; /* Mencegah scrollbar jika gambar terlalu besar */
        }

        /* Latar belakang dengan gambar ifsu.jpeg dan blur */
        .background-blur {
            position: fixed; /* Menempel di viewport */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('ifsu.jpeg'); /* <-- PASTIKAN PATH GAMBAR BENAR! */
            background-size: cover;
            background-position: center;
            filter: blur(8px); /* Efek blur */
            -webkit-filter: blur(8px); /* Kompatibilitas browser lama */
            z-index: -1; /* Pastikan berada di belakang konten */
            transform: scale(1.05); /* Sedikit scaling untuk menyembunyikan tepi buram */
        }

        .container-custom {
            background-color: var(--card-bg);
            /* Untuk semi-transparan jika diinginkan: background-color: rgba(255, 255, 255, 0.9); */
            padding: 40px 50px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2); /* Shadow sedikit lebih kuat */
            max-width: 900px;
            width: 100%;
            border: 1px solid rgba(0,0,0,0.1); /* Border sedikit lebih kuat */
            position: relative; /* Agar z-index bekerja dengan benar */
            z-index: 1; /* Pastikan di atas background-blur */
        }
        h2 {
            font-family: 'Poppins', sans-serif;
            margin-bottom: 30px;
            color: var(--primary-blue); /* Judul biru utama */
            font-weight: 700;
            text-align: center;
            font-size: 2.5rem;
            line-height: 1.2;
        }
        .subtitle {
            font-size: 1.1rem;
            color: var(--secondary-accent); /* Subtitle abu-abu */
            text-align: center;
            margin-bottom: 40px;
        }
        .action-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            background-color: var(--primary-blue); /* Tombol aksi biru utama */
            color: var(--text-light);
            text-decoration: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            height: 100%;
        }
        .action-link:hover {
            background-color: var(--dark-blue); /* Tombol aksi biru lebih gelap saat hover */
            color: var(--text-light);
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .action-link .icon {
            font-size: 1.5rem;
        }
        .logout-btn {
            background-color: #dc3545; /* Tetap merah untuk logout */
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 1rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-decoration: none;
            display: inline-block;
            margin-top: 30px;
            font-weight: 600;
        }
        .logout-btn:hover {
            background-color: #c82333;
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container-custom {
                padding: 30px;
            }
            h2 {
                font-size: 2rem;
            }
            .action-link {
                font-size: 1rem;
                padding: 15px;
            }
            .action-link .icon {
                font-size: 1.2rem;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="background-blur"></div>
    <div class="container container-custom">
        <h2>Selamat Datang, Admin <?php echo htmlspecialchars($username_admin); ?>!</h2>
        <p class="subtitle">Ini adalah halaman khusus untuk Administrator. Anda memiliki akses penuh ke sistem.</p>

        <div class="row g-4 mt-5">
            <div class="col-md-6">
                <a href="kelola_pengaduan.php" class="action-link">
                    <span class="icon"><i class="fas fa-file-alt"></i></span>
                    Kelola Pengaduan dan Saran
                </a>
            </div>
            <div class="col-md-6">
                <a href="kelola_pengguna_view.php" class="action-link">
                    <span class="icon"><i class="fas fa-users-cog"></i></span>
                    Kelola Pengguna Sistem
                </a>
            </div>
            
        </div>

        <div class="text-center mt-4">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>