<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';

if (!is_logged_in()) {
    header('Location: ../auth/login.php');
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Proses Hapus Akun
if (isset($_POST['delete_account'])) {
    $delete_messages_query = "DELETE FROM messages WHERE user_id = ? OR receiver = ?";
    $stmt_delete_messages = $conn->prepare($delete_messages_query);
    $stmt_delete_messages->bind_param("ii", $user_id, $user_id);
    $stmt_delete_messages->execute();

    $delete_user_query = "DELETE FROM users WHERE id = ?";
    $stmt_delete = $conn->prepare($delete_user_query);
    $stmt_delete->bind_param("i", $user_id);

    if ($stmt_delete->execute()) {
        session_destroy();
        header('Location: ../auth/login.php');
        exit();
    } else {
        echo "<p class='text-red-500'>Gagal menghapus akun. Silakan coba lagi.</p>";
    }
}

// Proses pencarian kontak
$search_query = '';
if (isset($_POST['search'])) {
    $search_query = mysqli_real_escape_string($conn, $_POST['search']);
}
$search_param = "%" . $search_query . "%";

$query_with_chat = "
    SELECT u.id, u.username
    FROM users u
    INNER JOIN messages m ON (m.user_id = u.id OR m.receiver = u.id)
    WHERE u.id != ? 
    AND (m.user_id = ? OR m.receiver = ?)
    GROUP BY u.id
    HAVING COUNT(m.id) > 0
";

$query_search = "
    SELECT u.id, u.username
    FROM users u
    WHERE u.id != ? 
    AND LOWER(u.username) LIKE LOWER(?)
";

$stmt_with_chat = $conn->prepare($query_with_chat);
$stmt_with_chat->bind_param("iii", $user_id, $user_id, $user_id);
$stmt_with_chat->execute();
$contacts_with_chat = $stmt_with_chat->get_result();

$stmt_search = $conn->prepare($query_search);
$stmt_search->bind_param("is", $user_id, $search_param);
$stmt_search->execute();
$search_result = $stmt_search->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacts</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        #sidebar {
            position: fixed;
            top: 0;
            right: 0;
            height: 100%;
            width: 230px;
            color: white;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            z-index: 50;
        }
        .contact-list {
            max-height: 280px; /* Tinggi maksimum daftar kontak */
            overflow-y: auto; /* Scroll secara vertikal jika daftar terlalu panjang */
        }
        @media (min-width: 1024px) {
            #sidebar {
                display: none;
            }
        }
        .desktop-menu {
            display: none;
        }
        @media (min-width: 1024px) {
            .desktop-menu {
                display: flex;
            }
        }
    </style>
</head>
<body class="lg:bg-gradient-to-br from-purple-700 via-indigo-600 to-blue-500 min-h-screen text-white">

    <!-- Sidebar untuk Mobile/Tablet -->
    <div id="sidebar" class="bg-gray-100 shadow-xl">
        <div class="p-4 space-y-4">
            <a href="../auth/logout.php" 
               class="flex items-center space-x-2 justify-center font-bold text-white bg-red-500 hover:bg-red-600 p-2 rounded-md">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
            <form method="POST" class="flex bg-red-500 hover:bg-red-700 p-2 rounded-md justify-center">
                <button type="submit" name="delete_account"
                        class="flex items-center space-x-2 font-bold text-white"
                        onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini?')">
                    <i class="fas fa-trash-alt"></i>
                    <span>Hapus Akun</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Konten Utama -->
    <div class="flex lg:items-center justify-center min-h-screen lg:py-10 lg:px-4">
        <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl overflow-hidden flex">
            <div class="w-full bg-gray-100 p-4">
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-black text-xl font-bold">Kontak</h1>
                    <div class="desktop-menu space-x-4">
                        <a href="../auth/logout.php" 
                           class="flex items-center space-x-2 font-bold text-white bg-red-500 hover:bg-red-600 p-2 rounded-md">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                        <form method="POST">
                            <button type="submit" name="delete_account" 
                                    class="flex items-center space-x-2 font-bold text-white bg-red-500 hover:bg-red-600 p-2 rounded-md"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini?')">
                                <i class="fas fa-trash-alt"></i>
                                <span>Hapus Akun</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Pencarian Kontak -->
                <form method="POST" class="mb-3 relative">
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo $search_query; ?>" 
                            class="w-full p-2 pl-10 rounded-md border-gray-300 text-black" 
                            placeholder="Cari kontak...">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-500"></i>
                    </div>
                </form>

                <!-- Jika ada hasil pencarian -->
                <?php if ($search_query != ''): ?>
                    <h2 class="font-semibold text-lg text-black mb-2">Hasil Pencarian:</h2>
                    <?php if ($search_result->num_rows > 0): ?>
                        <ul class="space-y-4 mt-3">
                            <?php while ($contact = $search_result->fetch_assoc()): ?>
                                <li>
                                    <a href="chat.php?chat_with=<?php echo $contact['username']; ?>" 
                                       class="flex items-center p-2 bg-gray-200 rounded-xl hover:bg-gray-300 transition duration-200">
                                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white">
                                            <?php echo strtoupper($contact['username'][0]); ?>
                                        </div>
                                        <span class="ml-4 text-black font-medium"><?php echo $contact['username']; ?></span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="mt-4 text-gray-600">User tidak ditemukan.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <ul class="contact-list space-y-4 mt-6">
                        <?php while ($contact = $contacts_with_chat->fetch_assoc()) { ?>
                            <li>
                                <a href="chat.php?chat_with=<?php echo $contact['username']; ?>" 
                                   class="flex items-center p-2 bg-gray-200 rounded-xl hover:bg-gray-300 transition duration-200">
                                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white">
                                        <?php echo strtoupper($contact['username'][0]); ?>
                                    </div>
                                    <span class="font-medium text-black ml-4"><?php echo $contact['username']; ?></span>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        let isSidebarOpen = false;
        let startX = 0;

        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });

        document.addEventListener('touchmove', (e) => {
            const moveX = e.touches[0].clientX;

            if (startX - moveX > 50 && !isSidebarOpen) {
                sidebar.style.transform = 'translateX(0)';
                isSidebarOpen = true;
            }

            if (moveX - startX > 50 && isSidebarOpen) {
                sidebar.style.transform = 'translateX(100%)';
                isSidebarOpen = false;
            }
        });
    </script>
</body>
</html>