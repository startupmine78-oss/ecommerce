<?php
require_once 'db.php';

$trackNum = sanitize($_GET['track'] ?? '');
$orderId  = intval($_GET['order'] ?? 0);

// Find tracking info
$tracking = null;
if ($trackNum) {
    $tn = mysqli_real_escape_string($conn, $trackNum);
    $tracking = mysqli_fetch_assoc(mysqli_query($conn, "SELECT t.*, o.total_amount, o.shipping_name, o.shipping_address, o.shipping_phone, o.payment_method, o.created_at as order_date FROM delivery_tracking t LEFT JOIN orders o ON t.order_id = o.id WHERE t.tracking_number = '$tn'"));
} elseif ($orderId && isLoggedIn()) {
    $tracking = mysqli_fetch_assoc(mysqli_query($conn, "SELECT t.*, o.total_amount, o.shipping_name, o.shipping_address, o.shipping_phone, o.payment_method, o.created_at as order_date FROM delivery_tracking t LEFT JOIN orders o ON t.order_id = o.id WHERE t.order_id = $orderId AND o.user_id = {$_SESSION['user_id']}"));
}

// Demo tracking for UI showcase
if (!$tracking) {
    $tracking = [
        'tracking_number'   => 'SMN0000001',
        'status'            => 'in_transit',
        'estimated_delivery'=> date('Y-m-d', strtotime('+1 day')),
        'shipping_name'     => 'Бат-Эрдэнэ',
        'shipping_address'  => 'Баянгол дүүрэг, 7-р хороо, 3-р хороолол 7 байр 15 тоот',
        'shipping_phone'    => '9911 2233',
        'total_amount'      => 449000,
        'order_date'        => date('Y-m-d H:i:s', strtotime('-1 day')),
        'payment_method'    => 'qpay',
        'carrier'           => 'shopexpress',
        'current_location'  => 'ЗКМ, Улаанбаатар',
        'driver_name'       => 'Дорж',
        'driver_phone'      => '9922 3344',
        'order_id'          => 1,
    ];
    $demoMode = true;
}

// Status steps definition
$allStatuses = [
    'order_placed'       => ['label' => 'Захиалга хүлээн авлаа',    'icon' => '✅', 'color' => '#10B981'],
    'payment_confirmed'  => ['label' => 'Төлбөр баталгаажлаа',      'icon' => '💳', 'color' => '#3B82F6'],
    'preparing'          => ['label' => 'Багцлаж бэлтгэж байна',    'icon' => '📦', 'color' => '#8B5CF6'],
    'picked_up'          => ['label' => 'Хүргэлтэнд гарлаа',        'icon' => '🚐', 'color' => '#F59E0B'],
    'in_transit'         => ['label' => 'Замдаа яваа',               'icon' => '🚚', 'color' => '#FF6B35'],
    'out_for_delivery'   => ['label' => 'Таны хаяг руу яваа',       'icon' => '🏃', 'color' => '#EF4444'],
    'delivered'          => ['label' => 'Хүргэгдсэн',                'icon' => '🎉', 'color' => '#10B981'],
];

$statusOrder = array_keys($allStatuses);
$currentIdx  = array_search($tracking['status'], $statusOrder);

$pageTitle = 'Хүргэлт хянах — ShopMN';
include 'includes/header.php';
?>

