<?php
/**
 * admin/export_excel.php
 * ShopMN — Excel тайлан гаргах
 * PhpSpreadsheet байхгүй үед XLS-compatible HTML fallback ашиглана
 */
require_once 'auth.php';
requireAdmin();

$sheet    = sanitize($_GET['sheet'] ?? 'full');
$dateFrom = sanitize($_GET['from']  ?? date('Y-m-d', strtotime('-30 days')));
$dateTo   = sanitize($_GET['to']    ?? date('Y-m-d'));
$dfEsc    = mysqli_real_escape_string($conn, $dateFrom);
$dtEsc    = mysqli_real_escape_string($conn, $dateTo);

// ── Try PhpSpreadsheet first ───────────────────────────────────
$hasSS = @include_once __DIR__ . '/../vendor/autoload.php';
// Only use it if Spreadsheet class exists (not just PHPMailer)
if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    $hasSS = false;
}

if ($hasSS) {
    // ── PhpSpreadsheet path ─────────────────────────────────
    // (production дээр composer require phpoffice/phpspreadsheet хийсний дараа ажиллана)
    buildWithSpreadsheet($conn, $sheet, $dfEsc, $dtEsc, $dateFrom, $dateTo);
} else {
    // ── Fallback: XLS-compatible XML ─────────────────────────
    buildXMLExcel($conn, $sheet, $dfEsc, $dtEsc, $dateFrom, $dateTo);
}

// ════════════════════════════════════════════════════════════════
// FALLBACK: Excel XML (Excel 2003 XML format — opens in all Excel)
// ════════════════════════════════════════════════════════════════
function buildXMLExcel($conn, $sheet, $dfEsc, $dtEsc, $dateFrom, $dateTo) {
    $filename = "shopmn_report_{$sheet}_{$dateFrom}_{$dateTo}.xls";
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
         xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
         xmlns:x="urn:schemas-microsoft-com:office:excel">' . "\n";

    // Styles
    echo '<Styles>
<Style ss:ID="hdr">
  <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11" ss:FontName="Arial"/>
  <Interior ss:Color="#1A1A2E" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  <Borders><Border ss:Position="Bottom" ss:Weight="1"/></Borders>
</Style>
<Style ss:ID="hdr2">
  <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11" ss:FontName="Arial"/>
  <Interior ss:Color="#0F3460" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
</Style>
<Style ss:ID="title">
  <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="14" ss:FontName="Arial"/>
  <Interior ss:Color="#1A1A2E" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
</Style>
<Style ss:ID="money">
  <NumberFormat ss:Format="#,##0&quot;₮&quot;"/>
  <Alignment ss:Horizontal="Right"/>
  <Font ss:FontName="Arial" ss:Size="10"/>
</Style>
<Style ss:ID="pct">
  <NumberFormat ss:Format="0.0%"/>
  <Alignment ss:Horizontal="Center"/>
  <Font ss:FontName="Arial" ss:Size="10"/>
</Style>
<Style ss:ID="num">
  <NumberFormat ss:Format="#,##0"/>
  <Alignment ss:Horizontal="Center"/>
  <Font ss:FontName="Arial" ss:Size="10"/>
</Style>
<Style ss:ID="d">
  <Font ss:FontName="Arial" ss:Size="10"/>
  <Alignment ss:Horizontal="Left"/>
</Style>
<Style ss:ID="dc">
  <Font ss:FontName="Arial" ss:Size="10"/>
  <Alignment ss:Horizontal="Center"/>
</Style>
<Style ss:ID="alt">
  <Font ss:FontName="Arial" ss:Size="10"/>
  <Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Left"/>
</Style>
<Style ss:ID="orange">
  <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11" ss:FontName="Arial"/>
  <Interior ss:Color="#FF6B35" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center"/>
</Style>
<Style ss:ID="green_cell">
  <Font ss:Color="#065F46" ss:FontName="Arial" ss:Size="10" ss:Bold="1"/>
  <Interior ss:Color="#D1FAE5" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center"/>
</Style>
<Style ss:ID="red_cell">
  <Font ss:Color="#991B1B" ss:FontName="Arial" ss:Size="10" ss:Bold="1"/>
  <Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center"/>
</Style>
<Style ss:ID="amber_cell">
  <Font ss:Color="#92400E" ss:FontName="Arial" ss:Size="10"/>
  <Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/>
  <Alignment ss:Horizontal="Center"/>
</Style>
</Styles>' . "\n";

    if ($sheet === 'full' || $sheet === 'revenue') {
        writeRevenueSheet($conn, $dfEsc, $dtEsc, $dateFrom, $dateTo);
    }
    if ($sheet === 'full' || $sheet === 'orders') {
        writeOrdersSheet($conn, $dfEsc, $dtEsc);
    }
    if ($sheet === 'full' || $sheet === 'products') {
        writeProductsSheet($conn, $dfEsc, $dtEsc);
    }
    if ($sheet === 'full' || $sheet === 'tracking') {
        writeTrackingSheet($conn, $dfEsc, $dtEsc);
    }

    echo '</Workbook>';
}

