<?php
require_once 'config.php';

error_log("Presensi Siswa Access - Time: " . date('Y-m-d H:i:s') . " | Role: " . ($_SESSION['role'] ?? 'unset') . " | User ID: " . ($_SESSION['user_id'] ?? 'unset') . " | Username: " . ($_SESSION['username'] ?? 'unset'));

$error_message = '';
$presensi_rekap = [];

if (empty($_SESSION) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa' || !isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || !isset($_SESSION['username']) || empty($_SESSION['username'])) {
    $error_message = 'Sesi tidak valid atau data tidak lengkap. <a href="login.php" class="alert-link">Login ulang sekarang</a>.';
    error_log("Authentication failed: Invalid or incomplete session data - Session: " . print_r($_SESSION, true));
} else {
    try {
        // Tes koneksi
        $test_stmt = $pdo->query("SELECT 1");
        error_log("Database connection test successful for User ID: " . $_SESSION['user_id']);

        // Query untuk rekap presensi berdasarkan User ID
        $stmt = $pdo->prepare("
            SELECT 
                e.nama_ekstra, 
                p.tanggal, 
                p.status,
                e.hari,
                e.jam_mulai,
                e.jam_selesai
            FROM tb_presensi p
            JOIN tb_ekstrakurikuler e ON p.id_ekstra = e.id_ekstra
            WHERE p.nis = ? 
            ORDER BY p.tanggal DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $presensi_rekap = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Presensi rekap for User ID {$_SESSION['user_id']}: " . json_encode($presensi_rekap));

        if (empty($presensi_rekap)) {
            error_log("No presensi data found for User ID: " . $_SESSION['user_id']);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage() . " | Query: " . $stmt->queryString);
        $error_message = "Gagal memuat data presensi. Coba lagi nanti. Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Presensi Siswa - SMKN 2 Magelang</title>
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
            .table th, .table td {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container my-4">
        <div class="page-header">
            <h2 class="display-5 fw-bold mb-0">Rekap Presensi Siswa</h2>
            <p class="lead mb-0">Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
        </div>

        <div class="card-main">
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <h4 class="mb-3">Daftar Presensi</h4>
            <?php if (!empty($presensi_rekap)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Nama Ekstrakurikuler</th>
                                <th>Tanggal</th>
                                <th>Hari</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($presensi_rekap as $presensi): ?>
                                <tr>
                                    <td><?= htmlspecialchars($presensi['nama_ekstra']) ?></td>
                                    <td><?= htmlspecialchars(date('d-m-Y', strtotime($presensi['tanggal']))) ?></td>
                                    <td><?= htmlspecialchars($presensi['hari']) ?></td>
                                    <td><?= htmlspecialchars(date('H:i', strtotime($presensi['jam_mulai']))) ?></td>
                                    <td><?= htmlspecialchars(date('H:i', strtotime($presensi['jam_selesai']))) ?></td>
                                    <td><?= htmlspecialchars($presensi['status'] ?? 'Belum Tercatat') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center" role="alert">
                    Belum ada data presensi untuk Anda. Pastikan Anda telah mengikuti ekstrakurikuler dan presensi telah dicatat.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>