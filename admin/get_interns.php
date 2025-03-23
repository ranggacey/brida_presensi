<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkAdmin();

// Ambil parameter halaman dari URL
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah data per halaman
$offset = ($page - 1) * $limit;

// Query untuk mengambil daftar intern dengan pagination
$query = "SELECT DISTINCT u.id, u.nama, u.email 
          FROM attendance a 
          JOIN users u ON a.user_id = u.id 
          WHERE a.status IN ('Tepat Waktu', 'Terlambat')
          ORDER BY u.nama ASC
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);
if (!$result) {
    die(json_encode(['error' => 'Query error: ' . $conn->error]));
}

// Query untuk menghitung total data
$total_query = "SELECT COUNT(DISTINCT u.id) AS total 
                FROM attendance a 
                JOIN users u ON a.user_id = u.id 
                WHERE a.status IN ('Tepat Waktu', 'Terlambat')";
$total_result = $conn->query($total_query);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Format data ke JSON
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode([
    'rows' => $rows,
    'total_pages' => $total_pages
]);