// ── Helper: XML cell ──
function cell($val, $type='String', $style='d') {
    $val = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');
    if ($type === 'Number') {
        return "<Cell ss:StyleID=\"$style\"><Data ss:Type=\"Number\">$val</Data></Cell>";
    }
    return "<Cell ss:StyleID=\"$style\"><Data ss:Type=\"String\">$val</Data></Cell>";
}
function mcell($val, $merge, $style='hdr') {
    $val = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');
    return "<Cell ss:MergeAcross=\"$merge\" ss:StyleID=\"$style\"><Data ss:Type=\"String\">$val</Data></Cell>";
}
function row(...$cells) {
    return "<Row>" . implode('', $cells) . "</Row>\n";
}

// ── Sheet 1: Revenue summary ──
function writeRevenueSheet($conn, $dfEsc, $dtEsc, $dateFrom, $dateTo) {
    echo '<Worksheet ss:Name="Орлогын тайлан"><Table ss:DefaultColumnWidth="90">
<Column ss:Width="80"/>
<Column ss:Width="130"/>
<Column ss:Width="90"/>
<Column ss:Width="110"/>
<Column ss:Width="110"/>
';
    echo row(mcell("🛒  ShopMN — Орлогын тайлан  ({$dateFrom} — {$dateTo})", 4, 'title'));
    echo "<Row ss:Height=\"8\"></Row>\n";

    // KPIs
    $kpi = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(total_amount),0) AS rev, COUNT(*) AS orders,
                COALESCE(AVG(total_amount),0) AS avg_o,
                SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled
         FROM orders WHERE DATE(created_at) BETWEEN '$dfEsc' AND '$dtEsc'"
    ));
    echo row(
        cell("Нийт орлого",   'String','hdr'),
        cell($kpi['rev'],     'Number','money'),
        cell("Нийт захиалга", 'String','hdr'),
        cell($kpi['orders'],  'Number','num'),
        cell("Дундаж захиалга",'String','hdr')
    );
    echo row(
        cell("Хүргэгдсэн", 'String','hdr2'),
        cell($kpi['delivered'],'Number','green_cell'),
        cell("Цуцлагдсан",  'String','hdr2'),
        cell($kpi['cancelled'],'Number','red_cell'),
        cell($kpi['avg_o'],   'Number','money')
    );

    echo "<Row ss:Height=\"8\"></Row>\n";

    // Daily breakdown
    echo row(
        mcell("📅  Өдөр бүрийн орлого", 4, 'hdr')
    );
    echo row(
        cell("Огноо",'String','hdr'),
        cell("Орлого (₮)",'String','hdr'),
        cell("Захиалга",'String','hdr'),
        cell("Хүргэгдсэн",'String','hdr'),
        cell("Дундаж (₮)",'String','hdr')
    );

    $daily = mysqli_query($conn,
        "SELECT DATE(created_at) AS d,
                COALESCE(SUM(total_amount),0) AS rev,
                COUNT(*) AS orders,
                SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered
         FROM orders
         WHERE DATE(created_at) BETWEEN '$dfEsc' AND '$dtEsc'
         GROUP BY DATE(created_at) ORDER BY d ASC"
    );
    $i = 0;
    while ($dr = mysqli_fetch_assoc($daily)) {
        $sty = $i % 2 === 0 ? 'd' : 'alt';
        echo row(
            cell(date('Y.m.d', strtotime($dr['d'])), 'String', $sty),
            cell($dr['rev'],      'Number','money'),
            cell($dr['orders'],   'Number','num'),
            cell($dr['delivered'],'Number','green_cell'),
            cell($dr['orders']>0 ? round($dr['rev']/$dr['orders']) : 0, 'Number','money')
        );
        $i++;
    }
    echo '</Table></Worksheet>' . "\n";
}

