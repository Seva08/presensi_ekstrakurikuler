<?php
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pembina') {
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}
require_once 'config.php';

$error_message = '';
$extracurriculars = [];

// Ambil data pembina dari session (asumsi nama pembina disimpan di session saat login)
$nama_pembina = $_SESSION['nama'] ?? 'Pembina';

// Ambil daftar ekstrakurikuler yang dibina
try {
    $stmt_extras = $pdo->prepare("
        SELECT id_ekstra, nama_ekstra, hari, jam_mulai, jam_selesai
        FROM tb_ekstrakurikuler
        WHERE id_pembina = ?
        ORDER BY nama_ekstra
    ");
    $stmt_extras->execute([$_SESSION['user_id']]);
    $extracurriculars = $stmt_extras->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching extracurriculars: " . $e->getMessage());
    $error_message = "Terjadi kesalahan saat mengambil data ekstrakurikuler.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
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
            <h2 class="display-5 fw-bold mb-0">Dashboard Pembina</h2>
            <p class="lead mb-0">Selamat datang, <?= htmlspecialchars($nama_pembina) ?>!</p>
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
                    <a href="index.php?page=input_presensi" class="btn btn-primary feature-btn">
                        <i class="fas fa-check-circle me-2"></i>Input Presensi
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="index.php?page=report" class="btn btn-secondary feature-btn">
                        <i class="fas fa-file-alt me-2"></i>Lihat Laporan Presensi
                    </a>
                </div>
            </div>

            <h4 class="mb-3">Ekstrakurikuler yang Dibina</h4>
            <?php if (!empty($extracurriculars)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Nama Ekstrakurikuler</th>
                                <th>Hari</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($extracurriculars as $extra): ?>
                                <tr>
                                    <td><?= htmlspecialchars($extra['nama_ekstra']) ?></td>
                                    <td><?= htmlspecialchars($extra['hari']) ?></td>
                                    <td><?= htmlspecialchars(date('H:i', strtotime($extra['jam_mulai']))) ?></td>
                                    <td><?= htmlspecialchars(date('H:i', strtotime($extra['jam_selesai']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center" role="alert">
                    Tidak ada ekstrakurikuler yang dibina saat ini.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>