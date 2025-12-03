<?php
require_once 'config.php';

// Pastikan hanya admin yang dapat mengakses halaman ini
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
    $search_query = "WHERE nama LIKE ?";
    $params = ["%$search%"];
}

try {
    // Handle POST request untuk CRUD
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $pdo->beginTransaction();

        if ($action === 'create') {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO tb_pembina (nama, password) VALUES (?, ?)");
            if ($stmt->execute([$_POST['nama'], $hashed_password])) {
                $success_message = "Data pembina berhasil ditambahkan.";
            } else {
                $error_message = "Gagal menambahkan data pembina.";
            }
        } elseif ($action === 'update') {
            $sql = "UPDATE tb_pembina SET nama = ? WHERE id_pembina = ?";
            $update_params = [$_POST['nama'], $_POST['id_pembina']];

            if (!empty($_POST['password'])) {
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql = "UPDATE tb_pembina SET nama = ?, password = ? WHERE id_pembina = ?";
                $update_params = [$_POST['nama'], $hashed_password, $_POST['id_pembina']];
            }
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($update_params)) {
                $success_message = "Data pembina berhasil diperbarui.";
            } else {
                $error_message = "Gagal memperbarui data pembina.";
            }
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM tb_pembina WHERE id_pembina = ?");
            if ($stmt->execute([$_POST['id_pembina']])) {
                $success_message = "Data pembina berhasil dihapus.";
            } else {
                $error_message = "Gagal menghapus data pembina.";
            }
        }
        $pdo->commit();
        echo "<script>window.location.href = 'index.php?page=admin_pembina&p=$page&search=" . urlencode($search) . "';</script>";
        exit();
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    $error_message = "Gagal memproses data: " . $e->getMessage();
}

if (isset($_GET['success_message'])) {
    $success_message = htmlspecialchars($_GET['success_message']);
}

// Ambil total data pembina
$sql_count = "SELECT COUNT(id_pembina) AS total FROM tb_pembina " . $search_query;
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_records = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $limit);

// Ambil data pembina dengan pagination dan pencarian
$sql_pembina = "SELECT id_pembina, nama FROM tb_pembina " . $search_query . " LIMIT ?, ?";
$stmt_pembina = $pdo->prepare($sql_pembina);

// Bind parameter pencarian (jika ada) dan LIMIT secara eksplisit
$param_index = 1;
foreach ($params as $param) {
    $stmt_pembina->bindValue($param_index++, $param, PDO::PARAM_STR);
}
$stmt_pembina->bindValue($param_index++, $start, PDO::PARAM_INT);
$stmt_pembina->bindValue($param_index++, $limit, PDO::PARAM_INT);
$stmt_pembina->execute();

$pembinas = $stmt_pembina->fetchAll(PDO::FETCH_ASSOC);
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
        <h2 class="display-5 fw-bold mb-0">Kelola Pembina</h2>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card card-main">
                <div class="action-bar d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <a href="index.php?page=dashboard_admin" class="btn btn-secondary mb-2 mb-md-0"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
                    
                    <form method="GET" class="search-form">
                        <input type="hidden" name="page" value="admin_pembina">
                        <input type="text" name="search" id="search-input" class="form-control search-input" placeholder="Cari Nama Pembina..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary btn-search" type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    
                    <button class="btn btn-primary mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-2"></i>Tambah Pembina</button>
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
                                <th scope="col">Nama Pembina</th>
                                <th scope="col" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pembinas) > 0): ?>
                                <?php $i = $start + 1; ?>
                                <?php foreach ($pembinas as $pembina): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($pembina['nama']) ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal<?= $pembina['id_pembina'] ?>" aria-label="Edit Pembina"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $pembina['id_pembina'] ?>" aria-label="Hapus Pembina"><i class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $pembina['id_pembina'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Pembina</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST" action="index.php?page=admin_pembina&p=<?= $page ?>&search=<?= urlencode($search) ?>">
                                                        <input type="hidden" name="action" value="update">
                                                        <input type="hidden" name="id_pembina" value="<?= $pembina['id_pembina'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Nama Pembina</label>
                                                            <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($pembina['nama']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Password (kosongkan jika tidak ingin diubah)</label>
                                                            <input type="password" class="form-control" name="password">
                                                        </div>
                                                        <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?= $pembina['id_pembina'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger">
                                                    <h5 class="modal-title text-white">Konfirmasi Hapus</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <p>Apakah Anda yakin ingin menghapus pembina <strong><?= htmlspecialchars($pembina['nama']) ?></strong>?</p>
                                                    <form method="POST" action="index.php?page=admin_pembina&p=<?= $page ?>&search=<?= urlencode($search) ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_pembina" value="<?= $pembina['id_pembina'] ?>">
                                                        <button type="submit" class="btn btn-danger w-100">Ya, Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">Tidak ada data pembina ditemukan.</td>
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
                                <a class="page-link" href="index.php?page=admin_pembina&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?page=admin_pembina&p=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?page=admin_pembina&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" aria-label="Next">
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
                <h5 class="modal-title">Tambah Pembina Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="index.php?page=admin_pembina&p=<?= $page ?>&search=<?= urlencode($search) ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Nama Pembina</label>
                        <input type="text" class="form-control" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Tambah</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>