// ── Sheet 2: Orders ──
function writeOrdersSheet($conn, $dfEsc, $dtEsc) {
    echo '<Worksheet ss:Name="Захиалгууд"><Table>
<Column ss:Width="70"/>
<Column ss:Width="120"/>
<Column ss:Width="100"/>
<Column ss:Width="110"/>
<Column ss:Width="90"/>
<Column ss:Width="100"/>
<Column ss:Width="100"/>
<Column ss:Width="120"/>
';
    echo row(mcell("📦  Захиалгуудын бүртгэл", 7, 'title'));
    echo "<Row ss:Height=\"6\"></Row>\n";
    echo row(
        cell("Захиалга №",'String','hdr'),
        cell("Хэрэглэгч",  'String','hdr'),
        cell("Утас",        'String','hdr'),
        cell("Нийт дүн",   'String','hdr'),
        cell("Статус",      'String','hdr'),
        cell("Төлбөр",      'String','hdr'),
        cell("Огноо",       'String','hdr'),
        cell("Хаяг",        'String','hdr')
    );

    $orders = mysqli_query($conn,
        "SELECT o.id, o.shipping_name, o.shipping_phone, o.total_amount,
                o.status, o.payment_method, o.payment_status,
                o.created_at, o.shipping_address
         FROM orders o
         WHERE DATE(o.created_at) BETWEEN '$dfEsc' AND '$dtEsc'
         ORDER BY o.id DESC LIMIT 1000"
    );

    $statusMn = [
        'pending'    => 'Хүлээгдэж байна',
        'processing' => 'Боловсруулж байна',
        'shipped'    => 'Хүргэлтэнд',
        'delivered'  => 'Хүргэгдсэн',
        'cancelled'  => 'Цуцлагдсан',
    ];
    $i = 0;
    while ($o = mysqli_fetch_assoc($orders)) {
        $sty = $i % 2 === 0 ? 'd' : 'alt';
        $stat = $statusMn[$o['status']] ?? $o['status'];
        $statStyle = $o['status'] === 'delivered' ? 'green_cell' :
                    ($o['status'] === 'cancelled' ? 'red_cell' : 'amber_cell');
        echo row(
            cell('#'.str_pad($o['id'],5,'0',STR_PAD_LEFT), 'String', $sty),
            cell($o['shipping_name'], 'String', $sty),
            cell($o['shipping_phone'] ?? '', 'String', $sty),
            cell($o['total_amount'], 'Number', 'money'),
            cell($stat, 'String', $statStyle),
            cell(strtoupper($o['payment_method']), 'String', 'dc'),
            cell(date('Y.m.d H:i', strtotime($o['created_at'])), 'String', 'dc'),
            cell(mb_substr($o['shipping_address'] ?? '', 0, 50), 'String', $sty)
        );
        $i++;
    }
    echo '</Table></Worksheet>' . "\n";
}

// ── Sheet 3: Products ──
function writeProductsSheet($conn, $dfEsc, $dtEsc) {
    echo '<Worksheet ss:Name="Бүтээгдэхүүн"><Table>
<Column ss:Width="30"/>
<Column ss:Width="180"/>
<Column ss:Width="110"/>
<Column ss:Width="100"/>
<Column ss:Width="80"/>
<Column ss:Width="50"/>
<Column ss:Width="120"/>
';
    echo row(mcell("⭐  Бүтээгдэхүүний борлуулалт", 6, 'title'));
    echo "<Row ss:Height=\"6\"></Row>\n";
    echo row(
        cell("#",'String','hdr'),
        cell("Нэр",'String','hdr'),
        cell("Ангилал",'String','hdr'),
        cell("Үнэ (₮)",'String','hdr'),
        cell("Борлуулалт",'String','hdr'),
        cell("Үнэлгээ",'String','hdr'),
        cell("Орлого (₮)",'String','hdr')
    );

    $prods = mysqli_query($conn,
        "SELECT p.name, c.name AS cat, p.price, p.rating, p.stock,
                COUNT(oi.id) AS sold,
                COALESCE(SUM(oi.quantity * oi.price),0) AS revenue
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN order_items oi ON p.id = oi.product_id
         LEFT JOIN orders o ON oi.order_id = o.id
             AND DATE(o.created_at) BETWEEN '$dfEsc' AND '$dtEsc'
         GROUP BY p.id
         ORDER BY revenue DESC LIMIT 500"
    );
    $i = 0;
    while ($p = mysqli_fetch_assoc($prods)) {
        $sty = $i % 2 === 0 ? 'd' : 'alt';
        echo row(
            cell($i+1, 'Number', 'num'),
            cell($p['name'], 'String', $sty),
            cell($p['cat'] ?? '—', 'String', 'dc'),
            cell($p['price'], 'Number', 'money'),
            cell($p['sold'], 'Number', 'num'),
            cell($p['rating'], 'Number', 'dc'),
            cell($p['revenue'], 'Number', 'money')
        );
        $i++;
    }
    echo '</Table></Worksheet>' . "\n";
}

