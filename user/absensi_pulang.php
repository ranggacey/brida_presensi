<?php
require_once "../includes/session.php";
require_once "../includes/config.php";
checkLogin();

date_default_timezone_set('Asia/Jakarta');

$user_id = getUserId();
$current_date = date("Y-m-d");
$current_time = date("H:i:s");
$current_day = date("N"); // 1=Senin, 5=Jumat, 7=Minggu

// Pastikan user sudah absen masuk
$stmt = $conn->prepare("SELECT id, waktu_pulang FROM attendance WHERE user_id = ? AND date = ?");
$stmt->bind_param("is", $user_id, $current_date);
$stmt->execute();
$result_check = $stmt->get_result();
$attendance = $result_check->fetch_assoc();

if (!$attendance) {
    echo "<script>alert('Anda belum melakukan absensi masuk hari ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Cek apakah user sudah melakukan absensi pulang hari ini
if (!empty($attendance['waktu_pulang'])) {
    echo "<script>alert('Anda sudah melakukan absensi pulang hari ini.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $captured_photo = $_POST['captured_photo'] ?? "";
    $keterangan_pulang = trim($_POST['keterangan_pulang'] ?? "Pulang");

    if (empty($captured_photo)) {
        echo "<script>alert('Foto tidak ditemukan.'); window.location.href='absensi_pulang.php';</script>";
        exit;
    }

    // Proses foto
    $data = preg_replace('#^data:image/\w+;base64,#i', '', $captured_photo);
    $decoded_image = base64_decode($data);
    $new_file_name = "user_{$user_id}_pulang_" . time() . ".jpg";
    $upload_dir = "../uploads/users/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $destination = $upload_dir . $new_file_name;
    if (!file_put_contents($destination, $decoded_image)) {
        echo "<script>alert('Gagal menyimpan foto.'); window.location.href='absensi_pulang.php';</script>";
        exit;
    }

    // Tentukan status pulang berdasarkan hari
    if ($current_day == 5) { // Jumat
        if ($current_time >= "11:30:00" && $current_time <= "11:45:00") {
            $status_pulang = "Tepat Waktu";
        } else {
            $status_pulang = "Terlambat";
        }
    } else { // Senin-Kamis
        if ($current_time >= "15:15:00" && $current_time <= "15:25:00") {
            $status_pulang = "Tepat Waktu";
        } else {
            $status_pulang = "Terlambat";
        }
    }

    // Update data absensi dengan waktu pulang
    $stmt_update = $conn->prepare("UPDATE attendance SET waktu_pulang = ?, status_pulang = ?, foto_pulang = ?, keterangan_pulang = ? WHERE user_id = ? AND date = ?");
    if ($stmt_update) {
        $stmt_update->bind_param("ssssis", $current_time, $status_pulang, $new_file_name, $keterangan_pulang, $user_id, $current_date);
        if ($stmt_update->execute()) {
            echo "<script>alert('Absensi pulang berhasil!\\nStatus: $status_pulang\\nWaktu: $current_time'); window.location.href='dashboard.php';</script>";
            exit;
        } else {
            echo "<script>alert('Gagal menyimpan absensi pulang: " . $stmt_update->error . "'); window.location.href='absensi_pulang.php';</script>";
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi Pulang</title>
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
        /* Styling tombol dengan kombinasi ungu */
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
        /* Video styling: mirror dengan scaleX(-1) */
        #video {
            width: 100%;
            height: auto;
            border: 2px solid #6f42c1;
            border-radius: 8px;
            -webkit-transform: scaleX(-1);
    transform: scaleX(-1);
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Notifikasi Hari dan Jam -->
        <div class="alert alert-info mb-4">
            <strong><i class="fa fa-info-circle"></i> Jadwal Presensi Pulang:</strong><br>
            Senin-Kamis: Tepat waktu antara 15:15 - 15:25<br>
            Jumat: Tepat waktu antara 11:30 - 11:45<br>
            Diluar waktu tersebut dianggap terlambat.
        </div>

        <h2 class="mb-4"><i class="fa fa-user-check"></i> Presensi Pulang</h2>
        
        <!-- Form absensi -->
        <form id="absensiPulangForm" method="POST">
            <!-- Step 1: Input Keterangan -->
            <div class="step active" id="step1">
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan_pulang" class="form-control" value="Pulang" readonly>
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
            document.getElementById('absensiPulangForm').submit();
        }

        // Edit Keterangan
        function enableEdit() {
            const input = document.querySelector('input[name="keterangan_pulang"]');
            input.removeAttribute('readonly');
            input.focus();
        }

        // Handle page unload
        window.addEventListener('beforeunload', () => stopCamera());
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
