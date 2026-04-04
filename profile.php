<?php
require_once 'db.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (!isLoggedIn()) { header('Location: login.php'); exit; }

$user = getCurrentUser();

// Active tab from URL hash or GET param
$activeTab = $_GET['tab'] ?? 'orders';

// ── UPDATE PROFILE ──
$success = '';
$error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_profile'])) {
        $name    = sanitize($_POST['name']);
        $phone   = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        if (strlen($name) < 2) {
            $error = 'Нэр хэт богино байна.';
        } else {
            mysqli_query($conn, "UPDATE users SET name='$name', phone='$phone', address='$address' WHERE id={$user['id']}");
            $success = 'Мэдээлэл амжилттай шинэчлэгдлээ!';
            $user = getCurrentUser();
            $activeTab = 'settings';
        }
    }
    if (isset($_POST['change_password'])) {
        $oldPass = $_POST['old_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confPass= $_POST['confirm_password'] ?? '';
        if (!password_verify($oldPass, $user['password'])) {
            $error = 'Хуучин нууц үг буруу байна.';
        } elseif (strlen($newPass) < 8) {
            $error = 'Шинэ нууц үг 8+ тэмдэгт байх ёстой.';
        } elseif ($newPass !== $confPass) {
            $error = 'Нууц үг давтал таарахгүй байна.';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password='$hash' WHERE id={$user['id']}");
            $success = 'Нууц үг амжилттай солигдлоо!';
        }
        $activeTab = 'settings';
    }
    if (isset($_POST['save_address'])) {
        $address = sanitize($_POST['full_address']);
        mysqli_query($conn, "UPDATE users SET address='$address' WHERE id={$user['id']}");
        $success = 'Хаяг хадгалагдлаа!';
        $user = getCurrentUser();
        $activeTab = 'address';
    }
}

// ── DATA ──
$orders = mysqli_query($conn,
    "SELECT o.*, COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON o.id = oi.order_id
     WHERE o.user_id = {$user['id']}
     GROUP BY o.id ORDER BY o.created_at DESC LIMIT 20"
);
$ordersArr = [];
while ($row = mysqli_fetch_assoc($orders)) $ordersArr[] = $row;

// Payment / transaction history
$transactions = mysqli_query($conn,
    "SELECT pt.*, o.total_amount, o.status AS order_status
     FROM payment_transactions pt
     LEFT JOIN orders o ON pt.order_id = o.id
     WHERE o.user_id = {$user['id']}
     ORDER BY pt.created_at DESC LIMIT 20"
);
$txArr = [];
while ($row = mysqli_fetch_assoc($transactions)) $txArr[] = $row;

$totalSpent  = array_sum(array_column($ordersArr, 'total_amount'));
$deliveredCnt= count(array_filter($ordersArr, fn($o) => $o['status'] === 'delivered'));

$statusColors = ['pending'=>'#F59E0B','processing'=>'#3B82F6','shipped'=>'#8B5CF6','delivered'=>'#10B981','cancelled'=>'#EF4444'];
$statusLabels = ['pending'=>'Хүлээгдэж байна','processing'=>'Боловсруулж байна','shipped'=>'Хүргэж байна','delivered'=>'Хүргэгдсэн','cancelled'=>'Цуцлагдсан'];
$payLabels    = ['pending'=>'Хүлээгдэж байна','completed'=>'Амжилттай','failed'=>'Амжилтгүй','refunded'=>'Буцаасан'];
$payColors    = ['pending'=>'#F59E0B','completed'=>'#10B981','failed'=>'#EF4444','refunded'=>'#8B5CF6'];

$pageTitle = 'Профайл — ShopMN';
include 'includes/header.php';
?>

<style>
/* ══════════════════════════════════════
   PROFILE PAGE
══════════════════════════════════════ */
.prof-wrap {
    max-width: 1100px;
    margin: 0 auto;
    padding: 28px 20px 60px;
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 22px;
    align-items: start;
}

