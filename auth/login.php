<?php
session_start();
include '../includes/db.php';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Cek apakah pengguna ada di database
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Simpan user_id dan username di session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ../messages/index.php');
            exit();
        } else {
            $error_message = "Username atau password salah.";
        }
    } else {
        $error_message = "Pengguna tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Chat App</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-green-400 to-teal-500 min-h-screen flex items-center justify-center px-1">

    <div class="w-full max-w-lg p-8 bg-white rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold text-center text-gray-900 mb-8">Login ke Akun Anda</h2>

        <form method="POST">
            <div class="mb-6">
                <label for="username" class="block text-lg font-medium text-gray-700">Username</label>
                <input type="text" name="username" id="username" class="w-full mt-2 p-3 text-lg border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
            </div>

            <div class="mb-6">
                <label for="password" class="block text-lg font-medium text-gray-700">Password</label>
                <input type="password" name="password" id="password" class="w-full mt-2 p-3 text-lg border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
            </div>

            <button type="submit" name="login" class="w-full py-3 bg-teal-600 text-white font-semibold text-lg rounded-lg hover:bg-teal-700 transition-all">Login</button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-700">Belum punya akun? <a href="register.php" class="text-teal-500 hover:text-teal-700">Daftar sekarang</a></p>
        </div>
    </div>

</body>
</html>