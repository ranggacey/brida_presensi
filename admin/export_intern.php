<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkAdmin();

if (!isset($_GET['id'])) {
    die("Intern ID tidak diberikan.");
}
$intern_id = intval($_GET['id']);

// Query untuk mengambil data absensi intern beserta data registrasi dari tabel users
$query = "SELECT 
            u.nama, 
            u.ttl, 
            u.alamat, 
            u.universitas, 
            u.fakultas, 
            u.prodi, 
            u.tgl_masuk_magang, 
            u.no_hp, 
            a.date AS tgl_absen, 
            a.waktu_absen, 
            a.status, 
            a.foto, 
            a.keterangan,
            a.waktu_pulang,
            a.status_pulang,
            a.foto_pulang,
            a.keterangan_pulang
          FROM attendance a
          JOIN users u ON a.user_id = u.id
          WHERE a.user_id = ?
          ORDER BY a.date ASC";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query error: " . $conn->error);
}
$stmt->bind_param("i", $intern_id);
$stmt->execute();
$result = $stmt->get_result();

// Load autoloader Composer
require_once "../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// Buat objek Spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header kolom di baris 1
$headers = [
    'No',
    'Nama',
    'TTL',
    'Alamat',
    'Universitas',
    'Fakultas',
    'Program Studi',
    'Tanggal Masuk Magang',
    'Tanggal Absen',
    'Waktu Absen',
    'Status Absen',
    'No HP',
    'Foto Masuk',
    'Keterangan Masuk',
    'Waktu Pulang',
    'Status Pulang',
    'Foto Pulang',
    'Keterangan Pulang'
];
$columns = range('A', 'R'); // Kolom A sampai R (18 kolom)

foreach ($headers as $index => $header) {
    $sheet->setCellValue("{$columns[$index]}1", $header);
    $sheet->getColumnDimension($columns[$index])->setAutoSize(true);
}

// Atur ukuran khusus untuk kolom Foto Masuk (M) dan Foto Pulang (Q)
$sheet->getColumnDimension('M')->setWidth(15);
$sheet->getColumnDimension('Q')->setWidth(15);

$rowNumber = 2;
$no = 1;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue("A{$rowNumber}", $no);
    $sheet->setCellValue("B{$rowNumber}", $row["nama"]);
    $sheet->setCellValue("C{$rowNumber}", $row["ttl"]);
    $sheet->setCellValue("D{$rowNumber}", $row["alamat"]);
    $sheet->setCellValue("E{$rowNumber}", $row["universitas"]);
    $sheet->setCellValue("F{$rowNumber}", $row["fakultas"]);
    $sheet->setCellValue("G{$rowNumber}", $row["prodi"]);
    $sheet->setCellValue("H{$rowNumber}", $row["tgl_masuk_magang"]);
    $sheet->setCellValue("I{$rowNumber}", $row["tgl_absen"]);
    $sheet->setCellValue("J{$rowNumber}", $row["waktu_absen"]);
    $sheet->setCellValue("K{$rowNumber}", $row["status"]);
    $sheet->setCellValue("L{$rowNumber}", $row["no_hp"]);

    // Sisipkan Foto Masuk di kolom M
    if (!empty($row["foto"])) {
        // Gunakan path absolut untuk memastikan file ditemukan
        $imagePath = __DIR__ . "/../uploads/users/" . $row["foto"];
        if (file_exists($imagePath)) {
            $drawing = new Drawing();
            $drawing->setName('Foto Masuk');
            $drawing->setDescription('Foto absensi masuk');
            $drawing->setPath($imagePath);
            $drawing->setHeight(50);
            $drawing->setWidth(50);
            $drawing->setCoordinates("M{$rowNumber}");
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
            $sheet->getRowDimension($rowNumber)->setRowHeight(55);
        } else {
            $sheet->setCellValue("M{$rowNumber}", "File tidak ditemukan");
        }
    } else {
        $sheet->setCellValue("M{$rowNumber}", "Tidak ada foto");
    }

    $sheet->setCellValue("N{$rowNumber}", $row["keterangan"]);

    // Data Absensi Pulang
    $sheet->setCellValue("O{$rowNumber}", $row["waktu_pulang"]);
    $sheet->setCellValue("P{$rowNumber}", $row["status_pulang"]);

    // Sisipkan Foto Pulang di kolom Q
    if (!empty($row["foto_pulang"])) {
        $imagePulangPath = __DIR__ . "/../uploads/users/" . $row["foto_pulang"];
        if (file_exists($imagePulangPath)) {
            $drawingPulang = new Drawing();
            $drawingPulang->setName('Foto Pulang');
            $drawingPulang->setDescription('Foto absensi pulang');
            $drawingPulang->setPath($imagePulangPath);
            $drawingPulang->setHeight(50);
            $drawingPulang->setWidth(50);
            $drawingPulang->setCoordinates("Q{$rowNumber}");
            $drawingPulang->setOffsetX(5);
            $drawingPulang->setOffsetY(5);
            $drawingPulang->setWorksheet($sheet);
            $sheet->getRowDimension($rowNumber)->setRowHeight(55);
        } else {
            $sheet->setCellValue("Q{$rowNumber}", "File tidak ditemukan");
        }
    } else {
        $sheet->setCellValue("Q{$rowNumber}", "Tidak ada foto");
    }

    $sheet->setCellValue("R{$rowNumber}", $row["keterangan_pulang"]);

    $rowNumber++;
    $no++;
}

// Styling Header agar lebih terlihat jelas
$headerStyle = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
];
$sheet->getStyle('A1:R1')->applyFromArray($headerStyle);

// Output file Excel ke browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=absensi_intern_{$intern_id}.xlsx");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