/* ── Sidebar ── */
.prof-sidebar {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 16px rgba(0,0,0,.07);
    position: sticky;
    top: 110px;
}
.prof-user-card {
    background: linear-gradient(135deg, #1A1A2E 0%, #16213E 100%);
    padding: 24px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 10px;
}
.prof-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FF6B35, #ffaa70);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Outfit', sans-serif;
    font-size: 1.6rem;
    font-weight: 800;
    color: white;
    border: 3px solid rgba(255,255,255,.2);
}
.prof-username {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: 1rem;
    color: white;
}
.prof-email {
    font-size: .75rem;
    color: rgba(255,255,255,.55);
}
.prof-badge {
    background: rgba(255,107,53,.25);
    color: #FF6B35;
    border: 1px solid rgba(255,107,53,.4);
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
}

/* Sidebar nav */
.prof-nav { padding: 8px 0; }
.prof-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    cursor: pointer;
    text-decoration: none;
    color: #475569;
    font-size: .88rem;
    font-weight: 500;
    transition: all .15s;
    border-left: 3px solid transparent;
}
.prof-nav-item:hover { background: #FFF5F0; color: #FF6B35; }
.prof-nav-item.active { background: #FFF5F0; color: #FF6B35; border-left-color: #FF6B35; font-weight: 700; }
.prof-nav-item.danger { color: #DC2626; }
.prof-nav-item.danger:hover { background: #FEF2F2; }
.nav-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .95rem;
    flex-shrink: 0;
    transition: transform .2s;
}
.prof-nav-item:hover .nav-icon { transform: scale(1.1); }
.nav-icon.orders   { background: #FFF3E0; }
.nav-icon.history  { background: #EDE7F6; }
.nav-icon.settings { background: #E3F2FD; }
.nav-icon.address  { background: #E8F5E9; }
.nav-icon.logout   { background: #FEE2E2; }
.prof-divider { height: 1px; background: #F1F5F9; margin: 6px 0; }

/* Stat mini-cards in sidebar */
.prof-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    padding: 14px;
    border-top: 1px solid #F1F5F9;
}
.ps-card {
    background: #F8FAFC;
    border-radius: 10px;
    padding: 10px 8px;
    text-align: center;
}
.ps-num {
    font-family: 'Outfit', sans-serif;
    font-size: 1.2rem;
    font-weight: 800;
    color: #FF6B35;
}
.ps-label { font-size: .65rem; color: #94A3B8; margin-top: 2px; }

/* ── Content area ── */
.prof-content {}

/* Tab panels */
.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* Section card */
.prof-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,.07);
    margin-bottom: 18px;
    overflow: hidden;
}
.prof-card-header {
    padding: 18px 22px 14px;
    border-bottom: 1px solid #F1F5F9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.prof-card-title {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: 1rem;
    color: #1E293B;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Order list */
.order-row {
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    gap: 14px;
    align-items: center;
    padding: 14px 22px;
    border-bottom: 1px solid #F8FAFC;
    transition: background .15s;
    text-decoration: none;
    color: inherit;
}
.order-row:last-child { border-bottom: none; }
.order-row:hover { background: #FAFBFF; }
.order-num {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: .92rem;
    color: #FF6B35;
}
.order-meta { font-size: .75rem; color: #94A3B8; margin-top: 2px; }
.order-amount {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: .95rem;
    color: #1E293B;
    white-space: nowrap;
}

/* Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 700;
    white-space: nowrap;
}

/* TX list */
.tx-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 22px;
    border-bottom: 1px solid #F8FAFC;
}
.tx-row:last-child { border-bottom: none; }
.tx-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.tx-info { flex: 1; }
.tx-title { font-weight: 600; font-size: .88rem; color: #1E293B; }
.tx-date  { font-size: .73rem; color: #94A3B8; margin-top: 2px; }
.tx-amount { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: .95rem; }

/* Form styles */
.pf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; padding: 20px 22px; }
.pf-group { display: flex; flex-direction: column; gap: 6px; }
.pf-group.full { grid-column: 1/-1; }
.pf-label { font-size: .82rem; font-weight: 700; color: #374151; }
.pf-input {
    padding: 10px 13px;
    border: 1.5px solid #E2E8F0;
    border-radius: 9px;
    font-size: .88rem;
    font-family: 'DM Sans', sans-serif;
    outline: none;
    transition: border-color .2s;
    color: #1E293B;
}
.pf-input:focus { border-color: #FF6B35; }
.pf-hint { font-size: .72rem; color: #94A3B8; }
.pf-footer {
    padding: 0 22px 20px;
    display: flex;
    gap: 10px;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: #94A3B8;
}
.empty-state .es-icon { font-size: 3rem; margin-bottom: 12px; }
.empty-state .es-title { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 1rem; color: #475569; margin-bottom: 6px; }
.empty-state .es-sub { font-size: .85rem; }

/* Alert */
.prof-alert {
    padding: 12px 18px;
    border-radius: 10px;
    margin-bottom: 16px;
    font-size: .88rem;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}
.prof-alert.success { background: #D1FAE5; color: #065F46; }
.prof-alert.error   { background: #FEE2E2; color: #991B1B; }

/* Address card */
.addr-card {
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px;
    margin: 0 22px 14px;
    position: relative;
    transition: border-color .2s;
}
.addr-card:hover { border-color: #FF6B35; }
.addr-card.default { border-color: #FF6B35; background: #FFF5F0; }
.addr-default-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #FF6B35;
    color: white;
    font-size: .65rem;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 10px;
}

@media (max-width: 860px) {
    .prof-wrap { grid-template-columns: 1fr; }
    .prof-sidebar { position: static; }
    .pf-grid { grid-template-columns: 1fr; }
    .order-row { grid-template-columns: 1fr auto; }
}
</style>

<div class="prof-wrap">

    <!-- ═══════════ SIDEBAR ═══════════ -->
    <aside class="prof-sidebar">
        <!-- User card -->
        <div class="prof-user-card">
            <div class="prof-avatar"><?= mb_strtoupper(mb_substr($user['name'], 0, 1)) ?></div>
            <div class="prof-username"><?= htmlspecialchars($user['name']) ?></div>
            <div class="prof-email"><?= htmlspecialchars($user['email']) ?></div>
            <span class="prof-badge">✓ Баталгаажсан</span>
        </div>

        <!-- Mini stats -->
        <div class="prof-stats">
            <div class="ps-card">
                <div class="ps-num"><?= count($ordersArr) ?></div>
                <div class="ps-label">Захиалга</div>
            </div>
            <div class="ps-card">
                <div class="ps-num"><?= $deliveredCnt ?></div>
                <div class="ps-label">Хүргэгдсэн</div>
            </div>
            <div class="ps-card" style="grid-column:1/-1;">
                <div class="ps-num" style="font-size:1rem;"><?= number_format($totalSpent) ?>₮</div>
                <div class="ps-label">Нийт зарцуулалт</div>
            </div>
        </div>

        <!-- Nav -->
        <nav class="prof-nav">
            <a href="?tab=orders"   class="prof-nav-item <?= $activeTab==='orders'  ?'active':'' ?>">
                <div class="nav-icon orders">📦</div> Миний захиалгууд
            </a>
            <a href="?tab=history"  class="prof-nav-item <?= $activeTab==='history' ?'active':'' ?>">
                <div class="nav-icon history">📋</div> Гүйлгээний түүх
            </a>
            <div class="prof-divider"></div>
            <a href="?tab=settings" class="prof-nav-item <?= $activeTab==='settings'?'active':'' ?>">
                <div class="nav-icon settings">⚙️</div> Хувийн тохиргоо
            </a>
            <a href="?tab=address"  class="prof-nav-item <?= $activeTab==='address' ?'active':'' ?>">
                <div class="nav-icon address">📍</div> Хүргэлтийн хаяг
            </a>
            <div class="prof-divider"></div>
            <a href="?logout=1" class="prof-nav-item danger"
               onclick="return confirm('Системээс гарах уу?')">
                <div class="nav-icon logout">🔒</div> Системээс гарах
            </a>
        </nav>
    </aside>

    <!-- ═══════════ CONTENT ═══════════ -->
    <div class="prof-content">

        <!-- Alerts -->
        <?php if ($success): ?>
        <div class="prof-alert success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="prof-alert error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- ── TAB 1: ORDERS ── -->
        <div class="tab-panel <?= $activeTab==='orders'?'active':'' ?>">
            <div class="prof-card">
                <div class="prof-card-header">
                    <div class="prof-card-title">📦 Миний захиалгууд</div>
                    <a href="products.php" class="btn-primary" style="font-size:.78rem;padding:6px 14px;">
                        + Шинэ захиалга
                    </a>
                </div>

                <?php if (empty($ordersArr)): ?>
                <div class="empty-state">
                    <div class="es-icon">📭</div>
                    <div class="es-title">Захиалга байхгүй байна</div>
                    <div class="es-sub">Анхны захиалгаа өгөөрэй!</div>
                    <a href="products.php" class="btn-primary" style="margin-top:16px;display:inline-flex;font-size:.88rem;">
                        Дэлгүүр хийх
                    </a>
                </div>
                <?php else: ?>
                <?php foreach ($ordersArr as $o): ?>
                <a href="tracking.php?order=<?= $o['id'] ?>" class="order-row">
                    <div>
                        <div class="order-num">#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></div>
                        <div class="order-meta"><?= date('Y.m.d H:i', strtotime($o['created_at'])) ?> · <?= $o['item_count'] ?> бараа</div>
                    </div>
                    <div style="font-size:.82rem;color:#64748B;">
                        <?= htmlspecialchars($o['shipping_name'] ?? '') ?>
                    </div>
                    <div class="order-amount"><?= number_format($o['total_amount']) ?>₮</div>
                    <div>
                        <span class="status-badge"
                              style="background:<?= $statusColors[$o['status']] ?? '#94A3B8' ?>20;color:<?= $statusColors[$o['status']] ?? '#94A3B8' ?>;">
                            <?= $statusLabels[$o['status']] ?? $o['status'] ?>
                        </span>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── TAB 2: HISTORY ── -->
        <div class="tab-panel <?= $activeTab==='history'?'active':'' ?>">
            <div class="prof-card">
                <div class="prof-card-header">
                    <div class="prof-card-title">📋 Гүйлгээний түүх</div>
                </div>

                <?php if (empty($txArr)): ?>
                <div class="empty-state">
                    <div class="es-icon">💳</div>
                    <div class="es-title">Гүйлгээ байхгүй байна</div>
                    <div class="es-sub">Захиалга хийсний дараа гүйлгээний түүх энд харагдана.</div>
                </div>
                <?php else: ?>
                <?php foreach ($txArr as $tx): ?>
                <div class="tx-row">
                    <div class="tx-icon" style="background:<?= $tx['status']==='completed'?'#D1FAE5':($tx['status']==='failed'?'#FEE2E2':'#FEF3C7') ?>;">
                        <?= $tx['status']==='completed'?'✅':($tx['status']==='failed'?'❌':'⏳') ?>
                    </div>
                    <div class="tx-info">
                        <div class="tx-title">
                            Захиалга #<?= str_pad($tx['order_id'], 5, '0', STR_PAD_LEFT) ?>
                            · <?= strtoupper(htmlspecialchars($tx['method'] ?? '')) ?>
                        </div>
                        <div class="tx-date"><?= date('Y.m.d H:i', strtotime($tx['created_at'])) ?></div>
                    </div>
                    <div>
                        <div class="tx-amount" style="color:<?= $payColors[$tx['status']] ?? '#64748B' ?>;">
                            <?= number_format($tx['total_amount'] ?? $tx['amount'] ?? 0) ?>₮
                        </div>
                        <span class="status-badge"
                              style="background:<?= $payColors[$tx['status']] ?? '#94A3B8' ?>20;color:<?= $payColors[$tx['status']] ?? '#94A3B8' ?>;margin-top:3px;display:inline-flex;">
                            <?= $payLabels[$tx['status']] ?? $tx['status'] ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── TAB 3: SETTINGS ── -->
        <div class="tab-panel <?= $activeTab==='settings'?'active':'' ?>">

            <!-- Profile info -->
            <div class="prof-card">
                <div class="prof-card-header">
                    <div class="prof-card-title">👤 Хувийн мэдээлэл</div>
                </div>
                <form method="POST">
                    <div class="pf-grid">
                        <div class="pf-group">
                            <label class="pf-label">Нэр *</label>
                            <input type="text" name="name" class="pf-input"
                                   value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="pf-group">
                            <label class="pf-label">Имэйл</label>
                            <input type="email" class="pf-input"
                                   value="<?= htmlspecialchars($user['email']) ?>" disabled
                                   style="background:#F8FAFC;cursor:not-allowed;">
                            <span class="pf-hint">Имэйл өөрчлөх боломжгүй</span>
                        </div>
                        <div class="pf-group">
                            <label class="pf-label">Утасны дугаар</label>
                            <input type="tel" name="phone" class="pf-input"
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                   placeholder="9911 2233">
                        </div>
                        <div class="pf-group">
                            <label class="pf-label">Бүртгэлийн огноо</label>
                            <input type="text" class="pf-input"
                                   value="<?= date('Y.m.d', strtotime($user['created_at'])) ?>" disabled
                                   style="background:#F8FAFC;cursor:not-allowed;">
                        </div>
                        <div class="pf-group full">
                            <label class="pf-label">Хаяг</label>
                            <textarea name="address" class="pf-input" rows="2"
                                      style="resize:vertical;"
                                      placeholder="Дүүрэг, хороо, байр, тоот..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="pf-footer">
                        <button type="submit" name="save_profile" class="btn-primary">
                            💾 Хадгалах
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change password -->
            <div class="prof-card">
                <div class="prof-card-header">
                    <div class="prof-card-title">🔑 Нууц үг солих</div>
                </div>
                <form method="POST">
                    <div class="pf-grid">
                        <div class="pf-group full">
                            <label class="pf-label">Хуучин нууц үг</label>
                            <input type="password" name="old_password" class="pf-input"
                                   placeholder="••••••••" required>
                        </div>
                        <div class="pf-group">
                            <label class="pf-label">Шинэ нууц үг (8+)</label>
                            <input type="password" name="new_password" class="pf-input"
                                   placeholder="••••••••" required minlength="8">
                        </div>
                        <div class="pf-group">
                            <label class="pf-label">Давтах</label>
                            <input type="password" name="confirm_password" class="pf-input"
                                   placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="pf-footer">
                        <button type="submit" name="change_password"
                                class="btn-primary" style="background:#1A1A2E;">
                            🔒 Нууц үг солих
                        </button>
                    </div>
                </form>
            </div>

            <!-- Danger zone -->
            <div class="prof-card">
                <div class="prof-card-header">
                    <div class="prof-card-title" style="color:#DC2626;">⚠️ Аюултай бүс</div>
                </div>
                <div style="padding:18px 22px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                    <div>
                        <div style="font-weight:700;font-size:.9rem;color:#1E293B;">Системээс гарах</div>
                        <div style="font-size:.78rem;color:#94A3B8;margin-top:2px;">Одоогийн session-г дуусгах</div>
                    </div>
                    <a href="?logout=1" onclick="return confirm('Гарах уу?')"
                       style="background:#FEE2E2;color:#DC2626;border:none;border-radius:9px;
                              padding:9px 18px;font-weight:700;font-size:.85rem;
                              cursor:pointer;text-decoration:none;font-family:'DM Sans',sans-serif;">
                        🔒 Гарах
                    </a>
                </div>
            </div>
        </div>

        <!-- ── TAB 4: ADDRESS ── -->
        <div class="tab-panel <?= $activeTab==='address'?'active':'' ?>">
            <div class="prof-card">
                <div class="prof-card-header">
                    <div class="prof-card-title">📍 Хүргэлтийн хаяг</div>
                </div>

                <!-- Current saved address -->
                <?php if (!empty($user['address'])): ?>
                <div class="addr-card default" style="margin-top:16px;">
                    <span class="addr-default-badge">✓ Үндсэн хаяг</span>
                    <div style="font-weight:700;font-size:.88rem;color:#1E293B;margin-bottom:4px;">
                        <?= htmlspecialchars($user['name']) ?>
                    </div>
                    <div style="font-size:.85rem;color:#475569;line-height:1.6;">
                        📍 <?= htmlspecialchars($user['address']) ?>
                    </div>
                    <?php if ($user['phone']): ?>
                    <div style="font-size:.8rem;color:#94A3B8;margin-top:4px;">
                        📱 <?= htmlspecialchars($user['phone']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="empty-state" style="padding:30px;">
                    <div class="es-icon">📍</div>
                    <div class="es-title">Хаяг бүртгэгдээгүй байна</div>
                    <div class="es-sub">Доор хаягаа нэмнэ үү</div>
                </div>
                <?php endif; ?>

                <!-- Add / update address form -->
                <form method="POST" style="padding:0 22px 20px;">
                    <div style="font-weight:700;font-size:.88rem;color:#1E293B;margin-bottom:12px;margin-top:8px;">
                        <?= empty($user['address']) ? '+ Хаяг нэмэх' : '✏️ Хаяг засах' ?>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                        <div class="pf-group">
                            <label class="pf-label">Дүүрэг *</label>
                            <select class="pf-input" id="addr-district">
                                <option value="">— Дүүрэг сонгох —</option>
                                <?php foreach (['Баянгол','Баянзүрх','Чингэлтэй','Хан-Уул','Сүхбаатар','Сонгинохайрхан','Налайх','Багануур','Багахангай'] as $d): ?>
                                <option><?= $d ?> дүүрэг</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="pf-group">
                            <label class="pf-label">Хороо *</label>
                            <select class="pf-input" id="addr-khoroo">
                                <option value="">— Хороо —</option>
                                <?php for ($k=1;$k<=30;$k++) echo "<option>$k-р хороо</option>"; ?>
                            </select>
                        </div>
                    </div>
                    <div class="pf-group" style="margin-bottom:12px;">
                        <label class="pf-label">Байр, орц, тоот *</label>
                        <input type="text" id="addr-detail" class="pf-input"
                               placeholder="3-р хороолол, 7 байр, 15 тоот">
                    </div>
                    <input type="hidden" name="full_address" id="full_address_input">
                    <button type="submit" name="save_address"
                            onclick="buildAddress()"
                            class="btn-primary">
                        💾 Хаяг хадгалах
                    </button>
                </form>
            </div>
        </div>

    </div><!-- /prof-content -->
</div><!-- /prof-wrap -->

<script>
// Build full address string before submit
function buildAddress() {
    var d = document.getElementById('addr-district').value;
    var k = document.getElementById('addr-khoroo').value;
    var det = document.getElementById('addr-detail').value;
    document.getElementById('full_address_input').value =
        [d, k, det].filter(Boolean).join(', ');
}

// Auto-hide alerts
setTimeout(function() {
    document.querySelectorAll('.prof-alert').forEach(function(el) {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(function() { el.remove(); }, 500);
    });
}, 3500);
</script>

<?php include 'includes/footer.php'; ?>