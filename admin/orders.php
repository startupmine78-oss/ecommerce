<?php
require_once 'auth.php';
requireAdmin();
$pageTitle = 'Захиалга удирдах';
$msg = '';

// ── UPDATE STATUS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid    = intval($_POST['order_id']);
    $status = sanitize($_POST['status']);
    $valid  = ['pending','processing','shipped','delivered','cancelled'];
    if (in_array($status, $valid)) {
        mysqli_query($conn, "UPDATE orders SET status='$status' WHERE id=$oid");
        // Update delivery tracking status too
        $trackStatus = match($status) {
            'processing' => 'preparing',
            'shipped'    => 'in_transit',
            'delivered'  => 'delivered',
            default      => 'order_placed'
        };
        mysqli_query($conn, "UPDATE delivery_tracking SET status='$trackStatus' WHERE order_id=$oid");
        $msg = 'success:Захиалгын статус шинэчлэгдлээ.';
    }
}

// ── FILTER ──
$page    = max(1, intval($_GET['p'] ?? 1));
$perPage = 15;
$search  = sanitize($_GET['q'] ?? '');
$status  = sanitize($_GET['status'] ?? '');
$view    = intval($_GET['view'] ?? 0);

$where = "1=1";
if ($search) $where .= " AND (o.id LIKE '%$search%' OR o.shipping_name LIKE '%$search%' OR o.shipping_phone LIKE '%$search%')";
if ($status) $where .= " AND o.status='$status'";

$total  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM orders o WHERE $where"))['c'];
$pages  = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$orders = mysqli_query($conn,
    "SELECT o.*, u.name uname, u.email uemail FROM orders o
     LEFT JOIN users u ON o.user_id=u.id
     WHERE $where ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset"
);

// ── VIEW SINGLE ORDER ──
$orderDetail = null; $orderItems = null;
if ($view) {
    $orderDetail = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT o.*, u.name uname, u.email uemail, u.phone uphone FROM orders o
         LEFT JOIN users u ON o.user_id=u.id WHERE o.id=$view"
    ));
    $orderItems = mysqli_query($conn,
        "SELECT oi.*, p.name pname, p.image_url FROM order_items oi
         LEFT JOIN products p ON oi.product_id=p.id WHERE oi.order_id=$view"
    );
}

$statusLabels = [
    'pending'=>['label'=>'Хүлээгдэж байна','badge'=>'badge-warning'],
    'processing'=>['label'=>'Боловсруулж байна','badge'=>'badge-info'],
    'shipped'=>['label'=>'Хүргэлтэнд','badge'=>'badge-orange'],
    'delivered'=>['label'=>'Хүргэгдсэн','badge'=>'badge-success'],
    'cancelled'=>['label'=>'Цуцлагдсан','badge'=>'badge-danger'],
];

include '_layout.php';
?>

<?php if ($msg): [$t,$txt] = explode(':',$msg,2); ?>
<div class="alert alert-<?= $t==='success'?'success':'error' ?>"><?= htmlspecialchars($txt) ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <div>
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Захиалгууд</div>
        <h2 style="font-family:'Outfit',sans-serif;font-size:1.2rem;font-weight:800">Нийт <?= number_format($total) ?> захиалга</h2>
    </div>
</div>

