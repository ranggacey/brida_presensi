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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Premium</title>
    
    <!-- Premium Assets -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css">
    <link rel="stylesheet" href="dashboard.css">
    <script src="side.js"></script>


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
                <a href="absensi.php" class="nav-link">
                    <i class="fas fa-fingerprint"></i>
                    <span>Presensi</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="update_identitas.php" class="nav-link">
                    <i class="fas fa-id-card"></i>
                    <span>Update Profil</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="riwayat.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    <span>Riwayat</span>
                </a>
            </li>
            <li class="nav-item">
        <a href="chat.php" class="nav-link active">
          <i class="fas fa-comments"></i>
          <span>Chat</span>
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

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="dashboard-grid">
            <!-- Profile Section -->
            <div class="profile-card" data-aos="zoom-in">
                <div class="profile-content">
                <img src="../uploads/users/user_<?= $user_id ?>.jpg" alt="Profile" class="profile-main-img">
                    <h2><?= htmlspecialchars($user_name) ?></h2>
                    <p style="color: #94a3b8; font-size: 0.9rem;"><?= htmlspecialchars($universitas) ?></p>
                    
                    
                    <div class="attendance-status" style="margin-top: 24px;">
                        <div class="status-badge <?= $sudah_presensi_masuk ? 'success' : '' ?>">
                            <i class="fas fa-door-open"></i>
                            <?= $sudah_presensi_masuk ? 'Telah Masuk' : 'Belum Masuk' ?>
                        </div>
                        <div class="status-badge <?= $sudah_presensi_pulang ? 'success' : '' ?>">
                            <i class="fas fa-door-closed"></i>
                            <?= $sudah_presensi_pulang ? 'Telah Pulang' : 'Belum Pulang' ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Section -->
            <div class="attendance-card" data-aos="zoom-in" data-aos-delay="200">
                <h2 style="margin-bottom: 16px;">Presensi Harian</h2>
                <p style="color: #64748b;">Status presensi hari ini</p>
                
                <div style="margin-top: 32px;">
                    <?php if (!$sudah_presensi_masuk): ?>
                        <a href="absensi.php" class="presensi-btn">
                            <i class="fas fa-door-open"></i>
                            Presensi Masuk
                        </a>
                    <?php elseif ($sudah_presensi_masuk && !$sudah_presensi_pulang): ?>
                        <a href="absensi_pulang.php" class="presensi-btn">
                            <i class="fas fa-door-closed"></i>
                            Presensi Pulang
                        </a>
                    <?php else: ?>
                        <div class="status-badge success" style="padding: 16px;">
                            <i class="fas fa-check-circle"></i>
                            Presensi Hari Ini Selesai
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Overlay Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

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