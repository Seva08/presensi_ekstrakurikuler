<?php
// Pastikan file konfigurasi sudah di-include
require_once 'config.php';

// Cek apakah pengguna sudah login dan memiliki peran 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

$error_message = '';
$success_message = '';

// Pengaturan Pagination
$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) {
    $page = 1;
}
$start = ($page - 1) * $limit;

// Pengaturan Search
$search = $_GET['search'] ?? '';
$search_query = '';
$params = [];

if (!empty($search)) {
    $search_query = "WHERE nama_ekstra LIKE ? OR hari LIKE ?";
    $params = ["%$search%", "%$search%"];
}

try {
    // Memproses permintaan POST untuk CRUD
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $pdo->beginTransaction();

        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO tb_ekstrakurikuler (nama_ekstra, id_pembina, hari, jam_mulai, jam_selesai) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$_POST['nama_ekstra'], $_POST['id_pembina'], $_POST['hari'], $_POST['jam_mulai'], $_POST['jam_selesai']])) {
                $success_message = "Data ekstrakurikuler berhasil ditambahkan.";
            }
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE tb_ekstrakurikuler SET nama_ekstra = ?, id_pembina = ?, hari = ?, jam_mulai = ?, jam_selesai = ? WHERE id_ekstra = ?");
            if ($stmt->execute([$_POST['nama_ekstra'], $_POST['id_pembina'], $_POST['hari'], $_POST['jam_mulai'], $_POST['jam_selesai'], $_POST['id_ekstra']])) {
                $success_message = "Data ekstrakurikuler berhasil diperbarui.";
            }
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM tb_ekstrakurikuler WHERE id_ekstra = ?");
            if ($stmt->execute([$_POST['id_ekstra']])) {
                $success_message = "Data ekstrakurikuler berhasil dihapus.";
            }
        }
        
        $pdo->commit();
        echo "<script>window.location.href = 'index.php?page=admin_ekstra&p=$page&search=" . urlencode($search) . "';</script>";
        exit();
    }

    // Menghitung total data untuk pagination dan pencarian
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM tb_ekstrakurikuler $search_query");
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetchColumn();
    $total_pages = ceil($total_records / $limit);
    
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
        $start = ($page - 1) * $limit;
    } elseif ($total_pages == 0) {
        $start = 0;
    }

    // Mengambil data ekstrakurikuler untuk halaman saat ini
    $sql = "SELECT e.*, p.nama AS nama_pembina 
            FROM tb_ekstrakurikuler e
            LEFT JOIN tb_pembina p ON e.id_pembina = p.id_pembina
            $search_query
            ORDER BY e.nama_ekstra
            LIMIT $start, $limit";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ekstras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mengambil daftar pembina untuk dropdown di modal
    $stmt_pembina = $pdo->query("SELECT id_pembina, nama FROM tb_pembina ORDER BY nama");
    $pembina_list = $stmt_pembina->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $pdo->rollBack();
    $error_message = "Error: " . $e->getMessage();
    error_log($error_message);
}
?>

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
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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
    .action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .search-form {
        display: flex;
        flex-grow: 1;
        margin: 0 1rem;
    }
    @media (max-width: 767px) {
        .action-bar {
            flex-direction: column;
        }
        .search-form {
            width: 100%;
            margin: 1rem 0;
        }
        .search-input {
            width: 100%;
        }
        .btn-search {
            width: auto;
        }
    }
    .search-input {
        flex-grow: 1;
        border-radius: 20px 0 0 20px;
        border: 1px solid var(--input-border);
        padding-left: 1rem;
        transition: all 0.3s ease;
    }
    .search-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        border-color: var(--primary-color);
    }
    .btn-search {
        border-radius: 0 20px 20px 0;
    }
    .table-responsive {
        margin-top: 1.5rem;
    }
    .table {
        margin-bottom: 0;
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
    .table tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
        cursor: pointer;
    }
    .btn-action {
        width: 70px;
    }
    .modal-content {
        border-radius: 8px;
        box-shadow: var(--shadow);
    }
    .modal-header {
        background-color: var(--primary-color);
        color: #fff;
        border-bottom: none;
        border-radius: 8px 8px 0 0;
    }
    .modal-header .btn-close {
        filter: invert(1);
    }
    .modal-body .form-label {
        font-weight: 500;
        color: #495057;
    }
    .pagination .page-link {
        color: var(--primary-color);
    }
    .pagination .page-item.active .page-link {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: #fff;
    }
</style>

