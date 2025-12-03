<?php
session_start();
require_once 'config.php';

$error_message = '';

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'pembina') {
        header("Location: index.php?page=dashboard_pembina");
    } else {
        header("Location: index.php?page=dashboard_siswa");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Presensi Ekstrakurikuler SMKN 2 Magelang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #1e40af;
            --background: #eff6ff;
            --card-bg: rgba(255, 255, 255, 0.9);
            --text-primary: #1e293b;
            --text-secondary: #6b7280;
            --border-color: rgba(209, 213, 219, 0.5);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.15);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary), #93c5fd);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        .login-container {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow: var(--shadow);
            max-width: 450px;
            width: 90%;
            animation: slideIn 0.6s ease-out;
            border: 1px solid var(--border-color);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .school-logo {
            max-width: 80px;
            margin: 0 auto 1.5rem;
            display: block;
            transition: transform 0.3s ease;
        }

        .school-logo:hover {
            transform: scale(1.1);
        }

        h2 {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1.8rem;
            text-align: center;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
            text-align: center;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .form-floating {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-floating .form-control {
            border-radius: 0.75rem;
            border: 1px solid var(--border-color);
            padding-left: 2.5rem;
            height: 50px;
            transition: all 0.3s ease;
            background: var(--glass-bg);
            backdrop-filter: blur(5px);
        }

        .form-floating .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
            background: #fff;
        }

        .form-floating label {
            color: var(--text-secondary);
            padding-left: 2.5rem;
            transition: all 0.3s ease;
        }

        .form-floating .input-icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1.1rem;
            z-index: 10;
        }

        .btn-login {
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 0.75rem;
            padding: 0.85rem;
            font-weight: 500;
            font-size: 1rem;
            color: #fff;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            background: linear-gradient(90deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }

        .alert {
            border-radius: 0.75rem;
            font-size: 0.9rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #b91c1c;
            margin-bottom: 1.5rem;
            animation: shake 0.3s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25%, 75% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
        }

        .footer-text {
            color: var(--text-secondary);
            font-size: 0.85rem;
            text-align: center;
            margin-top: 2rem;
            opacity: 0.8;
        }

        @media (max-width: 576px) {
            .login-container {
                padding: 2rem 1.5rem;
                width: 95%;
            }

            h2 {
                font-size: 1.5rem;
            }

            .subtitle {
                font-size: 0.85rem;
            }

            .school-logo {
                max-width: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="Uploads/logo_smkn2.png" alt="Logo SMKN 2 Magelang" class="school-logo">
        <h2>Presensi Ekstrakurikuler</h2>
        <p class="subtitle">SMKN 2 Magelang</p>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="login_proses.php" method="POST">
            <div class="form-floating">
                <i class="fas fa-user input-icon"></i>
                <input type="text" class="form-control" id="login_field" name="login_field" placeholder="NIS atau Username" required autofocus>
                <label for="login_field">NIS atau Username</label>
            </div>
            <div class="form-floating">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <button type="submit" class="btn btn-login">Masuk</button>
        </form>
        <p class="footer-text">&copy; <?= date('Y') ?> SMKN 2 Magelang</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>