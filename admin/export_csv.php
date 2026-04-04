<?php
require_once 'auth.php';
requireAdmin();

$sheet    = sanitize($_GET['sheet'] ?? 'orders');
$dateFrom = sanitize($_GET['from']  ?? date('Y-m-d', strtotime('-30 days')));
$dateTo   = sanitize($_GET['to']    ?? date('Y-m-d'));
$dfEsc    = mysqli_real_escape_string($conn, $dateFrom);
$dtEsc    = mysqli_real_escape_string($conn, $dateTo);

$filename = "shopmn_{$sheet}_{$dateFrom}_{$dateTo}.csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

function csvRow($fh, array $cols) {
    fputcsv($fh, $cols);
}

if ($sheet === 'orders' || $sheet === 'full') {
    csvRow($out, ['Захиалга №','Хэрэглэгч','Утас','Дүн (₮)','Статус','Төлбөр','Огноо','Хаяг']);
    $rs = mysqli_query($GLOBALS['conn'],
        "SELECT o.id, o.shipping_name, o.shipping_phone, o.total_amount,
                o.status, o.payment_method, o.created_at, o.shipping_address
         FROM orders o
         WHERE DATE(o.created_at) BETWEEN '$dfEsc' AND '$dtEsc'
         ORDER BY o.id DESC LIMIT 5000"
    );
    $statusMn = ['pending'=>'Хүлээгдэж байна','processing'=>'Боловсруулж байна',
                 'shipped'=>'Хүргэлтэнд','delivered'=>'Хүргэгдсэн','cancelled'=>'Цуцлагдсан'];
    while ($r = mysqli_fetch_assoc($rs)) {
        csvRow($out, [
            '#'.str_pad($r['id'],5,'0',STR_PAD_LEFT),
            $r['shipping_name'], $r['shipping_phone'] ?? '',
            $r['total_amount'],
            $statusMn[$r['status']] ?? $r['status'],
            strtoupper($r['payment_method']),
            date('Y.m.d H:i', strtotime($r['created_at'])),
            $r['shipping_address'] ?? '',
        ]);
    }
}

if ($sheet === 'tracking' || $sheet === 'full') {
    if ($sheet === 'full') csvRow($out, []); // blank line separator
    csvRow($out, ['Tracking №','Захиалга №','Хүлээн авагч','Хаяг','Статус','Жолооч','Хүргэлт огноо','Дүн (₮)']);
    $statusMn2 = ['order_placed'=>'Захиалга хийгдсэн','preparing'=>'Бэлтгэж байна',
                  'picked_up'=>'Жолооч авлаа','in_transit'=>'Замдаа яваа',
                  'out_for_delivery'=>'Таны хаяг руу','delivered'=>'Хүргэгдсэн'];
    $rs2 = mysqli_query($GLOBALS['conn'],
        "SELECT dt.tracking_number, dt.order_id, dt.status, dt.estimated_delivery,
                dt.driver_name, o.shipping_name, o.shipping_address,
                o.shipping_phone, o.total_amount
         FROM delivery_tracking dt
         LEFT JOIN orders o ON dt.order_id = o.id
         WHERE DATE(o.created_at) BETWEEN '$dfEsc' AND '$dtEsc'
         ORDER BY dt.id DESC LIMIT 5000"
    );
    while ($r = mysqli_fetch_assoc($rs2)) {
        csvRow($out, [
            $r['tracking_number'],
            '#'.str_pad($r['order_id'],5,'0',STR_PAD_LEFT),
            $r['shipping_name'] ?? '—',
            $r['shipping_address'] ?? '',
            $statusMn2[$r['status']] ?? $r['status'],
            $r['driver_name'] ?? '—',
            $r['estimated_delivery'] ? date('Y.m.d', strtotime($r['estimated_delivery'])) : '—',
            $r['total_amount'] ?? 0,
        ]);
    }
}

if ($sheet === 'products') {
    csvRow($out, ['#','Бүтээгдэхүүн','Ангилал','Үнэ (₮)','Борлуулалт','Нөөц','Үнэлгээ','Орлого (₮)']);
    $rs3 = mysqli_query($GLOBALS['conn'],
        "SELECT p.name, c.name AS cat, p.price, p.stock, p.rating,
                COUNT(oi.id) AS sold,
                COALESCE(SUM(oi.quantity * oi.price),0) AS revenue
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN order_items oi ON p.id = oi.product_id
         LEFT JOIN orders o ON oi.order_id = o.id
             AND DATE(o.created_at) BETWEEN '$dfEsc' AND '$dtEsc'
         GROUP BY p.id ORDER BY revenue DESC LIMIT 1000"
    );
    $i = 1;
    while ($p = mysqli_fetch_assoc($rs3)) {
        csvRow($out, [$i++, $p['name'], $p['cat']??'—', $p['price'],
                      $p['sold'], $p['stock'], $p['rating'], $p['revenue']]);
    }
}

fclose($out);