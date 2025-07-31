<?php

include 'config.php'; // Pastikan ini ada dan memanggil session_start()


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header('location: index.php'); // Arahkan ke halaman login
    exit; // Sangat penting untuk menghentikan eksekusi skrip
}

// Data pengguna yang sudah login (misalnya username) bisa diakses dari $_SESSION
$username_siswa = $_SESSION['username'] ?? 'Siswa'; // Tambahkan ?? 'Siswa' untuk default jika username tidak diset

// --- Di sini adalah bagian di mana Anda mulai membangun tampilan HTML dashboard siswa ---
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa | Suara Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
      :root {
            --primary-blue: #007bff; 
            --dark-blue: #0056b3;    
            --light-blue: #e0f2ff;   
            --text-dark: #343a40;    
            --text-light: #FFFFFF;
            --card-bg: #FFFFFF;
            --secondary-accent: #6c757d; 
        }

        body {
            font-family: 'Open Sans', sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative; 
            overflow: hidden; 
        }

       
        .background-blur {
            position: fixed; 
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('ifsu.jpeg'); 
            background-size: cover;
            background-position: center;
            filter: blur(8px); 
            -webkit-filter: blur(8px); 
            z-index: -1; 
            transform: scale(1.05); 
        }

        .container-custom {
            background-color: var(--card-bg);
           
            padding: 40px 50px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2); 
            max-width: 900px;
            width: 100%;
            border: 1px solid rgba(0,0,0,0.1); 
            position: relative; 
            z-index: 1; 
        }
        h2 {
            font-family: 'Poppins', sans-serif;
            margin-bottom: 30px;
            color: var(--primary-blue); 
            font-weight: 700;
            text-align: center;
            font-size: 2.5rem;
            line-height: 1.2;
        }
        .subtitle {
            font-size: 1.1rem;
            color: var(--secondary-accent); 
            text-align: center;
            margin-bottom: 40px;
        }
        .action-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            background-color: var(--primary-blue); 
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
            background-color: var(--dark-blue); 
            color: var(--text-light);
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .action-link .icon {
            font-size: 1.5rem;
        }
        .logout-btn {
            background-color: #dc3545; 
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
</head>
<body>
    <div class="background-blur"></div>
    <div class="container container-custom">
        <h2>Selamat Datang, <?php echo htmlspecialchars($username_siswa); ?>!</h2>
        <p class="subtitle">Ini adalah halaman khusus untuk Siswa. Anda dapat mengajukan pengaduan dan melacak statusnya.</p>

        <div class="row g-4 mt-5">
            <div class="col-md-6">
                <a href="form_pengaduan.php" class="action-link">
                    <span class="icon"><i class="fas fa-edit"></i></span> Kirim Pengaduan Baru
                </a>
            </div>
            <div class="col-md-6">
                <a href="pengaduan_siswa.php" class="action-link">
                    <span class="icon"><i class="fas fa-list-alt"></i></span> Cek Status Pengaduan Anda
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