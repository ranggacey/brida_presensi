<?php
session_start();
require_once '../includes/config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../user/dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Email dan password harus diisi!"]);
        exit();
    } else {
        $stmt = $conn->prepare("SELECT id, password, role, is_identity_updated FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $hashed_password, $role, $is_identity_updated);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = $role;

                if ($role == 'admin') {
                    $redirect_url = "../admin/dashboard.php";
                } else {
                    $redirect_url = !$is_identity_updated ? "../user/update_identitas.php" : "../user/dashboard.php";
                }

                echo json_encode(["status" => "success", "message" => "Login berhasil!", "redirect" => $redirect_url]);
                exit();
            } else {
                echo json_encode(["status" => "error", "message" => "Password salah!"]);
                exit();
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Email tidak ditemukan!"]);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="login.js" defer></script>
  <link rel="stylesheet" href="login.css">
</head>
<body>
  <!-- Gambar Pojok: ditempatkan terpisah dari container form -->
  <div class="corner-images">
    <img src="kiri.png" alt="Gambar Kiri" class="corner-img kiri">
    <img src="kanan.png" alt="Gambar Kanan" class="corner-img kanan">
  </div>
  <h2 class="page-title">Presensi Magang Brida Kota Semarang</h2>
  <div class="container">
    <div class="header">
    <h2>Login</h2>
    </div>
    <form id="loginForm">
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
      <input type="submit" value="Login">
    </form>
    <div class="links">
      <p>Belum punya akun? <a href="register.php">Daftar</a></p>
    </div>
  </div>

  <script>
    document.getElementById('loginForm').addEventListener('submit', function(event) {
      event.preventDefault();

      let formData = new FormData(this);

      fetch("", {
        method: "POST",
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === "success") {
          Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: data.message,
            showConfirmButton: false,
            timer: 2000
          }).then(() => {
            window.location.href = data.redirect;
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: data.message
          });
        }
      })
      .catch(error => {
        Swal.fire({
          icon: 'error',
          title: 'Terjadi Kesalahan!',
          text: 'Coba lagi nanti.'
        });
        console.error("Error:", error);
      });
    });
  </script>
</body>
</html>
