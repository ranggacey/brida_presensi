<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkLogin();

// Ambil data user dari session
$user_id = $_SESSION['user_id'];

// Query data user dengan join tabel attendance
$sql_user = "SELECT u.*, a.date, a.waktu_absen, a.waktu_pulang 
            FROM users u
            LEFT JOIN attendance a ON u.id = a.user_id AND a.date = CURDATE()
            WHERE u.id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    $user = $result_user->fetch_assoc();
    $user_name = $user['nama'];
    $user_foto = $user['foto'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($user_name);
    $universitas = $user['universitas'];
    $sudah_presensi_masuk = !empty($user['waktu_absen']);
    $sudah_presensi_pulang = !empty($user['waktu_pulang']);
} else {
    die("User tidak ditemukan");
}

// Proses form update identitas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan sanitasi input
    $ttl = htmlspecialchars(trim($_POST['ttl']));
    $alamat = htmlspecialchars(trim($_POST['alamat']));
    // Gunakan variabel baru untuk input universitas agar tidak tertimpa nilai awal
    $universitas_input = htmlspecialchars(trim($_POST['universitas']));
    $fakultas = htmlspecialchars(trim($_POST['fakultas']));
    $prodi = htmlspecialchars(trim($_POST['prodi']));
    $tgl_masuk_magang = htmlspecialchars(trim($_POST['tgl_masuk_magang']));
    $no_hp = htmlspecialchars(trim($_POST['no_hp']));

    // Proses upload foto profil
    $foto = $user['foto']; // Default: foto lama
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/users/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = basename($_FILES['foto']['name']);
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_ext)) {
            // Hapus foto lama jika ada
            if (!empty($user['foto']) && file_exists($upload_dir . $user['foto'])) {
                unlink($upload_dir . $user['foto']);
            }

            // Buat nama file baru: user_<id>.jpg (selalu disimpan sebagai JPG)
            $new_file_name = "user_{$user_id}.jpg";
            $destination = $upload_dir . $new_file_name;

            // Konversi file ke format JPG jika perlu
            if ($file_ext !== 'jpg') {
                $image = imagecreatefromstring(file_get_contents($file_tmp));
                imagejpeg($image, $destination, 90); // simpan sebagai JPG dengan kualitas 90%
                imagedestroy($image);
            } else {
                move_uploaded_file($file_tmp, $destination);
            }
            $foto = $new_file_name;
        } else {
            $error = "Format file tidak didukung. Hanya JPG, JPEG, dan PNG yang diperbolehkan.";
        }
    }

    // Proses upload surat permohonan magang (opsional)
    $surat_permohonan = $user['surat_permohonan']; // Default: surat lama
    if (isset($_FILES['surat_permohonan']) && $_FILES['surat_permohonan']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../uploads/surat_permohonan/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = basename($_FILES['surat_permohonan']['name']);
        $file_tmp = $_FILES['surat_permohonan']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'doc', 'docx'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = "surat_permohonan_{$user_id}_" . time() . ".{$file_ext}";
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                // Hapus surat lama jika ada
                if (!empty($user['surat_permohonan']) && file_exists($upload_dir . $user['surat_permohonan'])) {
                    unlink($upload_dir . $user['surat_permohonan']);
                }
                $surat_permohonan = $new_file_name;
            } else {
                $error = "Gagal mengunggah surat permohonan.";
            }
        } else {
            $error = "Format file tidak didukung. Hanya PDF, DOC, dan DOCX yang diperbolehkan.";
        }
    }

    // Update data user
    $update_query = "UPDATE users SET ttl = ?, alamat = ?, universitas = ?, fakultas = ?, prodi = ?, tgl_masuk_magang = ?, no_hp = ?, foto = ?, surat_permohonan = ?, is_identity_updated = TRUE WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssssssssi", $ttl, $alamat, $universitas_input, $fakultas, $prodi, $tgl_masuk_magang, $no_hp, $foto, $surat_permohonan, $user_id);

    if ($stmt->execute()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'status'   => 'success',
                'message'  => 'Data identitas berhasil diperbarui!',
                'redirect' => 'dashboard.php'
            ]);
            exit;
        } else {
            echo "<script>alert('Data identitas berhasil diperbarui!'); window.location.href='dashboard.php';</script>";
            exit;
        }
    } else {
        $error = "Gagal memperbarui data: " . $conn->error;
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit;
        } else {
            echo "<script>alert('{$error}');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Identitas</title>
    <!-- Premium Assets -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="identitas.css">
    <script src="side.js"></script>
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            box-sizing: border-box;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        .file-upload {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .file-upload input[type="file"] {
            display: none;
        }
        .upload-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .upload-content img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .presensi-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .presensi-btn:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        /* Quantum Loader Styles */
        .premium-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 10, 21, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(10px);
        }
        .quantum-loader {
            position: relative;
            width: 200px;
            height: 200px;
        }
        .hyper-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(
                #7d12ff 0%,
                #10d7e2 25%,
                #7d12ff 50%,
                #10d7e2 75%,
                #7d12ff 100%
            );
            animation: hyper-spin 1.8s linear infinite;
            filter: url(#goo);
        }
        .hyper-ring::after {
            content: '';
            position: absolute;
            inset: 12px;
            background: #0a0a15;
            border-radius: 50%;
        }
        .core-pulse {
            position: absolute;
            width: 30%;
            height: 30%;
            background: #10d7e2;
            border-radius: 50%;
            top: 35%;
            left: 35%;
            filter: blur(15px);
            animation: pulse 2s ease-in-out infinite;
        }
        .quantum-dots {
            position: absolute;
            width: 100%;
            height: 100%;
        }
        .quantum-dot {
            position: absolute;
            width: 8px;
            height: 8px;
            background: #7d12ff;
            border-radius: 50%;
            filter: blur(2px);
            animation: quantum 3s infinite ease-in-out;
        }
        @keyframes hyper-spin {
            0% { transform: rotate(0deg); opacity: 1; }
            50% { opacity: 0.8; }
            100% { transform: rotate(360deg); opacity: 1; }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 0.4; }
        }
        @keyframes quantum {
            0%, 100% {
                transform: rotate(0deg) translate(80px) rotate(0deg);
                opacity: 0;
            }
            30% { opacity: 1; }
            70% { background: #10d7e2; }
            100% {
                transform: rotate(360deg) translate(80px) rotate(-360deg);
                opacity: 0;
            }
        }
        .loading-text {
            position: absolute;
            bottom: -55px;
            width: 100%;
            text-align: center;
            color: #10d7e2;
            font-size: 1.2em;
            letter-spacing: 4px;
            text-transform: uppercase;
            animation: text-glow 2s ease-in-out infinite;
        }
        @keyframes text-glow {
            0%, 100% { text-shadow: 0 0 10px #7d12ff; }
            50% { text-shadow: 0 0 20px #10d7e2; }
        }
    </style>
</head>
<body>
    <!-- Quantum Loader -->
    <div class="premium-loader" id="loader">
        <div class="quantum-loader">
            <div class="hyper-ring"></div>
            <div class="core-pulse"></div>
            <div class="quantum-dots">
                <div class="quantum-dot" style="animation-delay: 0s"></div>
                <div class="quantum-dot" style="animation-delay: 0.3s"></div>
                <div class="quantum-dot" style="animation-delay: 0.6s"></div>
                <div class="quantum-dot" style="animation-delay: 0.9s"></div>
            </div>
            <div class="loading-text">Menyimpan Data</div>
        </div>
    </div>
    <!-- Hamburger Menu -->
    <button class="hamburger" id="hamburger">
        <i class="fas fa-bars"></i>
    </button>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../uploads/users/user_<?= $user_id ?>.jpg" alt="Profile" class="profile-img">
            <div>
                <h3 style="color: white; margin-bottom: 4px;"><?= htmlspecialchars($user_name) ?></h3>
                <p style="color: #94a3b8; font-size: 0.9rem;"><?= htmlspecialchars($universitas) ?></p>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="update_identitas.php" class="nav-link active">
                    <i class="fas fa-fingerprint"></i>
                    <span>Presensi</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="riwayat.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    <span>Riwayat</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
    <!-- Overlay Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="form-container" data-aos="zoom-in">
            <h2 style="text-align: center;">Update Identitas</h2>
            <?php if (isset($error)): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <!-- Upload Foto Profil -->
                <div class="form-group">
                    <label class="file-upload">
                        <input type="file" name="foto" accept="image/*">
                        <div class="upload-content">
                            <img src="../uploads/users/user_<?= $user_id ?>.jpg?<?= time() ?>" alt="Foto Profil">
                            <span>Ubah Foto Profil</span>
                        </div>
                    </label>
                </div>
                <!-- Form Fields -->
                <div class="form-grid">
                    <div class="form-group">
                        <label>TTL</label>
                        <input type="text" name="ttl" value="<?= htmlspecialchars($user['ttl']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" required><?= htmlspecialchars($user['alamat']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Universitas</label>
                        <input type="text" name="universitas" value="<?= htmlspecialchars($user['universitas']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Fakultas</label>
                        <input type="text" name="fakultas" value="<?= htmlspecialchars($user['fakultas']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Program Studi</label>
                        <input type="text" name="prodi" value="<?= htmlspecialchars($user['prodi']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Mulai Magang</label>
                        <input type="date" name="tgl_masuk_magang" value="<?= htmlspecialchars($user['tgl_masuk_magang']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nomor HP</label>
                        <input type="tel" name="no_hp" value="<?= htmlspecialchars($user['no_hp']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Surat Permohonan</label>
                        <label class="file-upload">
                            <input type="file" name="surat_permohonan" accept=".pdf,.doc,.docx">
                            <div class="upload-content">
                                <?php if ($user['surat_permohonan']): ?>
                                    <a href="../uploads/surat_permohonan/<?= $user['surat_permohonan'] ?>" target="_blank">
                                        Lihat Surat
                                    </a>
                                <?php else: ?>
                                    <span>Upload Surat Permohonan</span>
                                <?php endif; ?>
                            </div>
                        </label>
                    </div>
                </div>
                <button type="submit" class="presensi-btn">
                    <i class="fas fa-save"></i>
                    Simpan Perubahan
                </button>
            </form>
        </div>
    </main>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <svg style="visibility: hidden;">
        <defs>
            <filter id="goo">
                <feGaussianBlur in="SourceGraphic" stdDeviation="8" result="blur" />
                <feColorMatrix in="blur" mode="matrix" values="
                    1 0 1 0 0  
                    0 1 1 0 0  
                    0 0 1 0 0  
                    0 0 0 18 -8" result="goo" />
                <feBlend in="SourceGraphic" in2="goo" />
            </filter>
        </defs>
    </svg>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });

        document.querySelector('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const loader = document.getElementById('loader');
    const form = e.target;
    try {
        // Tampilkan loader
        loader.style.display = 'flex';
        const formData = new FormData(form);
        const response = await fetch('update_identitas.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }, // Tambahkan header ini
            body: formData
        });
        const result = await response.json();
        if (result.status === 'success') {
            alert(result.message);
            window.location.href = result.redirect;
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        alert(error.message || 'Terjadi kesalahan saat mengirim data');
    } finally {
        loader.style.display = 'none';
    }
});


       

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768) {
                if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                    sidebar.classList.remove('active');
                    overlay.style.display = 'none';
                }
            }
        });

        // Preserve sidebar state on page refresh
        window.onbeforeunload = function() {
            localStorage.setItem('sidebarState', sidebar.classList.contains('active'));
        };
        window.onload = function() {
            const sidebarState = localStorage.getItem('sidebarState');
            if (sidebarState === 'true' && window.innerWidth >= 768) {
                sidebar.classList.add('active');
            }
        };
    </script>
</body>
</html>
