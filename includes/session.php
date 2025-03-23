<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login
function checkLogin() {
    if (!isset($_SESSION["user_id"])) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Fungsi untuk memastikan hanya admin yang bisa mengakses halaman tertentu
function checkAdmin() {
    checkLogin(); // Pastikan user sudah login
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../user/dashboard.php"); // Redirect ke dashboard user jika bukan admin
        exit();
    }
}

// Fungsi untuk mendapatkan nama pengguna yang sedang login
function getUserName() {
    return $_SESSION["user_name"] ?? "Guest";
}

// Fungsi untuk mendapatkan ID pengguna yang sedang login
function getUserId() {
    return $_SESSION["user_id"] ?? null;
}

// Fungsi untuk mendapatkan peran pengguna
function getUserRole() {
    return $_SESSION["role"] ?? "user"; // Default "user" jika tidak ada
}
?>
