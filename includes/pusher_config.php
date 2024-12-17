<?php
require_once('../vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../'); // Arahkan ke folder utama
$dotenv->load();

$options = array(
    'cluster' => $_ENV['PUSHER_APP_CLUSTER'], // Menggunakan cluster dari .env
    'useTLS' => true
);

// Menggunakan kredensial dari .env
$pusher = new Pusher\Pusher(
    $_ENV['PUSHER_APP_KEY'],    // APP_KEY dari .env
    $_ENV['PUSHER_APP_SECRET'], // APP_SECRET dari .env
    $_ENV['PUSHER_APP_ID'],     // APP_ID dari .env
    $options
);
?>
