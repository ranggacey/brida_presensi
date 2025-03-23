<?php
require_once "config.php"; // Menghubungkan ke database

// Fungsi untuk membersihkan input dari karakter berbahaya
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim(htmlspecialchars($data)));
}

// Fungsi untuk mengecek apakah email sudah terdaftar
function emailExists($email) {
    global $conn;
    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

// Fungsi untuk memastikan hanya admin yang bisa mengakses halaman tertentu
function checkAdmin() {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit();
    }
}


// Fungsi untuk mendaftarkan pengguna baru
function registerUser($name, $email, $password) {
    global $conn;
    
    if (emailExists($email)) {
        return "Email sudah digunakan!";
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $query = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $name, $email, $hashedPassword);
    
    return $stmt->execute() ? "success" : "Pendaftaran gagal!";
}

// Fungsi untuk login
function loginUser($email, $password) {
    global $conn;
    $query = "SELECT id, name, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user["password"])) {
            session_start();
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            return "success";
        }
    }
    return "Email atau password salah!";
}

// Fungsi untuk logout
function logoutUser() {
    session_start();
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}


?>
