<?php
include 'config.php';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if ($_SESSION['role'] == 'petugas') { // <--- Tambahkan redirect untuk petugas juga
        header('location: dashboard_petugas.php'); // Pastikan Anda memiliki file dashboard_petugas.php
    } elseif ($_SESSION['role'] == 'siswa') {
        header('location: dashboard_siswa.php');
    }
    exit;
}

// Inisialisasi variabel untuk pesan pop-up
$popup_message = '';
$popup_type = ''; 

// Menampilkan pesan error/sukses dari sesi
if (isset($_SESSION['login_error'])) {
    $popup_message = $_SESSION['login_error'];
    $popup_type = 'danger';
    unset($_SESSION['login_error']);
} elseif (isset($_SESSION['register_success'])) { // Jika ada pesan sukses dari registrasi
    $popup_message = $_SESSION['register_success'];
    $popup_type = 'success';
    unset($_SESSION['register_success']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem Suara Kita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-blue: #007bff; 
            --dark-blue: #0056b3;  
            --secondary-yellow: #FFC107; 
            --dark-yellow: #E0A800;   
            --text-dark: #343a40;     
            --text-light: #FFFFFF;
            --card-bg: #FFFFFF;
            --subtitle-color: #6c757d; 
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

        .login-container {
            background-color: var(--card-bg);
            padding: 40px 50px; 
            border-radius: 15px; 
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px; 
            text-align: center;
            border: 1px solid rgba(0,0,0,0.1); 
            position: relative;
            z-index: 1;
        }

        .login-container .logo-container {
            margin-bottom: 30px; 
            text-align: center;
        }

        .login-container .logo-container img {
            width: 100px; 
            height: auto;
            border-radius: 50%; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }

        .login-container h2 {
            font-family: 'Poppins', sans-serif; 
            margin-bottom: 30px;
            color: var(--primary-blue); 
            font-weight: 700;
            text-align: center;
            font-size: 2rem; 
            line-height: 1.2;
        }
        .form-control {
            border-radius: 10px; 
            padding: 12px 15px;
            border: 1px solid rgba(0,0,0,0.2); 
        }
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25); 
        }
        .btn-login {
            background-color: var(--primary-blue); 
            color: var(--text-light);
            border: none;
            border-radius: 10px; 
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease; 
        }
        .btn-login:hover {
            background-color: var(--dark-blue); 
            color: var(--text-light);
            transform: translateY(-2px); 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
        }
        .login-link {
            margin-top: 25px;
            font-size: 0.95rem;
            color: var(--subtitle-color); 
        }
        .login-link a {
            color: var(--primary-blue); 
            text-decoration: none;
            font-weight: 600; 
        }
        .login-link a:hover {
            text-decoration: underline;
            color: var(--dark-blue); 
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
    </style>
</head>
<body>
    <div class="background-blur"></div> <div class="login-container">
        <div class="logo-container">
            <img src="logo.png" alt="Logo Sekolah"> </div>
        <h2>Login Sistem Suara Kita</h2>
        <form action="login_process.php" method="post">
            <div class="mb-3">
                <label for="username" class="form-label text-start w-100">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label text-start w-100">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-login w-100">Login</button>
        </form>
        <div class="login-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        // JavaScript untuk menampilkan pop-up alert
        <?php if (!empty($popup_message)): ?>
            const alertPlaceholder = document.createElement('div');
            alertPlaceholder.innerHTML = `
                <div class="alert alert-<?php echo $popup_type; ?> alert-dismissible fade show alert-fixed" role="alert">
                    <?php echo htmlspecialchars($popup_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            document.body.append(alertPlaceholder);

            // Opsional: Sembunyikan alert setelah beberapa detik
            setTimeout(() => {
                const alertElement = alertPlaceholder.querySelector('.alert');
                if (alertElement) {
                    const bsAlert = bootstrap.Alert.getInstance(alertElement) || new bootstrap.Alert(alertElement);
                    bsAlert.close();
                }
            }, 5000); // Alert akan hilang setelah 5 detik
        <?php endif; ?>
    </script>
</body>
</html>