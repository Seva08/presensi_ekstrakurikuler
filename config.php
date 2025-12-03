<?php
// PASTIKAN TIDAK ADA SPASI, BARIS KOSONG, ATAU KARAKTER APAPUN SEBELUM TAG <?php INI
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // MEMULAI SESI
}

$host = 'localhost';
$dbname = 'presensi_ekstrakurikuler';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Untuk debugging, aktifkan tampilan error PHP di browser:
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
// JIKA HANYA ADA KODE PHP, LEBIH BAIK TIDAK MENGGUNAKAN TAG PENUTUP ?>