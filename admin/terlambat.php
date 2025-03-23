<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkAdmin();

// Ambil filter bulan dan tahun dari query string, gunakan bulan dan tahun sekarang jika tidak ada
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear  = isset($_GET['year']) ? $_GET['year'] : date('Y');

$selectedMonthInt = (int)$selectedMonth;
$selectedYearInt  = (int)$selectedYear;

// Pagination: 8 data per halaman
$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Hitung total data terlambat (untuk pagination)
$totalStmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE status = 'Terlambat' AND MONTH(date) = ? AND YEAR(date) = ?");
$totalStmt->bind_param("ii", $selectedMonthInt, $selectedYearInt);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRecords = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Query untuk data terlambat dengan pagination
$stmt = $conn->prepare("SELECT u.nama, a.waktu_absen, a.date FROM attendance a JOIN users u ON a.user_id = u.id WHERE a.status = 'Terlambat' AND MONTH(a.date) = ? AND YEAR(a.date) = ? ORDER BY a.date DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iiii", $selectedMonthInt, $selectedYearInt, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Waktu yang diharapkan (misalnya 08:01:00)
$expectedTimeStr = "08:01:00";
$expectedTime = DateTime::createFromFormat("H:i:s", $expectedTimeStr);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Intern Terlambat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Animate.css CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Reset dan dasar */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f0f0f0;
      color: #333;
      line-height: 1.6;
    }
    /* Container Utama */
    .container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 20px;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    /* Header & Tombol Kembali */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .header h2 {
      font-size: 2rem;
    }
    .btn-back {
      background-color: #ff4757;
      color: #fff;
      padding: 10px 16px;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      transition: background-color 0.3s ease;
    }
    .btn-back:hover {
      background-color: #e84118;
    }
    /* Filter Form */
    .filter-form {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 20px;
    }
    .filter-group {
      flex: 1;
      min-width: 150px;
    }
    .filter-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }
    .filter-group select,
    .filter-group input[type="date"] {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .filter-group button {
      padding: 10px 16px;
      background-color: #3742fa;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .filter-group button:hover {
      background-color: #273c75;
    }
    /* Tabel */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    thead {
      background-color: #2f3542;
      color: #fff;
    }
    th, td {
      padding: 12px 16px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    tbody tr:hover {
      background-color: #f1f2f6;
    }
    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      list-style: none;
      gap: 8px;
      margin-top: 20px;
    }
    .pagination li { }
    .pagination a {
      display: inline-block;
      padding: 8px 12px;
      background-color: #3742fa;
      color: #fff;
      text-decoration: none;
      border-radius: 4px;
      transition: background-color 0.3s ease;
    }
    .pagination a:hover {
      background-color: #273c75;
    }
    .pagination .active a {
      background-color: #57606f;
    }
    /* Responsif */
    @media (max-width: 600px) {
      .filter-form { flex-direction: column; }
    }
  </style>
</head>
<body>
  <div class="container animate__animated animate__fadeIn">
    <!-- Header & Tombol Kembali -->
    <div class="header">
      <h2>Daftar Intern Terlambat</h2>
      <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
    
    <!-- Filter Form -->
    <form method="GET" class="filter-form animate__animated animate__fadeInUp">
      <div class="filter-group">
        <label for="month">Bulan</label>
        <select name="month" id="month">
          <?php
            for ($m = 1; $m <= 12; $m++) {
              $monthNum = str_pad($m, 2, "0", STR_PAD_LEFT);
              $selectedOpt = ($monthNum == $selectedMonth) ? "selected" : "";
              echo "<option value='$monthNum' $selectedOpt>" . date("F", mktime(0, 0, 0, $m, 1)) . "</option>";
            }
          ?>
        </select>
      </div>
      <div class="filter-group">
        <label for="year">Tahun</label>
        <select name="year" id="year">
          <?php
            $currentYear = date("Y");
            for ($y = $currentYear - 5; $y <= $currentYear; $y++) {
              $selectedOpt = ($y == $selectedYear) ? "selected" : "";
              echo "<option value='$y' $selectedOpt>$y</option>";
            }
          ?>
        </select>
      </div>
      <div class="filter-group">
        <button type="submit"><i class="fas fa-filter"></i> Filter</button>
      </div>
    </form>
    
    <!-- Tabel Data Terlambat -->
    <div class="animate__animated animate__fadeInUp">
      <table>
        <thead>
          <tr>
            <th>Nama</th>
            <th>Absen Masuk</th>
            <th>Tanggal</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <?php
                // Format waktu dari kolom waktu_absen (format H:i:s)
                $actualTime = DateTime::createFromFormat("H:i:s", $row['waktu_absen']);
                if ($expectedTime && $actualTime) {
                  if ($actualTime > $expectedTime) {
                    $diff = $expectedTime->diff($actualTime);
                    $totalMinutes = ($diff->h * 60) + $diff->i;
                    if ($totalMinutes >= 60) {
                      $jam = floor($totalMinutes / 60);
                      $menit = $totalMinutes % 60;
                      $telatText = " (telat {$jam} jam {$menit} menit)";
                    } else {
                      $telatText = " (telat {$totalMinutes} menit)";
                    }
                  } else {
                    $telatText = "";
                  }
                  $formattedTime = $actualTime->format("H:i:s") . $telatText;
                } else {
                  $formattedTime = htmlspecialchars($row['waktu_absen']);
                }
              ?>
              <tr>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= $formattedTime ?></td>
                <td><?= date("d M Y", strtotime($row['date'])) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="3" style="text-align: center;">Tidak ada data terlambat</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <!-- Pagination -->
    <ul class="pagination animate__animated animate__fadeInUp">
      <?php if ($page > 1): ?>
        <li><a href="?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&page=<?= $page - 1 ?>">Prev</a></li>
      <?php endif; ?>
      
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="<?= ($i == $page) ? 'active' : '' ?>"><a href="?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&page=<?= $i ?>"><?= $i ?></a></li>
      <?php endfor; ?>
      
      <?php if ($page < $totalPages): ?>
        <li><a href="?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&page=<?= $page + 1 ?>">Next</a></li>
      <?php endif; ?>
    </ul>
  </div>
</body>
</html>
