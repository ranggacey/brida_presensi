<?php
require_once "../includes/config.php";

$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Ambil halaman saat ini
$offset = ($page - 1) * $limit; // Hitung offset

$query_missing_attendance = $conn->query("
    SELECT u.nama 
    FROM users u 
    LEFT JOIN attendance a ON u.id = a.user_id AND a.date = CURDATE() 
    WHERE u.role = 'magang' AND a.id IS NULL
    LIMIT $limit OFFSET $offset
");

// Hitung total data untuk pagination
$total_missing_attendance = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users u 
    LEFT JOIN attendance a ON u.id = a.user_id AND a.date = CURDATE() 
    WHERE u.role = 'magang' AND a.id IS NULL
")->fetch_assoc()["total"];
$total_pages = ceil($total_missing_attendance / $limit); // Hitung total halaman
?>

<ul class="list-group">
    <?php while ($row = $query_missing_attendance->fetch_assoc()): ?>
        <li class="list-group-item"><?= htmlspecialchars($row['nama']) ?></li>
    <?php endwhile; ?>
</ul>

<!-- Pagination -->
<nav class="mt-3">
    <ul class="pagination">
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
            </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>