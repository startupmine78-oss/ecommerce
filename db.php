<?php
define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root');       
define('DB_PASS', '');           
define('DB_NAME', 'ecommerce_db');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Холболт амжилтгүй: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// Session эхлүүлэх
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    global $conn;
    if (!isLoggedIn()) return null;
    $id = $_SESSION['user_id'];
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
    return mysqli_fetch_assoc($result);
}

function getCartCount() {
    global $conn;
    $session_id = session_id();
    $user_id = $_SESSION['user_id'] ?? null;

    if ($user_id) {
        $result = mysqli_query($conn, "SELECT SUM(quantity) as cnt FROM cart WHERE user_id = $user_id");
    } else {
        $result = mysqli_query($conn, "SELECT SUM(quantity) as cnt FROM cart WHERE session_id = '$session_id'");
    }
    $row = mysqli_fetch_assoc($result);
    return $row['cnt'] ?? 0;
}

function formatPrice($price) {
    return number_format($price, 0, '.', ',') . '₮';
}

function sanitize($str) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($str)));
}
?>