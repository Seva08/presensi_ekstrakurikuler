<?php
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pembina') {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$error_message = '';
$success_message = '';
$show_table = false;
$extracurriculars = [];
$registered_students = [];
$is_scheduled_today = false;
$selected_ekstra_id = '';
$nama_ekstra_selected = '';
$extracurricular_schedule = [];

// Fetch extracurriculars for the pembina
try {
    $stmt_extras = $pdo->prepare("SELECT id_ekstra, nama_ekstra FROM tb_ekstrakurikuler WHERE id_pembina = ? ORDER BY nama_ekstra");
    $stmt_extras->execute([$_SESSION['user_id']]);
    $extracurriculars = $stmt_extras->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching extracurriculars: " . $e->getMessage());
    $error_message = "Terjadi kesalahan saat mengambil data ekstrakurikuler.";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_search'])) {
        $selected_ekstra_id = filter_input(INPUT_POST, 'id_ekstra', FILTER_VALIDATE_INT);
        if ($selected_ekstra_id) {
            try {
                // Validate extracurricular
                $stmt_check = $pdo->prepare("SELECT nama_ekstra, hari, jam_mulai, jam_selesai FROM tb_ekstrakurikuler WHERE id_ekstra = ?");
                $stmt_check->execute([$selected_ekstra_id]);
                $extra_data = $stmt_check->fetch(PDO::FETCH_ASSOC);
                
                if (!$extra_data) {
                    $error_message = "Ekstrakurikuler tidak ditemukan.";
                    $selected_ekstra_id = '';
                } else {
                    $nama_ekstra_selected = $extra_data['nama_ekstra'];
                    $extracurricular_schedule = $extra_data;
                    $today_day_ind = date('N');
                    $days_mapping = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                    $is_scheduled_today = ($days_mapping[$today_day_ind] === $extra_data['hari']);

                    // Fetch registered students
                    $stmt_students = $pdo->prepare("
                        SELECT pe.nis, s.nama AS nama_siswa
                        FROM tb_peserta_ekstra pe
                        JOIN tb_siswa s ON pe.nis = s.nis
                        WHERE pe.id_ekstra = ? AND pe.status = 'approved'
                        ORDER BY s.nama
                    ");
                    $stmt_students->execute([$selected_ekstra_id]);
                    $registered_students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

                    // Fetch today's presensi
                    $stmt_presensi = $pdo->prepare("SELECT nis, status, catatan FROM tb_presensi WHERE id_ekstra = ? AND tanggal = ?");
                    $stmt_presensi->execute([$selected_ekstra_id, date('Y-m-d')]);
                    $today_presensi = $stmt_presensi->fetchAll(PDO::FETCH_ASSOC);

                    $presensi_map = [];
                    foreach ($today_presensi as $presensi) {
                        $presensi_map[$presensi['nis']] = $presensi;
                    }
                    foreach ($registered_students as &$student) {
                        $student['presensi_today'] = $presensi_map[$student['nis']] ?? null;
                    }
                    unset($student); // Unset reference to avoid issues

                    $show_table = true;
                }
            } catch (PDOException $e) {
                error_log("Error processing form: " . $e->getMessage());
                $error_message = "Terjadi kesalahan saat memproses data.";
            }
        } else {
            $error_message = "Pilih ekstrakurikuler yang valid.";
        }
    } elseif (isset($_POST['save_presensi'])) {
        $selected_ekstra_id = filter_input(INPUT_POST, 'id_ekstra', FILTER_VALIDATE_INT);
        $tanggal = date('Y-m-d');
        $nis_list = $_POST['nis'] ?? [];
        $status_list = $_POST['status'] ?? [];
        $catatan_list = $_POST['catatan'] ?? [];

        if ($selected_ekstra_id && !empty($nis_list)) {
            try {
                $pdo->beginTransaction();
                foreach ($nis_list as $nis) {
                    $status = $status_list[$nis] ?? 'H';
                    $catatan = $catatan_list[$nis] ?? '';
                    if (in_array($status, ['H', 'I', 'S', 'A'])) {
                        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tb_presensi WHERE nis = ? AND id_ekstra = ? AND tanggal = ?");
                        $stmt_check->execute([$nis, $selected_ekstra_id, $tanggal]);
                        if ($stmt_check->fetchColumn() > 0) {
                            $stmt_update = $pdo->prepare("UPDATE tb_presensi SET status = ?, catatan = ? WHERE nis = ? AND id_ekstra = ? AND tanggal = ?");
                            $stmt_update->execute([$status, $catatan, $nis, $selected_ekstra_id, $tanggal]);
                        } else {
                            $stmt_insert = $pdo->prepare("INSERT INTO tb_presensi (id_ekstra, nis, tanggal, status, catatan) VALUES (?, ?, ?, ?, ?)");
                            $stmt_insert->execute([$selected_ekstra_id, $nis, $tanggal, $status, $catatan]);
                        }
                    }
                }
                $pdo->commit();
                $success_message = "Presensi siswa berhasil disimpan!";

                // Refresh student data
                try {
                    $stmt_students = $pdo->prepare("
                        SELECT pe.nis, s.nama AS nama_siswa
                        FROM tb_peserta_ekstra pe
                        JOIN tb_siswa s ON pe.nis = s.nis
                        WHERE pe.id_ekstra = ? AND pe.status = 'approved'
                        ORDER BY s.nama
                    ");
                    $stmt_students->execute([$selected_ekstra_id]);
                    $registered_students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

                    $stmt_extra_name = $pdo->prepare("SELECT nama_ekstra FROM tb_ekstrakurikuler WHERE id_ekstra = ?");
                    $stmt_extra_name->execute([$selected_ekstra_id]);
                    $nama_ekstra_selected = $stmt_extra_name->fetchColumn();

                    $stmt_presensi = $pdo->prepare("SELECT nis, status, catatan FROM tb_presensi WHERE id_ekstra = ? AND tanggal = ?");
                    $stmt_presensi->execute([$selected_ekstra_id, date('Y-m-d')]);
                    $today_presensi = $stmt_presensi->fetchAll(PDO::FETCH_ASSOC);

                    $presensi_map = [];
                    foreach ($today_presensi as $presensi) {
                        $presensi_map[$presensi['nis']] = $presensi;
                    }
                    foreach ($registered_students as &$student) {
                        $student['presensi_today'] = $presensi_map[$student['nis']] ?? null;
                    }
                    unset($student);

                    $show_table = true;
                } catch (PDOException $e) {
                    error_log("Error refreshing students: " . $e->getMessage());
                    $error_message = "Terjadi kesalahan saat memperbarui data siswa.";
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error saving presensi: " . $e->getMessage());
                $error_message = "Terjadi kesalahan saat menyimpan presensi.";
            }
        } else {
            $error_message = "Data input tidak lengkap atau tidak valid.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Presensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; }
        .card-main { background-color: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 2rem; max-width: 1200px; margin: 2rem auto; }
        .form-select, .form-control { border-radius: 8px; }
        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .btn-success { background-color: #28a745; border-color: #28a745; }
        .btn-outline-success { border-color: #28a745; color: #28a745; }
        .table-responsive { border: 1px solid #dee2e6; border-radius: 8px; margin-top: 1.5rem; }
        .table { --bs-table-bg: #fff; --bs-table-striped-bg: #f8f9fa; }
        .table-bordered { border: 1px solid #dee2e6; }
        .table th, .table td { border-color: #ced4da; padding: 0.75rem; font-size: 0.875rem; vertical-align: middle; }
        .table thead th { background-color: #e9ecef; font-weight: 600; color: #495057; }
        .table tbody tr:hover { background-color: #f1f3f5; }
        .catatan-input { width: 100%; min-width: 150px; }
        .status-btn { font-size: 0.8rem; padding: 0.2rem 0.5rem; margin-right: 0.2rem; background-color: transparent; color: #343a40; border-width: 1px; border-style: solid; }
        .status-btn.active { font-weight: bold; border-width: 2px; }
        .btn-hadir { border-color: #28a745; }
        .btn-hadir.active { border-color: #218838; }
        .btn-ijin { border-color: #17a2b8; }
        .btn-ijin.active { border-color: #138496; }
        .btn-sakit { border-color: #ffc107; }
        .btn-sakit.active { border-color: #e0a800; }
        .btn-alpha { border-color: #dc3545; }
        .btn-alpha.active { border-color: #c82333; }
        @media (max-width: 767px) {
            .table-responsive { overflow-x: auto; }
            .catatan-input { min-width: 100px; }
            .status-btn { font-size: 0.7rem; padding: 0.1rem 0.3rem; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container my-4">
        <div class="card card-main">
            <h2 class="text-center mb-1 fw-bold">Input Presensi</h2>
            <p class="text-center text-muted mb-4">Ekstrakurikuler SMKN 2 Magelang</p>

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

            <form action="" method="POST" class="mb-4">
                <input type="hidden" name="submit_search" value="1">
                <div class="row g-3 align-items-end">
                    <div class="col-md-9">
                        <label for="id_ekstra" class="form-label fw-bold">Pilih Ekstrakurikuler</label>
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
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Cari Siswa
                        </button>
                    </div>
                </div>
            </form>

            <?php if ($show_table): ?>
                <hr class="my-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Daftar Siswa <?= htmlspecialchars($nama_ekstra_selected) ?></h4>
                    <span class="text-muted"><?= date('l, d F Y') ?></span>
                </div>
                
                <?php if ($extracurricular_schedule): ?>
                    <div class="alert alert-info text-center" role="alert">
                        Jadwal: <strong><?= htmlspecialchars($extracurricular_schedule['hari']) ?></strong>, Pukul <strong><?= substr(htmlspecialchars($extracurricular_schedule['jam_mulai']), 0, 5) ?></strong> - <strong><?= substr(htmlspecialchars($extracurricular_schedule['jam_selesai']), 0, 5) ?></strong>
                    </div>
                <?php endif; ?>

                <?php if ($is_scheduled_today): ?>
                    <form action="" method="POST">
                        <input type="hidden" name="save_presensi" value="1">
                        <input type="hidden" name="id_ekstra" value="<?= htmlspecialchars($selected_ekstra_id) ?>">
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-outline-success" onclick="setAllHadir()">
                                <i class="fas fa-check-circle me-2"></i>Hadir Semua
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>NIS</th>
                                        <th>Nama Siswa</th>
                                        <th>Status</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($registered_students)): ?>
                                        <?php foreach ($registered_students as $index => $student): ?>
                                            <?php $currentStatus = $student['presensi_today']['status'] ?? 'H'; ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($student['nis']) ?></td>
                                                <td><?= htmlspecialchars($student['nama_siswa']) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group" aria-label="Status buttons">
                                                        <button type="button" class="btn status-btn btn-hadir <?= $currentStatus === 'H' ? 'active' : '' ?>" onclick="setStatus(this, 'H', '<?= htmlspecialchars($student['nis']) ?>')">HADIR</button>
                                                        <button type="button" class="btn status-btn btn-ijin <?= $currentStatus === 'I' ? 'active' : '' ?>" onclick="setStatus(this, 'I', '<?= htmlspecialchars($student['nis']) ?>')">IJIN</button>
                                                        <button type="button" class="btn status-btn btn-sakit <?= $currentStatus === 'S' ? 'active' : '' ?>" onclick="setStatus(this, 'S', '<?= htmlspecialchars($student['nis']) ?>')">SAKIT</button>
                                                        <button type="button" class="btn status-btn btn-alpha <?= $currentStatus === 'A' ? 'active' : '' ?>" onclick="setStatus(this, 'A', '<?= htmlspecialchars($student['nis']) ?>')">ALPHA</button>
                                                    </div>
                                                    <input type="hidden" name="status[<?= htmlspecialchars($student['nis']) ?>]" id="status-<?= htmlspecialchars($student['nis']) ?>" value="<?= $currentStatus ?>">
                                                    <input type="hidden" name="nis[]" value="<?= htmlspecialchars($student['nis']) ?>">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control catatan-input" name="catatan[<?= htmlspecialchars($student['nis']) ?>]" value="<?= htmlspecialchars($student['presensi_today']['catatan'] ?? '') ?>">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Tidak ada siswa yang terdaftar di ekstrakurikuler ini.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (!empty($registered_students)): ?>
                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Simpan Semua
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning text-center" role="alert">
                        Ekstrakurikuler <strong><?= htmlspecialchars($nama_ekstra_selected) ?></strong> tidak memiliki jadwal pada hari ini (<?= date('l') ?>).
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setStatus(button, status, nis) {
            const btnGroup = button.parentElement;
            const buttons = btnGroup.querySelectorAll('.status-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            document.getElementById(`status-${nis}`).value = status;
        }

        function setAllHadir() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const hadirBtn = row.querySelector('.btn-hadir');
                if (hadirBtn) {
                    const nis = hadirBtn.getAttribute('onclick').match(/'([^']+)'/)[1];
                    setStatus(hadirBtn, 'H', nis);
                }
            });
        }
    </script>
</body>
</html>