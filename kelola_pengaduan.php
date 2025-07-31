<?php
// kelola_pengaduan.php

// --- KODE TAMBAHAN UNTUK MEMPERBAIKI ZONA WAKTU ---
// Ini harus ada di bagian paling awal skrip Anda, sebelum operasi tanggal apa pun.

// Pastikan session sudah dimulai, jika belum, mulai.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta'); // Tetapkan zona waktu di awal file

include 'config.php'; // Pastikan file ini ada dan terkonfigurasi dengan benar

// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('location: index.php');
    exit;
}

// Logika untuk mengambil data pengaduan dari database
$pengaduans = [];
// MENGAMBIL KOLOM BARU: 'updated_at' dan 'komentar_admin_history'
$sql = "SELECT p.id, u.username, p.kategori, p.judul, p.deskripsi, p.status, p.created_at, p.lampiran_path, p.komentar_admin, p.updated_at, p.komentar_admin_history
        FROM pengaduan p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC"; // Urutkan dari yang terbaru

$result = $conn->query($sql);

if ($result === FALSE) {
    // Tangani error jika query gagal
    die("<div class='container' style='color: red; text-align: center; margin-top: 100px;'><h2>Error Query Database:</h2><p>" . htmlspecialchars($conn->error) . "</p><a href='dashboard_admin.php' class='back-button'>⬅ Kembali ke Dashboard Admin</a></div>");
}

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pengaduans[] = $row;
    }
}

// Logika untuk menampilkan pesan sukses/error setelah aksi (misal: update status)
$popup_message = '';
$popup_type = '';

