<?php 
// File ini adalah konten yang akan di-include oleh index.php
// Pastikan tidak ada tag <html>, <head>, atau <body> ganda di sini.

// Logika PHP dari file asli
$error_message = '';
$success_message = '';

try {
    error_log("Session: user_id = {$_SESSION['user_id']}, role = {$_SESSION['role']}");

    $stmt_all_peserta = $pdo->prepare("
        SELECT pe.id_ekstra, e.hari, e.jam_mulai, e.jam_selesai, pe.status
        FROM tb_peserta_ekstra pe
        JOIN tb_ekstrakurikuler e ON pe.id_ekstra = e.id_ekstra
        WHERE pe.nis = ? AND pe.status IN ('approved', 'pending')
    ");
    $stmt_all_peserta->execute([$_SESSION['user_id']]);
    $all_extras = $stmt_all_peserta->fetchAll(PDO::FETCH_ASSOC);
    error_log("Fetched extras for nis {$_SESSION['user_id']}: " . json_encode($all_extras));

    $stmt_ekstra = $pdo->query("SELECT id_ekstra, nama_ekstra, hari, jam_mulai, jam_selesai FROM tb_ekstrakurikuler ORDER BY nama_ekstra");
    $ekstras = $stmt_ekstra->fetchAll(PDO::FETCH_ASSOC);

    $registered_extras = array_column($all_extras, 'id_ekstra');
    $approved_extras = array_column(array_filter($all_extras, fn($e) => $e['status'] === 'approved'), 'id_ekstra');
    $pending_extras = array_column(array_filter($all_extras, fn($e) => $e['status'] === 'pending'), 'id_ekstra');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_ekstra = $_POST['id_ekstra'] ?? '';
        error_log("POST received: id_ekstra = $id_ekstra");

        if (empty($id_ekstra)) {
            $error_message = "Pilih ekstrakurikuler terlebih dahulu.";
        } else {
            if (count($all_extras) >= 2) {
                $error_message = "Anda sudah mencapai batas maksimum 2 ekstrakurikuler.";
            } else {
                $stmt_check = $pdo->prepare("
                    SELECT COUNT(*) FROM tb_peserta_ekstra WHERE nis = ? AND id_ekstra = ? AND status IN ('pending', 'approved')
                ");
                $stmt_check->execute([$_SESSION['user_id'], $id_ekstra]);
                $already_registered = $stmt_check->fetchColumn();
                error_log("Check result for nis {$_SESSION['user_id']}, id_ekstra $id_ekstra: $already_registered");

                if ($already_registered) {
                    $error_message = "Anda sudah terdaftar atau memiliki pendaftaran tertunda untuk ekstrakurikuler ini.";
                } else {
                    $stmt_selected = $pdo->prepare("SELECT hari, jam_mulai, jam_selesai FROM tb_ekstrakurikuler WHERE id_ekstra = ?");
                    $stmt_selected->execute([$id_ekstra]);
                    $selected_extra = $stmt_selected->fetch(PDO::FETCH_ASSOC);
                    error_log("Selected extra schedule: " . json_encode($selected_extra));

                    $conflict = false;
                    foreach ($all_extras as $extra) {
                        if ($extra['hari'] === $selected_extra['hari']) {
                            $existing_start = strtotime($extra['jam_mulai']);
                            $existing_end = strtotime($extra['jam_selesai']);
                            $new_start = strtotime($selected_extra['jam_mulai']);
                            $new_end = strtotime($selected_extra['jam_selesai']);

                            if (!($new_end <= $existing_start || $new_start >= $existing_end)) {
                                $conflict = true;
                                $error_message = "Jadwal ekstrakurikuler ini bertabrakan dengan ekstrakurikuler lain yang sudah Anda daftar atau ajukan.";
                                break;
                            }
                        }
                    }

                    if (!$conflict) {
                        $stmt_insert = $pdo->prepare("INSERT INTO tb_peserta_ekstra (nis, id_ekstra, tgl_daftar, status) VALUES (?, ?, CURDATE(), 'pending')");
                        $insert_result = $stmt_insert->execute([$_SESSION['user_id'], $id_ekstra]);
                        error_log("Insert attempt for nis {$_SESSION['user_id']}, id_ekstra $id_ekstra: " . ($insert_result ? 'Success' : 'Failed') . ", Error: " . print_r($stmt_insert->errorInfo(), true));

                        if ($insert_result) {
                            $success_message = "Pendaftaran berhasil dikirim! Menunggu konfirmasi dari pembina.";
                            $stmt_all_peserta->execute([$_SESSION['user_id']]);
                            $all_extras = $stmt_all_peserta->fetchAll(PDO::FETCH_ASSOC);
                            $registered_extras = array_column($all_extras, 'id_ekstra');
                            $approved_extras = array_column(array_filter($all_extras, fn($e) => $e['status'] === 'approved'), 'id_ekstra');
                            $pending_extras = array_column(array_filter($all_extras, fn($e) => $e['status'] === 'pending'), 'id_ekstra');
                        } else {
                            $error_message = "Gagal mengirim pendaftaran. Silakan coba lagi. Error: " . $stmt_insert->errorInfo()[2];
                        }
                    }
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log("PDO Exception: " . $e->getMessage());
    $error_message = "Terjadi kesalahan database. Silakan coba lagi nanti. Error: " . $e->getMessage();
}
?>

<!-- Custom CSS untuk tampilan ini -->
<style>
    :root {
        --primary: #4B9CD3;
        --primary-dark: #2A6F97;
        --success: #28A745;
        --warning: #FFC107;
        --background: #F9F9F9;
        --card-bg: #E0E7FF;
        --text-primary: #1A2E44;
        --text-secondary: #6B7280;
        --shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #F9F9F9 0%, #E0E7FF 100%);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        color: var(--text-primary);
        padding-top: 65px; /* Menyesuaikan dengan tinggi navbar */
    }
    .hero {
        background: url('https://source.unsplash.com/1200x300?school') no-repeat center/cover;
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        margin-bottom: 2rem;
        border-radius: 0 0 15px 15px;
        animation: fadeIn 1s ease-out;
    }
    .hero h1 {
        color: var(--text-primary); /* Mengubah warna font menjadi tidak putih */
        text-shadow: 0 1px 2px rgba(255, 255, 255, 0.5); /* Menyesuaikan text-shadow agar terlihat jelas */
    }
    .container {
        flex: 1;
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }
    .card-main {
        background-color: var(--card-bg);
        border-radius: 15px;
        box-shadow: var(--shadow);
        padding: 2.5rem;
        animation: slideUp 0.8s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    h2 {
        font-weight: 600;
        font-size: 2.5rem;
        margin-bottom: 1rem;
        text-align: center;
    }
    .subtitle {
        color: var(--text-secondary);
        font-size: 1.1rem;
        text-align: center;
        margin-bottom: 2rem;
    }
    .alert {
        border-radius: 10px;
        font-size: 1rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .card-extra {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: white;
        margin-bottom: 1.5rem;
    }
    .card-extra:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }
    .card-extra .card-body {
        padding: 1.5rem;
    }
    .card-extra .card-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.8rem;
    }
    .card-extra .card-text {
        color: var(--text-secondary);
        font-size: 1rem;
        margin-bottom: 1.2rem;
    }
    .card-available { border-left: 5px solid var(--primary); }
    .card-registered { border-left: 5px solid var(--success); }
    .card-pending { border-left: 5px solid var(--warning); }
    .badge-registered {
        background-color: var(--success);
        color: white;
        font-size: 0.9rem;
        padding: 0.3rem 0.8rem;
        border-radius: 10px;
    }
    .badge-pending {
        background-color: var(--warning);
        color: white;
        font-size: 0.9rem;
        padding: 0.3rem 0.8rem;
        border-radius: 10px;
    }
    .btn-primary {
        background-color: var(--primary);
        border: none;
        border-radius: 8px;
        padding: 0.6rem 1.2rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
    }
    .footer-text {
        color: var(--text-secondary);
        font-size: 0.9rem;
        text-align: center;
        padding: 1.5rem 0;
        border-top: 1px solid #E0E7FF;
    }
    @media (max-width: 768px) {
        .card-extra { margin-bottom: 1rem; }
        h2 { font-size: 2rem; }
        .card-main { padding: 1.5rem; }
    }
</style>

<!-- Hero section -->
<div class="hero">
    <h1 class="display-4">Daftar Ekstrakurikuler</h1>
</div>

<!-- Main content container -->
<div class="container">
    <div class="card card-main">
        <p class="subtitle">SMKN 2 Magelang</p>

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

        <div class="mb-4">
            <input type="text" class="form-control" id="filterInput" placeholder="Cari ekstrakurikuler..." style="max-width: 300px; margin: 0 auto;">
        </div>

        <?php if (!empty($ekstras)): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($ekstras as $ekstra): ?>
                    <div class="col">
                        <div class="card card-extra <?= in_array($ekstra['id_ekstra'], $registered_extras) ? (in_array($ekstra['id_ekstra'], $approved_extras) ? 'card-registered' : 'card-pending') : 'card-available' ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($ekstra['nama_ekstra']) ?></h5>
                                <p class="card-text">
                                    <i class="fas fa-calendar-day me-2"></i>
                                    <?= htmlspecialchars($ekstra['hari']) ?>,
                                    <?= date('H:i', strtotime($ekstra['jam_mulai'])) ?> -
                                    <?= date('H:i', strtotime($ekstra['jam_selesai'])) ?>
                                </p>
                                <?php if (!in_array($ekstra['id_ekstra'], $registered_extras)): ?>
                                    <form action="index.php?page=daftar_ekstra" method="POST" onsubmit="return confirmRegistration(event, '<?= htmlspecialchars($ekstra['nama_ekstra']) ?>')">
                                        <input type="hidden" name="id_ekstra" value="<?= htmlspecialchars($ekstra['id_ekstra']) ?>">
                                        <button type="submit" class="btn btn-primary">Daftar</button>
                                    </form>
                                <?php elseif (in_array($ekstra['id_ekstra'], $approved_extras)): ?>
                                    <span class="badge badge-registered">Sudah Terdaftar</span>
                                <?php else: ?>
                                    <span class="badge badge-pending">Menunggu Konfirmasi</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">Tidak ada ekstrakurikuler yang tersedia saat ini.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    function confirmRegistration(event, nama_ekstra) {
        if (!confirm(`Apakah Anda yakin ingin mendaftar untuk ${nama_ekstra}?`)) {
            event.preventDefault();
            return false;
        }
        return true;
    }

    document.getElementById('filterInput').addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('.card-extra').forEach(card => {
            const title = card.querySelector('.card-title').textContent.toLowerCase();
            card.style.display = title.includes(filter) ? '' : 'none';
        });
    });
</script>
