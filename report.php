<?php
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pembina') {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$error_message = '';
$show_report = false;
$extracurriculars = [];
$report_data = [];
$selected_ekstra_id = '';
$bulan_selected = '';
$tahun_selected = '';
$report_scheduled_days = [];
$report_extra = '';
$report_month = '';
$report_year = '';

$month_names = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

try {
    $stmt_extras = $pdo->prepare("SELECT id_ekstra, nama_ekstra, hari FROM tb_ekstrakurikuler WHERE id_pembina = ? ORDER BY nama_ekstra");
    $stmt_extras->execute([$_SESSION['user_id']]);
    $extracurriculars = $stmt_extras->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching extracurriculars: " . $e->getMessage());
    $error_message = "Gagal memuat daftar ekstrakurikuler.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_ekstra_id = filter_input(INPUT_POST, 'id_ekstra', FILTER_VALIDATE_INT);
    $bulan_selected = filter_input(INPUT_POST, 'bulan', FILTER_VALIDATE_INT);
    $tahun_selected = filter_input(INPUT_POST, 'tahun', FILTER_VALIDATE_INT);

    if ($selected_ekstra_id && $bulan_selected && $tahun_selected) {
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $bulan_selected, $tahun_selected);
        $start_date = sprintf("%04d-%02d-01", $tahun_selected, $bulan_selected);
        $end_date = sprintf("%04d-%02d-%02d", $tahun_selected, $bulan_selected, $days_in_month);

        try {
            // Fetch extracurricular schedule
            $stmt_hari = $pdo->prepare("SELECT hari, nama_ekstra FROM tb_ekstrakurikuler WHERE id_ekstra = ?");
            $stmt_hari->execute([$selected_ekstra_id]);
            $extra_data = $stmt_hari->fetch(PDO::FETCH_ASSOC);

            if ($extra_data) {
                $hari = $extra_data['hari'];
                $report_extra = $extra_data['nama_ekstra'];
                $day_map = ['Senin' => 'Monday', 'Selasa' => 'Tuesday', 'Rabu' => 'Wednesday', 'Kamis' => 'Thursday', 'Jumat' => 'Friday', 'Sabtu' => 'Saturday', 'Minggu' => 'Sunday'];
                $scheduled_days = [];
                for ($d = 1; $d <= $days_in_month; $d++) {
                    $date = sprintf("%04d-%02d-%02d", $tahun_selected, $bulan_selected, $d);
                    if (date('l', strtotime($date)) === $day_map[$hari]) {
                        $scheduled_days[] = $d;
                    }
                }
                $report_scheduled_days = $scheduled_days;

                // Fetch students
                $stmt_students = $pdo->prepare("
                    SELECT pe.nis, s.nama AS nama_siswa
                    FROM tb_peserta_ekstra pe
                    JOIN tb_siswa s ON pe.nis = s.nis
                    WHERE pe.id_ekstra = ? AND pe.status = 'approved'
                    ORDER BY s.nama
                ");
                $stmt_students->execute([$selected_ekstra_id]);
                $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

                // Initialize report data
                $report_data = [];
                foreach ($students as $index => $student) {
                    $report_data[] = [
                        'no' => $index + 1,
                        'nis' => $student['nis'],
                        'nama_siswa' => $student['nama_siswa'],
                        'presensi' => array_fill(1, $days_in_month, ''),
                        'counts' => ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0]
                    ];
                }

                if ($report_data) {
                    // Fetch presensi data
                    $stmt_presensi = $pdo->prepare("
                        SELECT nis, DAY(tanggal) as tanggal_hari, status
                        FROM tb_presensi
                        WHERE id_ekstra = ? AND tanggal BETWEEN ? AND ?
                    ");
                    $stmt_presensi->execute([$selected_ekstra_id, $start_date, $end_date]);
                    $presensi_data = $stmt_presensi->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($presensi_data as $presensi) {
                        $nis = $presensi['nis'];
                        $day = $presensi['tanggal_hari'];
                        $status = $presensi['status'];

                        foreach ($report_data as &$student) {
                            if ($student['nis'] === $nis && in_array($day, $scheduled_days)) {
                                $student['presensi'][$day] = $status;
                                $student['counts'][$status]++;
                            }
                        }
                        unset($student);
                    }

                    $show_report = true;
                    $report_month = $month_names[$bulan_selected];
                    $report_year = $tahun_selected;
                } else {
                    $show_report = true; // Show empty report
                }
            } else {
                $error_message = "Hari jadwal ekstrakurikuler tidak ditemukan.";
            }
        } catch (PDOException $e) {
            error_log("Error generating report: " . $e->getMessage());
            $error_message = "Terjadi kesalahan saat menghasilkan laporan.";
        }
    } else {
        $error_message = "Pilih ekstrakurikuler, bulan, dan tahun yang valid.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Presensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; }
        .card-main { background-color: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 2rem; max-width: 1200px; margin: 2rem auto; }
        .form-select, .form-control { border-radius: 8px; }
        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .table-responsive { border: 1px solid #dee2e6; border-radius: 8px; margin-top: 1.5rem; }
        .table { --bs-table-bg: #fff; --bs-table-striped-bg: #f8f9fa; }
        .table-bordered { border: 1px solid #dee2e6; }
        .table th, .table td { border-color: #ced4da; padding: 0.75rem; font-size: 0.875rem; vertical-align: middle; }
        .table thead th { background-color: #e9ecef; font-weight: 600; color: #495057; }
        .table tbody tr:hover { background-color: #f1f3f5; }
        .badge { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
        .bg-success { background-color: #28a745; color: #fff; }
        .bg-warning { background-color: #ffc107; color: #343a40; }
        .bg-info { background-color: #17a2b8; color: #fff; }
        .bg-danger { background-color: #dc3545; color: #fff; }
        .report-header { font-weight: 600; }
        .report-footer { text-align: center; margin-top: 2rem; }
        .report-signature p { margin: 0; }
        .no-print { display: block; }
        @media print {
            .no-print { display: none; }
            .card-main { box-shadow: none; }
            .report-footer { position: fixed; bottom: 20mm; width: 100%; }
        }
        @media (max-width: 767px) {
            .table-responsive { overflow-x: auto; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container my-4">
        <div class="card card-main">
            <h2 class="text-center mb-1 fw-bold">Laporan Presensi</h2>
            <p class="text-center text-muted mb-4">Ekstrakurikuler SMKN 2 Magelang</p>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="id_ekstra" class="form-label fw-bold">Ekstrakurikuler</label>
                        <select class="form-select" id="id_ekstra" name="id_ekstra" required>
                            <option value="">Pilih Ekstrakurikuler</option>
                            <?php foreach ($extracurriculars as $extra): ?>
                                <option value="<?= htmlspecialchars($extra['id_ekstra']) ?>" <?= ($selected_ekstra_id == $extra['id_ekstra']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($extra['nama_ekstra']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="bulan" class="form-label fw-bold">Bulan</label>
                        <select class="form-select" id="bulan" name="bulan" required>
                            <option value="">Pilih Bulan</option>
                            <?php foreach ($month_names as $num => $name): ?>
                                <option value="<?= $num ?>" <?= ($bulan_selected == $num) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="tahun" class="form-label fw-bold">Tahun</label>
                        <select class="form-select" id="tahun" name="tahun" required>
                            <option value="">Pilih Tahun</option>
                            <?php for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= ($tahun_selected == $y) ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Cari
                        </button>
                    </div>
                </div>
            </form>

            <?php if ($show_report && !empty($report_data)): ?>
                <hr class="no-print my-4">
                <div class="report-header text-center mb-4">
                    <h4>EKSTRAKURIKULER : <?= strtoupper(htmlspecialchars($report_extra)) ?></h4>
                    <h4>PERIODE : <?= strtoupper(htmlspecialchars($report_month)) ?> <?= htmlspecialchars($report_year) ?></h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th rowspan="2">NO</th>
                                <th rowspan="2">NIS</th>
                                <th rowspan="2">NAMA SISWA</th>
                                <th colspan="<?= count($report_scheduled_days) ?>">TANGGAL</th>
                                <th colspan="4">KETERANGAN</th>
                            </tr>
                            <tr>
                                <?php foreach ($report_scheduled_days as $d): ?>
                                    <th><?= $d ?></th>
                                <?php endforeach; ?>
                                <th>H</th>
                                <th>S</th>
                                <th>I</th>
                                <th>A</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['no']) ?></td>
                                    <td><?= htmlspecialchars($student['nis']) ?></td>
                                    <td class="text-start"><?= htmlspecialchars($student['nama_siswa']) ?></td>
                                    <?php foreach ($report_scheduled_days as $d): ?>
                                        <td>
                                            <?php $status = $student['presensi'][$d] ?? ''; ?>
                                            <?php if ($status): ?>
                                                <span class="badge rounded-pill <?= $status === 'H' ? 'bg-success' : ($status === 'S' ? 'bg-warning' : ($status === 'I' ? 'bg-info' : 'bg-danger')) ?>">
                                                    <?= htmlspecialchars($status) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td><?= htmlspecialchars($student['counts']['H']) ?></td>
                                    <td><?= htmlspecialchars($student['counts']['S']) ?></td>
                                    <td><?= htmlspecialchars($student['counts']['I']) ?></td>
                                    <td><?= htmlspecialchars($student['counts']['A']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="report-footer">
                    <div class="report-signature">
                        <p>Magelang, <?= date('d F Y') ?></p>
                        <p>Pembina Ekstrakurikuler</p>
                        <br><br><br>
                        <p>( <?= htmlspecialchars($_SESSION['username'] ?? '____________________') ?> )</p>
                    </div>
                </div>
                <div class="text-center no-print mt-4">
                    <button type="button" class="btn btn-secondary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Cetak Laporan
                    </button>
                </div>
            <?php elseif ($show_report): ?>
                <div class="alert alert-info text-center no-print" role="alert">
                    Tidak ada data presensi atau siswa untuk periode ini.
                </div>
            <?php endif; ?>

            <p class="footer-text text-center mt-5 mb-0 text-muted">&copy; <?= date('Y') ?> SMKN 2 Magelang</p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>