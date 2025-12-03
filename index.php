<?php
session_start();
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['role']) && !isset($_SESSION['user_id'])) {
    if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header("Location: login.php");
        exit;
    }
}

$page = $_GET['page'] ?? 'home';
$user_role = $_SESSION['role'] ?? 'guest';

// Debugging
error_log("Index: user_id=" . ($_SESSION['user_id'] ?? 'unset') . ", role=" . $user_role . ", page=" . $page);

// Define allowed pages by role
$allowed_pages_by_role = [
    'admin' => [
        'home',
        'dashboard_admin',
        'admin_kelas',
        'admin_pembina',
        'admin_siswa',
        'admin_ekstra',
    ],
    'pembina' => [
        'home',
        'dashboard_pembina',
        'input_presensi',
        'konfirmasi_ekstra',
        'report',
    ],
    'siswa' => [
        'home',
        'dashboard_siswa',
        'daftar_ekstra',
        'presensi_siswa',
    ],
    'guest' => ['home', 'login'],
];

$allowed_pages = $allowed_pages_by_role[$user_role] ?? $allowed_pages_by_role['guest'];

// Check if requested page is allowed and exists
if (!in_array($page, $allowed_pages) || !file_exists("$page.php")) {
    if ($user_role === 'admin') {
        $page = 'dashboard_admin';
    } elseif ($user_role === 'pembina') {
        $page = 'dashboard_pembina';
    } elseif ($user_role === 'siswa') {
        $page = 'dashboard_siswa';
    } else {
        $page = 'home';
    }
    if (!file_exists("$page.php")) {
        $page = 'error_page';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <title>Presensi Ekstrakurikuler - SMKN 2 Magelang</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/g/3nC6O8HHzG95yD9l6X4GfM3">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container content-container">
        <?php
        if ($page === 'error_page') {
            echo "<div class='alert alert-danger'>Halaman tidak ditemukan atau Anda tidak memiliki akses.</div>";
        } elseif (file_exists("$page.php")) {
            include "$page.php";
        } else {
            echo "<div class='alert alert-danger'>Terjadi kesalahan saat memuat halaman.</div>";
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>