<?php
session_start();
include '../includes/db.php';

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['username'] = $username;  // Set session username (optional, bisa dihilangkan)
        header('Location: login.php');  // Redirect ke halaman login setelah registrasi
        exit();
    } else {
        echo "<div class='text-red-500 text-center mt-4'>Error: " . $sql . "<br>" . mysqli_error($conn) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Chat App</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-green-400 to-teal-500 min-h-screen flex items-center justify-center px-1">

    <div class="w-full max-w-lg p-8 bg-white rounded-xl shadow-lg">
        <h2 class="text-3xl font-semibold text-center text-gray-900 mb-8">Buat Akun</h2>

        <form method="POST">
            <div class="mb-6">
                <label for="username" class="block text-lg font-medium text-gray-700">Username</label>
                <input type="text" name="username" id="username" class="w-full mt-2 p-3 text-lg border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
            </div>

            <div class="mb-6">
                <label for="password" class="block text-lg font-medium text-gray-700">Password</label>
                <input type="password" name="password" id="password" class="w-full mt-2 p-3 text-lg border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" required>
            </div>

            <button type="submit" name="register" class="w-full py-3 bg-teal-600 text-white font-semibold text-lg rounded-lg hover:bg-teal-700 transition-all">Register</button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-700">Sudah punya akun? <a href="login.php" class="text-teal-500 hover:text-teal-700">Login sekarang</a></p>
        </div>
    </div>

</body>
</html>