if (isset($_SESSION['action_success'])) {
    $popup_message = $_SESSION['action_success'];
    $popup_type = 'success';
    unset($_SESSION['action_success']);
} elseif (isset($_SESSION['action_error'])) {
    $popup_message = $_SESSION['action_error'];
    $popup_type = 'danger';
    unset($_SESSION['action_error']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengaduan - Admin | Suara Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-blue: #007bff; /* Biru terang untuk elemen utama */
            --dark-blue: #0056b3;     /* Biru lebih gelap untuk hover */
            --secondary-yellow: #FFC107; /* Kuning untuk aksen */
            --dark-yellow: #E0A800;   /* Kuning lebih gelap untuk hover */
            --text-dark: #343a40;     /* Hampir hitam untuk teks */
            --text-light: #FFFFFF;
            --card-bg: #FFFFFF;
            --subtitle-color: #6c757d; /* Abu-abu untuk teks subtitle */
            --navbar-bg: #343a40; /* Navbar background dark */
            --navbar-text: #FFFFFF;
            --table-header-bg: #E9ECEF; /* Warna header tabel Bootstrap default */

            /* Warna spesifik untuk status pengaduan */
            --status-diproses: #17a2b8; /* Info blue */
            --status-pending: #ffc107; /* Warning yellow */
            --status-ditolak: #dc3545; /* Danger red */
            --status-selesai: #28a745; /* Success green */
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #E6F7FF; /* Warna biru sangat muda sebagai latar belakang */
            color: var(--text-dark);
            padding-top: 0;
            padding-bottom: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Latar belakang dengan gambar ifsu.jpeg dan blur */
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
            transform: scale(1.05);
        }

        .container-custom {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            margin-top: 30px;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
            flex-grow: 1;
        }
        h2 {
            font-family: 'Poppins', sans-serif;
            margin-bottom: 30px;
            color: var(--primary-blue);
            font-weight: 700;
            text-align: center;
            font-size: 2.2rem;
            line-height: 1.2;
        }
        .btn-action {
            background-color: var(--primary-blue);
            color: var(--text-light);
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-action:hover {
            background-color: var(--dark-blue);
            color: var(--text-light);
            transform: translateY(-1px);
        }
        .btn-detail {
            background-color: var(--subtitle-color);
            color: var(--text-light);
        }
        .btn-detail:hover {
            background-color: #5A6268;
            color: var(--text-light);
        }
        .btn-submit {
            background-color: var(--primary-blue);
            color: var(--text-light);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-submit:hover {
            background-color: var(--dark-blue);
            color: var(--text-light);
            transform: translateY(-1px);
        }

        .status-badge {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 5px;
            color: var(--text-light); /* Default text light for all status badges */
        }
        .status-pending { background-color: var(--status-pending); color: var(--text-dark); /* Keep text dark for yellow */ }
        .status-diproses { background-color: var(--status-diproses); }
        .status-selesai { background-color: var(--status-selesai); }
        .status-ditolak { background-color: var(--status-ditolak); }

        /* Navigasi Dashboard */
        .navbar-custom {
            background-color: var(--primary-blue); /* Navbar background biru */
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: var(--navbar-text) !important;
            font-weight: 600;
        }
        .navbar-custom .nav-link:hover {
            color: var(--secondary-yellow) !important; /* Kuning saat hover */
        }
        .navbar-custom .nav-item .nav-link.active {
            color: var(--secondary-yellow) !important;
            border-bottom: 2px solid var(--secondary-yellow);
            padding-bottom: 3px;
        }

        .logout-btn {
            background-color: var(--secondary-yellow); /* Tombol logout kuning */
            color: var(--text-dark); /* Teks gelap di tombol kuning */
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .logout-btn:hover {
            background-color: var(--dark-yellow); /* Kuning lebih gelap saat hover */
            transform: translateY(-1px);
        }
        .alert-fixed {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
            min-width: 300px;
            max-width: 90%;
        }

        /* Gaya Tabel */
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .table thead {
            background-color: var(--dark-blue); /* Header tabel biru gelap */
            color: var(--text-light);
        }
        .table th, .table td {
            vertical-align: middle;
            padding: 12px;
            border-color: rgba(0,0,0,0.1);
        }
        .table-hover tbody tr:hover {
            background-color: #E0F2F7; /* Hover background biru sangat muda */
        }
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }
        .modal-header {
            background-color: var(--primary-blue);
            color: var(--text-light);
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            border-bottom: none;
        }
        .modal-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }
        .modal-header .btn-close {
            filter: invert(1) brightness(2);
        }
        .modal-body strong {
            color: var(--primary-blue);
        }
        .form-select {
            border-radius: 8px;
            border-color: rgba(0,0,0,0.2);
        }
        .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .form-control {
            border-radius: 8px;
            border-color: rgba(0,0,0,0.2);
        }
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .modal-image {
            max-width: 100%;
            height: auto;
            display: block;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .file-icon-modal {
            font-size: 5em; /* Ukuran ikon lebih besar di modal */
            color: var(--primary-blue);
            display: block;
            margin: 15px auto 0;
            text-align: center;
        }
        .file-icon-modal a {
            text-decoration: none;
            color: inherit;
        }

        /* ----- CSS BARU/MODIFIKASI UNTUK THUMBNAIL DI TABEL ----- */
        .thumbnail-lampiran {
            max-width: 70px; /* Ukuran maksimal thumbnail */
            max-height: 70px; /* Ukuran maksimal thumbnail */
            width: auto;
            height: auto;
            display: block; /* Agar gambar tidak inline dan bisa diatur margin */
            margin: 0 auto; /* Tengah gambar */
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            object-fit: cover; /* Memastikan gambar mengisi area tanpa terdistorsi */
        }

        .file-icon-table {
            font-size: 1.8em; /* Ukuran ikon sedikit lebih besar */
            color: var(--primary-blue);
            display: block; /* Agar ikon berada di tengah sel dan bisa diatur margin */
            margin: 0 auto;
            text-align: center;
        }
        /* Tambahan untuk teks di bawah ikon */
        .file-icon-table + br + span {
            font-size: 0.8em;
            display: block;
            text-align: center;
            color: var(--subtitle-color);
        }
        /* ----------------------------------------------------- */

        /* Gaya untuk tombol hapus */
        .btn-delete {
            background-color: var(--status-ditolak); /* Merah untuk hapus */
            color: var(--text-light);
        }
        .btn-delete:hover {
            background-color: #BD2130; /* Merah sedikit lebih gelap saat hover */
            color: var(--text-light);
        }

        /* Gaya untuk history komentar */
        .comment-history-item {
            border-bottom: 1px dashed #e0e0e0;
            padding-bottom: 5px;
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        .comment-history-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .comment-history-item strong {
            color: var(--text-dark); /* Menggunakan warna teks standar, bukan primary-blue */
        }
        .comment-history-item span {
            color: var(--subtitle-color);
        }

        /* Gaya khusus untuk kolom aksi agar tidak kebawah */
        .table th:last-child,
        .table td:last-child {
            white-space: nowrap; /* Mencegah teks/konten di kolom ini melengkung */
            width: 150px; /* Lebar minimum yang cukup untuk 2 tombol (misal 70px * 2 + padding) */
            text-align: center; /* Opsional: Pusatkan tombol */
        }
        .table td:last-child .btn {
            margin: 2px; /* Sedikit jarak antar tombol */
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
                        <a class="nav-link active" aria-current="page" href="kelola_pengaduan.php">Kelola Pengaduan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_admin.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola_pengguna_view.php">Kelola Pengguna</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cetak_laporan_pengaduan.php">Laporan</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="logout-btn">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container container-custom">
        <h2>Daftar Pengaduan/Saran</h2>

        <?php if (!empty($popup_message)): ?>
            <div class="alert alert-<?php echo $popup_type; ?> alert-dismissible fade show alert-fixed" role="alert">
                <?php echo htmlspecialchars($popup_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($pengaduans)): ?>
            <div class="alert alert-info text-center" role="alert">
                Belum ada pengaduan/saran yang masuk.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Pengirim</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Judul</th>
                            <th scope="col">Status</th>
                            <th scope="col">Tanggal Edit</th>
                            <th scope="col">Tanggal Dibuat</th>
                            <th scope="col">Lampiran</th>
                            <th scope="col">Komentar Admin</th>
                            <th scope="col">Aksi</th> </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($pengaduans as $pengaduan): ?>
                        <tr>
                            <th scope="row"><?php echo $no++; ?></th>
                            <td><?php echo htmlspecialchars($pengaduan['username'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($pengaduan['kategori'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($pengaduan['judul'] ?? ''); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                $status_display = $pengaduan['status'] ?? 'unknown';
                                switch ($status_display) {
                                    case 'pending': $status_class = 'status-pending'; break;
                                    case 'diproses': $status_class = 'status-diproses'; break;
                                    case 'selesai': $status_class = 'status-selesai'; break;
                                    case 'ditolak': $status_class = 'status-ditolak'; break;
                                    default: $status_class = ''; break;
                                }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst(htmlspecialchars($status_display)); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y H:i', strtotime($pengaduan['updated_at'] ?? $pengaduan['created_at'] ?? 'now')); ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($pengaduan['created_at'] ?? 'now')); ?></td>
                            <td>
                                <?php
                                $lampiran_path_table = htmlspecialchars($pengaduan['lampiran_path'] ?? '');
                                if (!empty($lampiran_path_table)) {
                                    $fileExtensionTable = pathinfo($lampiran_path_table, PATHINFO_EXTENSION);
                                    $fullPathTable = 'uploads/' . $lampiran_path_table;
                                    if (file_exists($fullPathTable)) {
                                        if (in_array(strtolower($fileExtensionTable), ['jpg', 'jpeg', 'png', 'gif'])) {
                                            echo '<a href="' . $fullPathTable . '" target="_blank" title="Lihat Foto">';
                                            echo '<img src="' . $fullPathTable . '" alt="Lampiran Foto" class="thumbnail-lampiran">';
                                            echo '</a>';
                                        } elseif (strtolower($fileExtensionTable) === 'pdf') {
                                            echo '<a href="' . $fullPathTable . '" target="_blank" title="Lihat PDF"><i class="fas fa-file-pdf file-icon-table"></i><br>PDF</a>';
                                        } else {
                                            echo '<a href="' . $fullPathTable . '" target="_blank" title="Lihat Lampiran"><i class="fas fa-file file-icon-table"></i><br>File</a>';
                                        }
                                    } else {
                                        echo 'File tidak ditemukan';
                                    }
                                } else {
                                    echo 'Tidak ada';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                // Ambil komentar terbaru untuk ditampilkan di tabel utama
                                $latest_comment = 'Belum ada komentar.';
                                if (!empty($pengaduan['komentar_admin_history'])) {
                                    $history = json_decode($pengaduan['komentar_admin_history'], true);
                                    if (is_array($history) && !empty($history)) {
                                        $last_entry = end($history);
                                        $latest_comment = htmlspecialchars($last_entry['comment']);
                                    }
                                } else if (!empty($pengaduan['komentar_admin'])) {
                                    // Fallback for old 'komentar_admin' if history is empty
                                    $latest_comment = htmlspecialchars($pengaduan['komentar_admin']);
                                }
                                echo nl2br($latest_comment);
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-detail"
                                    data-bs-toggle="modal" data-bs-target="#detailModal"
                                    data-id="<?php echo htmlspecialchars($pengaduan['id'] ?? ''); ?>"
                                    data-username="<?php echo htmlspecialchars($pengaduan['username'] ?? ''); ?>"
                                    data-kategori="<?php echo htmlspecialchars($pengaduan['kategori'] ?? ''); ?>"
                                    data-judul="<?php echo htmlspecialchars($pengaduan['judul'] ?? ''); ?>"
                                    data-deskripsi="<?php echo htmlspecialchars($pengaduan['deskripsi'] ?? ''); ?>"
                                    data-status="<?php echo htmlspecialchars($pengaduan['status'] ?? ''); ?>"
                                    data-created_at="<?php echo date('d M Y H:i', strtotime($pengaduan['created_at'] ?? 'now')); ?>"
                                    data-updated_at="<?php echo date('d M Y H:i', strtotime($pengaduan['updated_at'] ?? $pengaduan['created_at'] ?? 'now')); ?>"
                                    data-lampiran_path="<?php echo htmlspecialchars($pengaduan['lampiran_path'] ?? ''); ?>"
                                    data-komentar_admin_history='<?php echo htmlspecialchars($pengaduan['komentar_admin_history'] ?? '[]'); ?>'
                                    > Edit
                                </button>
                                <button type="button" class="btn btn-sm btn-delete"
                                    data-bs-toggle="modal" data-bs-target="#deleteConfirmModal"
                                    data-id="<?php echo htmlspecialchars($pengaduan['id'] ?? ''); ?>"> Hapus
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">Edit Pengaduan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>ID:</strong> <span id="modalId"></span></p>
                    <p><strong>Pengirim:</strong> <span id="modalUsername"></span></p>
                    <p><strong>Kategori:</strong> <span id="modalKategori"></span></p>
                    <p><strong>Judul:</strong> <span id="modalJudul"></span></p>
                    <p><strong>Deskripsi:</strong> <span id="modalDeskripsi"></span></p>
                    <p><strong>Status:</strong> <span id="modalStatus" class="status-badge"></span></p>
                    <p><strong>Tanggal Diajukan:</strong> <span id="modalCreatedAt"></span></p>
                    <p><strong>Tanggal Edit Terakhir:</strong> <span id="modalUpdatedAt"></span></p>
                    <div id="modalLampiranContainer" style="display: none;">
                        <p><strong>Lampiran:</strong></p>
                        <div id="modalLampiranContent">
                        </div>
                    </div>
                    
                    <div id="modalKomentarAdminHistoryContainer" style="margin-top: 15px;">
                        <p><strong>Riwayat Komentar Admin:</strong></p>
                        <div id="modalKomentarAdminHistory">
                            <p>Belum ada riwayat komentar.</p>
                        </div>
                    </div>

                    <hr>
                    <h5>Tindak Lanjuti Pengaduan</h5>
                    <form action="process_tindak_lanjut.php" method="post" enctype="multipart/form-upload">
                        <input type="hidden" name="pengaduan_id" id="modalPengaduanId">
                        <div class="mb-3">
                            <label for="action_status" class="form-label">Ubah Status:</label>
                            <select class="form-select" id="action_status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="diproses">Diproses</option>
                                <option value="selesai">Selesai</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>

                        <div id="uploadLampiranContainer" class="mb-3" style="display: none;">
                            <label for="new_lampiran" class="form-label">Ganti Lampiran (Hanya untuk status 'Selesai'):</label>
                            <input type="file" class="form-control" id="new_lampiran" name="new_lampiran" accept="image/*, application/pdf">
                            <small class="form-text text-muted">Unggah foto atau PDF baru untuk mengganti lampiran yang sudah ada.</small>
                        </div>

                        <div class="mb-3">
                            <label for="komentar_admin_input" class="form-label">Komentar/Tindakan Admin (Opsional):</label>
                            <textarea class="form-control" id="komentar_admin_input" name="komentar_admin" rows="3"></textarea>
                            <small class="form-text text-muted">Komentar ini akan ditambahkan ke riwayat.</small>
                        </div>
                        <button type="submit" class="btn btn-submit" id="submitActionButton">Simpan Tindakan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Konfirmasi Hapus Pengaduan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus pengaduan dengan ID: <strong id="deletePengaduanId"></strong>?
                    Tindakan ini tidak dapat dibatalkan.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form action="process_delete_pengaduan.php" method="post" style="display: inline;">
                        <input type="hidden" name="pengaduan_id" id="confirmDeletePengaduanId">
                        <button type="submit" class="btn btn-danger">Hapus Sekarang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // JavaScript untuk mengisi data ke modal saat tombol detail diklik
        const detailModal = document.getElementById('detailModal');
        detailModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget; // Tombol yang mengaktifkan modal

            // Ambil data dari atribut data-* tombol
            const id = button.getAttribute('data-id');
            const username = button.getAttribute('data-username');
            const kategori = button.getAttribute('data-kategori');
            const judul = button.getAttribute('data-judul');
            const deskripsi = button.getAttribute('data-deskripsi');
            const status = button.getAttribute('data-status');
            const createdAt = button.getAttribute('data-created_at');
            const updatedAt = button.getAttribute('data-updated_at');
            const lampiranPath = button.getAttribute('data-lampiran_path');
            // Ambil riwayat komentar admin
            const komentarAdminHistoryJson = button.getAttribute('data-komentar_admin_history');
            let komentarAdminHistory = [];
            try {
                komentarAdminHistory = JSON.parse(komentarAdminHistoryJson);
            } catch (e) {
                console.error("Error parsing JSON for komentar_admin_history:", e);
                komentarAdminHistory = [];
            }

            // Perbarui konten modal
            const modalTitle = detailModal.querySelector('.modal-title');
            const modalId = detailModal.querySelector('#modalId');
            const modalUsername = detailModal.querySelector('#modalUsername');
            const modalKategori = detailModal.querySelector('#modalKategori');
            const modalJudul = detailModal.querySelector('#modalJudul');
            const modalDeskripsi = detailModal.querySelector('#modalDeskripsi');
            const modalStatus = detailModal.querySelector('#modalStatus');
            const modalCreatedAt = detailModal.querySelector('#modalCreatedAt');
            const modalUpdatedAt = detailModal.querySelector('#modalUpdatedAt');
            const modalPengaduanId = detailModal.querySelector('#modalPengaduanId');
            const actionStatus = detailModal.querySelector('#action_status');
            const komentarAdminInput = detailModal.querySelector('#komentar_admin_input');
            const modalKomentarAdminHistoryDisplay = detailModal.querySelector('#modalKomentarAdminHistory');
            const submitActionButton = detailModal.querySelector('#submitActionButton');


            // Elemen untuk lampiran dan input upload file
            const modalLampiranContainer = detailModal.querySelector('#modalLampiranContainer');
            const modalLampiranContent = detailModal.querySelector('#modalLampiranContent');
            const uploadLampiranContainer = detailModal.querySelector('#uploadLampiranContainer');
            const newLampiranInput = detailModal.querySelector('#new_lampiran');


            modalTitle.textContent = `Detail Pengaduan #${id}`;
            modalId.textContent = id;
            modalUsername.textContent = username;
            modalKategori.textContent = kategori;
            modalJudul.textContent = judul;
            modalDeskripsi.textContent = deskripsi;
            modalCreatedAt.textContent = createdAt;
            modalUpdatedAt.textContent = updatedAt;
            modalPengaduanId.value = id;

            // Set status dan class badge
            modalStatus.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            modalStatus.className = 'status-badge'; // Reset class
            switch (status) {
                case 'pending': modalStatus.classList.add('status-pending'); break;
                case 'diproses': modalStatus.classList.add('status-diproses'); break;
                case 'selesai': modalStatus.classList.add('status-selesai'); break;
                case 'ditolak': modalStatus.classList.add('status-ditolak'); break;
            }

            // Atur nilai dropdown status
            actionStatus.value = status;
            
            // Tampilkan atau sembunyikan input upload lampiran berdasarkan status
            if (status === 'selesai') {
                uploadLampiranContainer.style.display = 'block';
                newLampiranInput.setAttribute('required', 'required'); // Lampiran baru wajib jika status selesai
            } else {
                uploadLampiranContainer.style.display = 'none';
                newLampiranInput.removeAttribute('required');
                newLampiranInput.value = ''; // Kosongkan input file saat tidak diperlukan
            }

            // Tampilkan lampiran di modal
            if (lampiranPath) {
                modalLampiranContainer.style.display = 'block';
                modalLampiranContent.innerHTML = ''; // Bersihkan konten sebelumnya
                const fileExtension = lampiranPath.split('.').pop().toLowerCase();
                const fullPath = 'uploads/' + lampiranPath;

                let lampiranHtml = '';
                if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
                    lampiranHtml = `<a href="${fullPath}" target="_blank"><img src="${fullPath}" alt="Lampiran Foto" class="modal-image"></a>`;
                } else if (fileExtension === 'pdf') {
                    lampiranHtml = `<a href="${fullPath}" target="_blank"><i class="fas fa-file-pdf file-icon-modal"></i></a><p class="text-center"><small>Klik ikon untuk melihat PDF</small></p>`;
                } else {
                    lampiranHtml = `<a href="${fullPath}" target="_blank"><i class="fas fa-file file-icon-modal"></i></a><p class="text-center"><small>Klik ikon untuk mengunduh file</small></p>`;
                }
                modalLampiranContent.innerHTML = lampiranHtml;
            } else {
                modalLampiranContainer.style.display = 'none';
                modalLampiranContent.innerHTML = '<p>Tidak ada lampiran.</p>';
            }

            // Tampilkan riwayat komentar admin
            modalKomentarAdminHistoryDisplay.innerHTML = ''; // Bersihkan konten sebelumnya
            if (komentarAdminHistory && komentarAdminHistory.length > 0) {
                komentarAdminHistory.forEach(item => {
                    const commentElement = document.createElement('div');
                    commentElement.classList.add('comment-history-item');
                    commentElement.innerHTML = `<strong>${item.date} (${item.status_old} &rarr; ${item.status_new}):</strong><br><span>${item.comment}</span>`;
                    modalKomentarAdminHistoryDisplay.appendChild(commentElement);
                });
            } else {
                modalKomentarAdminHistoryDisplay.innerHTML = '<p>Belum ada riwayat komentar.</p>';
            }

            // Kosongkan komentar admin input setiap kali modal dibuka
            komentarAdminInput.value = '';
        });

        // JavaScript untuk mengisi data ke modal konfirmasi hapus
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');
        deleteConfirmModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget; // Tombol yang mengaktifkan modal
            const id = button.getAttribute('data-id'); // Ambil ID dari tombol

            const modalBodyId = deleteConfirmModal.querySelector('#deletePengaduanId');
            const confirmInputId = deleteConfirmModal.querySelector('#confirmDeletePengaduanId');

            modalBodyId.textContent = id; // Tampilkan ID di dalam pesan modal
            confirmInputId.value = id; // Set nilai input hidden untuk form
        });

        // Auto-hide alert messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alertElement = document.querySelector('.alert-fixed');
            if (alertElement) {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alertElement);
                    bsAlert.close();
                }, 5000); // 5000 milidetik = 5 detik
            }
        });

    </script>
</body>
</html>