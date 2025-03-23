<?php
session_start();
require_once '../includes/config.php';

// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validasi input
        if(empty($_POST['nama']) || empty($_POST['email']) || empty($_POST['password'])) {
            throw new Exception("Semua field harus diisi!");
        }

        $nama = htmlspecialchars(trim($_POST['nama']), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Validasi format email
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid!");
        }

        // Cek email terdaftar
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        
        if($check_email->get_result()->num_rows > 0) {
            throw new Exception("Email sudah terdaftar!");
        }

        // Insert data dengan transaction
        $conn->begin_transaction();
        
        $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role, created_at) 
                               VALUES (?, ?, ?, 'magang', NOW())");
        $stmt->bind_param("sss", $nama, $email, $password);
        
        if(!$stmt->execute()) {
            throw new Exception("Gagal menyimpan data: " . $stmt->error);
        }
        
        $conn->commit();

        $response = [
            "status" => "success",
            "message" => "Registrasi berhasil! Silakan login.",
            "redirect" => "login.php"
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Registration Error: " . $e->getMessage());
        
        $response = [
            "status" => "error",
            "message" => $e->getMessage()
        ];
    }

    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="register.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- Gambar Pojok: ditempatkan terpisah dari container form -->
  <div class="corner-images">
    <img src="kiri.png" alt="Gambar Kiri" class="corner-img kiri">
    <img src="kanan.png" alt="Gambar Kanan" class="corner-img kanan">
  </div>
  <h2 class="page-title">Presensi Magang Brida Kota Semarang</h2>
    <div class="container">
        <h2>Register</h2>
        <form id="registerForm">
            <div class="inputBox">
                <input type="text" name="nama" id="nama" required>
                <span>Nama Lengkap</span>
                <i></i>
            </div>
            <div class="inputBox">
                <input type="email" name="email" id="email" required>
                <span>Email</span>
                <i></i>
            </div>
            <div class="inputBox">
                <input type="password" name="password" id="password" required>
                <span>Password</span>
                <i></i>
            </div>
            <button type="submit">Register</button>
        </form>
        <div class="links">
            <p>Sudah punya akun? <a href="login.php">Login</a></p>
        </div>
    </div>

    <script>
        document.getElementById("registerForm").addEventListener("submit", function(e) {
            e.preventDefault();

            let formData = new FormData(this);

            fetch("", { // Kirim ke halaman ini sendiri
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Berhasil!",
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Gagal!",
                        text: data.message
                    });
                }
            })
            .catch(error => console.error("Error:", error));
        });
    </script>
</body>

</html>
