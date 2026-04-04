<?php
require_once '../db.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
$product = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT p.*, c.name as cat_name FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = $id
"));

if (!$product) { echo json_encode(['success'=>false]); exit; }

$discount = $product['original_price']
    ? round((1 - $product['price']/$product['original_price'])*100) : 0;

echo json_encode([
    'success' => true,
    'id' => $product['id'],
    'name' => $product['name'],
    'price' => formatPrice($product['price']),
    'original_price' => $product['original_price'] ? formatPrice($product['original_price']) : null,
    'discount' => $discount,
    'description' => $product['description'],
    'image' => $product['image_url'],
    'rating' => $product['rating'],
    'reviews_count' => $product['reviews_count'],
    'stock' => $product['stock'],
    'category' => $product['cat_name'],
    'badge' => $product['badge'],
    'url' => 'product_detail.php?id=' . $product['id']
]);