<?php
require_once 'auth.php';
requireAdmin();
$pageTitle = 'Тайлан & Шинжилгээ';

$rangePreset = $_GET['range'] ?? '30';
$dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime("-{$rangePreset} days"));
$dateTo   = $_GET['to']   ?? date('Y-m-d');

$dfEsc = mysqli_real_escape_string($conn, $dateFrom);
$dtEsc = mysqli_real_escape_string($conn, $dateTo);

$revenue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COALESCE(SUM(total_amount),0)         AS total,
        COUNT(*)                               AS orders,
        COALESCE(AVG(total_amount),0)          AS avg_order,
        COALESCE(SUM(CASE WHEN status='delivered' THEN total_amount END),0) AS delivered_rev,
        COALESCE(SUM(CASE WHEN status='cancelled' THEN total_amount END),0) AS cancelled_rev,
        SUM(CASE WHEN status='pending'    THEN 1 ELSE 0 END) AS pending_cnt,
        SUM(CASE WHEN status='delivered'  THEN 1 ELSE 0 END) AS delivered_cnt,
        SUM(CASE WHEN status='cancelled'  THEN 1 ELSE 0 END) AS cancelled_cnt
     FROM orders
     WHERE DATE(created_at) BETWEEN '$dfEsc' AND '$dtEsc'"
));

$dailyRev = mysqli_query($conn,
    "SELECT DATE(created_at) as d,
            COALESCE(SUM(total_amount),0) as rev,
            COUNT(*) as cnt
     FROM orders
     WHERE DATE(created_at) BETWEEN '$dfEsc' AND '$dtEsc'
     GROUP BY DATE(created_at)
     ORDER BY d ASC"
);
$chartDays = []; $chartRev = []; $chartCnt = [];
while ($dr = mysqli_fetch_assoc($dailyRev)) {
    $chartDays[] = date('m/d', strtotime($dr['d']));
    $chartRev[]  = (int)$dr['rev'];
    $chartCnt[]  = (int)$dr['cnt'];
}

$topProds = mysqli_query($conn,
    "SELECT p.name, p.price, p.image_url, c.name AS cat,
            COUNT(oi.id) AS sold,
            COALESCE(SUM(oi.quantity * oi.price),0) AS revenue
     FROM products p
     LEFT JOIN order_items oi ON p.id = oi.product_id
     LEFT JOIN orders o ON oi.order_id = o.id
         AND DATE(o.created_at) BETWEEN '$dfEsc' AND '$dtEsc'
     LEFT JOIN categories c ON p.category_id = c.id
     GROUP BY p.id ORDER BY revenue DESC LIMIT 10"
);

// ── CATEGORY SALES ───────────────────────────────────────────
$catSales = mysqli_query($conn,
    "SELECT c.name, COUNT(DISTINCT o.id) AS orders,
            COALESCE(SUM(oi.quantity * oi.price),0) AS revenue
     FROM categories c
     LEFT JOIN products p ON c.id = p.category_id
     LEFT JOIN order_items oi ON p.id = oi.product_id
     LEFT JOIN orders o ON oi.order_id = o.id
         AND DATE(o.created_at) BETWEEN '$dfEsc' AND '$dtEsc'
     GROUP BY c.id ORDER BY revenue DESC"
);
$catRows = []; while ($r = mysqli_fetch_assoc($catSales)) $catRows[] = $r;

$payments = mysqli_query($conn,
    "SELECT payment_method,
            COUNT(*) AS cnt,
            COALESCE(SUM(total_amount),0) AS total
     FROM orders
     WHERE DATE(created_at) BETWEEN '$dfEsc' AND '$dtEsc'
     GROUP BY payment_method ORDER BY total DESC"
);
$payRows = []; while ($r = mysqli_fetch_assoc($payments)) $payRows[] = $r;
$totalPayAmt = array_sum(array_column($payRows, 'total')) ?: 1;

