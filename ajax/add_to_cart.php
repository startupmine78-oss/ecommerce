<?php
// ajax/add_to_cart.php
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);
$quantity = max(1, intval($_POST['quantity'] ?? 1));

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

// Check product exists and has stock
$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id = $product_id AND stock > 0"));
if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Out of stock']);
    exit;
}

$session_id = session_id();
$user_id = $_SESSION['user_id'] ?? null;

// Check if already in cart
if ($user_id) {
    $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id"));
} else {
    $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM cart WHERE session_id = '$session_id' AND product_id = $product_id"));
}

if ($existing) {
    $newQty = min($existing['quantity'] + $quantity, $product['stock']);
    mysqli_query($conn, "UPDATE cart SET quantity = $newQty WHERE id = {$existing['id']}");
} else {
    if ($user_id) {
        mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)");
    } else {
        mysqli_query($conn, "INSERT INTO cart (session_id, product_id, quantity) VALUES ('$session_id', $product_id, $quantity)");
    }
}

$count = getCartCount();
echo json_encode(['success' => true, 'count' => $count]);