<div class="container-fluid py-4">
    <div class="page-header">
        <h2 class="display-5 fw-bold mb-0">Kelola Ekstrakurikuler</h2>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card card-main">
                <div class="action-bar d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <a href="index.php?page=dashboard_admin" class="btn btn-secondary mb-2 mb-md-0"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
                    
                    <form method="GET" class="search-form">
                        <input type="hidden" name="page" value="admin_ekstra">
                        <input type="text" name="search" id="search-input" class="form-control search-input" placeholder="Cari Ekstrakurikuler..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary btn-search" type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    
                    <button class="btn btn-primary mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-2"></i>Tambah Ekstra</button>
                </div>
                
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
                
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th scope="col">NO</th>
                                <th scope="col">Nama Ekstra</th>
                                <th scope="col">Pembina</th>
                                <th scope="col">Hari</th>
                                <th scope="col">Jam Mulai</th>
                                <th scope="col">Jam Selesai</th>
                                <th scope="col" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ekstras)): ?>
                                <?php $i = $start + 1; ?>
                                <?php foreach ($ekstras as $ekstra): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($ekstra['nama_ekstra']) ?></td>
                                        <td><?= htmlspecialchars($ekstra['nama_pembina']) ?></td>
                                        <td><?= htmlspecialchars($ekstra['hari']) ?></td>
                                        <td><?= htmlspecialchars(date('H:i', strtotime($ekstra['jam_mulai']))) ?></td>
                                        <td><?= htmlspecialchars(date('H:i', strtotime($ekstra['jam_selesai']))) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal<?= $ekstra['id_ekstra'] ?>" aria-label="Edit Ekstra"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $ekstra['id_ekstra'] ?>" aria-label="Hapus Ekstra"><i class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $ekstra['id_ekstra'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Ekstrakurikuler</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST" action="index.php?page=admin_ekstra&p=<?= $page ?>&search=<?= urlencode($search) ?>">
                                                        <input type="hidden" name="action" value="update">
                                                        <input type="hidden" name="id_ekstra" value="<?= $ekstra['id_ekstra'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nama Ekstra</label>
                                                            <input type="text" class="form-control" name="nama_ekstra" value="<?= htmlspecialchars($ekstra['nama_ekstra']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Pembina</label>
                                                            <select class="form-select" name="id_pembina" required>
                                                                <option value="">Pilih Pembina</option>
                                                                <?php foreach ($pembina_list as $pembina): ?>
                                                                    <option value="<?= $pembina['id_pembina'] ?>" <?= $pembina['id_pembina'] == $ekstra['id_pembina'] ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($pembina['nama']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Hari</label>
                                                            <select class="form-select" name="hari" required>
                                                                <?php $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']; ?>
                                                                <?php foreach ($days as $day): ?>
                                                                    <option value="<?= $day ?>" <?= $day == $ekstra['hari'] ? 'selected' : '' ?>>
                                                                        <?= $day ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Jam Mulai</label>
                                                            <input type="time" class="form-control" name="jam_mulai" value="<?= htmlspecialchars($ekstra['jam_mulai']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Jam Selesai</label>
                                                            <input type="time" class="form-control" name="jam_selesai" value="<?= htmlspecialchars($ekstra['jam_selesai']) ?>" required>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?= $ekstra['id_ekstra'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger">
                                                    <h5 class="modal-title text-white">Konfirmasi Hapus</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <p>Apakah Anda yakin ingin menghapus ekstrakurikuler <strong><?= htmlspecialchars($ekstra['nama_ekstra']) ?></strong>?</p>
                                                    <form method="POST" action="index.php?page=admin_ekstra&p=<?= $page ?>&search=<?= urlencode($search) ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_ekstra" value="<?= $ekstra['id_ekstra'] ?>">
                                                        <button type="submit" class="btn btn-danger w-100">Ya, Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data ekstrakurikuler yang ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($total_pages > 1): ?>
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?page=admin_ekstra&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?page=admin_ekstra&p=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?page=admin_ekstra&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Ekstrakurikuler Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="index.php?page=admin_ekstra&p=<?= $page ?>&search=<?= urlencode($search) ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Nama Ekstra</label>
                        <input type="text" class="form-control" name="nama_ekstra" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pembina</label>
                        <select class="form-select" name="id_pembina" required>
                            <option value="">Pilih Pembina</option>
                            <?php foreach ($pembina_list as $pembina): ?>
                                <option value="<?= $pembina['id_pembina'] ?>">
                                    <?= htmlspecialchars($pembina['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hari</label>
                        <select class="form-select" name="hari" required>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jam Mulai</label>
                        <input type="time" class="form-control" name="jam_mulai" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jam Selesai</label>
                        <input type="time" class="form-control" name="jam_selesai" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Tambah</button>
                </form>
            </div>
        </div>
    </div>
</div>