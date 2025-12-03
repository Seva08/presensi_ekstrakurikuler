<?php
// navbar.php (Hanya Navbar Atas)
// Asumsi session_start() dipanggil di index.php

require_once 'config.php'; // Pastikan path ke config.php benar

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

// Debugging session
error_log("Navbar: user_id=" . ($_SESSION['user_id'] ?? 'unset') . ", role=" . $user_role . ", page=" . ($_GET['page'] ?? 'unset'));

$current_page = $_GET['page'] ?? 'home';
?>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center fw-semibold" href="index.php?page=home">
            <img src="Uploads/logo_smkn2.png" alt="Logo SMKN 2 Magelang" height="40" class="me-2">
            <span class="d-none d-md-inline text-white">SMKN 2 Magelang</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php
                $home_active = ($current_page === 'home' || ($user_role === 'admin' && $current_page === 'dashboard_admin') || ($user_role === 'pembina' && $current_page === 'dashboard_pembina') || ($user_role === 'siswa' && $current_page === 'dashboard_siswa'));
                $home_link = ($user_role === 'admin') ? 'dashboard_admin' : ($user_role === 'pembina' ? 'dashboard_pembina' : ($user_role === 'siswa' ? 'dashboard_siswa' : 'home'));
                ?>
                <li class="nav-item">
                    <a class="nav-link <?= $home_active ? 'active' : '' ?>" href="index.php?page=<?= htmlspecialchars($home_link) ?>">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                </li>

                <?php if ($user_role === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'admin_ekstra') ? 'active' : '' ?>" href="index.php?page=admin_ekstra">
                            <i class="fas fa-star me-1"></i> Ekstrakurikuler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'admin_kelas') ? 'active' : '' ?>" href="index.php?page=admin_kelas">
                            <i class="fas fa-chalkboard me-1"></i> Kelas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'admin_pembina') ? 'active' : '' ?>" href="index.php?page=admin_pembina">
                            <i class="fas fa-user-tie me-1"></i> Pembina
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'admin_siswa') ? 'active' : '' ?>" href="index.php?page=admin_siswa">
                            <i class="fas fa-users me-1"></i> Siswa
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($user_role === 'pembina'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'input_presensi') ? 'active' : '' ?>" href="index.php?page=input_presensi">
                            <i class="fas fa-clipboard-check me-1"></i> Input Presensi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'report') ? 'active' : '' ?>" href="index.php?page=report">
                            <i class="fas fa-file-alt me-1"></i> Report Presensi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'konfirmasi_ekstra') ? 'active' : '' ?>" href="index.php?page=konfirmasi_ekstra">
                            <i class="fas fa-check-circle me-1"></i> Konfirmasi Ekstra
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($user_role === 'siswa'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'daftar_ekstra') ? 'active' : '' ?>" href="index.php?page=daftar_ekstra">
                            <i class="fas fa-plus-circle me-1"></i> Daftar Ekstrakurikuler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'presensi_siswa') ? 'active' : '' ?>" href="index.php?page=presensi_siswa">
                            <i class="fas fa-eye me-1"></i> Lihat Presensi
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <?php if ($user_role !== 'guest'): ?>
                <a class="btn btn-logout ms-2" href="logout.php" role="button">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
    :root {
        --primary: #3b82f6;
        --primary-dark: #1e40af;
        --navbar-bg: rgba(30, 64, 175, 0.95);
        --navbar-bg-collapse: rgba(30, 64, 175, 0.98);
        --text-primary: #ffffff;
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        --border-color: rgba(255, 255, 255, 0.2);
        --logout-bg: #dc2626;
        --logout-hover: #b91c1c;
    }

    body {
        font-family: 'Poppins', sans-serif;
        padding-top: 70px;
    }

    .navbar {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        backdrop-filter: blur(8px);
        min-height: 70px;
        box-shadow: var(--shadow);
        z-index: 1000;
    }

    .navbar-brand {
        color: var(--text-primary) !important;
        font-weight: 600;
        font-size: 1.25rem;
        letter-spacing: 0.5px;
        transition: transform 0.3s ease;
    }

    .navbar-brand:hover {
        transform: scale(1.05);
    }

    .navbar-brand img {
        height: 40px;
        transition: transform 0.3s ease;
    }

    .navbar-brand:hover img {
        transform: rotate(5deg);
    }

    .navbar-toggler {
        border: none;
        padding: 0.5rem;
        transition: all 0.3s ease;
    }

    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    .navbar-toggler:focus {
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
    }

    .navbar-collapse {
        border-radius: 0 0 8px 8px;
    }

    .nav-link {
        color: #ffffff !important;
        font-size: 0.95rem;
        font-weight: 500;
        padding: 0.6rem 1.2rem !important;
        border-radius: 6px;
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        align-items: center;
    }

    .nav-link:hover, .nav-link.active {
        color: #ffffff !important;
        border-bottom: 2px solid #ffffff;
    }

    .nav-link.active {
        font-weight: 600;
    }

    .nav-link i {
        font-size: 1.1rem;
        margin-right: 0.5rem;
    }

    .btn-logout {
        background: var(--logout-bg) !important;
        color: #ffffff !important;
        border: none;
        border-radius: 6px;
        padding: 0.5rem 1.2rem;
        font-weight: 500;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }

    .btn-logout:hover {
        background: var(--logout-hover) !important;
        color: #ffffff !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 991.98px) {
        .navbar-collapse {
            background: var(--navbar-bg-collapse);
            padding: 1rem;
            margin-top: 0.5rem;
            border-radius: 0 0 10px 10px;
            box-shadow: var(--shadow);
        }

        .nav-link {
            padding: 0.8rem 1.5rem !important;
            font-size: 1rem;
        }

        .btn-logout {
            margin: 1rem 1.5rem;
            width: fit-content;
        }

        .navbar-brand span {
            display: inline !important;
        }
    }

    @media (max-width: 576px) {
        .navbar-brand {
            font-size: 1.1rem;
        }

        .navbar-brand img {
            height: 35px;
        }

        .nav-link {
            font-size: 0.9rem;
        }
    }
</style>