// ── Sheet 4: Tracking ──
function writeTrackingSheet($conn, $dfEsc, $dtEsc) {
    echo '<Worksheet ss:Name="Tracking бүртгэл"><Table>
<Column ss:Width="110"/>
<Column ss:Width="70"/>
<Column ss:Width="100"/>
<Column ss:Width="120"/>
<Column ss:Width="90"/>
<Column ss:Width="90"/>
<Column ss:Width="80"/>
<Column ss:Width="100"/>
';
    echo row(mcell("📍  Хүргэлтийн Tracking бүртгэл", 7, 'title'));
    echo "<Row ss:Height=\"6\"></Row>\n";
    echo row(
        cell("Tracking №",      'String','hdr'),
        cell("Захиалга №",      'String','hdr'),
        cell("Хүлээн авагч",   'String','hdr'),
        cell("Хаяг",            'String','hdr'),
        cell("Статус",          'String','hdr'),
        cell("Жолооч",          'String','hdr'),
        cell("Хүргэлт огноо",  'String','hdr'),
        cell("Дүн (₮)",        'String','hdr')
    );

    $tracks = mysqli_query($conn,
        "SELECT dt.tracking_number, dt.order_id, dt.status, dt.estimated_delivery,
                dt.driver_name, o.shipping_name, o.shipping_address,
                o.shipping_phone, o.total_amount
         FROM delivery_tracking dt
         LEFT JOIN orders o ON dt.order_id = o.id
         WHERE DATE(o.created_at) BETWEEN '$dfEsc' AND '$dtEsc'
         ORDER BY dt.id DESC LIMIT 1000"
    );

    $statusMn2 = [
        'order_placed'     => 'Захиалга хийгдсэн',
        'preparing'        => 'Бэлтгэж байна',
        'picked_up'        => 'Жолооч авлаа',
        'in_transit'       => 'Замдаа яваа',
        'out_for_delivery' => 'Таны хаяг руу',
        'delivered'        => 'Хүргэгдсэн',
    ];
    $i = 0;
    while ($tk = mysqli_fetch_assoc($tracks)) {
        $sty = $i % 2 === 0 ? 'd' : 'alt';
        $stat = $statusMn2[$tk['status']] ?? $tk['status'];
        $statStyle = $tk['status'] === 'delivered' ? 'green_cell' :
                    (in_array($tk['status'],['in_transit','out_for_delivery']) ? 'amber_cell' : 'dc');
        echo row(
            cell($tk['tracking_number'], 'String', $sty),
            cell('#'.str_pad($tk['order_id'],5,'0',STR_PAD_LEFT), 'String', $sty),
            cell($tk['shipping_name'] ?? '—', 'String', $sty),
            cell(mb_substr($tk['shipping_address'] ?? '', 0, 45), 'String', $sty),
            cell($stat, 'String', $statStyle),
            cell($tk['driver_name'] ?? '—', 'String', 'dc'),
            cell($tk['estimated_delivery'] ? date('Y.m.d', strtotime($tk['estimated_delivery'])) : '—', 'String','dc'),
            cell($tk['total_amount'] ?? 0, 'Number', 'money')
        );
        $i++;
    }
    echo '</Table></Worksheet>' . "\n";
}

// ── PhpSpreadsheet path (if available) ──
function buildWithSpreadsheet($conn, $sheet, $dfEsc, $dtEsc, $dateFrom, $dateTo) {
    // Full implementation available when PhpSpreadsheet is installed
    // Redirects to XML fallback for now
    buildXMLExcel($conn, $sheet, $dfEsc, $dtEsc, $dateFrom, $dateTo);
}