<?php
require_once __DIR__ . '/../db.php';

function requireAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ' . getAdminBase() . '/login.php');
        exit;
    }
}

function getAdminBase() {
    return '/ecommerce/admin';
}

function adminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function getCurrentAdmin() {
    return $_SESSION['admin_name'] ?? 'Admin';
}

function getStats($conn) {
    return [
        'products'   => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM products"))['c'],
        'orders'     => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders"))['c'],
        'users'      => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'],
        'categories' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM categories"))['c'],
        'revenue'    => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) s FROM orders WHERE status != 'cancelled'"))['s'],
        'pending'    => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE status='pending'"))['c'],
        'low_stock'  => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM products WHERE stock < 10"))['c'],
        'today_orders'=> mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders WHERE DATE(created_at)=CURDATE()"))['c'],
    ];
}
