<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkAdmin();

// Load libraries
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;

// Ambil parameter export
$format = isset($_GET['format']) ? $_GET['format'] : 'excel';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0; // Ambil ID untuk export perorangan
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_universitas = isset($_GET['filter_universitas']) ? trim($_GET['filter_universitas']) : '';
$filter_fakultas = isset($_GET['filter_fakultas']) ? trim($_GET['filter_fakultas']) : '';
$filter_prodi = isset($_GET['filter_prodi']) ? trim($_GET['filter_prodi']) : '';

// Query data dengan filter
$query = "SELECT nama, ttl, alamat, universitas, fakultas, prodi, tgl_masuk_magang, no_hp, foto, surat_permohonan 
          FROM users 
          WHERE role = 'magang'";

// Jika export perorangan
if ($id > 0) {
    $query .= " AND id = $id";
} else {
    // Jika export semua data dengan filter
    if (!empty($search)) {
        $query .= " AND (nama LIKE '%".$conn->real_escape_string($search)."%' 
                    OR universitas LIKE '%".$conn->real_escape_string($search)."%'
                    OR fakultas LIKE '%".$conn->real_escape_string($search)."%'
                    OR prodi LIKE '%".$conn->real_escape_string($search)."%')";
    }

    if (!empty($filter_universitas)) {
        $query .= " AND universitas = '".$conn->real_escape_string($filter_universitas)."'";
    }

    if (!empty($filter_fakultas)) {
        $query .= " AND fakultas = '".$conn->real_escape_string($filter_fakultas)."'";
    }

    if (!empty($filter_prodi)) {
        $query .= " AND prodi = '".$conn->real_escape_string($filter_prodi)."'";
    }
}

$result = $conn->query($query);

if ($format === 'excel') {
    // Excel Export
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Header Style
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    
    // Data Style
    $dataStyle = [
        'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    
    // Set header
    $sheet->setCellValue('A1', 'Data Identitas Magang')->mergeCells('A1:J1');
    $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Column headers
    $headers = ['No', 'Nama', 'TTL', 'Alamat', 'Universitas', 'Fakultas', 'Prodi', 'Tanggal Masuk', 'No. HP', 'Surat Permohonan'];
    $sheet->fromArray($headers, null, 'A3');
    $sheet->getStyle('A3:J3')->applyFromArray($headerStyle);
    
    // Data
    $rowNumber = 4;
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A'.$rowNumber, $no);
        $sheet->setCellValue('B'.$rowNumber, $row['nama']);
        $sheet->setCellValue('C'.$rowNumber, $row['ttl']);
        $sheet->setCellValue('D'.$rowNumber, $row['alamat']);
        $sheet->setCellValue('E'.$rowNumber, $row['universitas']);
        $sheet->setCellValue('F'.$rowNumber, $row['fakultas']);
        $sheet->setCellValue('G'.$rowNumber, $row['prodi']);
        $sheet->setCellValue('H'.$rowNumber, $row['tgl_masuk_magang']);
        $sheet->setCellValue('I'.$rowNumber, $row['no_hp']);

        // Kolom Surat Permohonan (Hyperlink)
        if (!empty($row['surat_permohonan'])) {
            $url = 'https://presensi.brida.semarangkota.go.id/uploads/surat_permohonan/' . $row['surat_permohonan'];
            $sheet->setCellValue('J'.$rowNumber, $url); // Teks yang ditampilkan
            $sheet->getCell('J'.$rowNumber)->getHyperlink()->setUrl($url); // Set sebagai hyperlink
        } else {
            $sheet->setCellValue('J'.$rowNumber, 'Tidak Ada');
        }
        
        $rowNumber++;
        $no++;
    }
    
    // Set column widths
    foreach(range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Apply data style
    $sheet->getStyle('A4:J'.($rowNumber-1))->applyFromArray($dataStyle);
    
    // Set filename
    $filename = $id > 0 ? 'Data_Magang_Individu_'.$id.'_'.date('Ymd_His').'.xlsx' : 'Data_Magang_Semua_'.date('Ymd_His').'.xlsx';
    
    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

exit;