<div class="container" style="padding:30px 24px 60px;max-width:900px;margin:0 auto;">
    <div class="breadcrumb">
        <a href="index.php">Нүүр</a> <span>›</span>
        <a href="profile.php">Захиалгууд</a> <span>›</span>
        Хүргэлт хянах
    </div>

    <?php if (!empty($demoMode)): ?>
    <div class="alert alert-info" style="margin-bottom:20px;">ℹ️ Demo горим — жинхэнэ tracking харуулж байна</div>
    <?php endif; ?>

    <!-- Tracking search -->
    <div style="background:white;border-radius:16px;padding:20px 24px;box-shadow:0 4px 20px rgba(0,0,0,0.06);margin-bottom:20px;">
        <form method="GET" style="display:flex;gap:10px;">
            <input type="text" name="track" class="form-control" placeholder="Tracking дугаар: SMN0000001" value="<?= htmlspecialchars($trackNum) ?>" style="flex:1;">
            <button type="submit" class="btn-primary" style="padding:10px 20px;">🔍 Хайх</button>
        </form>
    </div>

    <!-- Main tracking card -->
    <div style="background:white;border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.1);">

        <!-- Header -->
        <div style="background:linear-gradient(135deg,var(--secondary),#16213E);color:white;padding:28px 32px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;">
                <div>
                    <div style="font-size:0.82rem;opacity:0.7;margin-bottom:4px;">Tracking дугаар</div>
                    <div style="font-family:'Outfit',sans-serif;font-size:1.6rem;font-weight:800;letter-spacing:2px;">
                        <?= htmlspecialchars($tracking['tracking_number']) ?>
                    </div>
                    <div style="margin-top:8px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <span style="background:<?= $allStatuses[$tracking['status']]['color'] ?? '#666' ?>;padding:5px 14px;border-radius:20px;font-weight:700;font-size:0.85rem;">
                            <?= $allStatuses[$tracking['status']]['icon'] ?? '📍' ?>
                            <?= $allStatuses[$tracking['status']]['label'] ?? $tracking['status'] ?>
                        </span>
                        <?php if ($tracking['carrier'] === 'shopexpress'): ?>
                        <span style="background:rgba(255,107,53,0.3);padding:5px 14px;border-radius:20px;font-size:0.8rem;">⚡ ShopExpress</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:0.82rem;opacity:0.7;">Хүргэгдэх хугацаа</div>
                    <div style="font-family:'Outfit',sans-serif;font-size:1.2rem;font-weight:800;color:var(--accent);">
                        <?= date('n-р сарын j', strtotime($tracking['estimated_delivery'])) ?>
                    </div>
                    <div style="font-size:0.8rem;opacity:0.7;margin-top:2px;">
                        <?= date('H:00 — ', strtotime('10:00')) ?>18:00 цагт
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress bar -->
        <div style="padding:24px 32px;border-bottom:1px solid var(--border);">
            <div style="position:relative;">
                <!-- Track line -->
                <div style="position:absolute;top:16px;left:16px;right:16px;height:4px;background:var(--border);border-radius:2px;z-index:0;"></div>
                <?php if ($currentIdx > 0): ?>
                <div style="position:absolute;top:16px;left:16px;height:4px;background:var(--primary);border-radius:2px;z-index:1;width:<?= min(100, round($currentIdx / (count($allStatuses)-1) * 100)) ?>%;transition:width 1s ease;"></div>
                <?php endif; ?>

                <!-- Status dots -->
                <div style="display:flex;justify-content:space-between;position:relative;z-index:2;">
                    <?php foreach ($allStatuses as $key => $info):
                        $idx = array_search($key, $statusOrder);
                        $isDone   = $idx <= $currentIdx;
                        $isActive = $idx === $currentIdx;
                    ?>
                    <div style="display:flex;flex-direction:column;align-items:center;gap:8px;flex:1;">
                        <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:<?= $isActive ? '1.1rem' : '0.85rem' ?>;background:<?= $isDone ? $info['color'] : 'var(--border)' ?>;color:white;transition:all 0.3s;<?= $isActive ? 'box-shadow:0 0 0 5px '.($info['color'].'30').';animation:pulse 1.5s infinite;' : '' ?>">
                            <?= $isDone ? $info['icon'] : ($idx < $currentIdx ? '✓' : '·') ?>
                        </div>
                        <span style="font-size:0.62rem;font-weight:600;color:<?= $isDone ? 'var(--text)' : 'var(--text-light)' ?>;text-align:center;max-width:60px;line-height:1.2;">
                            <?= $info['label'] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">

            <!-- Left: Timeline events -->
            <div style="padding:24px 32px;border-right:1px solid var(--border);">
                <h3 style="font-family:'Outfit',sans-serif;font-weight:700;margin-bottom:16px;font-size:1rem;">📋 Хөдөлгөөний түүх</h3>
                <div>
                    <?php
                    // Generate demo timeline
                    $events = [
                        ['icon'=>'✅','label'=>'Захиалга баталгаажлаа','desc'=>'Таны захиалга амжилттай хүлээн авагдлаа','loc'=>'ShopMN Систем','time'=>date('H:i', strtotime('-23 hours')).' '.date('Y.m.d', strtotime('-1 day')),'done'=>true],
                        ['icon'=>'💳','label'=>'Төлбөр баталгаажлаа','desc'=>'QPay төлбөр амжилттай баталгаажлаа','loc'=>'ShopMN Санхүү','time'=>date('H:i', strtotime('-22 hours')).' '.date('Y.m.d', strtotime('-1 day')),'done'=>true],
                        ['icon'=>'📦','label'=>'Багцлаж байна','desc'=>'Таны захиалга агуулахад бэлтгэгдэж байна','loc'=>'ShopMN Агуулах, ЗКМ','time'=>date('H:i', strtotime('-8 hours')).' '.date('Y.m.d', strtotime('today')),'done'=>true],
                        ['icon'=>'🚚','label'=>'Замдаа яваа','desc'=>'Таны захиалга хүргэлтийн машинд ачаагдлаа','loc'=>'ShopExpress ЗКМ Салбар','time'=>date('H:i', strtotime('-2 hours')).' '.date('Y.m.d'),'done'=>true,'active'=>true],
                        ['icon'=>'🏠','label'=>'Хүргэгдэх хаяг','desc'=>'Дараагийн зогсоол','loc'=>htmlspecialchars($tracking['shipping_address']),'time'=>date('Y.m.d', strtotime('+1 day')).' 10:00-18:00','done'=>false],
                    ];
                    foreach ($events as $ev):
                    ?>
                    <div style="display:flex;gap:12px;padding:10px 0;position:relative;">
                        <?php if (!end($events) || $ev !== end($events)): ?>
                        <div style="position:absolute;left:15px;top:36px;bottom:-10px;width:2px;background:<?= $ev['done'] ? 'var(--success)' : 'var(--border)' ?>;"></div>
                        <?php endif; ?>
                        <div style="width:32px;height:32px;border-radius:50%;background:<?= $ev['done'] ? ($ev['active'] ?? false ? 'var(--primary)' : 'var(--success)') : 'var(--border)' ?>;color:white;display:flex;align-items:center;justify-content:center;font-size:0.9rem;flex-shrink:0;<?= ($ev['active'] ?? false) ? 'animation:pulse 1.5s infinite;' : '' ?>">
                            <?= $ev['icon'] ?>
                        </div>
                        <div style="flex:1;padding-bottom:6px;">
                            <div style="font-weight:700;font-size:0.88rem;color:<?= $ev['done'] ? 'var(--text)' : 'var(--text-light)' ?>;"><?= $ev['label'] ?></div>
                            <div style="font-size:0.78rem;color:var(--text-light);"><?= $ev['desc'] ?></div>
                            <div style="font-size:0.72rem;color:var(--text-light);margin-top:2px;">📍 <?= $ev['loc'] ?></div>
                            <div style="font-size:0.72rem;color:var(--text-light);">⏰ <?= $ev['time'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right: Delivery info -->
            <div style="padding:24px 32px;">
                <h3 style="font-family:'Outfit',sans-serif;font-weight:700;margin-bottom:16px;font-size:1rem;">📍 Хүргэлтийн мэдээлэл</h3>

                <!-- Recipient -->
                <div style="background:var(--bg);border-radius:12px;padding:14px;margin-bottom:12px;">
                    <div style="font-size:0.75rem;font-weight:700;color:var(--text-light);text-transform:uppercase;margin-bottom:8px;">Хүлээн авагч</div>
                    <div style="font-weight:700;"><?= htmlspecialchars($tracking['shipping_name']) ?></div>
                    <div style="font-size:0.85rem;color:var(--text-light);">📱 <?= htmlspecialchars($tracking['shipping_phone']) ?></div>
                    <div style="font-size:0.82rem;color:var(--text-light);margin-top:4px;">📍 <?= htmlspecialchars($tracking['shipping_address']) ?></div>
                </div>

                <!-- Driver info -->
                <?php if (!empty($tracking['driver_name'])): ?>
                <div style="background:#f0fff8;border-radius:12px;padding:14px;margin-bottom:12px;border:1px solid #d1fae5;">
                    <div style="font-size:0.75rem;font-weight:700;color:#065F46;text-transform:uppercase;margin-bottom:8px;">🚗 Жолооч</div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:40px;height:40px;background:var(--success);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🧑</div>
                        <div>
                            <div style="font-weight:700;"><?= htmlspecialchars($tracking['driver_name']) ?></div>
                            <a href="tel:<?= htmlspecialchars($tracking['driver_phone']) ?>" style="color:var(--primary);font-weight:600;font-size:0.88rem;">
                                📞 <?= htmlspecialchars($tracking['driver_phone']) ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Order summary -->
                <div style="background:var(--bg);border-radius:12px;padding:14px;margin-bottom:12px;">
                    <div style="font-size:0.75rem;font-weight:700;color:var(--text-light);text-transform:uppercase;margin-bottom:8px;">Захиалгын дэлгэрэнгүй</div>
                    <div style="display:flex;justify-content:space-between;font-size:0.85rem;margin-bottom:4px;">
                        <span style="color:var(--text-light);">Дүн:</span>
                        <strong style="color:var(--primary);font-family:'Outfit',sans-serif;"><?= formatPrice($tracking['total_amount']) ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:0.85rem;margin-bottom:4px;">
                        <span style="color:var(--text-light);">Төлбөр:</span>
                        <span style="font-weight:600;text-transform:uppercase;"><?= htmlspecialchars($tracking['payment_method']) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:0.85rem;">
                        <span style="color:var(--text-light);">Огноо:</span>
                        <span><?= date('Y.m.d', strtotime($tracking['order_date'])) ?></span>
                    </div>
                </div>

                <!-- Live location map placeholder -->
                <div style="background:linear-gradient(135deg,#E0F2FE,#BAE6FD);border-radius:12px;padding:20px;text-align:center;border:2px dashed #7DD3FC;">
                    <div style="font-size:2rem;margin-bottom:8px;">🗺️</div>
                    <div style="font-weight:700;font-size:0.9rem;color:#0369A1;">Шууд хянах газрын зураг</div>
                    <div style="font-size:0.78rem;color:#0284C7;margin-top:4px;">Google Maps интеграц нэмэхэд</div>
                    <div style="font-size:0.72rem;color:#0369A1;margin-top:2px;">API key шаардлагатай</div>
                </div>
            </div>
        </div>

        <!-- Footer: Actions -->
        <div style="padding:20px 32px;border-top:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;background:var(--bg);">
            <a href="profile.php" class="btn-outline" style="color:var(--text);border-color:var(--border);padding:10px 18px;font-size:0.88rem;">
                ← Захиалгууд руу
            </a>
            <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($tracking['tracking_number']) ?>').then(()=>Toast.show('Tracking дугаар хуулагдлаа!','success'))"
                class="btn-outline" style="color:var(--text);border-color:var(--border);padding:10px 18px;font-size:0.88rem;">
                📋 Дугаар хуулах
            </button>
            <?php if (!empty($tracking['driver_phone'])): ?>
            <a href="tel:<?= htmlspecialchars($tracking['driver_phone']) ?>" class="btn-primary" style="padding:10px 18px;font-size:0.88rem;">
                📞 Жолоочид залгах
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>