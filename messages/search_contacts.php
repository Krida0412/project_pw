<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';

if (!is_logged_in()) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Tangkap parameter pencarian
$search_query = '';
if (isset($_POST['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_POST['search']);
}

// Query untuk mencari kontak yang sudah pernah berinteraksi
$query = "
    SELECT u.id, u.username
    FROM users u
    INNER JOIN messages m ON (m.user_id = u.id OR m.receiver = u.id)
    WHERE u.id != ? 
    AND (m.user_id = ? OR m.receiver = ?)
    AND u.username LIKE ? 
    GROUP BY u.id
    HAVING COUNT(m.id) > 0
";
$stmt = $conn->prepare($query);
$search_param = "%$search_query%";
$stmt->bind_param("isss", $user_id, $user_id, $user_id, $search_param);
$stmt->execute();
$contacts = $stmt->get_result();

if ($contacts->num_rows > 0) {
    // Menampilkan hasil pencarian
    while ($contact = $contacts->fetch_assoc()) {
        echo '<li>
                <a href="chat.php?chat_with=' . $contact['username'] . '" class="flex items-center p-2 bg-gray-200 rounded-xl hover:bg-gray-300 transition duration-200">
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white">
                        ' . strtoupper($contact['username'][0]) . '
                    </div>
                    <span class="ml-4">' . $contact['username'] . '</span>
                </a>
              </li>';
    }
} else {
    // Jika tidak ditemukan, tampilkan pesan
    echo '<li>User not found</li>';
}
?>
