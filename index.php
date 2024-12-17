<?php
// Pastikan session dimulai
session_start();

// Pastikan file koneksi dan autoload dipanggil dengan benar
require_once 'vendor/autoload.php';  // Ganti dengan path yang sesuai
require_once 'includes/db.php';

// Cek apakah pengguna sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: messages/index.php');
    exit;
} else {
    header('Location: auth/login.php');
    exit;
}
?>
