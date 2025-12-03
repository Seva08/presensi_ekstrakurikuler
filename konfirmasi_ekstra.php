<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'pembina') {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';

try {
    // Fetch approved registrations for extracurriculars managed by this pembina
    $stmt_approved = $pdo->prepare("
        SELECT pe.nis, pe.id_ekstra, s.nama AS nama_siswa, e.nama_ekstra
        FROM tb_peserta_ekstra pe
        JOIN tb_siswa s ON pe.nis = s.nis
        JOIN tb_ekstrakurikuler e ON pe.id_ekstra = e.id_ekstra
        WHERE e.id_pembina = ? AND pe.status = 'approved'
        ORDER BY s.nama
    ");
    $stmt_approved->execute([$_SESSION['user_id']]);
    $approved_registrations = $stmt_approved->fetchAll(PDO::FETCH_ASSOC);

    // Fetch pending registrations for extracurriculars managed by this pembina
    $stmt_pending = $pdo->prepare("
        SELECT pe.nis, pe.id_ekstra, pe.tgl_daftar, s.nama AS nama_siswa, e.nama_ekstra
        FROM tb_peserta_ekstra pe
        JOIN tb_siswa s ON pe.nis = s.nis
        JOIN tb_ekstrakurikuler e ON pe.id_ekstra = e.id_ekstra
        WHERE e.id_pembina = ? AND pe.status = 'pending'
        ORDER BY pe.tgl_daftar DESC
    ");
    $stmt_pending->execute([$_SESSION['user_id']]);
    $pending_registrations = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nis = $_POST['nis'] ?? '';
        $id_ekstra = $_POST['id_ekstra'] ?? '';
        $action = $_POST['action'] ?? '';

        if (empty($nis) || empty($id_ekstra) || empty($action)) {
            $error_message = "Data tidak lengkap. Silakan coba lagi.";
        } else {
            // Verify the registration belongs to this pembina's extracurricular
            $stmt_verify = $pdo->prepare("
                SELECT COUNT(*) 
                FROM tb_peserta_ekstra pe
                JOIN tb_ekstrakurikuler e ON pe.id_ekstra = e.id_ekstra
                WHERE pe.nis = ? AND pe.id_ekstra = ? AND e.id_pembina = ? AND pe.status = 'pending'
            ");
            $stmt_verify->execute([$nis, $id_ekstra, $_SESSION['user_id']]);
            $valid = $stmt_verify->fetchColumn();

            if (!$valid) {
                $error_message = "Pendaftaran tidak valid atau tidak ditemukan.";
            } else {
                if ($action === 'approve') {
                    // Approve: Update status to approved
                    $stmt_update = $pdo->prepare("UPDATE tb_peserta_ekstra SET status = 'approved' WHERE nis = ? AND id_ekstra = ?");
                    if ($stmt_update->execute([$nis, $id_ekstra])) {
                        $success_message = "Pendaftaran untuk NIS $nis berhasil disetujui.";
                    } else {
                        $error_message = "Gagal menyetujui pendaftaran. Silakan coba lagi.";
                    }
                } elseif ($action === 'reject') {
                    // Reject: Delete the record from tb_peserta_ekstra
                    $stmt_delete = $pdo->prepare("DELETE FROM tb_peserta_ekstra WHERE nis = ? AND id_ekstra = ?");
                    if ($stmt_delete->execute([$nis, $id_ekstra])) {
                        $success_message = "Pendaftaran untuk NIS $nis berhasil ditolak.";
                    } else {
                        $error_message = "Gagal menolak pendaftaran. Silakan coba lagi.";
                    }
                }
                // Refresh registrations
                $stmt_approved->execute([$_SESSION['user_id']]);
                $approved_registrations = $stmt_approved->fetchAll(PDO::FETCH_ASSOC);
                $stmt_pending->execute([$_SESSION['user_id']]);
                $pending_registrations = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
} catch (PDOException $e) {
    error_log("Confirmation error: " . $e->getMessage());
    $error_message = "Terjadi kesalahan database. Silakan coba lagi nanti.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pendaftaran Ekstrakurikuler - SMKN 2 Magelang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/g/3nC6O8HHzG95yD9l6X4GfM3">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --success: #16a34a;
            --warning: #f59e0b;
            --background: #ffffff;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            flex: 1;
            padding: 2rem;
        }

        .card-main {
            background-color: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .alert {
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        .card-extra {
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: slideIn 0.5s ease-out forwards;
        }

        .card-extra:nth-child(1) { animation-delay: 0.1s; }
        .card-extra:nth-child(2) { animation-delay: 0.2s; }
        .card-extra:nth-child(3) { animation-delay: 0.3s; }
        .card-extra:nth-child(4) { animation-delay: 0.4s; }

        .card-extra:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .card-extra .card-body {
            padding: 1.5rem;
        }

        .card-extra .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }

        .card-extra .card-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .card-registered {
            border-left: 4px solid var(--success);
        }

        .card-pending {
            border-left: 4px solid var(--warning);
        }

        .badge-registered {
            background-color: var(--success);
            font-size: 0.8rem;
        }

        .badge-pending {
            background-color: var(--warning);
            font-size: 0.8rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border: none;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-approve {
            background-color: var(--success);
            border: none;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-approve:hover {
            background-color: #15803d;
            transform: translateY(-2px);
        }

        .btn-reject {
            background-color: #ef4444;
            border: none;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-reject:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }

        .modal-content {
            border-radius: 0.75rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .footer-text {
            color: var(--text-secondary);
            font-size: 0.8rem;
            text-align: center;
            padding: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card card-main">
            <h2 class="text-center">Konfirmasi Pendaftaran Ekstrakurikuler</h2>
            <p class="subtitle text-center">SMKN 2 Magelang</p>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($approved_registrations)): ?>
                <h4 class="mt-4">Anggota Terdaftar</h4>
                <div class="row">
                    <?php foreach ($approved_registrations as $reg): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card card-extra card-registered">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($reg['nama_siswa']) ?></h5>
                                    <p class="card-text">
                                        <i class="fas fa-id-card me-2"></i>NIS: <?= htmlspecialchars($reg['nis']) ?>
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-book me-2"></i>Ekstrakurikuler: <?= htmlspecialchars($reg['nama_ekstra']) ?>
                                    </p>
                                    <span class="badge badge-registered">Sudah Terdaftar</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">Tidak ada anggota terdaftar saat ini.</p>
            <?php endif; ?>

            <?php if (!empty($pending_registrations)): ?>
                <h4 class="mt-4">Pendaftaran Tertunda</h4>
                <div class="row">
                    <?php foreach ($pending_registrations as $reg): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card card-extra card-pending">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($reg['nama_siswa']) ?></h5>
                                    <p class="card-text">
                                        <i class="fas fa-id-card me-2"></i>NIS: <?= htmlspecialchars($reg['nis']) ?>
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-book me-2"></i>Ekstrakurikuler: <?= htmlspecialchars($reg['nama_ekstra']) ?>
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-calendar-day me-2"></i>Tanggal Daftar: <?= htmlspecialchars($reg['tgl_daftar']) ?>
                                    </p>
                                    <button class="btn btn-primary confirm-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#confirmModal"
                                            data-nis="<?= htmlspecialchars($reg['nis']) ?>"
                                            data-id_ekstra="<?= htmlspecialchars($reg['id_ekstra']) ?>"
                                            data-nama_siswa="<?= htmlspecialchars($reg['nama_siswa']) ?>"
                                            data-nama_ekstra="<?= htmlspecialchars($reg['nama_ekstra']) ?>">
                                        <i class="fas fa-check-circle me-2"></i>Konfirmasi
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">Tidak ada pendaftaran tertunda untuk dikonfirmasi.</p>
            <?php endif; ?>

            <p class="footer-text">&copy; <?= date('Y') ?> SMKN 2 Magelang</p>
        </div>
    </div>

    <!-- Modal for Confirmation -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Konfirmasi Pendaftaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Nama Siswa:</strong> <span id="modal-nama-siswa"></span></p>
                    <p><strong>NIS:</strong> <span id="modal-nis"></span></p>
                    <p><strong>Ekstrakurikuler:</strong> <span id="modal-nama-ekstra"></span></p>
                    <p>Pilih aksi untuk pendaftaran ini:</p>
                </div>
                <div class="modal-footer">
                    <form id="confirmForm" action="index.php?page=konfirmasi_ekstra" method="POST">
                        <input type="hidden" name="nis" id="modal-nis-input">
                        <input type="hidden" name="id_ekstra" id="modal-id-ekstra-input">
                        <input type="hidden" name="action" id="modal-action-input">
                        <button type="submit" class="btn btn-approve" onclick="document.getElementById('modal-action-input').value='approve'">
                            <i class="fas fa-check me-2"></i>Setujui
                        </button>
                        <button type="submit" class="btn btn-reject" onclick="document.getElementById('modal-action-input').value='reject'">
                            <i class="fas fa-times me-2"></i>Tolak
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.querySelectorAll('.confirm-btn').forEach(button => {
            button.addEventListener('click', () => {
                const nis = button.getAttribute('data-nis');
                const id_ekstra = button.getAttribute('data-id_ekstra');
                const nama_siswa = button.getAttribute('data-nama_siswa');
                const nama_ekstra = button.getAttribute('data-nama_ekstra');

                document.getElementById('modal-nama-siswa').textContent = nama_siswa;
                document.getElementById('modal-nis').textContent = nis;
                document.getElementById('modal-nama-ekstra').textContent = nama_ekstra;
                document.getElementById('modal-nis-input').value = nis;
                document.getElementById('modal-id-ekstra-input').value = id_ekstra;
            });
        });
    </script>
</body>
</html>