<?php
require_once '../db.php';
header('Content-Type: application/json');

$session_id = session_id();
$user_id = $_SESSION['user_id'] ?? null;
$condition = $user_id ? "c.user_id = $user_id" : "c.session_id = '$session_id'";

$items = mysqli_query($conn, "SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.image_url FROM cart c LEFT JOIN products p ON c.product_id = p.id WHERE $condition");

$cartRows = [];
$subtotal = 0;
while ($row = mysqli_fetch_assoc($items)) {
    $cartRows[] = [
        'cart_id' => $row['id'],
        'product_id' => $row['product_id'],
        'name' => $row['name'],
        'price' => $row['price'],
        'price_formatted' => formatPrice($row['price']),
        'subtotal' => formatPrice($row['price'] * $row['quantity']),
        'quantity' => $row['quantity'],
        'image' => $row['image_url']
    ];
    $subtotal += $row['price'] * $row['quantity'];
}

echo json_encode([
    'items' => $cartRows,
    'count' => array_sum(array_column($cartRows, 'quantity')),
    'subtotal' => formatPrice($subtotal),
    'shipping' => $subtotal >= 50000 ? 'ҮНЭГҮЙ' : formatPrice(5000),
    'total' => formatPrice($subtotal + ($subtotal >= 50000 ? 0 : 5000))
]);