<?php
require_once 'config.php';

error_log("Dashboard Siswa Access - Time: " . date('Y-m-d H:i:s') . " | Role: " . ($_SESSION['role'] ?? 'unset') . " | NIS: " . ($_SESSION['nis'] ?? 'unset') . " | Username: " . ($_SESSION['username'] ?? 'unset'));

$error_message = '';
$show_dashboard = false;
$extracurriculars = [];
$presensi_summary = [];

if (empty($_SESSION) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa' || !isset($_SESSION['nis']) || empty($_SESSION['nis']) || !isset($_SESSION['username']) || empty($_SESSION['username'])) {
    $error_message = 'Sesi tidak valid atau data tidak lengkap. <a href="login.php" class="alert-link">Login ulang sekarang</a>.';
    error_log("Authentication failed: Invalid or incomplete session data - Session: " . print_r($_SESSION, true));
} else {
    $show_dashboard = true;
    $current_date = date('Y-m-d'); // 2025-08-28

    try {
        // Tes koneksi
        $test_stmt = $pdo->query("SELECT 1");
        error_log("Database connection test successful.");

        error_log("Executing query with NIS: " . $_SESSION['nis'] . ", Date: " . $current_date);
        $stmt = $pdo->prepare("
            SELECT e.id_ekstra, e.nama_ekstra, e.hari, e.jam_mulai, e.jam_selesai, p.status, p.tanggal
            FROM tb_ekstrakurikuler e
            JOIN tb_peserta_ekstra pe ON e.id_ekstra = pe.id_ekstra
            LEFT JOIN tb_presensi p ON p.id_ekstra = e.id_ekstra AND p.nis = ? AND p.tanggal = ?
            WHERE pe.nis = ? AND pe.status = 'approved'
            ORDER BY e.nama_ekstra
        ");
        $stmt->execute([$current_date, $_SESSION['nis'], $_SESSION['nis']]);
        $extracurriculars = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($extracurriculars as $extra) {
            $presensi_summary[$extra['nama_ekstra']] = $extra['status'] ?? 'Belum Tercatat';
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage() . " | Query: " . $stmt->queryString);
        $error_message = "Gagal memuat data. Coba lagi nanti. Error: " . $e->getMessage();
        $show_dashboard = true;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - SMKN 2 Magelang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --light-bg: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #dee2e6;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --input-border: #ced4da;
        }
        body {
            background-color: var(--light-bg);
            font-family: 'Inter', sans-serif;
        }
        .page-header {
            background-color: var(--primary-color);
            color: #fff;
            padding: 2rem 1.5rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            box-shadow: var(--shadow);
            text-align: center;
            background: linear-gradient(45deg, #007bff, #0056b3);
        }
        .card-main {
            border-radius: 8px;
            box-shadow: var(--shadow);
            border: none;
            background-color: var(--card-bg);
            padding: 1.5rem;
        }
        .dashboard-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .feature-btn {
            width: 100%;
            margin-bottom: 1rem;
        }
        .table-responsive {
            margin-top: 1.5rem;
        }
        .table th, .table td {
            white-space: nowrap;
            vertical-align: middle;
        }
        .table thead th {
            background-color: var(--primary-color);
            color: #fff;
            border-color: #0056b3;
        }
        @media (max-width: 767px) {
            .feature-btn {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container my-4">
        <div class="page-header">
            <h2 class="display-5 fw-bold mb-0">Dashboard Siswa</h2>
            <p class="lead mb-0">Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
        </div>

        <div class="card-main">
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-6">
                    <a href="index.php?page=daftar_ekstra" class="btn btn-primary feature-btn">
                        <i class="fas fa-plus-circle me-2"></i>Daftar Ekstrakurikuler
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="index.php?page=lihat_presensi_siswa" class="btn btn-secondary feature-btn">
                        <i class="fas fa-eye me-2"></i>Lihat Presensi Saya
                    </a>
                </div>
            </div>

            <h4 class="mb-3">Ekstrakurikuler yang Diikuti</h4>
            <?php if (!empty($extracurriculars)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Nama Ekstrakurikuler</th>
                                <th>Hari</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                                <th>Status Presensi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($extracurriculars as $extra): ?>
                                <tr>
                                    <td><?= htmlspecialchars($extra['nama_ekstra']) ?></td>
                                    <td><?= htmlspecialchars($extra['hari']) ?></td>
                                    <td><?= htmlspecialchars(date('H:i', strtotime($extra['jam_mulai']))) ?></td>
                                    <td><?= htmlspecialchars(date('H:i', strtotime($extra['jam_selesai']))) ?></td>
                                    <td><?= htmlspecialchars($presensi_summary[$extra['nama_ekstra']] ?? 'Belum Tercatat') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center" role="alert">
                    Anda belum terdaftar di ekstrakurikuler. <a href="index.php?page=daftar_ekstra" class="alert-link">Daftar sekarang</a>.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
</xaiArtifact>