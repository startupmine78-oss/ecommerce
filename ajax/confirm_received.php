<?php
// ajax/confirm_received.php — Хүргэлт хүлээн авсан баталгаажуулалт
require_once '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$orderId  = intval($_POST['order_id'] ?? 0);
$tracking = sanitize($_POST['tracking'] ?? '');

if (!$orderId && !$tracking) {
    echo json_encode(['success' => false, 'error' => 'Мэдээлэл дутуу']);
    exit;
}

$updated = false;

if ($orderId) {
    $r = mysqli_query($conn, "UPDATE orders SET status='delivered' WHERE id=$orderId");
    if ($r) {
        mysqli_query($conn, "UPDATE delivery_tracking SET status='delivered' WHERE order_id=$orderId");
        $updated = true;
    }
} elseif ($tracking) {
    $tn = mysqli_real_escape_string($conn, $tracking);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT order_id FROM delivery_tracking WHERE tracking_number='$tn'"));
    if ($row) {
        $oid = intval($row['order_id']);
        mysqli_query($conn, "UPDATE orders SET status='delivered' WHERE id=$oid");
        mysqli_query($conn, "UPDATE delivery_tracking SET status='delivered' WHERE tracking_number='$tn'");
        $updated = true;
    }
}

echo json_encode(['success' => $updated]);