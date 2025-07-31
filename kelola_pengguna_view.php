<?php
// kelola_pengguna_view.php
include 'config.php'; // Pastikan file koneksi database Anda terhubung dengan benar

// Mulai sesi jika belum dimulai (penting untuk $_SESSION)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "Akses ditolak. Anda tidak memiliki izin untuk melihat halaman ini.";
    header('location: index.php'); // Ganti dengan halaman login Anda
    exit;
}

// Periksa koneksi database
if ($conn->connect_error) {
    die("<div class='container' style='color: red; text-align: center; margin-top: 100px;'><h2>Koneksi Database Gagal!</h2><p>Mohon maaf, kami tidak dapat terhubung ke database saat ini. Silakan coba lagi nanti atau hubungi administrator.</p><a href='dashboard_admin.php' class='back-button'>⬅️ Kembali ke Dasbor Admin</a></div>");
}

// =========================================================================
// LOGIKA UNTUK MEMPROSES AKSI NONAKTIFKAN ATAU PULIHKAN (SUDAH TERGABUNG)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($user_id && ($action === 'deactivate' || $action === 'activate')) {
        // Pastikan admin tidak menonaktifkan dirinya sendiri
        if ($user_id == $_SESSION['id'] && $action === 'deactivate') {
            $_SESSION['error_message'] = "Anda tidak bisa menonaktifkan akun Anda sendiri.";
            header('location: kelola_pengguna_view.php');
            exit;
        }

        $new_status = ($action === 'deactivate') ? 'inactive' : 'active';
        $success_message = ($action === 'deactivate') ? "Pengguna berhasil dinonaktifkan." : "Pengguna berhasil dipulihkan.";

        // Buat dan jalankan query update
        $sql = "UPDATE users SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = $success_message;
        } else {
            $_SESSION['error_message'] = "Error saat memperbarui status pengguna: " . $conn->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Parameter tidak valid.";
    }

    // Redirect kembali ke halaman yang sama untuk mencegah resubmission form
    header('location: kelola_pengguna_view.php');
    exit;
}
// =========================================================================
// AKHIR DARI LOGIKA PROSES AKSI
// =========================================================================


// Ambil semua pengguna dari database, termasuk status
$sql = "SELECT id, username, role, status FROM users ORDER BY username ASC";
$result = $conn->query($sql);

