<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkAdmin(); // Pastikan hanya admin yang bisa mengakses halaman ini

// Ambil data user berdasarkan ID
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$query = "SELECT id, nama, email, role FROM users WHERE id = $user_id";
$result = $conn->query($query);
if (!$result || $result->num_rows === 0) {
    die("User tidak ditemukan.");
}
$user = $result->fetch_assoc();

// Proses form edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $conn->real_escape_string($_POST['nama']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);

    // Update data user
    $update_query = "UPDATE users SET nama = '$nama', email = '$email', role = '$role' WHERE id = $user_id";
    if ($conn->query($update_query)) {
        header("Location: kelola_user.php");
        exit();
    } else {
        $error = "Gagal mengupdate user: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-label {
            font-weight: 500;
            color: #34495e;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #3498db;
            padding: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            border-color: #1abc9c;
            box-shadow: 0 0 8px rgba(26, 188, 156, 0.3);
            outline: none;
        }

        .btn-primary {
            background-color: #3498db;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #7f8c8d;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: #95a5a6;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit User</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama</label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="magang" <?= $user['role'] === 'magang' ? 'selected' : '' ?>>Magang</option>
                </select>
            </div>
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="kelola_user.php" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>