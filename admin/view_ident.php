<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkAdmin();

// Pagination configuration
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search & Filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_universitas = isset($_GET['filter_universitas']) ? trim($_GET['filter_universitas']) : '';
$filter_fakultas = isset($_GET['filter_fakultas']) ? trim($_GET['filter_fakultas']) : '';
$filter_prodi = isset($_GET['filter_prodi']) ? trim($_GET['filter_prodi']) : '';

// Query dasar
$query = "SELECT id, nama, ttl, alamat, universitas, fakultas, prodi, tgl_masuk_magang, no_hp, foto, surat_permohonan FROM users WHERE role = 'magang'";

// Tambahkan kondisi search
if (!empty($search)) {
    $query .= " AND (nama LIKE '%$search%' OR universitas LIKE '%$search%' OR fakultas LIKE '%$search%' OR prodi LIKE '%$search%')";
}

// Tambahkan kondisi filter
if (!empty($filter_universitas)) {
    $query .= " AND universitas = '$filter_universitas'";
}
if (!empty($filter_fakultas)) {
    $query .= " AND fakultas = '$filter_fakultas'";
}
if (!empty($filter_prodi)) {
    $query .= " AND prodi = '$filter_prodi'";
}

// Hitung total data untuk pagination
$total_query = "SELECT COUNT(*) AS total FROM ($query) AS total_query";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_data = $total_row['total'];
$total_pages = ceil($total_data / $limit);

// Tambahkan pagination ke query utama
$query .= " ORDER BY nama ASC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

