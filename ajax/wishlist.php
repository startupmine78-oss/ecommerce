<?php
require_once '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false]); exit; }

$product_id = intval($_POST['product_id'] ?? 0);
if (!$product_id) { echo json_encode(['success'=>false]); exit; }

// Store wishlist in session for guests, DB for users
if (!isset($_SESSION['wishlist'])) $_SESSION['wishlist'] = [];

$wishlist = &$_SESSION['wishlist'];
$inWishlist = in_array($product_id, $wishlist);

if ($inWishlist) {
    $wishlist = array_values(array_filter($wishlist, fn($id) => $id !== $product_id));
    $action = 'removed';
} else {
    $wishlist[] = $product_id;
    $action = 'added';
}

echo json_encode(['success' => true, 'action' => $action, 'count' => count($wishlist)]);