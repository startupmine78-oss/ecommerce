<?php
require_once 'auth.php';
requireAdmin();
$pageTitle = 'Хяналтын самбар';
$stats = getStats($conn);

// Recent orders
$recentOrders = mysqli_query($conn,
    "SELECT o.*, u.name as uname FROM orders o LEFT JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 8"
);
// Top products
$topProducts = mysqli_query($conn,
    "SELECT p.name, p.price, p.stock, p.rating, COUNT(oi.id) sold
     FROM products p LEFT JOIN order_items oi ON p.id=oi.product_id
     GROUP BY p.id ORDER BY sold DESC, p.rating DESC LIMIT 6"
);
// Sales by category
$catSales = mysqli_query($conn,
    "SELECT c.name, COUNT(p.id) cnt FROM categories c LEFT JOIN products p ON c.id=p.category_id GROUP BY c.id ORDER BY cnt DESC"
);
$catRows = []; while ($r = mysqli_fetch_assoc($catSales)) $catRows[] = $r;
$maxCnt = max(array_column($catRows,'cnt') ?: [1]);

include '_layout.php';
?>

<!-- STAT CARDS -->
<div class="stat-grid">
    <div class="stat-card orange">
        <div class="stat-icon"><i class="fas fa-box"></i></div>
        <div class="stat-val"><?= number_format($stats['products']) ?></div>
        <div class="stat-label">Нийт бүтээгдэхүүн</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-val"><?= number_format($stats['orders']) ?></div>
        <div class="stat-label">Нийт захиалга</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-tugrik-sign"></i></div>
        <div class="stat-val"><?= number_format($stats['revenue'] / 1000000, 1) ?>M₮</div>
        <div class="stat-label">Нийт орлого</div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-val"><?= number_format($stats['users']) ?></div>
        <div class="stat-label">Бүртгэлтэй хэрэглэгч</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-val"><?= $stats['pending'] ?></div>
        <div class="stat-label">Хүлээгдэж буй захиалга</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-val"><?= $stats['low_stock'] ?></div>
        <div class="stat-label">Нөөц бага бараа</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-val"><?= $stats['today_orders'] ?></div>
        <div class="stat-label">Өнөөдрийн захиалга</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-tags"></i></div>
        <div class="stat-val"><?= $stats['categories'] ?></div>
        <div class="stat-label">Ангиллын тоо</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
<!-- Recent Orders -->
<div class="table-card">
    <div class="table-header">
        <span class="table-title">🛒 Сүүлийн захиалгууд</span>
        <a href="orders.php" class="btn btn-secondary btn-sm">Бүгд харах</a>
    </div>
    <table>
        <thead><tr>
            <th>#</th><th>Хэрэглэгч</th><th>Дүн</th><th>Статус</th>
        </tr></thead>
        <tbody>
        <?php while ($o = mysqli_fetch_assoc($recentOrders)):
            $sc = match($o['status']) {
                'pending'    => 'badge-warning',
                'processing' => 'badge-info',
                'shipped'    => 'badge-orange',
                'delivered'  => 'badge-success',
                'cancelled'  => 'badge-danger',
                default      => 'badge-gray'
            };
            $sl = match($o['status']) {
                'pending'=>'Хүлээгдэж байна','processing'=>'Боловсруулж байна',
                'shipped'=>'Хүргэлтэнд','delivered'=>'Хүргэгдсэн','cancelled'=>'Цуцлагдсан',default=>$o['status']
            };
        ?>
        <tr>
            <td><a href="orders.php?view=<?= $o['id'] ?>" style="color:#FF6B35;font-weight:700;text-decoration:none">#<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></a></td>
            <td><?= htmlspecialchars($o['uname'] ?? $o['shipping_name'] ?? 'Зочин') ?></td>
            <td class="price-cell"><?= number_format($o['total_amount']) ?>₮</td>
            <td><span class="badge <?= $sc ?>"><?= $sl ?></span></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Category chart -->
<div class="chart-wrap">
    <div class="chart-header">
        <span class="table-title">📊 Ангиллаар бараа</span>
    </div>
    <?php foreach ($catRows as $cr): $pct = round($cr['cnt']/$maxCnt*100); ?>
    <div class="chart-bar-row">
        <span class="chart-bar-label"><?= mb_substr($cr['name'],0,6) ?></span>
        <div class="chart-bar-track"><div class="chart-bar-fill" data-width="<?= $pct ?>" style="width:0"></div></div>
        <span class="chart-bar-val"><?= number_format($cr['cnt']) ?> бараа</span>
    </div>
    <?php endforeach; ?>
</div>
</div>

<!-- Top products -->
<div class="table-card">
    <div class="table-header">
        <span class="table-title">⭐ Шилдэг бүтээгдэхүүн</span>
        <a href="products.php" class="btn btn-secondary btn-sm">Бүгд</a>
    </div>
    <table>
        <thead><tr><th>Нэр</th><th>Үнэ</th><th>Нөөц</th><th>Үнэлгээ</th><th>Захиалга</th></tr></thead>
        <tbody>
        <?php while ($p = mysqli_fetch_assoc($topProducts)):
            $sc = $p['stock'] < 5 ? 'stock-low' : ($p['stock'] < 20 ? 'stock-med' : 'stock-ok');
        ?>
        <tr>
            <td><div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars(mb_substr($p['name'],0,40)) ?></div></td>
            <td class="price-cell"><?= number_format($p['price']) ?>₮</td>
            <td class="<?= $sc ?>"><?= $p['stock'] ?></td>
            <td>⭐ <?= $p['rating'] ?></td>
            <td><span class="badge badge-info"><?= $p['sold'] ?></span></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '_layout_end.php'; ?>