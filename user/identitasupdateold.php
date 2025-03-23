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
    $ttl = htmlspecialchars(trim($_POST['ttl']));
    $alamat = htmlspecialchars(trim($_POST['alamat']));
    $universitas = htmlspecialchars(trim($_POST['universitas']));
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
    $stmt->bind_param("sssssssssi", $ttl, $alamat, $universitas, $fakultas, $prodi, $tgl_masuk_magang, $no_hp, $foto, $surat_permohonan, $user_id);

    if ($stmt->execute()) {
        echo "<script>alert('Data identitas berhasil diperbarui!'); window.location.href='dashboard.php';</script>";
    } else {
        $error = "Gagal memperbarui data: " . $conn->error;
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css">
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
    </style>
    
</head>
<body>
    <!-- Hamburger Menu -->
  <button class="hamburger" id="hamburger">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
        <img 
    src="../uploads/users/user_<?= $user_id ?>.jpg?<?= time() ?>" 
    alt="Profile" 
    class="profile-img"
    id="sidebarProfileImg">
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
                <a href="absensi.php" class="nav-link active">
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

            
            <?php if(isset($error)): ?>
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
                                <?php if($user['surat_permohonan']): ?>
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
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });

        

        // Preserve scroll position on page refresh
        window.onbeforeunload = function() {
            localStorage.setItem('sidebarState', sidebar.classList.contains('active'));
        }

        window.onload = function() {
            const sidebarState = localStorage.getItem('sidebarState');
            if (sidebarState === 'true' && window.innerWidth >= 768) {
                sidebar.classList.add('active');
            }
        }
        
        document.querySelector('form').addEventListener('submit', function() {
    // Update gambar sidebar setelah 1 detik (waktu untuk upload selesai)
    setTimeout(() => {
        const sidebarImg = document.getElementById('sidebarProfileImg');
        const newSrc = `../uploads/users/user_<?= $user_id ?>.jpg?${Date.now()}`;
        sidebarImg.src = newSrc;
    }, 1000);
});
    </script>
</body>
</html>