// Untuk menampilkan pesan sukses/error dari aksi (nonaktifkan/pulihkan)
$message_type = '';
$message_text = '';
if (isset($_SESSION['success_message'])) {
    $message_type = 'success';
    $message_text = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
} elseif (isset($_SESSION['error_message'])) {
    $message_type = 'danger';
    $message_text = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Suara Kita - Admin | Kelola Pengguna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* (Kode CSS dari file sebelumnya tidak diubah) */
        :root {
            --primary-blue: #007bff; /* Biru standar Bootstrap */
            --dark-blue: #0056b3;    /* Biru lebih gelap untuk hover */
            --light-blue: #e0f2ff;   /* Biru sangat terang untuk background/hover */
            --text-dark: #343a40;    /* Hampir hitam untuk teks */
            --text-light: #FFFFFF;
            --card-bg: #FFFFFF;
            --secondary-accent: #6c757d; /* Abu-abu untuk teks subtitle */
            --danger-red: #dc3545; /* Merah untuk logout */
            --success-green: #28a745; /* Hijau untuk tombol aktifkan - DIBIARKAN UNTUK KONSISTENSI JIKA ADA WARNA LAIN */

            /* Warna spesifik untuk tabel */
            --table-header-bg: #E8F0F7; /* Warna header tabel terang */
            --table-header-text: var(--text-dark); /* Warna teks header tabel gelap */
            --table-border: #dee2e6; /* Border antar sel tabel */

            /* Font families */
            --font-poppins: 'Poppins', sans-serif;
            --font-open-sans: 'Open Sans', sans-serif;
        }

        body {
            font-family: var(--font-open-sans);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column; /* Untuk navbar di atas, konten di bawah */
            position: relative; /* Penting untuk penentuan posisi background-blur */
            overflow: hidden; /* Mencegah scrollbar jika gambar latar belakang diskalakan */
            padding-top: 0; /* Memastikan tidak ada padding atas default yang mengganggu navbar */
            margin: 0; /* Memastikan tidak ada margin default */
        }

        /* Latar belakang dengan ifsu.jpeg dan efek blur */
        .background-blur {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('ifsu.jpeg'); /* <-- PASTIKAN PATH INI BENAR! */
            background-size: cover;
            background-position: center;
            filter: blur(8px);
            -webkit-filter: blur(8px); /* Untuk browser lama */
            z-index: -1; /* Memastikan berada di belakang konten */
            transform: scale(1.05); /* Sedikit skala untuk menyembunyikan tepi blur */
        }

        /* Gaya Navbar Kustom (konsisten dengan dasbor) */
        .navbar-custom {
            background-color: var(--primary-blue);
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
            color: var(--light-blue) !important;
        }
        .navbar-custom .nav-item .nav-link.active {
            color: var(--light-blue) !important;
            font-weight: 600;
            border-bottom: 2px solid var(--light-blue);
            padding-bottom: 5px;
        }

        /* Tombol Logout di Navbar (konsisten dengan dasbor) */
        .logout-btn {
            background-color: var(--danger-red);
            color: var(--text-light);
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .logout-btn:hover {
            background-color: #bd2130; /* Merah lebih gelap saat hover */
            transform: translateY(-1px);
        }

        /* Kontainer Konten Utama */
        .container-main {
            flex-grow: 1; /* Memungkinkan kontainer mengambil ruang vertikal yang tersedia */
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Menyelaraskan konten ke atas di dalam kontainer flex */
            padding: 40px 15px; /* Padding vertikal dan horizontal */
            z-index: 1; /* Memastikan konten berada di atas latar belakang yang diblur */
        }

        /* Gaya Kartu Kustom (konsisten dengan dasbor dan laporan) */
        .card-custom {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 900px; /* Membatasi lebar kartu */
            text-align: center;
            border: none; /* Menghilangkan border kartu Bootstrap default */
        }
        h2 {
            font-family: var(--font-poppins);
            margin-bottom: 30px;
            color: var(--primary-blue); /* Biru utama yang konsisten untuk judul */
            font-weight: 700;
            font-size: 2.2rem;
            line-height: 1.2;
        }

        /* Gaya Tabel (konsisten dengan kelola_pengaduan dan laporan) */
        .table-responsive {
            margin-top: 20px;
        }
        .table {
            border-radius: 10px;
            overflow: hidden; /* Penting untuk border-radius pada tabel */
            border: 1px solid var(--table-border);
        }
        .table thead {
            background-color: var(--table-header-bg);
            color: var(--table-header-text);
        }
        .table th, .table td {
            vertical-align: middle;
            padding: 12px;
            border-color: var(--table-border);
        }
        .table-hover tbody tr:hover {
            background-color: var(--light-blue); /* Efek hover yang konsisten */
        }
        /* Penataan garis tabel */
        .table tbody tr:nth-of-type(even) {
            background-color: #f8f9fa; /* Abu-abu terang untuk baris genap */
        }
        .table tbody tr:nth-of-type(odd) {
            background-color: var(--card-bg); /* Putih untuk baris ganjil */
        }

        /* Tombol Kembali (konsisten dengan tombol detail/kembali) */
        .back-button {
            background-color: var(--secondary-accent); /* Menggunakan secondary-accent untuk tombol abu-abu */
            color: var(--text-light);
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 20px;
            display: inline-block;
            font-size: 0.9em;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .back-button:hover {
            background-color: var(--dark-blue); /* Biru lebih gelap saat hover untuk konsistensi */
            transform: translateY(-2px);
        }
        .no-users-message {
            color: #555;
            padding: 20px;
            border: 1px dashed #ccc;
            border-radius: 5px;
            margin-top: 20px;
            font-style: italic;
        }
        /* Gaya untuk tombol aksi */
        .btn-action-group {
            display: flex;
            gap: 5px;
            justify-content: center;
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
                        <a class="nav-link" href="dashboard_admin.php">Dasbor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="kelola_pengguna_view.php">Kelola Pengguna</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cetak_laporan_pengaduan.php">Laporan</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="logout-btn">Keluar (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-main">
        <div class="card-custom">
            <h2>Daftar Pengguna Sistem</h2>

            <?php if (!empty($message_text)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message_text); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php
            if ($result->num_rows > 0) {
                echo "<div class='table-responsive'>";
                echo "<table class='table table-hover table-bordered'>";
                echo "<thead>";
                echo "<tr>";
                echo "<th>No</th>";
                echo "<th>Username</th>";
                echo "<th>Peran</th>";
                echo "<th>Status</th>"; 
                echo "<th>Aksi</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                
                $no = 1;
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $no++ . "</td>";
                    echo "<td>" . htmlspecialchars($row["username"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["role"]) . "</td>";
                    echo "<td>" . ($row["status"] == 'active' ? "<span class='badge bg-success'>Aktif</span>" : "<span class='badge bg-secondary'>Nonaktif</span>") . "</td>";
                    echo "<td>";
                    echo "<div class='btn-action-group'>";
                    
                    if (isset($_SESSION['id']) && $row['id'] == $_SESSION['id']) {
                        echo "<button type='button' class='btn btn-secondary btn-sm' disabled>Nonaktifkan</button>";
                    } else if ($row['status'] == 'active') {
                        echo "<button type='button' class='btn btn-danger btn-sm nonaktif-btn' data-id='" . htmlspecialchars($row['id']) . "' data-username='" . htmlspecialchars($row['username']) . "'>Nonaktifkan</button>";
                        echo "<form id='form-nonaktif-" . htmlspecialchars($row['id']) . "' action='kelola_pengguna_view.php' method='POST' style='display:none;'>"; // Arahkan ke file yang sama
                        echo "<input type='hidden' name='id' value='" . htmlspecialchars($row['id']) . "'>";
                        echo "<input type='hidden' name='action' value='deactivate'>";
                        echo "</form>";
                    } else if ($row['status'] == 'inactive') {
                        echo "<button type='button' class='btn btn-success btn-sm pulihkan-btn' data-id='" . htmlspecialchars($row['id']) . "' data-username='" . htmlspecialchars($row['username']) . "'>Pulihkan</button>";
                        echo "<form id='form-pulihkan-" . htmlspecialchars($row['id']) . "' action='kelola_pengguna_view.php' method='POST' style='display:none;'>"; // Arahkan ke file yang sama
                        echo "<input type='hidden' name='id' value='" . htmlspecialchars($row['id']) . "'>";
                        echo "<input type='hidden' name='action' value='activate'>";
                        echo "</form>";
                    }
                    
                    echo "</div>"; // Close btn-action-group
                    echo "</td>";
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
            <a href="dashboard_admin.php" class="back-button">⬅️ Kembali ke Dasbor Admin</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tangkap semua tombol dengan kelas .nonaktif-btn
            const nonaktifButtons = document.querySelectorAll('.nonaktif-btn');

            nonaktifButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    const userId = this.dataset.id;
                    const username = this.dataset.username;

                    Swal.fire({
                        title: "Apakah Anda Yakin?",
                        text: `Anda akan menonaktifkan pengguna "${username}". Aksi ini tidak dapat dibatalkan!`,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#dc3545",
                        cancelButtonColor: "#6c757d",
                        confirmButtonText: "Ya, Nonaktifkan!",
                        cancelButtonText: "Batal"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Jika pengguna mengklik "Ya", kirimkan form yang relevan
                            const form = document.getElementById(`form-nonaktif-${userId}`);
                            form.submit();
                        }
                    });
                });
            });

            // Menambahkan event listener untuk tombol "Pulihkan"
            const pulihkanButtons = document.querySelectorAll('.pulihkan-btn');
            pulihkanButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    const userId = this.dataset.id;
                    const username = this.dataset.username;

                    Swal.fire({
                        title: "Apakah Anda Yakin?",
                        text: `Anda akan memulihkan kembali akun pengguna "${username}". Pengguna ini akan dapat login kembali.`,
                        icon: "info", 
                        showCancelButton: true,
                        confirmButtonColor: "#28a745", 
                        cancelButtonColor: "#6c757d",
                        confirmButtonText: "Ya, Pulihkan!",
                        cancelButtonText: "Batal"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById(`form-pulihkan-${userId}`);
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>