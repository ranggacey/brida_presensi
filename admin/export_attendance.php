<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkAdmin();

// Pagination configuration
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah data per halaman
$offset = ($page - 1) * $limit;

// Query untuk mengambil daftar intern (distinct) dengan pagination
$query = "SELECT DISTINCT u.id, u.nama, u.email 
          FROM attendance a 
          JOIN users u ON a.user_id = u.id 
          WHERE a.status IN ('Tepat Waktu', 'Terlambat')
          ORDER BY u.nama ASC
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);
if (!$result) {
    die("Query error: " . $conn->error);
}
$interns = [];
while ($row = $result->fetch_assoc()) {
    $interns[] = $row;
}

// Query untuk menghitung total data (untuk pagination)
$total_query = "SELECT COUNT(DISTINCT u.id) AS total 
                FROM attendance a 
                JOIN users u ON a.user_id = u.id 
                WHERE a.status IN ('Tepat Waktu', 'Terlambat')";
$total_result = $conn->query($total_query);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Export Absensi Per Intern</title>
  <!-- Tailwind CSS (untuk main content) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Animate.css CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Sidebar CSS (custom, tanpa Tailwind) -->
  <link rel="stylesheet" href="side.css">
  <!-- Sidebar JS -->
  <script src="side.js" defer></script>
  <style>
    /* Custom tambahan untuk main content jika diperlukan */
    body { font-family: 'Poppins', sans-serif; }
  </style>
</head>
<body class="min-h-screen">
  <!-- Hamburger Menu (tetap sama seperti dashboard) -->
  <button class="hamburger fixed left-6 top-6 z-50" id="hamburger">
    <i class="fas fa-bars text-white text-xl"></i>
  </button>
  
  <!-- Sidebar Navigation (gunakan bagian ini di export_attendance.php) -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-header flex items-center justify-center py-4 border-b border-red-700">
    <h3 class="text-white text-2xl font-bold">
      <i class="fas fa-user-shield mr-2"></i> Admin Panel
    </h3>
  </div>
  <ul class="sidebar-menu mt-6">
    <li class="nav-item">
      <a href="dashboard.php" class="nav-link flex items-center gap-3 p-3 hover:bg-red-800 rounded transition duration-300">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
      </a>     
    </li>
    <li class="nav-item">
      <a href="kelola_user.php" class="nav-link flex items-center gap-3 p-3 hover:bg-red-800 rounded transition duration-300">
        <i class="fas fa-users"></i>
        <span>Kelola User</span>
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

  
  <!-- Main Content Area (menggunakan Tailwind CSS) -->
  <div class="main-content ml-0 md:ml-[280px] p-6 bg-white min-h-screen animate__animated animate__fadeIn">
    <h2 class="text-3xl font-semibold mb-6">Export Absensi Per Intern</h2>
    
    <!-- Input Pencarian -->
    <div class="mb-4">
      <input type="text" id="searchInput" placeholder="Cari berdasarkan nama atau email..." class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    
    <!-- Tabel Data -->
    <div id="table-container" class="overflow-x-auto">
      <table class="min-w-full bg-white rounded shadow">
        <thead class="bg-gray-800 text-white">
          <tr>
            <th class="py-3 px-4">No</th>
            <th class="py-3 px-4">Nama</th>
            <th class="py-3 px-4">Email</th>
            <th class="py-3 px-4">Aksi</th>
          </tr>
        </thead>
        <tbody id="internTableBody">
          <?php if(count($interns) > 0): ?>
            <?php foreach($interns as $index => $intern): ?>
              <tr class="border-b hover:bg-gray-100">
                <td class="py-3 px-4"><?= ($page - 1) * $limit + $index + 1 ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($intern['nama']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($intern['email']) ?></td>
                <td class="py-3 px-4">
                  <a href="export_intern.php?id=<?= $intern['id'] ?>" class="text-blue-500 hover:underline mr-2">
                    <i class="fas fa-file-excel"></i> Export ke Excel
                  </a>
                  <a href="view_attendance.php?id=<?= $intern['id'] ?>" class="text-green-500 hover:underline">
                    <i class="fas fa-eye"></i> Lihat Data
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="text-center py-4">Tidak ada data.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <!-- Pagination -->
    <nav class="mt-6">
      <ul class="flex justify-center space-x-2" id="pagination">
        <?php if($page > 1): ?>
          <li>
            <a href="?page=<?= $page - 1 ?>" class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Prev</a>
          </li>
        <?php endif; ?>
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
          <li>
            <a href="?page=<?= $i ?>" class="px-3 py-2 <?= ($i == $page) ? 'bg-blue-700' : 'bg-blue-500' ?> text-white rounded hover:bg-blue-600"><?= $i ?></a>
          </li>
        <?php endfor; ?>
        <?php if($page < $total_pages): ?>
          <li>
            <a href="?page=<?= $page + 1 ?>" class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Next</a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
    
    <!-- Tombol Kembali -->
    <div class="mt-6">
      <a href="dashboard.php" class="inline-block bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
      </a>
    </div>
  </div>
  
  <script>
    // Fungsi pencarian sederhana
    document.getElementById('searchInput').addEventListener('input', function() {
      const filter = this.value.toLowerCase();
      const rows = document.querySelectorAll('#internTableBody tr');
      rows.forEach(row => {
        const nama = row.cells[1].textContent.toLowerCase();
        const email = row.cells[2].textContent.toLowerCase();
        row.style.display = (nama.includes(filter) || email.includes(filter)) ? '' : 'none';
      });
    });
  </script>
</body>
</html>