<!-- STATUS FILTER TABS -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <?php
    $tabs = [''=> 'Бүгд', 'pending'=>'Хүлээгдэж байна','processing'=>'Боловсруулж байна','shipped'=>'Хүргэлтэнд','delivered'=>'Хүргэгдсэн','cancelled'=>'Цуцлагдсан'];
    foreach ($tabs as $v => $l):
        $cnt = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders".($v?" WHERE status='$v'":"")))['c'];
    ?>
    <a href="?status=<?= $v ?>&q=<?= urlencode($search) ?>"
       class="btn <?= $status===$v?'btn-primary':'btn-secondary' ?> btn-sm">
       <?= $l ?> <span style="opacity:.7">(<?= $cnt ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<!-- SEARCH -->
<form method="GET" style="display:flex;gap:10px;margin-bottom:16px">
    <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
    <div class="search-box" style="flex:1;max-width:360px">
        <i class="fas fa-search"></i>
        <input type="text" name="q" placeholder="Захиалга №, нэр, утас..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <button class="btn btn-secondary" type="submit"><i class="fas fa-search"></i> Хайх</button>
</form>

<div style="display:grid;grid-template-columns:<?= $orderDetail ? '1fr 380px' : '1fr' ?>;gap:20px">

<!-- TABLE -->
<div class="table-card">
    <table>
        <thead><tr>
            <th>Захиалга #</th>
            <th>Хэрэглэгч</th>
            <th>Дүн</th>
            <th>Төлбөр</th>
            <th>Статус</th>
            <th>Огноо</th>
            <th>Үйлдэл</th>
        </tr></thead>
        <tbody>
        <?php while ($o = mysqli_fetch_assoc($orders)):
            $sl = $statusLabels[$o['status']] ?? ['label'=>$o['status'],'badge'=>'badge-gray'];
        ?>
        <tr>
            <td><a href="?view=<?= $o['id'] ?>&status=<?= urlencode($status) ?>&q=<?= urlencode($search) ?>"
                   style="color:#FF6B35;font-weight:700;text-decoration:none">
                #<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></a>
            </td>
            <td>
                <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($o['uname'] ?? $o['shipping_name'] ?? 'Зочин') ?></div>
                <div style="font-size:.75rem;color:#94A3B8"><?= htmlspecialchars($o['shipping_phone'] ?? '') ?></div>
            </td>
            <td class="price-cell"><?= number_format($o['total_amount']) ?>₮</td>
            <td>
                <span class="badge <?= $o['payment_status']==='paid'?'badge-success':($o['payment_status']==='refunded'?'badge-warning':'badge-gray') ?>">
                    <?= $o['payment_status']==='paid'?'Төлсөн':($o['payment_status']==='refunded'?'Буцаасан':'Төлөөгүй') ?>
                </span>
            </td>
            <td><span class="badge <?= $sl['badge'] ?>"><?= $sl['label'] ?></span></td>
            <td style="font-size:.78rem;color:#64748B"><?= date('m/d H:i', strtotime($o['created_at'])) ?></td>
            <td>
                <form method="POST" style="display:flex;gap:4px;align-items:center">
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                    <select name="status" class="form-control" style="padding:4px 8px;font-size:.78rem;width:auto">
                        <?php foreach ($statusLabels as $sv => $slab): ?>
                        <option value="<?= $sv ?>" <?= $o['status']===$sv?'selected':'' ?>><?= $slab['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="update_status" class="btn-icon" title="Хадгалах"><i class="fas fa-check" style="color:#10B981"></i></button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <!-- PAGINATION -->
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?p=<?= $page-1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($i=max(1,$page-2); $i<=min($pages,$page+2); $i++): ?>
        <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"
           class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
        <a href="?p=<?= $page+1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
        <span class="page-info"><?= $page ?> / <?= $pages ?></span>
    </div>
</div>

<!-- ORDER DETAIL PANEL -->
<?php if ($orderDetail): ?>
<div class="table-card" style="align-self:start;position:sticky;top:80px">
    <div class="table-header">
        <span class="table-title">📦 Захиалга #<?= str_pad($orderDetail['id'],5,'0',STR_PAD_LEFT) ?></span>
        <a href="orders.php" class="btn-icon"><i class="fas fa-times"></i></a>
    </div>
    <div style="padding:16px">
        <!-- Status badge -->
        <?php $sl = $statusLabels[$orderDetail['status']] ?? ['label'=>$orderDetail['status'],'badge'=>'badge-gray']; ?>
        <div style="margin-bottom:14px"><span class="badge <?= $sl['badge'] ?>" style="font-size:.85rem;padding:5px 14px"><?= $sl['label'] ?></span></div>

        <!-- Customer info -->
        <div style="background:#F8FAFC;border-radius:10px;padding:12px;margin-bottom:12px;font-size:.85rem">
            <div style="font-weight:700;margin-bottom:6px;color:#374151">👤 Хэрэглэгч</div>
            <div><?= htmlspecialchars($orderDetail['uname'] ?? 'Зочин') ?></div>
            <?php if ($orderDetail['uemail']): ?><div style="color:#94A3B8"><?= htmlspecialchars($orderDetail['uemail']) ?></div><?php endif; ?>
            <div>📱 <?= htmlspecialchars($orderDetail['shipping_phone'] ?? '') ?></div>
        </div>

        <!-- Shipping -->
        <div style="background:#F8FAFC;border-radius:10px;padding:12px;margin-bottom:12px;font-size:.85rem">
            <div style="font-weight:700;margin-bottom:6px;color:#374151">📍 Хүргэлтийн хаяг</div>
            <div><?= htmlspecialchars($orderDetail['shipping_name'] ?? '') ?></div>
            <div style="color:#64748B"><?= htmlspecialchars($orderDetail['shipping_address'] ?? '') ?></div>
        </div>

        <!-- Items -->
        <div style="font-weight:700;font-size:.85rem;color:#374151;margin-bottom:8px">🛒 Захиалсан бараа</div>
        <?php while ($item = mysqli_fetch_assoc($orderItems)): ?>
        <div style="display:flex;gap:8px;align-items:center;padding:8px 0;border-bottom:1px solid #F1F5F9">
            <img src="<?= htmlspecialchars($item['image_url']) ?>" style="width:36px;height:36px;object-fit:cover;border-radius:6px" onerror="this.src='https://via.placeholder.com/36'">
            <div style="flex:1;font-size:.8rem">
                <div style="font-weight:600"><?= htmlspecialchars(mb_substr($item['pname'],0,28)) ?></div>
                <div style="color:#94A3B8"><?= $item['quantity'] ?> × <?= number_format($item['price']) ?>₮</div>
            </div>
            <div style="font-weight:700;font-size:.82rem;color:#FF6B35"><?= number_format($item['price'] * $item['quantity']) ?>₮</div>
        </div>
        <?php endwhile; ?>

        <!-- Totals -->
        <div style="padding-top:10px;font-size:.88rem">
            <div style="display:flex;justify-content:space-between;padding:4px 0;color:#64748B">
                <span>Дэд нийт:</span>
                <span><?= number_format($orderDetail['total_amount']) ?>₮</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:4px 0;color:#64748B">
                <span>Төлбөрийн хэлбэр:</span>
                <span style="text-transform:uppercase;font-weight:600"><?= htmlspecialchars($orderDetail['payment_method']) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0 0;font-weight:800;font-family:'Outfit',sans-serif;font-size:1rem;border-top:1px solid #E2E8F0;margin-top:4px">
                <span>Нийт:</span>
                <span style="color:#FF6B35"><?= number_format($orderDetail['total_amount']) ?>₮</span>
            </div>
        </div>

        <a href="<?= '../tracking.php?order=' . $orderDetail['id'] ?>" target="_blank" class="btn btn-secondary" style="width:100%;justify-content:center;margin-top:12px">
            <i class="fas fa-map-marker-alt"></i> Tracking харах
        </a>
    </div>
</div>
<?php endif; ?>
</div>

<?php include '_layout_end.php'; ?>
