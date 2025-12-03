<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_field = $_POST['login_field'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($login_field) || empty($password)) {
        $_SESSION['error_message'] = "Semua field harus diisi.";
        header("Location: login.php");
        exit();
    }

    try {
        // Check tb_user first (Admin)
        $stmt_user = $pdo->prepare("SELECT id_user, username, password FROM tb_user WHERE username = ?");
        $stmt_user->execute([$login_field]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = 'admin';
            header("Location: index.php?page=dashboard_admin");
            exit();
        }

        // Check tb_siswa (NIS)
        $stmt_siswa = $pdo->prepare("SELECT nis, nama, password FROM tb_siswa WHERE nis = ?");
        $stmt_siswa->execute([$login_field]);
        $siswa = $stmt_siswa->fetch(PDO::FETCH_ASSOC);

        if ($siswa && password_verify($password, $siswa['password'])) {
            $_SESSION['user_id'] = $siswa['nis'];
            $_SESSION['username'] = $siswa['nama'];
            $_SESSION['role'] = 'siswa';
            header("Location: index.php?page=dashboard_siswa");
            exit();
        }

        // Check tb_pembina (username)
        $stmt_pembina = $pdo->prepare("SELECT id_pembina, nama, username, password FROM tb_pembina WHERE username = ?");
        $stmt_pembina->execute([$login_field]);
        $pembina = $stmt_pembina->fetch(PDO::FETCH_ASSOC);

        if ($pembina && password_verify($password, $pembina['password'])) {
            $_SESSION['user_id'] = $pembina['id_pembina'];
            $_SESSION['username'] = $pembina['nama'];
            $_SESSION['role'] = 'pembina';
            header("Location: index.php?page=dashboard_pembina");
            exit();
        }

        // If no match found
        $_SESSION['error_message'] = "Login gagal. Periksa NIS/Username atau password.";
        header("Location: login.php");
        exit();

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error_message'] = "Terjadi kesalahan database. Silakan coba lagi nanti.";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>