// Ambil data unik untuk filter dropdown
$query_universitas = "SELECT DISTINCT universitas FROM users WHERE role = 'magang' ORDER BY universitas";
$query_fakultas = "SELECT DISTINCT fakultas FROM users WHERE role = 'magang' ORDER BY fakultas";
$query_prodi = "SELECT DISTINCT prodi FROM users WHERE role = 'magang' ORDER BY prodi";
$universitas_list = $conn->query($query_universitas);
$fakultas_list = $conn->query($query_fakultas);
$prodi_list = $conn->query($query_prodi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>View Identitas Magang</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Animate.css CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
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
        <a href="../auth/logout.php" class="nav-link flex items-center gap-3 p-3 hover:bg-red-800 rounded transition duration-300">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </a>
      </li>
    </ul>
  </nav>

  <!-- Main Content Area -->
  <div class="main-content ml-0 md:ml-[280px] p-6 bg-white min-h-screen animate__animated animate__fadeIn">
    <h2 class="text-3xl font-bold mb-6 text-center">Data Identitas Magang</h2>

    <!-- Search dan Filter Form -->
    <form method="GET" class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <input type="text" name="search" placeholder="Cari nama, universitas, fakultas, atau prodi..." value="<?= htmlspecialchars($search) ?>" class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <select name="filter_universitas" class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Pilih Universitas</option>
            <?php while ($row = $universitas_list->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($row['universitas']) ?>" <?= $filter_universitas === $row['universitas'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['universitas']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <select name="filter_fakultas" class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Pilih Fakultas</option>
            <?php while ($row = $fakultas_list->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($row['fakultas']) ?>" <?= $filter_fakultas === $row['fakultas'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['fakultas']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <select name="filter_prodi" class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Pilih Prodi</option>
            <?php while ($row = $prodi_list->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($row['prodi']) ?>" <?= $filter_prodi === $row['prodi'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['prodi']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>
      <div class="mt-4">
        <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition">
          <i class="fas fa-filter"></i> Filter
        </button>
      </div>
    </form>

    <!-- Tombol Reset Filter -->
    <div class="mb-4">
      <a href="view_ident.php" class="block w-full text-center bg-gray-500 text-white py-2 px-4 rounded hover:bg-gray-600 transition">
        Reset Filter
      </a>
    </div>

    <!-- Tabel Data -->
    <div class="overflow-x-auto">
      <table class="min-w-full bg-white rounded shadow">
        <thead class="bg-gray-800 text-white">
          <tr>
            <th class="py-3 px-4">No</th>
            <th class="py-3 px-4">Nama</th>
            <th class="py-3 px-4">TTL</th>
            <th class="py-3 px-4">Alamat</th>
            <th class="py-3 px-4">Universitas</th>
            <th class="py-3 px-4">Fakultas</th>
            <th class="py-3 px-4">Prodi</th>
            <th class="py-3 px-4">Tanggal Masuk</th>
            <th class="py-3 px-4">No. HP</th>
            <th class="py-3 px-4">Foto</th>
            <th class="py-3 px-4">Surat Permohonan</th>
            <th class="py-3 px-4">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if ($result->num_rows > 0): ?>
            <?php $no = $offset + 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr class="hover:bg-gray-100">
                <td class="py-3 px-4"><?= $no++ ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($row['nama']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($row['ttl']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($row['alamat']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($row['universitas']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($row['fakultas']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($row['prodi']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($row['tgl_masuk_magang']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($row['no_hp']) ?></td>
                <td class="py-3 px-4 text-center">
                  <?php if (!empty($row['foto'])): ?>
                    <img src="../uploads/users/<?= htmlspecialchars($row['foto']) ?>?<?= time() ?>" alt="Foto Profil" class="w-24 h-24 object-cover rounded">
                  <?php else: ?>
                    <span class="text-gray-500">Tidak ada foto</span>
                  <?php endif; ?>
                </td>
                <td class="py-3 px-4 text-center">
                  <?php if (!empty($row['surat_permohonan'])): ?>
                    <a href="../uploads/surat_permohonan/<?= htmlspecialchars($row['surat_permohonan']) ?>" target="_blank" class="bg-blue-500 text-white py-1 px-3 rounded hover:bg-blue-600 transition text-sm">
                      Lihat Surat
                    </a>
                  <?php else: ?>
                    <span class="text-gray-500">Tidak ada surat</span>
                  <?php endif; ?>
                </td>
                <td class="py-3 px-4 text-center">
                  <div class="flex flex-col gap-2">
                    <a href="export_ident.php?format=excel&id=<?= $row['id'] ?>" class="bg-green-500 text-white py-1 px-3 rounded hover:bg-green-600 transition text-sm">
                      <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="12" class="text-center py-4">Tidak ada data ditemukan.</td>
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
            <a class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter_universitas=<?= urlencode($filter_universitas) ?>&filter_fakultas=<?= urlencode($filter_fakultas) ?>&filter_prodi=<?= urlencode($filter_prodi) ?>">
              &laquo;
            </a>
          </li>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <li>
            <a class="px-3 py-2 <?= ($i == $page) ? 'bg-blue-700' : 'bg-blue-500' ?> text-white rounded hover:bg-blue-600" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter_universitas=<?= urlencode($filter_universitas) ?>&filter_fakultas=<?= urlencode($filter_fakultas) ?>&filter_prodi=<?= urlencode($filter_prodi) ?>">
              <?= $i ?>
            </a>
          </li>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
          <li>
            <a class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter_universitas=<?= urlencode($filter_universitas) ?>&filter_fakultas=<?= urlencode($filter_fakultas) ?>&filter_prodi=<?= urlencode($filter_prodi) ?>">
              &raquo;
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>

    <!-- Tombol Export Semua Data dan Kembali ke Dashboard -->
    <div class="mt-6 flex justify-end space-x-4">
      <a href="dashboard.php" class="bg-gray-700 text-white py-2 px-4 rounded hover:bg-gray-600 transition">
        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
      </a>
      <a href="export_ident.php?format=excel&search=<?= urlencode($search) ?>&filter_universitas=<?= urlencode($filter_universitas) ?>&filter_fakultas=<?= urlencode($filter_fakultas) ?>&filter_prodi=<?= urlencode($filter_prodi) ?>" class="bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 transition">
        Export Semua ke Excel
      </a>
    </div>
  </div>

  <!-- Auto-Refresh Gambar dengan JavaScript -->
  <script>
    setInterval(() => {
      document.querySelectorAll('img').forEach(img => {
        const src = img.src.split('?')[0];
        img.src = `${src}?${Date.now()}`;
      });
    }, 2000);
  </script>
</body>
</html>
