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

// Pengaturan Search dan Status Filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'aktif'; // Default to 'aktif'
$search_query = "WHERE s.status = ?";
$params = [$status_filter];

if (!empty($search)) {
    $search_query .= " AND (s.nis LIKE ? OR s.nama LIKE ? OR k.nama_kelas LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

try {
    // Handle POST request untuk CRUD
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $pdo->beginTransaction();

        if ($action === 'create') {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO tb_siswa (nis, nama, id_kelas, password, status) VALUES (?, ?, ?, ?, 'aktif')");
            if ($stmt->execute([$_POST['nis'], $_POST['nama'], $_POST['id_kelas'], $hashed_password])) {
                $success_message = "Data siswa berhasil ditambahkan.";
            } else {
                $error_message = "Gagal menambahkan data siswa.";
            }
        } elseif ($action === 'update') {
            $sql = "UPDATE tb_siswa SET nama = ?, id_kelas = ? WHERE nis = ?";
            $update_params = [$_POST['nama'], $_POST['id_kelas'], $_POST['nis']];

            if (!empty($_POST['password'])) {
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql = "UPDATE tb_siswa SET nama = ?, id_kelas = ?, password = ? WHERE nis = ?";
                $update_params = [$_POST['nama'], $_POST['id_kelas'], $hashed_password, $_POST['nis']];
            }
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($update_params)) {
                $success_message = "Data siswa berhasil diperbarui.";
            } else {
                $error_message = "Gagal memperbarui data siswa.";
            }
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("UPDATE tb_siswa SET status = 'non-aktif' WHERE nis = ?");
            if ($stmt->execute([$_POST['nis']])) {
                $success_message = "Data siswa berhasil dinon-aktifkan.";
            } else {
                $error_message = "Gagal menon-aktifkan data siswa.";
            }
        } elseif ($action === 'restore') {
            $stmt = $pdo->prepare("UPDATE tb_siswa SET status = 'aktif' WHERE nis = ?");
            if ($stmt->execute([$_POST['nis']])) {
                $success_message = "Data siswa berhasil diaktifkan kembali.";
            } else {
                $error_message = "Gagal mengaktifkan data siswa.";
            }
        }
        $pdo->commit();
        echo "<script>window.location.href = 'index.php?page=admin_siswa&p=$page&search=" . urlencode($search) . "&status=$status_filter';</script>";
        exit();
    }

    // Ambil data kelas untuk dropdown
    $stmt_kelas_dropdown = $pdo->query("SELECT id_kelas, nama_kelas FROM tb_kelas ORDER BY nama_kelas");
    $kelass_dropdown = $stmt_kelas_dropdown->fetchAll(PDO::FETCH_ASSOC);

    // Ambil total data siswa
    $sql_count = "SELECT COUNT(s.nis) AS total FROM tb_siswa s LEFT JOIN tb_kelas k ON s.id_kelas = k.id_kelas " . $search_query;
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $limit);

    // Sesuaikan halaman jika parameter page melebihi total halaman
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
        $start = ($page - 1) * $limit;
    } elseif ($total_pages == 0) {
        $start = 0;
    }

    // Ambil data siswa dengan pagination dan pencarian
    $sql_siswa = "SELECT s.nis, s.nama, s.id_kelas, k.nama_kelas, s.status FROM tb_siswa s LEFT JOIN tb_kelas k ON s.id_kelas = k.id_kelas " . $search_query . " ORDER BY s.nama LIMIT ?, ?";
    $stmt_siswa = $pdo->prepare($sql_siswa);

    // Bind parameter pencarian (jika ada) dan LIMIT secara eksplisit
    $param_index = 1;
    foreach ($params as $param) {
        $stmt_siswa->bindValue($param_index++, $param, PDO::PARAM_STR);
    }
    $stmt_siswa->bindValue($param_index++, $start, PDO::PARAM_INT);
    $stmt_siswa->bindValue($param_index++, $limit, PDO::PARAM_INT);
    $stmt_siswa->execute();

    $siswas = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $pdo->rollBack();
    $error_message = "Gagal memproses data: " . $e->getMessage();
}

