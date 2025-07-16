<?php
include 'config.php';

// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('location: index.php');
    exit;
}

// Logika untuk mengambil data pengaduan dari database
$pengaduans = [];
// Hapus `p.lokasi` dari SELECT query
$sql = "SELECT p.id, u.username, p.kategori, p.judul, p.deskripsi, p.status, p.created_at
        FROM pengaduan p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC"; // Urutkan dari yang terbaru

$result = $conn->query($sql);

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
            --dark-blue: #0056b3;    /* Biru lebih gelap untuk hover */
            --secondary-yellow: #FFC107; /* Kuning untuk aksen */
            --dark-yellow: #E0A800;  /* Kuning lebih gelap untuk hover */
            --text-dark: #343a40;    /* Hampir hitam untuk teks */
            --text-light: #FFFFFF;
            --card-bg: #FFFFFF;
            --subtitle-color: #6c757d; /* Abu-abu untuk teks subtitle */
            --navbar-bg: #343a40; /* Navbar background dark */
            --navbar-text: #FFFFFF;
            --table-header-bg: #E9ECEF; /* Warna header tabel Bootstrap default */
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
            color: var(--text-dark);
        }
        .status-pending { background-color: var(--secondary-yellow); }
        .status-diproses { background-color: var(--primary-blue); color: var(--text-light); }
        .status-selesai { background-color: #28a745; color: white; }
        .status-ditolak { background-color: #dc3545; color: white; }

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
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="logout-btn">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container container-custom">
        <h2>Daftar Pengaduan/Saran</h2>

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
                            <th scope="col">Tanggal</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($pengaduans as $pengaduan): ?>
                        <tr>
                            <th scope="row"><?php echo $no++; ?></th>
                            <td><?php echo htmlspecialchars($pengaduan['username']); ?></td>
                            <td><?php echo htmlspecialchars($pengaduan['kategori']); ?></td>
                            <td><?php echo htmlspecialchars($pengaduan['judul']); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                switch ($pengaduan['status']) {
                                    case 'pending': $status_class = 'status-pending'; break;
                                    case 'diproses': $status_class = 'status-diproses'; break;
                                    case 'selesai': $status_class = 'status-selesai'; break;
                                    case 'ditolak': $status_class = 'status-ditolak'; break;
                                }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst(htmlspecialchars($pengaduan['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y H:i', strtotime($pengaduan['created_at'])); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-detail"
                                    data-bs-toggle="modal" data-bs-target="#detailModal"
                                    data-id="<?php echo $pengaduan['id']; ?>"
                                    data-username="<?php echo htmlspecialchars($pengaduan['username']); ?>"
                                    data-kategori="<?php echo htmlspecialchars($pengaduan['kategori']); ?>"
                                    data-judul="<?php echo htmlspecialchars($pengaduan['judul']); ?>"
                                    data-deskripsi="<?php echo htmlspecialchars($pengaduan['deskripsi']); ?>"
                                    data-status="<?php echo htmlspecialchars($pengaduan['status']); ?>"
                                    data-created_at="<?php echo date('d M Y H:i', strtotime($pengaduan['created_at'])); ?>">
                                    Detail
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
                    <h5 class="modal-title" id="detailModalLabel">Detail Pengaduan</h5>
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
                    <hr>
                    <h5>Tindak Lanjuti Pengaduan</h5>
                    <form action="process_tindak_lanjut.php" method="post">
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
                        <div class="mb-3">
                            <label for="komentar_admin" class="form-label">Komentar/Tindakan Admin (Opsional):</label>
                            <textarea class="form-control" id="komentar_admin" name="komentar_admin" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-submit">Simpan Tindakan</button>
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

            // Perbarui konten modal
            const modalTitle = detailModal.querySelector('.modal-title');
            const modalId = detailModal.querySelector('#modalId');
            const modalUsername = detailModal.querySelector('#modalUsername');
            const modalKategori = detailModal.querySelector('#modalKategori');
            const modalJudul = detailModal.querySelector('#modalJudul');
            const modalDeskripsi = detailModal.querySelector('#modalDeskripsi');
            const modalStatus = detailModal.querySelector('#modalStatus');
            const modalCreatedAt = detailModal.querySelector('#modalCreatedAt');
            const modalPengaduanId = detailModal.querySelector('#modalPengaduanId');
            const actionStatus = detailModal.querySelector('#action_status');

            modalTitle.textContent = `Detail Pengaduan #${id}`;
            modalId.textContent = id;
            modalUsername.textContent = username;
            modalKategori.textContent = kategori;
            modalJudul.textContent = judul;
            modalDeskripsi.textContent = deskripsi;
            modalCreatedAt.textContent = createdAt;
            modalPengaduanId.value = id; // Set ID untuk form tindakan

            // Set status dan class badge
            modalStatus.textContent = status.charAt(0).toUpperCase() + status.slice(1); // Kapitalisasi huruf pertama
            modalStatus.className = `status-badge status-${status}`; // Set class badge

            // Set nilai dropdown status di form tindakan
            actionStatus.value = status;
        });

        // JavaScript untuk menampilkan pop-up alert (sama seperti di halaman lain)
        <?php if (!empty($popup_message)): ?>
            const alertPlaceholder = document.createElement('div');
            alertPlaceholder.innerHTML = `
                <div class="alert alert-<?php echo $popup_type; ?> alert-dismissible fade show alert-fixed" role="alert">
                    <?php echo htmlspecialchars($popup_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            document.body.append(alertPlaceholder);

            setTimeout(() => {
                const alertElement = alertPlaceholder.querySelector('.alert');
                if (alertElement) {
                    const bsAlert = bootstrap.Alert.getInstance(alertElement) || new bootstrap.Alert(alertElement);
                    bsAlert.close();
                }
            }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>