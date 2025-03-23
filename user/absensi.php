<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkLogin();

// Set timezone sesuai kebutuhan
date_default_timezone_set('Asia/Jakarta');

$user_id = getUserId();
$current_date = date("Y-m-d");
$current_time = date("H:i:s");
$current_day = date("N"); // 1=Senin, 5=Jumat, 7=Minggu

// Jika form disubmit (setelah proses capture)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Cek apakah user sudah absen hari ini
    $stmt_check = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND date = ?");
    $stmt_check->bind_param("is", $user_id, $current_date);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        echo "<script>alert('Anda sudah melakukan absensi hari ini.'); window.location.href='dashboard.php';</script>";
        exit;
    }

    // Validasi hari kerja (Senin-Jumat)
    if ($current_day > 5) {
        echo "<script>alert('Absensi hanya dapat dilakukan Senin sampai Jumat.'); window.location.href='absensi.php';</script>";
        exit;
    }

    // Validasi jam absensi berdasarkan hari
    $threshold_time = ($current_day == 5) ? "07:31:00" : "08:01:00"; // Jumat 07:31, lain 08:01
    if ($current_time > $threshold_time) {
        $status = "Terlambat";
        $threshold = strtotime($threshold_time);
        $currentTimestamp = strtotime($current_time);
        $diffSeconds = $currentTimestamp - $threshold;
        $telatFormatted = gmdate("H:i:s", $diffSeconds);
        $infoTelat = " (Telat $telatFormatted)";
    } else {
        $status = "Tepat Waktu";
        $infoTelat = "";
    }

    // Validasi jam absensi (07:00 - 16:05)
    if ($current_time < "07:00:00" || $current_time > "16:05:00") {
        echo "<script>alert('Absensi hanya dapat dilakukan antara jam 07:00 - 16:05.'); window.location.href='absensi.php';</script>";
        exit;
    }

    // Ambil data dari form
    $captured_photo = $_POST['captured_photo'] ?? ""; 
    $keterangan = trim($_POST['keterangan'] ?? "Hadir");

    // Validasi foto
    if (empty($captured_photo)) {
        echo "<script>alert('Foto tidak ditemukan. Pastikan kamera menangkap wajah Anda.'); window.location.href='absensi.php';</script>";
        exit;
    }

    // Ambil data universitas
    $stmt_user = $conn->prepare("SELECT universitas FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $row_user = $result_user->fetch_assoc();
    $universitas = $row_user['universitas'] ?? "";

    // Proses foto
    $data = preg_replace('#^data:image/\w+;base64,#i', '', $captured_photo);
    $decoded_image = base64_decode($data);
    $new_file_name = "user_{$user_id}_" . time() . ".jpg";
    $upload_dir = "../uploads/users/";
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $destination = $upload_dir . $new_file_name;
    if (!file_put_contents($destination, $decoded_image)) {
        echo "<script>alert('Gagal menyimpan foto hasil capture.'); window.location.href='absensi.php';</script>";
        exit;
    }

    // Simpan ke database
    $stmt_insert = $conn->prepare("INSERT INTO attendance (user_id, date, waktu_absen, status, universitas, foto, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt_insert) {
        $keterangan .= $infoTelat; // Gabungkan keterangan dengan info telat
        $stmt_insert->bind_param("issssss", $user_id, $current_date, $current_time, $status, $universitas, $new_file_name, $keterangan);
        if ($stmt_insert->execute()) {
            echo "<script>
                alert('Absensi berhasil!\\nStatus: $status\\nWaktu: $current_time');
                window.location.href='dashboard.php';
            </script>";
            exit;
        } else {
            echo "<script>alert('Gagal menyimpan absensi: " . $stmt_insert->error . "'); window.location.href='absensi.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Query insert gagal: " . $conn->error . "'); window.location.href='absensi.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi Masuk</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h2 {
            color: #6f42c1;
        }
        .alert-info {
            background-color: #e9dff5;
            border-color: #d0bce0;
            color: #6f42c1;
        }
        /* Override button styles dengan kombinasi ungu */
        .btn-primary, .btn-success, .btn-secondary {
            background-color: #6f42c1 !important;
            border-color: #6f42c1 !important;
            color: #ffffff !important;
        }
        .btn-primary:hover, .btn-success:hover, .btn-secondary:hover {
            background-color: #5a379e !important;
            border-color: #5a379e !important;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        /* Video styling: mirror menggunakan scaleX(-1) */
        #video {
            width: 100%;
            height: auto;
            border: 2px solid #6f42c1;
            border-radius: 8px;
            transform: scaleX(-1);
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Notifikasi Hari dan Jam -->
        <div class="alert alert-info mb-4">
            <strong><i class="fa fa-info-circle"></i> Jadwal Presensi:</strong><br>
            Senin-Kamis: Tepat waktu sebelum 08:00<br>
            Jumat: Tepat waktu sebelum 07:30<br>
            Batas akhir absen: 16:05
        </div>

        <h2 class="mb-4"><i class="fa fa-user-check"></i> Presensi Masuk</h2>
        
        <!-- Form absensi -->
        <form id="absensiForm" method="POST">
            <!-- Step 1: Input Keterangan -->
            <div class="step active" id="step1">
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" class="form-control" value="Hadir" readonly>
                    <button type="button" class="btn btn-secondary mt-2" onclick="enableEdit()">
                        <i class="fa fa-edit"></i> Ubah Keterangan
                    </button>
                </div>
                <button type="button" class="btn btn-primary" onclick="nextStep()">
                    <i class="fa fa-camera"></i> Lanjut ke Kamera
                </button>
            </div>

            <!-- Step 2: Capture Foto -->
            <div class="step" id="step2">
                <div class="mb-4">
                    <p class="text-muted">Pastikan wajah Anda terlihat jelas di dalam frame</p>
                    <video id="video" autoplay></video>
                    <input type="hidden" name="captured_photo" id="captured_photo">
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" onclick="previousStep()">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </button>
                    <button type="button" class="btn btn-success" onclick="captureAndSubmit()">
                        <i class="fa fa-check"></i> Ambil Foto & Presensi
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Step Navigation
        let currentStep = 1;
        let mediaStream = null;

        function showStep(step) {
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            document.getElementById(`step${step}`).classList.add('active');
        }

        function nextStep() {
            currentStep = 2;
            showStep(2);
            startCamera();
        }

        function previousStep() {
            currentStep = 1;
            showStep(1);
            stopCamera();
        }

        // Camera Handling
        async function startCamera() {
            try {
                mediaStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                const video = document.getElementById('video');
                video.srcObject = mediaStream;
            } catch (error) {
                alert('Gagal mengakses kamera: ' + error.message);
                previousStep();
            }
        }

        function stopCamera() {
            if (mediaStream) {
                mediaStream.getTracks().forEach(track => track.stop());
                mediaStream = null;
            }
        }

        // Foto Capture
        function captureAndSubmit() {
            const video = document.getElementById('video');
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            document.getElementById('captured_photo').value = canvas.toDataURL('image/jpeg');
            document.getElementById('absensiForm').submit();
        }

        // Edit Keterangan
        function enableEdit() {
            const input = document.querySelector('input[name="keterangan"]');
            input.removeAttribute('readonly');
            input.focus();
        }

        // Handle page unload
        window.addEventListener('beforeunload', () => stopCamera());
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