if (isset($_GET['success_message'])) {
    $success_message = htmlspecialchars($_GET['success_message']);
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
    .modal-header.bg-success {
        background-color: #28a745;
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
    .nav-tabs .nav-link {
        color: var(--primary-color);
        border-radius: 8px 8px 0 0;
    }
    .nav-tabs .nav-link.active {
        background-color: var(--primary-color);
        color: #fff;
        border-color: var(--primary-color);
    }
</style>

<div class="container-fluid py-4">
    <div class="page-header">
        <h2 class="display-5 fw-bold mb-0">Kelola Siswa</h2>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card card-main">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link <?= $status_filter == 'aktif' ? 'active' : '' ?>" href="index.php?page=admin_siswa&p=1&search=<?= urlencode($search) ?>&status=aktif">Siswa Aktif</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status_filter == 'non-aktif' ? 'active' : '' ?>" href="index.php?page=admin_siswa&p=1&search=<?= urlencode($search) ?>&status=non-aktif">Siswa Non-Aktif</a>
                    </li>
                </ul>

                <div class="action-bar d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <a href="index.php?page=dashboard_admin" class="btn btn-secondary mb-2 mb-md-0"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
                    
                    <form method="GET" class="search-form">
                        <input type="hidden" name="page" value="admin_siswa">
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                        <input type="text" name="search" id="search-input" class="form-control search-input" placeholder="Cari NIS/Nama/Kelas..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary btn-search" type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    
                    <?php if ($status_filter == 'aktif'): ?>
                        <button class="btn btn-primary mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-2"></i>Tambah Siswa</button>
                    <?php endif; ?>
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
                                <th scope="col">NIS</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Kelas</th>
                                <th scope="col" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($siswas) > 0): ?>
                                <?php $i = $start + 1; ?>
                                <?php foreach ($siswas as $siswa): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($siswa['nis']) ?></td>
                                        <td><?= htmlspecialchars($siswa['nama']) ?></td>
                                        <td><?= htmlspecialchars($siswa['nama_kelas']) ?></td>
                                        <td class="text-center">
                                            <?php if ($status_filter == 'aktif'): ?>
                                                <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal<?= $siswa['nis'] ?>" aria-label="Edit Siswa"><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $siswa['nis'] ?>" aria-label="Non-aktifkan Siswa"><i class="fas fa-trash-alt"></i></button>
                                            <?php else: ?>
                                                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#restoreModal<?= $siswa['nis'] ?>" aria-label="Aktifkan Siswa"><i class="fas fa-undo"></i></button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Modal -->
                                    <?php if ($status_filter == 'aktif'): ?>
                                        <div class="modal fade" id="editModal<?= $siswa['nis'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Siswa</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form method="POST" action="index.php?page=admin_siswa&p=<?= $page ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">
                                                            <input type="hidden" name="action" value="update">
                                                            <input type="hidden" name="nis" value="<?= $siswa['nis'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">NIS</label>
                                                                <input type="text" class="form-control" value="<?= htmlspecialchars($siswa['nis']) ?>" disabled>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Nama Siswa</label>
                                                                <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($siswa['nama']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Kelas</label>
                                                                <select class="form-select" name="id_kelas" required>
                                                                    <?php foreach ($kelass_dropdown as $kelas): ?>
                                                                        <option value="<?= $kelas['id_kelas'] ?>" <?= $kelas['id_kelas'] == $siswa['id_kelas'] ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
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
                                        <div class="modal fade" id="deleteModal<?= $siswa['nis'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-sm">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger">
                                                        <h5 class="modal-title text-white">Konfirmasi Non-aktifkan</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <p>Apakah Anda yakin ingin menon-aktifkan siswa <strong><?= htmlspecialchars($siswa['nama']) ?></strong>?</p>
                                                        <form method="POST" action="index.php?page=admin_siswa&p=<?= $page ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="nis" value="<?= $siswa['nis'] ?>">
                                                            <button type="submit" class="btn btn-danger w-100">Ya, Non-aktifkan</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Restore Modal -->
                                        <div class="modal fade" id="restoreModal<?= $siswa['nis'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-sm">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-success">
                                                        <h5 class="modal-title text-white">Konfirmasi Aktifkan</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <p>Apakah Anda yakin ingin mengaktifkan kembali siswa <strong><?= htmlspecialchars($siswa['nama']) ?></strong>?</p>
                                                        <form method="POST" action="index.php?page=admin_siswa&p=<?= $page ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">
                                                            <input type="hidden" name="action" value="restore">
                                                            <input type="hidden" name="nis" value="<?= $siswa['nis'] ?>">
                                                            <button type="submit" class="btn btn-success w-100">Ya, Aktifkan</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data siswa ditemukan.</td>
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
                                <a class="page-link" href="index.php?page=admin_siswa&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="index.php?page=admin_siswa&p=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="index.php?page=admin_siswa&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>" aria-label="Next">
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
<?php if ($status_filter == 'aktif'): ?>
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Siswa Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="index.php?page=admin_siswa&p=<?= $page ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">NIS</label>
                            <input type="text" class="form-control" name="nis" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Siswa</label>
                            <input type="text" class="form-control" name="nama" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <select class="form-select" name="id_kelas" required>
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($kelass_dropdown as $kelas): ?>
                                    <option value="<?= $kelas['id_kelas'] ?>">
                                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>