<?php
// config.php

// 1. **PENTING:** Atur Zona Waktu PHP di awal skrip
// Ini memastikan semua fungsi tanggal/waktu PHP menggunakan zona waktu WIB
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'suara_kita'); // Nama database yang sudah kita buat

// Inisialisasi koneksi MySQLi
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// --- Tambahkan ini untuk mengatur zona waktu MySQL untuk sesi ini ---
// Pastikan koneksi berhasil sebelum menjalankan query ini
// Ini akan memastikan MySQL menginterpretasikan dan mengembalikan waktu dalam WIB
$conn->query("SET time_zone = '+07:00'"); // Untuk WIB (GMT+7)

// Untuk memulai sesi PHP (penting untuk menyimpan status login)
if (session_status() == PHP_SESSION_NONE) { // Pastikan session belum dimulai
    session_start();
}
?>