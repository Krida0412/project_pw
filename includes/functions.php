<?php
// Fungsi untuk mengecek apakah pengguna sudah login
function is_logged_in() {
    return isset($_SESSION['username']);
}
?>