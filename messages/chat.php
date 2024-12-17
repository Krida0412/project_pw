<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/pusher_config.php';

if (!is_logged_in()) {
    header('Location: ../auth/login.php');
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Query untuk menampilkan kontak yang sudah berinteraksi (ada pesan)
$query = "
    SELECT u.id, u.username
    FROM users u
    INNER JOIN messages m ON (m.user_id = u.id OR m.receiver = u.id)
    WHERE u.id != ? 
    AND (m.user_id = ? OR m.receiver = ?)
    GROUP BY u.id
    HAVING COUNT(m.id) > 0
";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$contacts = $stmt->get_result();

// Dapatkan ID pengguna yang diajak chat
if (isset($_GET['chat_with'])) {
    $chat_with = $_GET['chat_with'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $chat_with);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $receiver = $result->fetch_assoc()['id'];
    } else {
        die("User not found.");
    }
} else {
    die("No user specified.");
}

// Proses pengiriman pesan
if (isset($_POST['message'])) {
    $message = htmlspecialchars(mysqli_real_escape_string($conn, $_POST['message']));
    $stmt = $conn->prepare("INSERT INTO messages (user_id, message, receiver, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $message, $receiver);
    $stmt->execute();

    // Kirim notifikasi ke Pusher untuk pesan baru
    $data = [
        'username' => $username,
        'message' => $message
    ];
    // Trigger Pusher untuk memberi tahu pesan baru
    $pusher->trigger('chat-channel', 'new-message', $data);
}

// Ambil pesan-pesan yang ada
$stmt = $conn->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON m.user_id = u.id WHERE (m.user_id = ? AND m.receiver = ?) OR (m.user_id = ? AND m.receiver = ?) ORDER BY m.created_at ASC");
$stmt->bind_param("iiii", $user_id, $receiver, $receiver, $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Room</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.pusher.com/7.0.3/pusher.min.js"></script>
    <link rel="stylesheet" href="../assets/css/chat.css">
    <!-- Tambahkan Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="lg:bg-gradient-to-br from-purple-700 via-indigo-600 to-blue-500 min-h-screen text-white">

    <div class="flex lg:items-center justify-center min-h-screen py-0 lg:px-4">
        <div class="w-full max-w-4xl bg-white lg:rounded-2xl shadow-xl overflow-hidden flex">

            <!-- Sidebar Kontak -->
            <div id="sidebar" class="w-1/3 bg-gray-100 p-4 transition-all">
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-black text-xl font-bold">Kontak</h1>
                    
                </div>
                <a href="index.php" 
                    class="text-sm flex font-bold items-center justify-center space-x-2 py-2 px-2 mb-6 bg-blue-500 text-white rounded-md hover:bg-blue-700">
                    <i class="fa-solid fa-house"></i>
                    <span>Kembali ke Home</span>
                </a>
                <ul class="space-y-4 mt-6">
                    <?php if ($contacts->num_rows > 0) { ?>
                        <?php while ($contact = $contacts->fetch_assoc()) { ?>
                            <li>
                                <a href="chat.php?chat_with=<?php echo $contact['username']; ?>" class="flex items-center p-2 bg-gray-200 rounded-xl hover:bg-gray-300 transition duration-200">
                                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white">
                                        <?php echo strtoupper($contact['username'][0]); ?>
                                    </div>
                                    <span class="ml-4 text-black font-medium"><?php echo $contact['username']; ?></span>
                                </a>
                            </li>
                        <?php } ?>
                    <?php } else { ?>
                        <li class="text-gray-500">User not found</li>
                    <?php } ?>
                </ul>
            </div>

            <!-- Konten Chat -->
            <div class="w-full lg:w-full bg-gray-200 lg:p-6 flex flex-col">
                <div class="flex items-center justify-between mb-2 border-2 bg-white px-5 pt-2 pb-3 lg:rounded-md">
                    <div class="flex items-center">
                        <!-- Logo lingkaran di samping nama pengguna -->
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white mr-4">
                            <?php echo strtoupper($chat_with[0]); ?>
                        </div>
                        <h1 class="text-xl text-black font-semibold"><?php echo $chat_with; ?></h1>
                    </div>
                </div>


                <div id="chat-container" class="flex flex-col space-y-4 overflow-y-auto mb-6">
                    <?php while ($message = $result->fetch_assoc()) { ?>
                        <div class="message <?php echo $message['user_id'] == $user_id ? 'my-message' : 'other-message'; ?>" id="message-<?php echo $message['id']; ?>">
                        
                            <!-- Pesan -->
                            <div class="text-md <?php echo $message['user_id'] == $user_id ? 'text-left' : 'text-left'; ?>">
                                <span><?php echo $message['message']; ?></span>
                            </div>
                            
                            <!-- Waktu Pesan -->
                            <div class="message-footer flex justify-between items-center">
                                <div class="text-xs text-gray-500 <?php echo $message['user_id'] == $user_id ? 'text-right' : 'text-left'; ?>">
                                    <span><?php echo date('H:i', strtotime($message['created_at'])); ?></span>
                                </div>                          
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <form method="POST" class="flex mt-auto mb-2 mx-2 lg:mb-0 lg:mx-0">
                    <input type="text" name="message" class="text-black flex-1 p-3 rounded-md border-gray-900 solid" placeholder="Ketik pesan anda..." required>
                    <button type="submit" class="ml-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Kirim</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Fungsi untuk memastikan chat selalu di-scroll ke bawah
    function scrollToBottom() {
        var chatContainer = document.getElementById('chat-container');
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    // Panggil scrollToBottom() saat halaman dimuat
    window.onload = function() {
        scrollToBottom();
    }

    // Setup Pusher untuk mendengarkan pesan baru dan scroll otomatis
    var pusher = new Pusher('<?php echo $_ENV["PUSHER_APP_KEY"]; ?>', {
        cluster: '<?php echo $_ENV["PUSHER_APP_CLUSTER"]; ?>',
        forceTLS: true
    });

    var channel = pusher.subscribe('chat-channel');

    // Menangani pesan baru
    channel.bind('new-message', function(data) {
        // Menambahkan pesan baru ke chat container
        var newMessage = document.createElement('div');
        newMessage.classList.add('message');
        newMessage.classList.add(data.username == '<?php echo $username; ?>' ? 'my-message' : 'other-message');
        newMessage.id = 'message-' + data.message_id;  // Menambahkan ID unik pada setiap pesan
        newMessage.innerHTML = '<div class="message-text text-lg">' + data.message + '</div><div class="text-xs text-gray-500 mt-2 text-left">Just Now</div>';
        document.getElementById('chat-container').appendChild(newMessage);

        // Scroll ke bawah
        scrollToBottom();
    });

    // Fungsi untuk scroll otomatis ke bawah setiap kali pesan baru ditambahkan
    function scrollToBottom() {
        var chatContainer = document.getElementById('chat-container');
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }


    // Sidebar Toggle: Gestur geser untuk membuka/tutup sidebar
    const sidebar = document.getElementById('sidebar');
    let isSidebarOpen = false;

    // Menangani gesture geser untuk membuka sidebar (dari kanan ke kiri)
    document.addEventListener('touchstart', (e) => {
        const startX = e.touches[0].clientX;

        document.addEventListener('touchmove', (e) => {
            const moveX = e.touches[0].clientX;

            if (startX - moveX > 50 && !isSidebarOpen) {
                // Geser cukup jauh untuk membuka sidebar
                sidebar.classList.add('open');
                isSidebarOpen = true;
            } else if (moveX - startX > 50 && isSidebarOpen) {
                // Geser kembali untuk menutup sidebar
                sidebar.classList.remove('open');
                isSidebarOpen = false;
            }
        });

        // Menghentikan event touchmove jika gesture terlalu cepat
        document.addEventListener('touchend', () => {
            document.removeEventListener('touchmove', null);
        });
    });

    // Menambahkan event listener untuk tombol tutup sidebar (optional)
    document.getElementById('close-sidebar-btn')?.addEventListener('click', () => {
        sidebar.classList.remove('open');
        isSidebarOpen = false;
    });
    </script>

</body>
</html>