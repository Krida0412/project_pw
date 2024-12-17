<?php
// Koneksi ke database
$servername = "localhost";
$username = "root";  // Gunakan sesuai dengan konfigurasi Anda
$password = "";  // Password MySQL, kosongkan jika tidak ada
$dbname = "chat_app";  // Nama database Anda

// Membuat koneksi
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Cek koneksi
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
