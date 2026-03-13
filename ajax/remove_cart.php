<?php
// ajax/remove_cart.php
require_once '../db.php';
header('Content-Type: application/json');

$cart_id = intval($_POST['cart_id'] ?? 0);
if (!$cart_id) { echo json_encode(['success'=>false]); exit; }

mysqli_query($conn, "DELETE FROM cart WHERE id = $cart_id");
echo json_encode(['success' => true, 'count' => getCartCount()]);