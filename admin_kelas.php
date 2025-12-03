<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

$error_message = '';
$success_message = '';

// Pengaturan Pagination
$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
// Pastikan halaman tidak kurang dari 1
if ($page < 1) {
    $page = 1;
}
$start = ($page - 1) * $limit;

// Pengaturan Search
$search = $_GET['search'] ?? '';
$search_query = '';
$params = [];

if (!empty($search)) {
    $search_query = "WHERE nama_kelas LIKE ? OR jurusan LIKE ?";
    $params = ["%$search%", "%$search%"];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $pdo->beginTransaction();

        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO tb_kelas (jenjang, jurusan, nama_kelas) VALUES (?, ?, ?)");
            if ($stmt->execute([$_POST['jenjang'], $_POST['jurusan'], $_POST['nama_kelas']])) {
                $success_message = "Data kelas berhasil ditambahkan.";
            }
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE tb_kelas SET jenjang = ?, jurusan = ?, nama_kelas = ? WHERE id_kelas = ?");
            if ($stmt->execute([$_POST['jenjang'], $_POST['jurusan'], $_POST['nama_kelas'], $_POST['id_kelas']])) {
                $success_message = "Data kelas berhasil diperbarui.";
            }
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM tb_kelas WHERE id_kelas = ?");
            if ($stmt->execute([$_POST['id_kelas']])) {
                $success_message = "Data kelas berhasil dihapus.";
            }
        }
        $pdo->commit();
        
        // Redirect menggunakan JavaScript untuk menghindari header
        echo "<script>window.location.href = 'index.php?page=admin_kelas&p=$page&search=" . urlencode($search) . "';</script>";
        exit();
    }

    // Menghitung total data untuk pagination, termasuk filter pencarian
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM tb_kelas $search_query");
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetchColumn();
    $total_pages = ceil($total_records / $limit);
    
    // Sesuaikan halaman jika parameter page melebihi total halaman
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
        $start = ($page - 1) * $limit;
    } elseif ($total_pages == 0) {
        $start = 0;
    }

    // Mengambil data kelas untuk halaman saat ini dengan filter pencarian
    $sql = "SELECT * FROM tb_kelas $search_query ORDER BY jenjang, jurusan, nama_kelas LIMIT $start, $limit";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $kelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $pdo->rollBack();
    $error_message = "Error: " . $e->getMessage();
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
        <h2 class="display-5 fw-bold mb-0">Kelola Kelas</h2>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card card-main">
                <div class="action-bar d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <a href="index.php?page=dashboard_admin" class="btn btn-secondary mb-2 mb-md-0"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
                    
                    <form method="GET" class="search-form">
                        <input type="hidden" name="page" value="admin_kelas">
                        <input type="text" name="search" id="search-input" class="form-control search-input" placeholder="Cari Kelas..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary btn-search" type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    
                    <button class="btn btn-primary mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-2"></i>Tambah Kelas</button>
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
                                <th scope="col">Jenjang</th>
                                <th scope="col">Jurusan</th>
                                <th scope="col">Nama Kelas</th>
                                <th scope="col" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($kelas)): ?>
                                <?php $i = $start + 1; ?>
                                <?php foreach ($kelas as $k): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($k['jenjang']) ?></td>
                                        <td><?= htmlspecialchars($k['jurusan']) ?></td>
                                        <td><?= htmlspecialchars($k['nama_kelas']) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal<?= $k['id_kelas'] ?>" aria-label="Edit Kelas"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $k['id_kelas'] ?>" aria-label="Hapus Kelas"><i class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $k['id_kelas'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Kelas</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST" action="index.php?page=admin_kelas&p=<?= $page ?>&search=<?= urlencode($search) ?>">
                                                        <input type="hidden" name="action" value="update">
                                                        <input type="hidden" name="id_kelas" value="<?= $k['id_kelas'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Jenjang</label>
                                                            <select class="form-select" name="jenjang" required>
                                                                <option value="10" <?= $k['jenjang'] == '10' ? 'selected' : '' ?>>10</option>
                                                                <option value="11" <?= $k['jenjang'] == '11' ? 'selected' : '' ?>>11</option>
                                                                <option value="12" <?= $k['jenjang'] == '12' ? 'selected' : '' ?>>12</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Jurusan</label>
                                                            <select class="form-select" name="jurusan" required>
                                                                <option value="AKL" <?= $k['jurusan'] == 'AKL' ? 'selected' : '' ?>>AKL</option>
                                                                <option value="MPLB" <?= $k['jurusan'] == 'MPLB' ? 'selected' : '' ?>>MPLB</option>
                                                                <option value="PM" <?= $k['jurusan'] == 'PM' ? 'selected' : '' ?>>PM</option>
                                                                <option value="PPLG" <?= $k['jurusan'] == 'PPLG' ? 'selected' : '' ?>>PPLG</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Nama Kelas</label>
                                                            <input type="text" class="form-control" name="nama_kelas" value="<?= htmlspecialchars($k['nama_kelas']) ?>" required>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?= $k['id_kelas'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger">
                                                    <h5 class="modal-title text-white">Konfirmasi Hapus</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <p>Apakah Anda yakin ingin menghapus kelas <strong><?= htmlspecialchars($k['nama_kelas']) ?></strong>?</p>
                                                    <form method="POST" action="index.php?page=admin_kelas&p=<?= $page ?>&search=<?= urlencode($search) ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_kelas" value="<?= $k['id_kelas'] ?>">
                                                        <button type="submit" class="btn btn-danger w-100">Ya, Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data kelas yang ditemukan.</td>
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
                                <a class="page-link" href="index.php?page=admin_kelas&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?page=admin_kelas&p=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?page=admin_kelas&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" aria-label="Next">
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
                <h5 class="modal-title">Tambah Kelas Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="index.php?page=admin_kelas&p=<?= $page ?>&search=<?= urlencode($search) ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Jenjang</label>
                        <select class="form-select" name="jenjang" required>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jurusan</label>
                        <select class="form-select" name="jurusan" required>
                            <option value="AKL">AKL</option>
                            <option value="MPLB">MPLB</option>
                            <option value="PM">PM</option>
                            <option value="PPLG">PPLG</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Kelas</label>
                        <input type="text" class="form-control" name="nama_kelas" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Tambah</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>