<?php
include 'config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header('location: index.php');
    exit;
}

$popup_message = '';
$popup_type = '';

if (isset($_SESSION['pengaduan_success'])) {
    $popup_message = $_SESSION['pengaduan_success'];
    $popup_type = 'success';
    unset($_SESSION['pengaduan_success']);
} elseif (isset($_SESSION['pengaduan_error'])) {
    $popup_message = $_SESSION['pengaduan_error'];
    $popup_type = 'danger';
    unset($_SESSION['pengaduan_error']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Pengaduan/Saran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-blue: #007bff; /* Warna biru yang lebih cerah dari pengaduan_siswa.php */
            --dark-blue: #0056b3;
            --light-blue: #e0f2ff; /* Digunakan untuk hover dan active link */

            --danger-red: #dc3545;
            --success-green: #28a745;
            --text-dark: #343a40;
            --text-light: #FFFFFF;
            --card-bg: #FFFFFF;
            --input-border: #ced4da;
            --input-focus-border: var(--primary-blue); /* Menggunakan primary-blue */
            --body-bg-gradient: linear-gradient(135deg, #e0f2f7, #c1e4f4);
            --font-poppins: 'Poppins', sans-serif;
            --font-open-sans: 'Open Sans', sans-serif;
        }

        body {
            font-family: var(--font-open-sans);
            background: var(--body-bg-gradient);
            background-attachment: fixed;
            color: var(--text-dark);
            margin: 0;
            padding-top: 0;
            padding-bottom: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
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
            background-color: var(--primary-blue); /* Disamakan dengan pengaduan_siswa.php */
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
            color: var(--light-blue) !important; /* Disamakan dengan pengaduan_siswa.php */
        }
        .navbar-custom .nav-item .nav-link.active {
            color: var(--light-blue) !important; /* Disamakan dengan pengaduan_siswa.php */
            font-weight: 600;
            border-bottom: 2px solid var(--light-blue); /* Disamakan dengan pengaduan_siswa.php */
            padding-bottom: 5px;
        }

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
            background-color: #bd2130;
            transform: translateY(-1px);
        }

        /* Container utama untuk konten halaman */
        .container-main {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 15px;
        }

        .card-custom {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 700px;
            text-align: center;
        }
        h2 {
            font-family: var(--font-poppins);
            margin-bottom: 30px;
            color: var(--primary-blue); /* Menggunakan primary-blue */
            font-weight: 700;
            font-size: 2.2rem;
            line-height: 1.2;
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid var(--input-border);
            font-size: 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25); /* Menggunakan rgba dari primary-blue */
        }
        /* Style for file input to match other inputs */
        .form-control[type="file"] {
            padding-top: 10px; /* Adjust padding for file input */
            padding-bottom: 10px;
        }

        .btn-submit {
            background-color: var(--primary-blue); /* Menggunakan primary-blue */
            color: var(--text-light);
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-submit:hover {
            background-color: var(--dark-blue); /* Menggunakan dark-blue */
            transform: translateY(-1px);
        }

        .form-text {
            color: #6c757d;
            font-size: 0.875em;
            text-align: left;
            margin-top: 5px;
        }

        /* Gaya untuk pesan peringatan wajib */
        .form-text.required-note {
            color: var(--danger-red); /* Merah untuk pesan wajib */
            font-weight: 600;
        }

        .alert-fixed {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
            min-width: 300px;
            max-width: 90%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border-radius: 8px;
        }
    </style>
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
                        <a class="nav-link active" aria-current="page" href="form_pengaduan.php">Ajukan Pengaduan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pengaduan_siswa.php">Cek Status Pengaduan</a>
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
            <h2>Ajukan Pengaduan atau Saran</h2>
            <form action="process_pengaduan.php" method="post" enctype="multipart/form-data" id="pengaduanForm">
                <div class="mb-3">
                    <label for="kategori" class="form-label">Kategori:</label>
                    <select class="form-select" id="kategori" name="kategori" required>
                        <option value="">Pilih Kategori</option>
                        <option value="Kebersihan">Kebersihan *</option>
                        <option value="Fasilitas">Fasilitas *</option>
                        <option value="Keamanan">Keamanan</option>
                        <option value="Perilaku">Perilaku Siswa/Guru</option>
                        <option value="Lain-lain">Lain-lain</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="judul" class="form-label">Judul Pengaduan/Saran:</label>
                    <input type="text" class="form-control" id="judul" name="judul" required>
                </div>
                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi Lengkap:</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="6" required></textarea>
                </div>
                <div class="mb-4">
                    <label for="lampiran" class="form-label">Lampiran (Foto/Dokumen):</label>
                    <input type="file" class="form-control" id="lampiran" name="lampiran">
                    <div class="form-text" id="lampiranHelpText">Ukuran file maksimal 2MB. Format: JPG, PNG, PDF.</div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-submit">Kirim Pengaduan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
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

        // JavaScript validation and dynamic help text for file attachment on "Fasilitas" and "Kebersihan" categories
        document.addEventListener('DOMContentLoaded', function() {
            const kategoriSelect = document.getElementById('kategori');
            const lampiranInput = document.getElementById('lampiran');
            const lampiranHelpText = document.getElementById('lampiranHelpText');
            const pengaduanForm = document.getElementById('pengaduanForm');

            function updateLampiranRequirement() {
                const selectedKategori = kategoriSelect.value;
                if (selectedKategori === 'Kebersihan' || selectedKategori === 'Fasilitas') {
                    lampiranInput.setAttribute('required', 'required');
                    lampiranHelpText.innerHTML = 'Lampiran foto/dokumen **wajib** disertakan untuk kategori ini. Ukuran file maksimal 2MB. Format: JPG, PNG, PDF.';
                    lampiranHelpText.classList.add('required-note');
                } else {
                    lampiranInput.removeAttribute('required');
                    lampiranHelpText.innerHTML = 'Ukuran file maksimal 2MB. Format: JPG, PNG, PDF.';
                    lampiranHelpText.classList.remove('required-note');
                }
            }

            // Panggil fungsi saat halaman dimuat (untuk default value atau jika form di-reload dengan pilihan sebelumnya)
            updateLampiranRequirement();

            // Panggil fungsi setiap kali pilihan kategori berubah
            kategoriSelect.addEventListener('change', updateLampiranRequirement);

            // Validasi saat form disubmit (tambahan untuk memastikan browser support required attribute)
            pengaduanForm.addEventListener('submit', function(event) {
                const kategori = kategoriSelect.value;
                // Cek hanya jika input lampiran memang diatur required oleh JS
                if (lampiranInput.hasAttribute('required') && lampiranInput.files.length === 0) {
                    event.preventDefault(); // Mencegah form submit
                    const errorMessage = 'Untuk kategori "' + kategori + '", lampiran foto/dokumen wajib disertakan.';

                    // Tampilkan pesan error menggunakan Bootstrap Alert
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-danger alert-fixed';
                    errorDiv.setAttribute('role', 'alert');
                    errorDiv.innerHTML = errorMessage +
                                         '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    document.body.appendChild(errorDiv);

                    // Auto-hide the error message after a few seconds
                    setTimeout(() => {
                        const bsAlert = bootstrap.Alert.getInstance(errorDiv) || new bootstrap.Alert(errorDiv);
                        bsAlert.close();
                    }, 5000);
                }
            });
        });
    </script>
</body>
</html>