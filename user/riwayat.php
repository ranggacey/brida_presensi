<?php
session_start();
require_once "../includes/session.php";
require_once "../includes/config.php";
checkLogin();

$user_id = $_SESSION['user_id'];

// Filter parameters dari GET (jika ada)
$date_from     = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to       = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$limit  = 10; // jumlah record per halaman
$page   = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) { $page = 1; }
$offset = ($page - 1) * $limit;

// Bangun kondisi WHERE berdasarkan filter
$where = "WHERE user_id = ?";
$params = [$user_id];
$types  = "i";

if ($date_from !== '' && $date_to !== '') {
    $where .= " AND date BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types   .= "ss";
} elseif ($date_from !== '') {
    $where .= " AND date >= ?";
    $params[] = $date_from;
    $types   .= "s";
} elseif ($date_to !== '') {
    $where .= " AND date <= ?";
    $params[] = $date_to;
    $types   .= "s";
}

if ($status_filter !== '') {
    $where .= " AND status = ?";
    $params[] = $status_filter;
    $types   .= "s";
}

if ($search !== '') {
    $where .= " AND (keterangan LIKE ? OR date LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= "ss";
}

// Hitung total record (untuk pagination)
$countQuery = "SELECT COUNT(*) as total FROM attendance $where";
$stmt_count = $conn->prepare($countQuery);
if($stmt_count){
    if(!empty($params)){
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total = $result_count->fetch_assoc()['total'];
    $stmt_count->close();
} else {
    $total = 0;
}

// Query utama dengan pagination
$query = "SELECT date, waktu_absen, waktu_pulang, status, keterangan, keterangan_pulang, foto, foto_pulang 
          FROM attendance $where 
          ORDER BY date ASC, waktu_absen ASC 
          LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if($stmt){
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die("Query error: " . $conn->error);
}

// Ambil data untuk grafik status distribusi
$chartQuery = "SELECT status, COUNT(*) as count FROM attendance WHERE user_id = ? GROUP BY status";
$stmt_chart = $conn->prepare($chartQuery);
$stmt_chart->bind_param("i", $user_id);
$stmt_chart->execute();
$result_chart = $stmt_chart->get_result();
$status_data = [];
while ($row = $result_chart->fetch_assoc()) {
    $status_data[$row['status']] = $row['count'];
}
$stmt_chart->close();

// Ambil data untuk grafik tren harian
$dailyQuery = "SELECT date, COUNT(*) as count FROM attendance WHERE user_id = ? GROUP BY date ORDER BY date ASC";
$stmt_daily = $conn->prepare($dailyQuery);
$stmt_daily->bind_param("i", $user_id);
$stmt_daily->execute();
$result_daily = $stmt_daily->get_result();
$dates = [];
$daily_counts = [];
while ($row = $result_daily->fetch_assoc()) {
    $dates[] = $row['date'];
    $daily_counts[] = $row['count'];
}
$stmt_daily->close();

$total_pages = ceil($total / $limit);

// Ambil data user (untuk sidebar)
$userQuery = "SELECT nama, universitas FROM users WHERE id = ?";
$stmt_user = $conn->prepare($userQuery);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if($result_user->num_rows > 0){
    $user_data = $result_user->fetch_assoc();
    $user_name = $user_data['nama'];
    $universitas = $user_data['universitas'];
} else {
    die("User tidak ditemukan");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Riwayat Presensi</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Dashboard CSS untuk Sidebar -->
  <link rel="stylesheet" href="dashboard.css">
  <script src="side.js"></script>
</head>
<body class="bg-white">
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
            <a href="update_identitas.php" class="nav-link">
                    <i class="fas fa-id-card"></i>
                    <span>Update Profil</span>
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
  
  <!-- Sidebar Overlay untuk mobile -->
  <div class="sidebar-overlay fixed inset-0 z-40 hidden" id="sidebarOverlay"></div>
  
  <!-- Main Content -->
  <main class="main-content ml-0 md:ml-64 p-4 transition-all duration-300">
    <div class="container mx-auto">
      <h2 class="text-3xl font-bold text-center mb-8 text-gray-800">Riwayat Presensi</h2>
      
      <!-- Filter Form -->
      <form method="GET" class="mb-8 bg-white p-6 rounded-lg shadow-lg">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" class="w-full p-2 border border-gray-300 rounded" placeholder="Dari Tanggal">
          <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" class="w-full p-2 border border-gray-300 rounded" placeholder="Sampai Tanggal">
          <select name="status" class="w-full p-2 border border-gray-300 rounded">
            <option value="">Semua Status</option>
            <option value="Tepat Waktu" <?= ($status_filter === "Tepat Waktu") ? 'selected' : '' ?>>Tepat Waktu</option>
            <option value="Terlambat" <?= ($status_filter === "Terlambat") ? 'selected' : '' ?>>Terlambat</option>
          </select>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="w-full p-2 border border-gray-300 rounded" placeholder="Cari keterangan atau tanggal">
        </div>
        <div class="mt-4 text-center">
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
          <a href="riwayat.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Reset</a>
        </div>
      </form>
      
      <!-- Data Table -->
      <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full">
          <thead class="bg-gray-100">
            <tr>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">No</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Tanggal Presensi</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Waktu Presensi</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Waktu Pulang</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Status</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Keterangan Masuk</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Keterangan Pulang</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Foto Masuk</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600">Foto Pulang</th>
            </tr>
          </thead>
          <tbody class="text-gray-600 text-sm font-light">
            <?php if($result->num_rows > 0): ?>
              <?php $no = $offset + 1; ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="border-b border-gray-200 hover:bg-gray-50">
                  <td class="py-3 px-4 text-left"><?= $no ?></td>
                  <td class="py-3 px-4 text-left"><?= htmlspecialchars($row["date"]) ?></td>
                  <td class="py-3 px-4 text-left"><?= htmlspecialchars($row["waktu_absen"]) ?></td>
                  <td class="py-3 px-4 text-left"><?= !empty($row["waktu_pulang"]) ? htmlspecialchars($row["waktu_pulang"]) : '-' ?></td>
                  <td class="py-3 px-4 text-left"><?= htmlspecialchars($row["status"]) ?></td>
                  <td class="py-3 px-4 text-left"><?= htmlspecialchars($row["keterangan"]) ?></td>
                  <td class="py-3 px-4 text-left"><?= !empty($row["keterangan_pulang"]) ? htmlspecialchars($row["keterangan_pulang"]) : '-' ?></td>
                  <td class="py-3 px-4 text-left">
                    <?php if (!empty($row["foto"]) && file_exists("../uploads/users/" . $row["foto"])): ?>
                      <img src="../uploads/users/<?= htmlspecialchars($row["foto"]) ?>" alt="Foto Masuk" class="w-16 h-16 object-cover rounded">
                    <?php else: ?>
                      Tidak ada foto
                    <?php endif; ?>
                  </td>
                  <td class="py-3 px-4 text-left">
                    <?php if (!empty($row["foto_pulang"]) && file_exists("../uploads/users/" . $row["foto_pulang"])): ?>
                      <img src="../uploads/users/<?= htmlspecialchars($row["foto_pulang"]) ?>" alt="Foto Pulang" class="w-16 h-16 object-cover rounded">
                    <?php else: ?>
                      Tidak ada foto
                    <?php endif; ?>
                  </td>
                </tr>
                <?php $no++; endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="py-3 px-4 text-center">Data tidak ditemukan</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <nav class="mt-6">
        <ul class="flex justify-center space-x-2">
          <?php if ($page > 1): ?>
            <li>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Previous</a>
            </li>
          <?php endif; ?>
          <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <li>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" class="px-3 py-2 <?= ($p == $page) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?> rounded">
                <?= $p ?>
              </a>
            </li>
          <?php endfor; ?>
          <?php if ($page < $total_pages): ?>
            <li>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Next</a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
      
      <!-- Chart: Status Distribution -->
      <div class="mt-10 p-6 bg-white rounded shadow">
        <h4 class="text-xl font-semibold text-center mb-4">Diagram Presensi</h4>
        <div class="w-full" style="height: 300px;">
          <canvas id="statusChart"></canvas>
        </div>
      </div>
    </div>
  </main>
  
  <!-- Custom JS -->
  <script>
    
    
    // Chart.js: Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
      type: 'pie',
      data: {
        labels: <?= json_encode(array_keys($status_data)) ?>,
        datasets: [{
          data: <?= json_encode(array_values($status_data)) ?>,
          backgroundColor: ['#4CAF50', '#F44336', '#FFC107', '#2196F3'],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { font: { size: 14 } }
          }
        },
        animation: { duration: 1000, easing: 'easeInOutQuart' }
      }
    });
  </script>
</body>
</html>
