<?php
require_once 'db.php';

$trackNum = sanitize($_GET['track'] ?? '');
$orderId  = intval($_GET['order'] ?? 0);

// Find tracking info
$tracking = null;
if ($trackNum) {
    $tn = mysqli_real_escape_string($conn, $trackNum);
    $tracking = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT t.*, o.total_amount, o.shipping_name, o.shipping_address,
                o.shipping_phone, o.payment_method, o.created_at as order_date,
                o.status as order_status
         FROM delivery_tracking t
         LEFT JOIN orders o ON t.order_id = o.id
         WHERE t.tracking_number = '$tn'"
    ));
} elseif ($orderId && isLoggedIn()) {
    $tracking = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT t.*, o.total_amount, o.shipping_name, o.shipping_address,
                o.shipping_phone, o.payment_method, o.created_at as order_date,
                o.status as order_status
         FROM delivery_tracking t
         LEFT JOIN orders o ON t.order_id = o.id
         WHERE t.order_id = $orderId AND o.user_id = {$_SESSION['user_id']}"
    ));
}

// Demo tracking data
$demoMode = false;
if (!$tracking) {
    $tracking = [
        'tracking_number'    => 'SMN0012847',
        'status'             => 'in_transit',
        'estimated_delivery' => date('Y-m-d', strtotime('+1 day')),
        'shipping_name'      => 'Бат-Эрдэнэ',
        'shipping_address'   => 'Хан-Уул дүүрэг, 7-р хороо, 3-р хороолол 7 байр 15 тоот',
        'shipping_phone'     => '9911 2233',
        'total_amount'       => 449000,
        'order_date'         => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'payment_method'     => 'qpay',
        'carrier'            => 'shopexpress',
        'current_location'   => 'Хан-Уул дүүрэг',
        'driver_name'        => 'Дорж Баатар',
        'driver_phone'       => '9922 3344',
        'order_id'           => 1,
        'order_status'       => 'shipped',
    ];
    $demoMode = true;
}

// Status map
$statusMap = [
    'order_placed'      => ['label' => 'Захиалга баталгаажлаа',  'icon' => '✅', 'color' => '#10B981', 'step' => 0],
    'payment_confirmed' => ['label' => 'Төлбөр баталгаажлаа',    'icon' => '💳', 'color' => '#3B82F6', 'step' => 1],
    'preparing'         => ['label' => 'Бэлтгэж байна',          'icon' => '📦', 'color' => '#8B5CF6', 'step' => 2],
    'picked_up'         => ['label' => 'Жолооч авлаа',           'icon' => '🛵', 'color' => '#F59E0B', 'step' => 3],
    'in_transit'        => ['label' => 'Замдаа яваа',             'icon' => '🚚', 'color' => '#FF6B35', 'step' => 4],
    'out_for_delivery'  => ['label' => 'Таны хаяг руу яваа',     'icon' => '🏃', 'color' => '#EF4444', 'step' => 5],
    'delivered'         => ['label' => 'Хүргэгдсэн',             'icon' => '🎉', 'color' => '#10B981', 'step' => 6],
];
$currentStatus = $statusMap[$tracking['status']] ?? $statusMap['in_transit'];

$pageTitle = 'Хүргэлт хянах — ShopMN';
include 'includes/header.php';
?>

<style>
/* ──── TRACKING PAGE LAYOUT ──── */
.trk-wrap {
    max-width: 780px;
    margin: 24px auto;
    padding: 0 16px 60px;
}

/* Search bar */
.trk-search {
    background: white;
    border-radius: 14px;
    padding: 16px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    margin-bottom: 16px;
    display: flex;
    gap: 10px;
}
.trk-search input {
    flex: 1;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    padding: 10px 14px;
    font-size: .9rem;
    outline: none;
    font-family: 'DM Sans', sans-serif;
    transition: border-color .2s;
}
.trk-search input:focus { border-color: var(--primary); }

/* Main card */
.trk-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.1);
}