$delivStats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN o.status='delivered' THEN 1 ELSE 0 END) AS delivered,
        SUM(CASE WHEN o.status='shipped' OR o.status='processing' THEN 1 ELSE 0 END) AS in_transit,
        SUM(CASE WHEN o.status='cancelled' THEN 1 ELSE 0 END) AS cancelled
     FROM delivery_tracking dt
     LEFT JOIN orders o ON dt.order_id = o.id
     WHERE DATE(o.created_at) BETWEEN '$dfEsc' AND '$dtEsc'"
));
$delivTotal = max($delivStats['total'] ?? 1, 1);

$userStats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS new_users FROM users
     WHERE DATE(created_at) BETWEEN '$dfEsc' AND '$dtEsc'"
));
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users"))['cnt'];

include '_layout.php';
?>

<style>
.rep-section { margin-bottom: 24px; }
.rep-grid-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
.rep-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.rep-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:18px; }

.kpi-card {
    background:white; border-radius:12px;
    padding:16px 18px; box-shadow:0 1px 8px rgba(0,0,0,.06);
    border-left:4px solid var(--accent-color,#FF6B35);
    display:flex; flex-direction:column; gap:6px;
}
.kpi-icon { width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1rem; }
.kpi-val  { font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;color:#1E293B; }
.kpi-label{ font-size:.75rem;color:#64748B;font-weight:500; }
.kpi-change{ font-size:.72rem;font-weight:700; }
.kpi-change.up{color:#10B981;} .kpi-change.down{color:#EF4444;}

.chart-card { background:white;border-radius:12px;padding:18px 20px;box-shadow:0 1px 8px rgba(0,0,0,.06); }
.chart-card-title { font-family:'Outfit',sans-serif;font-weight:700;font-size:.95rem;margin-bottom:14px; }

.prog-bar-wrap { margin-bottom:10px; }
.prog-row { display:flex;align-items:center;gap:8px;margin-bottom:6px; }
.prog-label{ font-size:.78rem;color:#475569;width:100px;flex-shrink:0; }
.prog-track{ flex:1;height:8px;background:#F1F5F9;border-radius:4px;overflow:hidden; }
.prog-fill { height:100%;border-radius:4px;transition:width 1s ease; }
.prog-val  { font-size:.75rem;font-weight:700;color:#1E293B;width:50px;text-align:right;flex-shrink:0; }

.export-bar {
    background:white;border-radius:12px;padding:14px 18px;
    box-shadow:0 1px 8px rgba(0,0,0,.06);margin-bottom:18px;
    display:flex;align-items:center;gap:12px;flex-wrap:wrap;
}
.range-btn {
    padding:6px 14px;border-radius:20px;font-size:.78rem;font-weight:700;
    cursor:pointer;border:1.5px solid #E2E8F0;background:white;color:#475569;
    transition:all .15s;
}
.range-btn.active,.range-btn:hover{background:#1A1A2E;color:white;border-color:#1A1A2E;}
.export-btn {
    margin-left:auto;display:flex;align-items:center;gap:6px;
    padding:8px 18px;border-radius:8px;font-size:.82rem;font-weight:700;
    cursor:pointer;border:none;font-family:'DM Sans',sans-serif;
    transition:all .15s;
}
.btn-excel { background:#217346;color:white; }
.btn-excel:hover { background:#1a5c38; }
.btn-pdf   { background:#DC2626;color:white; }
.btn-pdf:hover { background:#b91c1c; }
.btn-csv   { background:#0F3460;color:white; }
.btn-csv:hover { background:#0a2540; }

.del-stat-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:10px; }
.del-stat {
    background:#F8FAFC;border-radius:9px;padding:12px;text-align:center;
    border-top:3px solid #E2E8F0;
}
.del-stat.green{border-color:#10B981;} .del-stat.orange{border-color:#FF6B35;}
.del-stat.blue{border-color:#3B82F6;} .del-stat.red{border-color:#EF4444;}
.del-stat-val{ font-family:'Outfit',sans-serif;font-size:1.4rem;font-weight:800;color:#1E293B; }
.del-stat-lbl{ font-size:.72rem;color:#64748B;margin-top:2px; }
</style>

<!-- Export & Date bar -->
<div class="export-bar">
    <span style="font-size:.82rem;font-weight:700;color:#475569;">Хугацааны хэлбэр:</span>
    <?php foreach (['7'=>'7 хоног','30'=>'30 хоног','90'=>'3 сар','365'=>'1 жил'] as $d => $l): ?>
    <a href="?range=<?= $d ?>" class="range-btn <?= $rangePreset==$d?'active':'' ?>"><?= $l ?></a>
    <?php endforeach; ?>

    <form method="GET" style="display:flex;gap:6px;align-items:center;margin-left:8px;">
        <input type="date" name="from" value="<?= $dateFrom ?>" class="form-control" style="padding:5px 8px;font-size:.78rem;width:130px;">
        <span style="color:#94A3B8;">—</span>
        <input type="date" name="to"   value="<?= $dateTo   ?>" class="form-control" style="padding:5px 8px;font-size:.78rem;width:130px;">
        <button type="submit" class="btn btn-secondary btn-sm">Хайх</button>
    </form>

    <a href="export_excel.php?from=<?= $dateFrom ?>&to=<?= $dateTo ?>"
       class="export-btn btn-excel" download>
        <i class="fas fa-file-excel"></i> Excel татах
    </a>
    <a href="export_csv.php?from=<?= $dateFrom ?>&to=<?= $dateTo ?>"
       class="export-btn btn-csv" download>
        <i class="fas fa-file-csv"></i> CSV
    </a>
    <button onclick="window.print()" class="export-btn btn-pdf">
        <i class="fas fa-print"></i> Хэвлэх
    </button>
</div>

<!-- KPI cards -->
<div class="rep-grid-4">
    <div class="kpi-card" style="--accent-color:#FF6B35">
        <div class="kpi-icon" style="background:#FFF5F0;color:#FF6B35"><i class="fas fa-coins"></i></div>
        <div class="kpi-val"><?= number_format($revenue['total']/1000000,1) ?>M₮</div>
        <div class="kpi-label">Нийт орлого</div>
    </div>
    <div class="kpi-card" style="--accent-color:#3B82F6">
        <div class="kpi-icon" style="background:#EFF6FF;color:#3B82F6"><i class="fas fa-shopping-bag"></i></div>
        <div class="kpi-val"><?= number_format($revenue['orders']) ?></div>
        <div class="kpi-label">Нийт захиалга</div>
    </div>
    <div class="kpi-card" style="--accent-color:#10B981">
        <div class="kpi-icon" style="background:#F0FDF4;color:#10B981"><i class="fas fa-check-circle"></i></div>
        <div class="kpi-val"><?= number_format($revenue['delivered_cnt']) ?></div>
        <div class="kpi-label">Хүргэгдсэн</div>
    </div>
    <div class="kpi-card" style="--accent-color:#8B5CF6">
        <div class="kpi-icon" style="background:#F5F3FF;color:#8B5CF6"><i class="fas fa-receipt"></i></div>
        <div class="kpi-val"><?= number_format($revenue['avg_order']/1000,0) ?>K₮</div>
        <div class="kpi-label">Дундаж захиалга</div>
    </div>
    <div class="kpi-card" style="--accent-color:#F59E0B">
        <div class="kpi-icon" style="background:#FFFBEB;color:#F59E0B"><i class="fas fa-clock"></i></div>
        <div class="kpi-val"><?= $revenue['pending_cnt'] ?></div>
        <div class="kpi-label">Хүлээгдэж буй</div>
    </div>
    <div class="kpi-card" style="--accent-color:#EF4444">
        <div class="kpi-icon" style="background:#FEF2F2;color:#EF4444"><i class="fas fa-times-circle"></i></div>
        <div class="kpi-val"><?= $revenue['cancelled_cnt'] ?></div>
        <div class="kpi-label">Цуцлагдсан</div>
    </div>
    <div class="kpi-card" style="--accent-color:#0F3460">
        <div class="kpi-icon" style="background:#E0F2FE;color:#0F3460"><i class="fas fa-users"></i></div>
        <div class="kpi-val"><?= number_format($totalUsers) ?></div>
        <div class="kpi-label">Нийт хэрэглэгч</div>
    </div>
    <div class="kpi-card" style="--accent-color:#10B981">
        <div class="kpi-icon" style="background:#F0FDF4;color:#10B981"><i class="fas fa-user-plus"></i></div>
        <div class="kpi-val"><?= $userStats['new_users'] ?></div>
        <div class="kpi-label">Шинэ хэрэглэгч</div>
    </div>
</div>

<!-- Charts row -->
<div class="rep-grid-2 rep-section">
    <!-- Revenue chart -->
    <div class="chart-card">
        <div class="chart-card-title">📈 Өдрийн орлого (₮)</div>
        <canvas id="revenueChart" height="140"></canvas>
    </div>
    <!-- Orders chart -->
    <div class="chart-card">
        <div class="chart-card-title">📦 Өдрийн захиалга</div>
        <canvas id="ordersChart" height="140"></canvas>
    </div>
</div>

<!-- Delivery stats + Category -->
<div class="rep-grid-2 rep-section">
    <!-- Delivery stats -->
    <div class="chart-card">
        <div class="chart-card-title">🚚 Хүргэлтийн статус</div>
        <div class="del-stat-grid" style="margin-bottom:16px;">
            <div class="del-stat green">
                <div class="del-stat-val"><?= $delivStats['delivered'] ?? 0 ?></div>
                <div class="del-stat-lbl">Хүргэгдсэн</div>
            </div>
            <div class="del-stat orange">
                <div class="del-stat-val"><?= $delivStats['in_transit'] ?? 0 ?></div>
                <div class="del-stat-lbl">Замдаа</div>
            </div>
            <div class="del-stat blue">
                <div class="del-stat-val"><?= $delivStats['total'] ?? 0 ?></div>
                <div class="del-stat-lbl">Нийт</div>
            </div>
            <div class="del-stat red">
                <div class="del-stat-val"><?= $delivStats['cancelled'] ?? 0 ?></div>
                <div class="del-stat-lbl">Цуцлагдсан</div>
            </div>
        </div>
        <canvas id="deliveryChart" height="130"></canvas>
    </div>

    <!-- Category sales -->
    <div class="chart-card">
        <div class="chart-card-title">📂 Ангиллаар борлуулалт</div>
        <?php
        $maxCatRev = max(array_column($catRows, 'revenue') ?: [1]);
        foreach ($catRows as $cr):
            $pct = $maxCatRev > 0 ? round($cr['revenue']/$maxCatRev*100) : 0;
        ?>
        <div class="prog-bar-wrap">
            <div class="prog-row">
                <span class="prog-label"><?= mb_substr(htmlspecialchars($cr['name']),0,10) ?></span>
                <div class="prog-track">
                    <div class="prog-fill" data-width="<?= $pct ?>"
                         style="width:0;background:linear-gradient(90deg,#FF6B35,#ffaa88)"></div>
                </div>
                <span class="prog-val"><?= number_format($cr['revenue']/1000) ?>K</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Top products + Payment methods -->
<div class="rep-grid-2 rep-section">
    <!-- Top products -->
    <div class="table-card">
        <div class="table-header">
            <span class="table-title">⭐ Шилдэг 10 бүтээгдэхүүн</span>
            <a href="export_excel.php?sheet=products&from=<?= $dateFrom ?>&to=<?= $dateTo ?>"
               class="btn btn-sm" style="background:#217346;color:white;text-decoration:none;">
                <i class="fas fa-file-excel"></i> Excel
            </a>
        </div>
        <table>
            <thead><tr>
                <th>#</th><th>Нэр</th><th>Ангилал</th><th>Борлуулалт</th><th>Орлого</th>
            </tr></thead>
            <tbody>
            <?php $i=1; while ($p = mysqli_fetch_assoc($topProds)): ?>
            <tr>
                <td style="font-weight:700;color:#FF6B35"><?= $i++ ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <img src="<?= htmlspecialchars($p['image_url']) ?>"
                             style="width:32px;height:32px;object-fit:cover;border-radius:6px"
                             onerror="this.src='https://via.placeholder.com/32'">
                        <span style="font-size:.82rem;font-weight:600">
                            <?= htmlspecialchars(mb_substr($p['name'],0,28)) ?>
                        </span>
                    </div>
                </td>
                <td><span class="badge badge-info" style="font-size:.7rem"><?= htmlspecialchars($p['cat'] ?? '—') ?></span></td>
                <td style="font-weight:700"><?= number_format($p['sold']) ?></td>
                <td class="price-cell" style="font-size:.82rem"><?= number_format($p['revenue']) ?>₮</td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Payment methods -->
    <div class="chart-card">
        <div class="chart-card-title">💳 Төлбөрийн хэрэгсэл</div>
        <canvas id="paymentChart" height="180"></canvas>
        <div style="margin-top:14px;">
        <?php foreach ($payRows as $pr):
            $pct = round($pr['total']/$totalPayAmt*100);
            $icons = ['qpay'=>'🟦','cash'=>'💵','socialpay'=>'💙','monpay'=>'💚','card_visa'=>'💳','khanpay'=>'🔵','most_money'=>'🟠'];
            $icon = $icons[$pr['payment_method']] ?? '💳';
        ?>
        <div class="prog-row" style="margin-bottom:8px;">
            <span class="prog-label" style="width:90px;"><?= $icon ?> <?= htmlspecialchars(strtoupper($pr['payment_method'])) ?></span>
            <div class="prog-track">
                <div class="prog-fill" data-width="<?= $pct ?>"
                     style="width:0;background:linear-gradient(90deg,#1A1A2E,#334155)"></div>
            </div>
            <span class="prog-val"><?= $pct ?>%</span>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Tracking overview table -->
<div class="table-card rep-section">
    <div class="table-header">
        <span class="table-title">📍 Сүүлийн tracking бүртгэл</span>
        <div style="display:flex;gap:8px;">
            <a href="tracking_list.php" class="btn btn-secondary btn-sm">Бүгд харах</a>
            <a href="export_excel.php?sheet=tracking&from=<?= $dateFrom ?>&to=<?= $dateTo ?>"
               class="btn btn-sm" style="background:#217346;color:white;text-decoration:none;">
                <i class="fas fa-file-excel"></i> Excel
            </a>
        </div>
    </div>
    <table>
        <thead><tr>
            <th>Tracking №</th>
            <th>Захиалга</th>
            <th>Хүлээн авагч</th>
            <th>Статус</th>
            <th>Жолооч</th>
            <th>Хүргэлтийн огноо</th>
            <th>Дүн</th>
            <th>Үйлдэл</th>
        </tr></thead>
        <tbody>
        <?php
        $tracks = mysqli_query($conn,
            "SELECT dt.*, o.shipping_name, o.shipping_phone, o.total_amount,
                    o.shipping_address, o.status AS order_status
             FROM delivery_tracking dt
             LEFT JOIN orders o ON dt.order_id = o.id
             WHERE DATE(o.created_at) BETWEEN '$dfEsc' AND '$dtEsc'
             ORDER BY dt.id DESC LIMIT 20"
        );
        $statusLabels = [
            'order_placed'      => ['Захиалга хийгдсэн','badge-gray'],
            'preparing'         => ['Бэлтгэж байна',    'badge-info'],
            'picked_up'         => ['Жолооч авлаа',     'badge-warning'],
            'in_transit'        => ['Замдаа яваа',       'badge-orange'],
            'out_for_delivery'  => ['Таны хаяг руу',    'badge-orange'],
            'delivered'         => ['Хүргэгдсэн',        'badge-success'],
        ];
        while ($tk = mysqli_fetch_assoc($tracks)):
            [$sl, $sc] = $statusLabels[$tk['status']] ?? [$tk['status'], 'badge-gray'];
        ?>
        <tr>
            <td>
                <a href="../tracking.php?track=<?= htmlspecialchars($tk['tracking_number']) ?>"
                   target="_blank"
                   style="color:#FF6B35;font-weight:700;font-size:.82rem;text-decoration:none;font-family:'Outfit',sans-serif;">
                    <?= htmlspecialchars($tk['tracking_number']) ?>
                </a>
            </td>
            <td>
                <a href="orders.php?view=<?= $tk['order_id'] ?>"
                   style="color:#3B82F6;font-weight:600;font-size:.82rem;text-decoration:none;">
                    #<?= str_pad($tk['order_id'],5,'0',STR_PAD_LEFT) ?>
                </a>
            </td>
            <td>
                <div style="font-size:.82rem;font-weight:600"><?= htmlspecialchars($tk['shipping_name'] ?? '—') ?></div>
                <div style="font-size:.72rem;color:#94A3B8"><?= htmlspecialchars($tk['shipping_phone'] ?? '') ?></div>
            </td>
            <td><span class="badge <?= $sc ?>"><?= $sl ?></span></td>
            <td style="font-size:.78rem"><?= htmlspecialchars($tk['driver_name'] ?? '—') ?></td>
            <td style="font-size:.75rem;color:#64748B">
                <?= $tk['estimated_delivery'] ? date('m/d', strtotime($tk['estimated_delivery'])) : '—' ?>
            </td>
            <td class="price-cell" style="font-size:.82rem"><?= number_format($tk['total_amount'] ?? 0) ?>₮</td>
            <td>
                <div style="display:flex;gap:4px;">
                    <a href="../tracking.php?track=<?= htmlspecialchars($tk['tracking_number']) ?>"
                       target="_blank" class="btn-icon" title="Tracking харах">
                        <i class="fas fa-map-marker-alt" style="color:#FF6B35"></i>
                    </a>
                    <a href="orders.php?view=<?= $tk['order_id'] ?>"
                       class="btn-icon" title="Захиалга">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#64748B';

var days = <?= json_encode($chartDays) ?>;
var revs = <?= json_encode($chartRev)  ?>;
var cnts = <?= json_encode($chartCnt)  ?>;

// Revenue chart
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: days,
        datasets: [{
            label: 'Орлого (₮)',
            data: revs,
            backgroundColor: 'rgba(255,107,53,.8)',
            borderRadius: 5,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { ticks: { callback: v => (v/1000000).toFixed(1)+'M₮' }, grid: { color: '#F1F5F9' } },
            x: { grid: { display: false } }
        }
    }
});

// Orders chart
new Chart(document.getElementById('ordersChart'), {
    type: 'line',
    data: {
        labels: days,
        datasets: [{
            label: 'Захиалга',
            data: cnts,
            borderColor: '#1A1A2E',
            backgroundColor: 'rgba(26,26,46,.08)',
            fill: true, tension: 0.4, pointRadius: 3,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: '#F1F5F9' } },
            x: { grid: { display: false } }
        }
    }
});

// Delivery donut
new Chart(document.getElementById('deliveryChart'), {
    type: 'doughnut',
    data: {
        labels: ['Хүргэгдсэн','Замдаа','Цуцлагдсан'],
        datasets: [{
            data: [
                <?= $delivStats['delivered'] ?? 0 ?>,
                <?= $delivStats['in_transit'] ?? 0 ?>,
                <?= $delivStats['cancelled']  ?? 0 ?>
            ],
            backgroundColor: ['#10B981','#FF6B35','#EF4444'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true, cutout: '65%',
        plugins: { legend: { position: 'bottom' } }
    }
});

// Payment pie
<?php $pmLabels=[]; $pmData=[]; foreach($payRows as $pr){ $pmLabels[]=strtoupper($pr['payment_method']); $pmData[]=$pr['total']; } ?>
new Chart(document.getElementById('paymentChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($pmLabels) ?>,
        datasets: [{
            data: <?= json_encode($pmData) ?>,
            backgroundColor: ['#1A1A2E','#3B82F6','#64748B','#10B981','#FF6B35'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true, cutout: '60%',
        plugins: { legend: { position: 'bottom', labels:{ font:{size:10} } } }
    }
});

// Animate progress bars
document.querySelectorAll('.prog-fill').forEach(b => {
    setTimeout(() => b.style.width = (b.dataset.width||'0')+'%', 300);
});
</script>

<?php include '_layout_end.php'; ?>