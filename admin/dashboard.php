<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkAdmin();

// Query untuk statistik ringkasan
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()["total"];
$total_attendance = $conn->query("SELECT COUNT(*) AS total FROM attendance")->fetch_assoc()["total"];
$total_active_interns = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'magang' AND tgl_masuk_magang <= CURDATE()")->fetch_assoc()["total"];
$total_late_this_month = $conn->query("SELECT COUNT(*) AS total FROM attendance WHERE status = 'Terlambat' AND MONTH(date) = MONTH(CURDATE())")->fetch_assoc()["total"];

// Query untuk daftar intern terlambat terbanyak
$query_late_interns = $conn->query("
    SELECT u.nama, COUNT(a.id) AS total_terlambat 
    FROM attendance a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.status = 'Terlambat' AND MONTH(a.date) = MONTH(CURDATE()) 
    GROUP BY u.nama 
    ORDER BY total_terlambat DESC 
    LIMIT 5
");

// Query untuk data kehadiran bulanan (untuk grafik nanti)
$query_monthly_attendance = $conn->query("
    SELECT 
        DATE_FORMAT(date, '%Y-%m') AS bulan,
        SUM(CASE WHEN status = 'Tepat Waktu' THEN 1 ELSE 0 END) AS tepat_waktu,
        SUM(CASE WHEN status = 'Terlambat' THEN 1 ELSE 0 END) AS terlambat
    FROM attendance
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY bulan ASC
");

$monthly_data = [];
while ($row = $query_monthly_attendance->fetch_assoc()) {
  $monthly_data[] = $row;
}

$labels = [];
$tepat_waktu = [];
$terlambat = [];
foreach ($monthly_data as $data) {
  $labels[] = date('M Y', strtotime($data['bulan'] . '-01'));
  $tepat_waktu[] = $data['tepat_waktu'];
  $terlambat[] = $data['terlambat'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Animate.css CDN untuk animasi -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-pVI+5FJvfKd6... (truncated)" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Sidebar CSS -->
  <link rel="stylesheet" href="side.css">
  <!-- Sidebar JS -->
  <script src="side.js"></script>
  <!-- Custom CSS tambahan jika diperlukan -->
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f1f5f9;
    }
  </style>
</head>

<body class="min-h-screen">
  <!-- Hamburger Menu -->
  <button class="hamburger fixed left-6 top-6 z-50" id="hamburger">
    <i class="fas fa-bars text-white text-xl"></i>
  </button>

  <!-- Sidebar Navigation (tetap sama seperti dashboard) -->
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-header flex items-center justify-center py-4 border-b border-red-700">
      <h3 class="text-white text-2xl font-bold">
        <i class="fas fa-user-shield mr-2"></i> Admin Panel
      </h3>
    </div>
    <ul class="sidebar-menu mt-6">
      <li class="nav-item">
        <a href="kelola_user.php" class="nav-link flex items-center gap-3 p-3 hover:bg-red-800 rounded transition duration-300">
          <i class="fas fa-users"></i>
          <span>Kelola User</span>
        </a>     
      </li>
      <li class="nav-item">
        <a href="export_attendance.php" class="nav-link flex items-center gap-3 p-3 hover:bg-red-800 rounded transition duration-300">
          <i class="fas fa-file-export"></i>
          <span>Export Presensi</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="view_ident.php" class="nav-link flex items-center gap-3 p-3 hover:bg-red-800 rounded transition duration-300">
          <i class="fas fa-id-card"></i>
          <span>Export Identitas</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="../auth/logout.php" class="nav-link flex items-center gap-3 p-3 hover:bg-red-800 rounded transition duration-300">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </a>
      </li>
    </ul>
  </nav>

  <!-- Main Content Area -->
  <main class="main-content ml-0 md:ml-[280px] p-6 bg-white min-h-screen animate__animated animate__fadeIn">
    <h2 class="text-3xl font-semibold text-center mb-8 animate__animated animate__fadeInDown">Dashboard Admin</h2>

    <!-- Statistik Ringkasan -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
      <!-- Total Pengguna -->
      <div class="card bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition duration-300 animate__animated animate__zoomIn">
        <div class="flex items-center">
          <div class="p-3 bg-red-600 rounded-full text-white">
            <i class="fas fa-users text-2xl"></i>
          </div>
          <div class="ml-4">
            <p class="text-lg font-medium text-gray-600">Total Pengguna</p>
            <p class="text-3xl font-bold"><?= $total_users ?></p>
          </div>
        </div>
      </div>
      <!-- Total Presensi -->
      <div class="card bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition duration-300 animate__animated animate__zoomIn animate__delay-1s">
        <div class="flex items-center">
          <div class="p-3 bg-green-600 rounded-full text-white">
            <i class="fas fa-calendar-check text-2xl"></i>
          </div>
          <div class="ml-4">
            <p class="text-lg font-medium text-gray-600">Total Presensi</p>
            <p class="text-3xl font-bold"><?= $total_attendance ?></p>
          </div>
        </div>
      </div>
      <!-- Akun Aktif -->
      <div class="card bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition duration-300 animate__animated animate__zoomIn animate__delay-2s">
        <div class="flex items-center">
          <div class="p-3 bg-yellow-500 rounded-full text-white">
            <i class="fas fa-user-check text-2xl"></i>
          </div>
          <div class="ml-4">
            <p class="text-lg font-medium text-gray-600">Akun Aktif</p>
            <p class="text-3xl font-bold"><?= $total_active_interns ?></p>
          </div>
        </div>
      </div>
      <!-- Terlambat Bulan Ini (clickable menuju terlambat.php) -->
      <a href="terlambat.php" class="block animate__animated animate__zoomIn animate__delay-3s">
        <div class="card bg-white rounded-lg shadow-lg p-6 transform hover:scale-105 transition duration-300">
          <div class="flex items-center">
            <div class="p-3 bg-red-700 rounded-full text-white">
              <i class="fas fa-exclamation-triangle text-2xl"></i>
            </div>
            <div class="ml-4">
              <p class="text-lg font-medium text-gray-600">Terlambat Bulan Ini</p>
              <p class="text-3xl font-bold"><?= $total_late_this_month ?></p>
            </div>
          </div>
        </div>
      </a>
    </section>

    <!-- Grafik Statistik & Daftar Intern Terlambat -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
      <!-- Grafik Kehadiran Bulanan -->
      <div class="card bg-white rounded-lg shadow-lg p-6 animate__animated animate__fadeInUp">
        <h5 class="text-2xl font-semibold mb-4 text-center">Grafik Kehadiran Bulanan</h5>
        <canvas id="attendanceChart"></canvas>
      </div>
      <!-- Top 5 Intern Terlambat Bulan Ini -->
      <div class="card bg-white rounded-lg shadow-lg p-6 animate__animated animate__fadeInUp animate__delay-1s">
        <h5 class="text-2xl font-semibold mb-4 text-center">Top 5 Intern Terlambat Bulan Ini</h5>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Nama</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Total Terlambat</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php while ($row = $query_late_interns->fetch_assoc()): ?>
              <tr class="hover:bg-gray-100 transition duration-200">
                <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?= htmlspecialchars($row['nama']) ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900"><?= $row['total_terlambat'] ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Notifikasi Terbaru -->
    <section class="mb-10 animate__animated animate__fadeInUp animate__delay-2s">
      <div class="card bg-white rounded-lg shadow-lg p-6">
        <h5 class="text-2xl font-semibold mb-4 text-center">Belum Absen Hari Ini</h5>
        <div id="missing-attendance-container">
          <p class="text-gray-500 text-center">Data absensi akan muncul di sini.</p>
        </div>
      </div>
    </section>
  </main>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Inisialisasi Chart.js dan fungsi AJAX untuk data absensi
    document.addEventListener('DOMContentLoaded', function() {
      // Inisialisasi Grafik Kehadiran
      const ctx = document.getElementById('attendanceChart').getContext('2d');
      const attendanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: <?= json_encode($labels) ?>,
          datasets: [
            {
              label: 'Tepat Waktu',
              data: <?= json_encode($tepat_waktu) ?>,
              backgroundColor: '#28a745'
            },
            {
              label: 'Terlambat',
              data: <?= json_encode($terlambat) ?>,
              backgroundColor: '#dc3545'
            }
          ]
        },
        options: {
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });

      // Muat data absensi yang belum hadir via AJAX
      loadMissingAttendance(1);
    });

    function loadMissingAttendance(page) {
      const xhr = new XMLHttpRequest();
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          document.getElementById('missing-attendance-container').innerHTML = xhr.responseText;
          const paginationLinks = document.querySelectorAll('#missing-attendance-container .page-link');
          paginationLinks.forEach(link => {
            link.addEventListener('click', function(event) {
              event.preventDefault();
              const page = this.getAttribute('href').split('=')[1];
              loadMissingAttendance(page);
            });
          });
        }
      };
      xhr.open('GET', 'get_missing_attendance.php?page=' + page, true);
      xhr.send();
    }
  </script>
</body>

</html>
