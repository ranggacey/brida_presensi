<?php
date_default_timezone_set('Asia/Jakarta');

$host = "localhost";  // Server database (biasanya localhost)
$user = "root";       // Username database (default: root)
$pass = "";           // Password database (default: kosong di XAMPP)
$dbname = "contoh"; // Nama database

// Membuat koneksi ke database
$conn = new mysqli($host, $user, $pass, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
