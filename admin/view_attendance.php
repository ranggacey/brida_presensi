<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkAdmin();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID pengguna tidak ditemukan.");
}

$user_id = intval($_GET['id']);

// Ambil informasi user
$query_user = $conn->prepare("SELECT nama, email, universitas, fakultas, prodi, tgl_masuk_magang, foto FROM users WHERE id = ?");
$query_user->bind_param("i", $user_id);
$query_user->execute();
$result_user = $query_user->get_result();
if ($result_user->num_rows === 0) die("Pengguna tidak ditemukan.");
$user = $result_user->fetch_assoc();

// Hitung durasi magang
$durasi_str = '-';
if ($user['tgl_masuk_magang']) {
    $tgl_masuk = new DateTime($user['tgl_masuk_magang']);
    $sekarang = new DateTime();
    $durasi = $tgl_masuk->diff($sekarang);
    $durasi_str = $durasi->format('%m Bulan %d Hari');
}

// Filter
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Query data dengan filter, termasuk kolom foto masuk dan foto pulang
$query = "SELECT date, waktu_absen, waktu_pulang, status, keterangan, foto, foto_pulang 
          FROM attendance 
          WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if (!empty($start_date) && !empty($end_date)) {
    $query .= " AND date BETWEEN ? AND ?";
    array_push($params, $start_date, $end_date);
    $types .= "ss";
}

if (!empty($filter_status)) {
    $query .= " AND status = ?";
    array_push($params, $filter_status);
    $types .= "s";
}

$query .= " ORDER BY date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Hitung statistik
$query_stats = $conn->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(status = 'Tepat Waktu') AS tepat_waktu,
        SUM(status = 'Terlambat') AS terlambat,
        SEC_TO_TIME(AVG(TIME_TO_SEC(waktu_absen))) AS rata_masuk,
        SEC_TO_TIME(AVG(TIME_TO_SEC(waktu_pulang))) AS rata_pulang
    FROM attendance 
    WHERE user_id = ?
");
$query_stats->bind_param("i", $user_id);
$query_stats->execute();
$stats = $query_stats->get_result()->fetch_assoc();

// Fungsi format waktu
function formatJam($time)
{
    return $time ? date('H:i:s', strtotime($time)) : '-';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Absensi - <?= htmlspecialchars($user['nama']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #3498db;
        }

        .table th {
            background-color: #343a40;
            color: #fff;
        }

        .table tbody tr:hover {
            background-color: #f1f1f1;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 5px;
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-danger {
            background-color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Info -->
        <div class="card mb-4 shadow">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <?php if (!empty($user['foto'])): ?>
                        <img
                            src="../uploads/users/<?= htmlspecialchars($user['foto']) ?>?<?= time() ?>"
                            alt="Foto Profil"
                            class="profile-picture me-4"
                            id="headerProfileImg">
                    <?php endif; ?>

                    <div>
                        <h2 class="card-title"><?= htmlspecialchars($user['nama']) ?></h2>
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Universitas:</strong> <?= htmlspecialchars($user['universitas']) ?></p>
                                <p class="mb-1"><strong>Mulai Magang:</strong> <?= date('d M Y', strtotime($user['tgl_masuk_magang'])) ?></p>
                                <p class="mb-1"><strong>Fakultas:</strong> <?= htmlspecialchars($user['fakultas']) ?></p>
                                <p class="mb-1"><strong>Prodi:</strong> <?= htmlspecialchars($user['prodi']) ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Durasi Magang:</strong> <?= $durasi_str ?></p>
                                <p class="mb-1"><strong>Total Hadir:</strong> <?= $stats['total'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card mb-4 shadow">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="id" value="<?= $user_id ?>">
                    <div class="col-md-3">
                        <label class="form-label">Rentang Tanggal</label>
                        <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                        <input type="date" class="form-control mt-2" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Semua Status</option>
                            <option value="Tepat Waktu" <?= ($filter_status == 'Tepat Waktu') ? 'selected' : '' ?>>Tepat Waktu</option>
                            <option value="Terlambat" <?= ($filter_status == 'Terlambat') ? 'selected' : '' ?>>Terlambat</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Terapkan</button>
                        <a href="?id=<?= $user_id ?>" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5 class="card-title">Tepat Waktu</h5>
                        <p class="display-6 text-success"><?= $stats['tepat_waktu'] ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5 class="card-title">Terlambat</h5>
                        <p class="display-6 text-danger"><?= $stats['terlambat'] ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5 class="card-title">Rata² Masuk</h5>
                        <p class="text-muted"><?= formatJam($stats['rata_masuk']) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5 class="card-title">Rata² Pulang</h5>
                        <p class="text-muted"><?= formatJam($stats['rata_pulang']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Absensi -->
        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Tanggal</th>
                                <th>Masuk</th>
                                <th>Pulang</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th>Foto Masuk</th>
                                <th>Foto Pulang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['date']) ?></td>
                                    <td><?= htmlspecialchars($row['waktu_absen']) ?></td>
                                    <td><?= htmlspecialchars($row['waktu_pulang'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-<?= ($row['status'] == 'Tepat Waktu') ? 'success' : 'danger' ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                    <td>
                                        <?php if (!empty($row['foto']) && $row['foto'] != '-'): ?>
                                            <img src="../uploads/users/<?= htmlspecialchars($row['foto']) ?>?<?= time() ?>" alt="Foto Masuk" style="width:80px;height:80px;">
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['foto_pulang'])): ?>
                                            <img src="../uploads/users/<?= htmlspecialchars($row['foto_pulang']) ?>?<?= time() ?>" alt="Foto Pulang" style="width:80px;height:80px;">
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kalender Absensi -->
        <div class="card shadow mt-4">
            <div class="card-body">
                <h4 class="card-title mb-4">Kalender Absensi</h4>
                <div id="calendar"></div>
            </div>
        </div>

        <a href="export_attendance.php" class="btn btn-secondary mt-4">Kembali</a>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/id.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth', // Tampilan default: bulanan
                locale: 'id', // Bahasa Indonesia
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    <?php
                    // Query data absensi untuk kalender
                    $query_calendar = $conn->prepare("SELECT date, status FROM attendance WHERE user_id = ?");
                    $query_calendar->bind_param("i", $user_id);
                    $query_calendar->execute();
                    $result_calendar = $query_calendar->get_result();

                    while ($row = $result_calendar->fetch_assoc()) {
                        $color = ($row['status'] == 'Tepat Waktu') ? '#28a745' : '#dc3545'; // Warna berdasarkan status
                        echo "{
                        title: '{$row['status']}',
                        start: '{$row['date']}',
                        color: '$color'
                    },";
                    }
                    ?>
                ],
                eventContent: function(arg) {
                    return {
                        html: `<div class="fc-event-title">${arg.event.title}</div>`
                    };
                }
            });
            calendar.render();
        });

        // Auto-refresh gambar setelah halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const timestamp = Date.now();

            // Refresh gambar header
            const headerImg = document.getElementById('headerProfileImg');
            if (headerImg) {
                headerImg.src = `../uploads/users/<?= $user['foto'] ?>?${timestamp}`;
            }

            // Jika ada gambar utama lainnya, bisa di-refresh juga
            const mainImg = document.getElementById('mainProfileImg');
            if(mainImg){
                mainImg.src = `../uploads/users/user_<?= $user_id ?>.jpg?${timestamp}`;
            }
        });
    </script>
</body>

</html>
