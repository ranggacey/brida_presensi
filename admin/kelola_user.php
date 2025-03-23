<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkAdmin(); // Pastikan hanya admin yang bisa mengakses

// Jika ada request delete, hapus user dan redirect kembali
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id = $user_id");
    header("Location: kelola_user.php");
    exit();
}

// Pagination configuration
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah data per halaman
$offset = ($page - 1) * $limit;

// Query untuk mengambil daftar user dengan pagination
$query = "SELECT id, nama, email, role FROM users ORDER BY role DESC, nama ASC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);
if (!$result) {
    die("Query error: " . $conn->error);
}

// Query untuk menghitung total data
$total_query = "SELECT COUNT(*) AS total FROM users";
$total_result = $conn->query($total_query);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Pagination: maksimal 5 nomor halaman
$max_visible_pages = 5;
$start_page = max(1, $page - floor($max_visible_pages / 2));
$end_page = $start_page + $max_visible_pages - 1;
if ($end_page > $total_pages) {
    $end_page = $total_pages;
    $start_page = max(1, $end_page - $max_visible_pages + 1);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola User - Admin</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Animate.css CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Sidebar CSS -->
  <link rel="stylesheet" href="side.css">
  <!-- Sidebar JS -->
  <script src="side.js" defer></script>
  <style>
    body { font-family: 'Poppins', sans-serif; background-color: #f1f5f9; }
  </style>
</head>
<body class="min-h-screen">
  <!-- Hamburger Menu (untuk perangkat mobile) -->
  <button class="hamburger fixed left-6 top-6 z-50" id="hamburger">
    <i class="fas fa-bars text-white text-xl"></i>
  </button>

  <!-- Sidebar Navigation (tetap sama persis seperti dashboard) -->
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
  <div class="main-content ml-0 md:ml-[280px] p-6 bg-white min-h-screen animate__animated animate__fadeIn">
    <h2 class="text-3xl font-bold mb-6">Kelola User</h2>
    
    <table class="min-w-full bg-white border border-gray-200 shadow rounded">
      <thead class="bg-gray-800 text-white">
        <tr>
          <th class="py-3 px-4">No</th>
          <th class="py-3 px-4">Nama</th>
          <th class="py-3 px-4">Email</th>
          <th class="py-3 px-4">Role</th>
          <th class="py-3 px-4">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <?php 
        $no = ($page - 1) * $limit + 1;
        while ($row = $result->fetch_assoc()):
        ?>
        <tr class="hover:bg-gray-100">
          <td class="py-3 px-4"><?= $no ?></td>
          <td class="py-3 px-4"><?= htmlspecialchars($row['nama']) ?></td>
          <td class="py-3 px-4"><?= htmlspecialchars($row['email']) ?></td>
          <td class="py-3 px-4"><?= ($row['role'] === 'admin') ? "Admin" : "Magang" ?></td>
          <td class="py-3 px-4 space-x-2">
            <a href="edit_user.php?id=<?= $row['id'] ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-2 rounded text-sm">
              <i class="fas fa-edit"></i> Edit
            </a>
            <a href="kelola_user.php?delete=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus user ini?')" class="bg-red-500 hover:bg-red-600 text-white py-1 px-2 rounded text-sm">
              <i class="fas fa-trash-alt"></i> Hapus
            </a>
          </td>
        </tr>
        <?php 
        $no++;
        endwhile;
        ?>
      </tbody>
    </table>
    
    <!-- Pagination -->
    <div class="mt-6">
      <ul class="flex justify-center space-x-2">
        <?php if ($page > 1): ?>
          <li>
            <a href="kelola_user.php?page=<?= $page - 1 ?>" class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
              Prev
            </a>
          </li>
        <?php endif; ?>
        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
          <li>
            <a href="kelola_user.php?page=<?= $i ?>" class="px-3 py-2 <?= ($i == $page) ? 'bg-blue-700' : 'bg-blue-500' ?> text-white rounded hover:bg-blue-600">
              <?= $i ?>
            </a>
          </li>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
          <li>
            <a href="kelola_user.php?page=<?= $page + 1 ?>" class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
              Next
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
    
    <!-- Tombol Kembali -->
    <div class="mt-6">
      <a href="dashboard.php" class="inline-block bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
      </a>
    </div>
  </div>
</body>
</html>
