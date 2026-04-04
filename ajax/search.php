<?php
require_once '../db.php';
header('Content-Type: application/json');

$q = sanitize($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode([]); exit; }

$results = mysqli_query($conn, "
    SELECT p.id, p.name, p.price, p.original_price, p.image_url, p.rating, c.name as cat_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.name LIKE '%$q%' OR p.description LIKE '%$q%'
    LIMIT 8
");

$data = [];
while ($row = mysqli_fetch_assoc($results)) {
    $data[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'price' => formatPrice($row['price']),
        'original_price' => $row['original_price'] ? formatPrice($row['original_price']) : null,
        'image' => $row['image_url'],
        'rating' => $row['rating'],
        'category' => $row['cat_name'],
        'url' => 'product_detail.php?id=' . $row['id']
    ];
}

echo json_encode($data);