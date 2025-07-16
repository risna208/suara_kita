<?php
// Konfigurasi Database
define('DB_SERVER', 'localhost'); // Ganti jika host database Anda berbeda
define('DB_USERNAME', 'root');   // Ganti dengan username database Anda
define('DB_PASSWORD', '');       // Ganti dengan password database Anda
define('DB_NAME', 'suara_kita'); // Nama database yang sudah kita buat

// Inisialisasi koneksi MySQLi
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Untuk memulai sesi PHP (penting untuk menyimpan status login)
session_start();
?>