/* Header */
.trk-header {
    background: linear-gradient(135deg, #1A1A2E 0%, #16213E 100%);
    padding: 20px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.trk-header-left .trk-label {
    font-size: .72rem;
    color: rgba(255,255,255,.5);
    letter-spacing: .5px;
    text-transform: uppercase;
}
.trk-header-left .trk-num {
    font-family: 'Outfit', monospace;
    font-size: 1.3rem;
    font-weight: 800;
    color: white;
    letter-spacing: 2px;
    margin-top: 2px;
}
.trk-eta {
    background: var(--primary);
    color: white;
    padding: 7px 16px;
    border-radius: 20px;
    font-size: .82rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 5px;
    font-family: 'Outfit', sans-serif;
}
.trk-eta .eta-dot {
    width: 7px; height: 7px;
    background: white;
    border-radius: 50%;
    animation: pulse 1.2s infinite;
    flex-shrink: 0;
}

/* Map */
.trk-map-wrap {
    position: relative;
    height: 300px;
    background: #e8ede0;
    overflow: hidden;
}
.trk-map-svg { width: 100%; height: 100%; display: block; }

/* Map overlay cards */
.map-overlay {
    position: absolute;
    top: 12px;
    left: 12px;
    background: white;
    border-radius: 10px;
    padding: 10px 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,.15);
    min-width: 130px;
}
.mo-label { font-size: .68rem; color: #94A3B8; margin-bottom: 2px; text-transform: uppercase; letter-spacing: .5px; }
.mo-live { display: flex; align-items: center; gap: 5px; margin-bottom: 4px; }
.live-dot {
    width: 8px; height: 8px;
    background: #10B981;
    border-radius: 50%;
    animation: pulse 1.2s infinite;
    flex-shrink: 0;
}
.mo-dist { font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 800; color: #1A1A2E; line-height: 1; }
.mo-unit { font-size: .72rem; color: #64748B; margin-top: 1px; }

.map-speed {
    position: absolute;
    bottom: 12px;
    right: 12px;
    background: white;
    border-radius: 10px;
    padding: 8px 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,.15);
    text-align: center;
}
.speed-num { font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 800; color: #1A1A2E; }
.speed-unit { font-size: .65rem; color: #94A3B8; }

/* Driver card */
.trk-driver {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    border-bottom: 1px solid #F1F5F9;
}
.driver-av {
    width: 50px; height: 50px;
    background: linear-gradient(135deg, var(--primary), #e85d2f);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Outfit', sans-serif;
    font-size: 1.1rem; font-weight: 800;
    color: white; flex-shrink: 0;
}
.driver-meta { flex: 1; }
.driver-meta .d-name { font-weight: 700; font-size: .95rem; color: #1E293B; }
.driver-meta .d-plate { font-size: .78rem; color: #94A3B8; margin-top: 2px; font-family: monospace; }
.driver-meta .d-stars { font-size: .75rem; color: #94A3B8; margin-top: 2px; display: flex; align-items: center; gap: 3px; }
.driver-actions { display: flex; gap: 8px; }
.d-action-btn {
    width: 40px; height: 40px;
    border-radius: 50%;
    border: 1.5px solid #E2E8F0;
    background: white;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    font-size: 1rem;
    transition: all .15s;
    text-decoration: none;
}
.d-action-btn:hover { background: #FFF5F0; border-color: var(--primary); }

/* Steps */
.trk-steps { padding: 20px 22px; }
.step-item {
    display: flex;
    gap: 14px;
    position: relative;
}
.step-item:not(:last-child) { margin-bottom: 0; }
.step-item:not(:last-child) .step-line {
    position: absolute;
    left: 15px; top: 34px;
    width: 2px;
    bottom: 0;
    background: #E2E8F0;
}
.step-item.s-done:not(:last-child) .step-line { background: #10B981; }

.step-dot {
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem;
    flex-shrink: 0;
    z-index: 1;
    margin-top: 4px;
}
.step-item.s-done  .step-dot { background: #10B981; color: white; }
.step-item.s-active .step-dot {
    background: var(--primary); color: white;
    animation: bounceSoft 1.2s ease-in-out infinite;
}
.step-item.s-pend  .step-dot {
    background: #F1F5F9; color: #94A3B8;
    border: 1.5px solid #E2E8F0;
}

.step-body { flex: 1; padding: 6px 0 20px; }
.step-body .sb-title { font-weight: 700; font-size: .88rem; color: #1E293B; }
.step-body .sb-sub { font-size: .75rem; color: #94A3B8; margin-top: 3px; }
.step-body .sb-time { color: #10B981; font-weight: 600; }

/* Progress bar */
.trk-progress-wrap { padding: 0 22px 16px; }
.trk-prog-label {
    display: flex; justify-content: space-between;
    font-size: .75rem; font-weight: 600;
    color: #64748B; margin-bottom: 7px;
}
.trk-prog-track {
    height: 6px; background: #F1F5F9;
    border-radius: 3px; overflow: hidden;
}
.trk-prog-fill {
    height: 100%;
    background: linear-gradient(90deg, #FF6B35, #ffaa88);
    border-radius: 3px;
    transition: width 1s ease;
}

/* Info section */
.trk-info { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 0 22px 20px; }
.info-box {
    background: #F8FAFC;
    border-radius: 10px;
    padding: 12px 14px;
}
.info-box .ib-label { font-size: .68rem; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
.info-box .ib-val { font-size: .88rem; font-weight: 600; color: #1E293B; line-height: 1.4; }
.info-box .ib-sub { font-size: .75rem; color: #64748B; margin-top: 2px; }

/* Action footer */
.trk-footer {
    padding: 16px 22px;
    border-top: 1px solid #F1F5F9;
    display: flex;
    gap: 10px;
}
.trk-btn-outline {
    flex: 1;
    padding: 11px;
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    color: #475569;
    transition: all .15s;
    font-family: 'DM Sans', sans-serif;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.trk-btn-outline:hover { background: #F1F5F9; }
.trk-btn-primary {
    flex: 2;
    padding: 11px;
    background: #1A1A2E;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: .88rem;
    font-weight: 700;
    cursor: pointer;
    font-family: 'Outfit', sans-serif;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: background .2s;
}
.trk-btn-primary:hover { background: var(--primary); }
.trk-btn-primary.confirmed { background: #10B981; pointer-events: none; }

/* Animations */
@keyframes pulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.6);opacity:.4} }
@keyframes bounceSoft { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-3px)} }
@keyframes driverMove { 0%{stroke-dashoffset:200} 100%{stroke-dashoffset:0} }
@keyframes vehiclePulse { 0%,100%{r:16} 50%{r:18} }
@keyframes destPulse { 0%,100%{r:14;opacity:.25} 50%{r:22;opacity:.1} }
@keyframes routeDash { to{stroke-dashoffset:-28} }
@keyframes moveX { 0%{transform:translate(0px,0px)} 25%{transform:translate(60px,-8px)} 50%{transform:translate(100px,12px)} 75%{transform:translate(130px,0px)} 100%{transform:translate(155px,18px)} }
@keyframes speedFlicker { 0%,100%{opacity:1} 50%{opacity:.7} }

.route-dash { stroke-dasharray: 14 7; animation: routeDash 1.4s linear infinite; }
.vehicle-grp { animation: moveX 8s ease-in-out infinite alternate; }

@media (max-width: 600px) {
    .trk-info { grid-template-columns: 1fr; }
    .trk-driver { flex-wrap: wrap; }
}
</style>

<div class="trk-wrap">
    <div class="breadcrumb">
        <a href="index.php">Нүүр</a> <span>›</span>
        <a href="profile.php">Захиалгууд</a> <span>›</span>
        Хүргэлт хянах
    </div>

    <?php if ($demoMode): ?>
    <div class="alert alert-info" style="margin-bottom:14px;">ℹ️ Demo горим — жинхэнэ захиалгын дугаар оруулж хянана уу</div>
    <?php endif; ?>

    <!-- Search -->
    <form method="GET" class="trk-search">
        <input type="text" name="track" placeholder="Tracking дугаар: SMN0012847"
               value="<?= htmlspecialchars($trackNum) ?>">
        <button type="submit" class="btn-primary" style="padding:10px 18px;font-size:.85rem;">
            🔍 Хайх
        </button>
    </form>

    <!-- Main tracking card -->
    <div class="trk-card">

        <!-- Header -->
        <div class="trk-header">
            <div class="trk-header-left">
                <div class="trk-label">Tracking дугаар</div>
                <div class="trk-num"><?= htmlspecialchars($tracking['tracking_number']) ?></div>
            </div>
            <div class="trk-eta" id="etaBadge">
                <span class="eta-dot"></span>
                <span id="etaText">~<?= $demoMode ? '12' : '8' ?> минут</span>
            </div>
        </div>

        <!-- Animated Map -->
        <div class="trk-map-wrap">
            <svg class="trk-map-svg" viewBox="0 0 680 300" xmlns="http://www.w3.org/2000/svg">
                <!-- Background -->
                <rect width="680" height="300" fill="#e8ede0"/>
                <!-- City blocks -->
                <rect x="0"   y="0"   width="100" height="90"  fill="#d4dbc8" rx="0"/>
                <rect x="120" y="0"   width="140" height="90"  fill="#d4dbc8"/>
                <rect x="280" y="0"   width="130" height="90"  fill="#d4dbc8"/>
                <rect x="430" y="0"   width="250" height="90"  fill="#d8e0c8"/>
                <rect x="0"   y="110" width="90"  height="100" fill="#d4dbc8"/>
                <rect x="110" y="110" width="120" height="100" fill="#d4dbc8"/>
                <rect x="250" y="110" width="110" height="100" fill="#c8d8c0"/>
                <rect x="380" y="110" width="120" height="100" fill="#d4dbc8"/>
                <rect x="520" y="110" width="160" height="100" fill="#d4dbc8"/>
                <rect x="0"   y="230" width="110" height="70"  fill="#d4dbc8"/>
                <rect x="130" y="230" width="140" height="70"  fill="#d4dbc8"/>
                <rect x="290" y="230" width="120" height="70"  fill="#d4dbc8"/>
                <rect x="430" y="230" width="100" height="70"  fill="#d4dbc8"/>
                <rect x="550" y="230" width="130" height="70"  fill="#d4dbc8"/>
                <!-- Roads -->
                <rect x="100" y="0"   width="20" height="300" fill="#c2caba"/>
                <rect x="260" y="0"   width="20" height="300" fill="#c2caba"/>
                <rect x="410" y="0"   width="20" height="300" fill="#c2caba"/>
                <rect x="510" y="0"   width="20" height="300" fill="#c2caba"/>
                <rect x="0"   y="90"  width="680" height="20" fill="#c2caba"/>
                <rect x="0"   y="210" width="680" height="20" fill="#c2caba"/>
                <!-- Route path -->
                <path class="route-dash" d="M140 260 L140 220 L270 220 L270 100 L420 100 L420 220 L520 220"
                      fill="none" stroke="#FF6B35" stroke-width="5"
                      stroke-linecap="round" stroke-linejoin="round" opacity=".8"/>
                <!-- Destination pulse ring -->
                <circle id="destPulseRing" cx="520" cy="220" r="18" fill="#FF6B35" opacity=".2">
                    <animate attributeName="r" values="14;24;14" dur="2s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values=".3;.08;.3" dur="2s" repeatCount="indefinite"/>
                </circle>
                <!-- Destination pin -->
                <circle cx="520" cy="220" r="12" fill="#FF6B35"/>
                <text x="520" y="224" text-anchor="middle" font-size="10" fill="white">🏠</text>
                <!-- Origin -->
                <circle cx="140" cy="260" r="10" fill="#3B82F6"/>
                <circle cx="140" cy="260" r="4" fill="white"/>
                <!-- Driver vehicle (animated) -->
                <g class="vehicle-grp" transform="translate(140,260)">
                    <circle cx="0" cy="-6" r="16" fill="#1A1A2E" opacity=".92"/>
                    <text x="0" y="-2" text-anchor="middle" font-size="13">🛵</text>
                    <polygon points="0,-24 -5,-18 5,-18" fill="#FF6B35"/>
                </g>
                <!-- Street labels -->
                <rect x="155" y="146" width="78" height="18" rx="4" fill="rgba(255,255,255,.85)"/>
                <text x="194" y="159" text-anchor="middle" font-size="10" fill="#64748B" font-family="sans-serif">Хан-Уул</text>
                <rect x="290" y="75" width="68" height="18" rx="4" fill="rgba(255,255,255,.85)"/>
                <text x="324" y="88" text-anchor="middle" font-size="10" fill="#64748B" font-family="sans-serif">Замчид</text>
                <rect x="424" y="146" width="72" height="18" rx="4" fill="rgba(255,255,255,.85)"/>
                <text x="460" y="159" text-anchor="middle" font-size="10" fill="#64748B" font-family="sans-serif">Сонгино</text>
            </svg>

            <!-- Overlay: distance card -->
            <div class="map-overlay">
                <div class="mo-live">
                    <span class="live-dot"></span>
                    <span style="font-size:.7rem;color:#94A3B8;">ШУУД</span>
                </div>
                <div class="mo-dist" id="distVal">1.4</div>
                <div class="mo-unit">км үлдсэн</div>
            </div>

            <!-- Speed indicator -->
            <div class="map-speed">
                <div class="speed-num" id="speedVal">28</div>
                <div class="speed-unit">км/цаг</div>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="trk-progress-wrap" style="margin-top:16px;">
            <div class="trk-prog-label">
                <span>Захиалга</span>
                <span id="progressPct">71%</span>
                <span>Хүргэгдсэн</span>
            </div>
            <div class="trk-prog-track">
                <div class="trk-prog-fill" id="progressBar" style="width:71%"></div>
            </div>
        </div>

        <!-- Driver info -->
        <div class="trk-driver">
            <div class="driver-av"><?= mb_strtoupper(mb_substr($tracking['driver_name'] ?? 'Д', 0, 1)) ?></div>
            <div class="driver-meta">
                <div class="d-name"><?= htmlspecialchars($tracking['driver_name'] ?? 'Дорж Баатар') ?></div>
                <div class="d-plate">УБА-7234 · Toyota Hiace</div>
                <div class="d-stars">
                    <span style="color:#F59E0B;">★★★★★</span>
                    <span>4.9 (1,240 хүргэлт)</span>
                </div>
            </div>
            <div class="driver-actions">
                <a href="tel:<?= htmlspecialchars($tracking['driver_phone'] ?? '99223344') ?>"
                   class="d-action-btn" title="Жолоочид залгах">📞</a>
                <button class="d-action-btn" title="Мессеж илгээх" onclick="sendMessage()">💬</button>
            </div>
        </div>

        <!-- Timeline steps -->
        <div class="trk-steps">
            <?php
            $steps = [
                ['key'=>'order_placed',      'icon'=>'✓', 'title'=>'Захиалга баталгаажлаа',   'sub'=>date('H:i', strtotime($tracking['order_date']??'now')).' '.date('Y.m.d', strtotime($tracking['order_date']??'now'))],
                ['key'=>'payment_confirmed', 'icon'=>'✓', 'title'=>'Төлбөр баталгаажлаа',     'sub'=>strtoupper($tracking['payment_method']??'QPay').' — амжилттай'],
                ['key'=>'preparing',         'icon'=>'📦','title'=>'Бэлтгэж дууслаа',          'sub'=>'ShopMN агуулах'],
                ['key'=>'in_transit',        'icon'=>'🛵','title'=>'Замдаа яваа',              'sub'=>htmlspecialchars($tracking['current_location']??'Хан-Уул дүүрэг').' • 1.4 км үлдсэн', 'id'=>'stepEnroute'],
                ['key'=>'delivered',         'icon'=>'🏠','title'=>'Хүргэгдэх',               'sub'=>'Ойролцоогоор '.date('H:i', strtotime($tracking['estimated_delivery']??'+1 day'.' 14:00')).' гэхэд', 'id'=>'stepDelivery'],
            ];

            $activeKey  = $tracking['status'];
            $activeStep = $statusMap[$activeKey]['step'] ?? 4;

            foreach ($steps as $i => $s):
                $sStep = $statusMap[$s['key']]['step'] ?? $i;
                if ($sStep < $activeStep)       $cls = 's-done';
                elseif ($sStep === $activeStep) $cls = 's-active';
                else                            $cls = 's-pend';
                $isLast = ($i === count($steps)-1);
            ?>
            <div class="step-item <?= $cls ?>">
                <?php if (!$isLast): ?><div class="step-line"></div><?php endif; ?>
                <div class="step-dot"><?= $s['icon'] ?></div>
                <div class="step-body" <?= isset($s['id']) ? 'id="'.$s['id'].'"' : '' ?>>
                    <div class="sb-title"><?= $s['title'] ?></div>
                    <div class="sb-sub <?= $cls==='s-done'?'sb-time':'' ?>"><?= $s['sub'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Info grid -->
        <div class="trk-info">
            <div class="info-box">
                <div class="ib-label">👤 Хүлээн авагч</div>
                <div class="ib-val"><?= htmlspecialchars($tracking['shipping_name']) ?></div>
                <div class="ib-sub">📱 <?= htmlspecialchars($tracking['shipping_phone']) ?></div>
            </div>
            <div class="info-box">
                <div class="ib-label">💰 Захиалгын дүн</div>
                <div class="ib-val" style="color:var(--primary);"><?= formatPrice($tracking['total_amount']) ?></div>
                <div class="ib-sub"><?= strtoupper(htmlspecialchars($tracking['payment_method'])) ?></div>
            </div>
            <div class="info-box" style="grid-column:1/-1;">
                <div class="ib-label">📍 Хүргэлтийн хаяг</div>
                <div class="ib-val"><?= htmlspecialchars($tracking['shipping_address']) ?></div>
            </div>
        </div>

        <!-- Footer actions -->
        <div class="trk-footer">
            <button class="trk-btn-outline" onclick="copyTracking()">
                📋 Дугаар хуулах
            </button>
            <button class="trk-btn-outline" onclick="shareTracking()">
                🔗 Хуваалцах
            </button>
            <button class="trk-btn-primary" id="confirmBtn" onclick="confirmDelivery()">
                ✅ Хүлээн авлаа
            </button>
        </div>

    </div><!-- /trk-card -->

    <!-- Order history quick links -->
    <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
        <a href="profile.php" style="color:var(--primary);font-size:.85rem;font-weight:600;text-decoration:none;">
            ← Бүх захиалга
        </a>
        <span style="color:#94A3B8;">·</span>
        <a href="index.php" style="color:#94A3B8;font-size:.85rem;text-decoration:none;">
            Нүүр хуудас
        </a>
        <?php if ($tracking['order_id'] ?? 0): ?>
        <span style="color:#94A3B8;">·</span>
        <a href="admin/orders.php?view=<?= $tracking['order_id'] ?>"
           style="color:#94A3B8;font-size:.85rem;text-decoration:none;" target="_blank">
            Захиалга дэлгэрэнгүй
        </a>
        <?php endif; ?>
    </div>

</div><!-- /trk-wrap -->

<script>
(function() {
    var dist    = 1.4;
    var speed   = 28;
    var eta     = 12;
    var prog    = 71;
    var done    = false;

    var distEl   = document.getElementById('distVal');
    var speedEl  = document.getElementById('speedVal');
    var etaEl    = document.getElementById('etaText');
    var progBar  = document.getElementById('progressBar');
    var progPct  = document.getElementById('progressPct');
    var enNote   = document.getElementById('stepEnroute');
    var delNote  = document.getElementById('stepDelivery');

    function pad(n) { return n < 10 ? '0'+n : ''+n; }

    function getEtaStr(mins) {
        if (mins <= 0) return 'Ирж байна!';
        var d = new Date();
        d.setMinutes(d.getMinutes() + Math.round(mins));
        return '~' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ' гэхэд';
    }

    function tick() {
        if (done) return;

        /* Natural-feeling random updates */
        speed = Math.min(55, Math.max(12, speed + (Math.random() * 8 - 4)));
        dist  = Math.max(0, dist - (speed / 3600 * 3 + Math.random() * 0.02));
        eta   = Math.max(0, eta - (1 + Math.random() * 0.3));
        prog  = Math.min(100, prog + (Math.random() * 1.5 + 0.5));

        distEl.textContent  = dist.toFixed(1);
        speedEl.textContent = Math.round(speed);
        etaEl.textContent   = eta > 0 ? '~' + Math.round(eta) + ' мин' : 'Ирж байна!';
        progBar.style.width = Math.round(prog) + '%';
        progPct.textContent = Math.round(prog) + '%';

        if (enNote) {
            enNote.querySelector('.sb-sub').textContent =
                (dist < 0.3 ? 'Таны хаягт ойртлоо' :
                 dist < 0.8 ? 'Таны хороонд байна' : 'Хан-Уул дүүрэгт байна') +
                ' • ' + dist.toFixed(1) + ' км үлдсэн';
        }
        if (delNote) {
            delNote.querySelector('.sb-sub').textContent = 'Ойролцоогоор ' + getEtaStr(eta);
        }

        if (dist <= 0 || prog >= 100) {
            done = true;
            etaEl.textContent = '✓ Ирлээ!';
            document.getElementById('etaBadge').style.background = '#10B981';
            if (progBar) progBar.style.background = '#10B981';
            clearInterval(timer);
        }
    }

    var timer = setInterval(tick, 3000);

    /* ── Actions ── */
    window.copyTracking = function() {
        var num = '<?= htmlspecialchars($tracking['tracking_number']) ?>';
        if (navigator.clipboard) {
            navigator.clipboard.writeText(num)
                .then(function() { showToast('Tracking дугаар хуулагдлаа! 📋'); })
                .catch(function() { fallbackCopy(num); });
        } else {
            fallbackCopy(num);
        }
    };

    function fallbackCopy(text) {
        var el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        showToast('Хуулагдлаа! 📋');
    }

    window.shareTracking = function() {
        var url = window.location.origin + '/tracking.php?track=<?= htmlspecialchars($tracking['tracking_number']) ?>';
        if (navigator.share) {
            navigator.share({ title: 'ShopMN Tracking', url: url });
        } else {
            copyTracking();
            showToast('Холбоос хуулагдлаа! 🔗');
        }
    };

    window.confirmDelivery = function() {
        var btn = document.getElementById('confirmBtn');
        btn.textContent = '✓ Баярлалаа!';
        btn.classList.add('confirmed');
        done = true;
        clearInterval(timer);
        etaEl.textContent = '✓ Хүргэгдлээ';
        document.getElementById('etaBadge').style.background = '#10B981';
        if (progBar) { progBar.style.width = '100%'; progBar.style.background = '#10B981'; }
        if (progPct) progPct.textContent = '100%';
        showToast('Захиалга хүлээн авлаа! Баярлалаа 🎉', 'success');

        /* Update DB via AJAX */
        fetch('ajax/confirm_received.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'order_id=<?= intval($tracking['order_id'] ?? 0) ?>&tracking=<?= htmlspecialchars($tracking['tracking_number']) ?>'
        }).catch(function() {});
    };

    window.sendMessage = function() {
        if (typeof Toast !== 'undefined') {
            Toast.show('Мессеж функц тун удахгүй! 💬', 'info');
        } else {
            showToast('Мессеж функц тун удахгүй! 💬');
        }
    };

    function showToast(msg, type) {
        if (typeof Toast !== 'undefined') {
            Toast.show(msg, type || 'success');
            return;
        }
        var t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = [
            'position:fixed', 'bottom:28px', 'right:28px',
            'background:#1A1A2E', 'color:white',
            'padding:12px 20px', 'border-radius:10px',
            'font-size:.88rem', 'font-weight:600',
            'box-shadow:0 8px 30px rgba(0,0,0,.25)',
            'z-index:99999', 'opacity:0',
            'transition:opacity .3s'
        ].join(';');
        document.body.appendChild(t);
        requestAnimationFrame(function() { t.style.opacity = '1'; });
        setTimeout(function() {
            t.style.opacity = '0';
            setTimeout(function() { t.remove(); }, 400);
        }, 3000);
    }
})();
</script>

<?php include 'includes/footer.php'; ?>