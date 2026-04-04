<?php
require_once 'db.php';
require_once 'config/config.php';

$session_id = session_id();
$user_id    = $_SESSION['user_id'] ?? null;
$user       = getCurrentUser();

// Сагс татах
$condition = $user_id ? "c.user_id = $user_id" : "c.session_id = '$session_id'";
$cartItems = mysqli_query($conn,
    "SELECT c.*, p.name, p.price, p.image_url, p.stock
     FROM cart c LEFT JOIN products p ON c.product_id = p.id
     WHERE $condition");

$cartRows = []; $subtotal = 0;
while ($row = mysqli_fetch_assoc($cartItems)) {
    $cartRows[] = $row;
    $subtotal  += $row['price'] * $row['quantity'];
}
if (empty($cartRows)) { header('Location: cart.php'); exit; }

$shipping = $subtotal >= 50000 ? 0 : 5000;
$total    = $subtotal + $shipping;
$tomorrow = date('Y оны n-р сарын j', strtotime('+1 day'));
$dayAfter = date('Y оны n-р сарын j', strtotime('+2 days'));
$nextWeek = date('Y оны n-р сарын j', strtotime('+5 days'));

// ══════════════════════════════════════════
// POST HANDLER
// ══════════════════════════════════════════
$success  = false;
$orderId  = null;
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sName   = sanitize($_POST['shipping_name']    ?? '');
    $sPhone  = sanitize($_POST['shipping_phone']   ?? '');
    $sDistr  = sanitize($_POST['district']         ?? '');
    $sKhoroo = sanitize($_POST['khoroo']           ?? '');
    $sAddr   = sanitize($_POST['shipping_address'] ?? '');
    $payment = sanitize($_POST['payment_method']   ?? 'cash');

    if (!$sName || !$sPhone || !$sDistr || !$sKhoroo || !$sAddr) {
        $errorMsg = 'Бүх талбарыг бөглөнө үү.';
    } else {
        $fullAddr = "$sDistr дүүрэг, $sKhoroo-р хороо, $sAddr";
        $uid      = $user_id ? $user_id : 'NULL';

        // 1. ORDER
        $ins = mysqli_query($conn,
            "INSERT INTO orders
               (user_id, total_amount, shipping_name, shipping_address,
                shipping_phone, payment_method, payment_status, status)
             VALUES
               ($uid, $total, '$sName', '$fullAddr',
                '$sPhone', '$payment', 'unpaid', 'pending')");

        if (!$ins) {
            $errorMsg = 'DB алдаа: ' . mysqli_error($conn);
        } else {
            $orderId = mysqli_insert_id($conn);

            // 2. ORDER ITEMS
            foreach ($cartRows as $item) {
                $pid   = (int)$item['product_id'];
                $qty   = (int)$item['quantity'];
                $price = (float)$item['price'];
                mysqli_query($conn,
                    "INSERT INTO order_items (order_id, product_id, quantity, price)
                     VALUES ($orderId, $pid, $qty, $price)");
                mysqli_query($conn,
                    "UPDATE products SET stock = stock - $qty
                     WHERE id = $pid AND stock >= $qty");
            }

            // 3. DELIVERY TRACKING
            $trackNum = 'SMN' . str_pad($orderId, 7, '0', STR_PAD_LEFT);
            $estDate  = date('Y-m-d', strtotime('+2 days'));
            mysqli_query($conn,
                "INSERT INTO delivery_tracking
                   (order_id, tracking_number, estimated_delivery, status)
                 VALUES ($orderId, '$trackNum', '$estDate', 'order_placed')");
            $trackId = mysqli_insert_id($conn);
            mysqli_query($conn,
                "INSERT INTO delivery_events
                   (tracking_id, status, description, location, icon)
                 VALUES ($trackId, 'Захиалга баталгаажлаа',
                   'Таны захиалга амжилттай хүлээн авагдлаа',
                   'ShopMN Систем', 'fas fa-check-circle')");

            // 4. PAYMENT TRANSACTION
            mysqli_query($conn,
                "INSERT INTO payment_transactions (order_id, method, amount, status)
                 VALUES ($orderId, '$payment', $total, 'pending')");

            // 5. CART CLEAR
            if ($user_id)
                mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");
            else
                mysqli_query($conn, "DELETE FROM cart WHERE session_id = '$session_id'");

            $success = true;
        }
    }
}

$pageTitle = 'Захиалга — ShopMN';
include 'includes/header.php';
?>

<style>
/* ─── CHECKOUT ─── */
.co-wrap { max-width:1160px; margin:0 auto; padding:24px 16px; }
.co-grid { display:grid; grid-template-columns:1fr 360px; gap:24px; align-items:start; }

/* Steps */
.steps   { display:flex; margin-bottom:22px; }
.step    { display:flex; align-items:center; gap:6px; flex:1; }
.snum    { width:26px; height:26px; border-radius:50%; display:flex; align-items:center;
           justify-content:center; font-weight:800; font-size:.78rem; flex-shrink:0; }
.step.done   .snum { background:var(--success); color:#fff; }
.step.active .snum { background:var(--primary);  color:#fff; }
.step.wait   .snum { background:var(--border);   color:var(--text-light); }
.slabel { font-size:.78rem; font-weight:600; color:var(--text-light); }
.step.active .slabel,.step.done .slabel { color:var(--text); }
.sline      { flex:1; height:2px; background:var(--border); margin:0 6px; }
.sline.done { background:var(--success); }

/* Card */
.co-card  { background:#fff; border-radius:14px; padding:20px;
            box-shadow:0 2px 12px rgba(0,0,0,.06); margin-bottom:14px; }
.co-title { font-family:'Outfit',sans-serif; font-size:1rem; font-weight:800;
            margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.co-num   { width:26px; height:26px; background:var(--primary); color:#fff;
            border-radius:50%; display:flex; align-items:center;
            justify-content:center; font-size:.78rem; font-weight:800; flex-shrink:0; }

/* Address grid */
.ag      { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.ag .full { grid-column:1/-1; }

/* Delivery */
.dopt     { display:flex; align-items:center; gap:12px; padding:12px 14px;
            border:2px solid var(--border); border-radius:10px; cursor:pointer;
            margin-bottom:8px; transition:all .15s; }
.dopt:hover,.dopt.sel { border-color:var(--primary); background:#fff8f5; }
.dopt input[type=radio] { accent-color:var(--primary); flex-shrink:0; }

/* Payment cards */
.pgrid   { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.pcard   { border:2px solid var(--border); border-radius:12px; padding:12px 8px;
           cursor:pointer; text-align:center; position:relative;
           transition:all .2s; background:#fff; }
.pcard:hover   { border-color:var(--primary); transform:translateY(-2px); }
.pcard.sel     { border-color:var(--primary); background:#fff8f5; }
.pcard.sel::after { content:'✓'; position:absolute; top:6px; right:8px;
                    color:var(--primary); font-weight:800; }
.plogo  { font-size:1.8rem; display:block; margin-bottom:4px; }
.pname  { font-weight:700; font-size:.82rem; }
.pdesc  { font-size:.68rem; color:var(--text-light); margin-top:1px; }
.pcard.wide { grid-column:1/-1; text-align:left; display:flex;
              align-items:center; gap:12px; padding:14px; }

/* Summary */
.sum-card  { background:#fff; border-radius:14px; padding:20px;
             box-shadow:0 2px 12px rgba(0,0,0,.06); position:sticky; top:110px; }
.sum-items { max-height:240px; overflow-y:auto; margin-bottom:12px; }
.sum-item  { display:flex; gap:8px; align-items:center;
             padding:8px 0; border-bottom:1px solid var(--border); }
.sum-item img { width:46px; height:46px; object-fit:cover; border-radius:8px; flex-shrink:0; }
.sum-row   { display:flex; justify-content:space-between; padding:4px 0; font-size:.88rem; }
.sum-total { display:flex; justify-content:space-between; padding:10px 0 0;
             border-top:2px solid var(--border);
             font-family:'Outfit',sans-serif; font-weight:800; font-size:1rem; }

/* Hint boxes */
.hint-qr   { background:#EFF6FF; border-radius:10px; padding:12px; font-size:.82rem;
             display:flex; gap:8px; align-items:flex-start; margin-top:12px; }
.hint-cash { background:#FEF3C7; border-radius:10px; padding:12px;
             font-size:.82rem; margin-top:12px; display:none; }

/* QR Modal */
.qr-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.65);
              z-index:99999; align-items:center; justify-content:center;
              backdrop-filter:blur(4px); }
.qr-overlay.show { display:flex; }
.qr-box  { background:#fff; border-radius:20px; padding:28px 24px;
           max-width:350px; width:92%; text-align:center;
           box-shadow:0 30px 80px rgba(0,0,0,.35); animation:bounceIn .35s ease; }
.qr-svg-wrap { width:180px; height:180px; margin:14px auto;
               border:2px solid var(--border); border-radius:12px;
               display:flex; align-items:center; justify-content:center; background:#fafafa; }
.qr-svg-wrap svg { width:160px; height:160px; }
.qr-timer { font-family:'Outfit',sans-serif; font-size:1.7rem;
            font-weight:800; color:var(--primary); margin:6px 0; }
.qr-banks { display:flex; flex-wrap:wrap; gap:4px; justify-content:center; margin:8px 0; }
.qr-chip  { background:var(--bg); border:1px solid var(--border);
            border-radius:6px; padding:2px 7px; font-size:.68rem; font-weight:700; }

/* Error */
.err-box { background:#FEE2E2; color:#991B1B; border-radius:10px;
           padding:12px 16px; margin-bottom:14px; font-size:.88rem; }

/* Success */
.suc-card { max-width:520px; margin:50px auto; background:#fff; border-radius:20px;
            padding:40px; box-shadow:0 16px 50px rgba(0,0,0,.1); text-align:center; }
.suc-icon { width:68px; height:68px; background:linear-gradient(135deg,var(--success),#059669);
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            margin:0 auto 14px; font-size:1.7rem; color:#fff; animation:bounceIn .5s ease; }
.tl-item  { display:flex; gap:12px; padding:9px 0; position:relative; }
.tl-item:not(:last-child)::before { content:''; position:absolute; left:13px; top:32px;
    bottom:-9px; width:2px; background:var(--border); }
.tl-dot  { width:26px; height:26px; border-radius:50%; display:flex; align-items:center;
           justify-content:center; font-size:.8rem; flex-shrink:0; }
.tl-dot.done   { background:var(--success); color:#fff; }
.tl-dot.active { background:var(--primary); color:#fff; animation:pulse 1.5s infinite; }
.tl-dot.pend   { background:var(--border);  color:var(--text-light); }

@media(max-width:860px){
    .co-grid { grid-template-columns:1fr; }
    .sum-card { position:static; }
    .ag { grid-template-columns:1fr; }
}
@media(max-width:480px){ .pgrid { grid-template-columns:1fr; } }
</style>

<div class="co-wrap">
<?php if ($success): ?>
<!-- ═══════════ SUCCESS ═══════════ -->
<div class="suc-card">
    <div class="suc-icon">✅</div>
    <h2 style="font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;margin-bottom:6px;">Захиалга амжилттай!</h2>
    <p style="color:var(--text-light);font-size:.86rem;margin-bottom:4px;">Захиалгын дугаар:</p>
    <div style="font-family:'Outfit',sans-serif;font-size:1.3rem;font-weight:800;color:var(--primary);margin-bottom:20px;">
        #<?= str_pad($orderId, 7, '0', STR_PAD_LEFT) ?>
    </div>
    <div style="background:var(--bg);border-radius:12px;padding:16px;text-align:left;margin-bottom:20px;">
        <div style="font-family:'Outfit',sans-serif;font-weight:700;margin-bottom:10px;font-size:.92rem;">🚚 Хүргэлтийн хуваарь</div>
        <div class="tl-item"><div class="tl-dot done">✓</div><div><b style="font-size:.86rem;">Захиалга баталгаажлаа</b><div style="font-size:.75rem;color:var(--text-light);"><?= date('Y.m.d H:i') ?></div></div></div>
        <div class="tl-item"><div class="tl-dot active">⚙</div><div><b style="font-size:.86rem;">Бэлтгэж байна</b><div style="font-size:.75rem;color:var(--success);">Өнөөдөр эхэлнэ</div></div></div>
        <div class="tl-item"><div class="tl-dot pend">📦</div><div><b style="font-size:.86rem;">Хүргэлтэнд гарна</b><div style="font-size:.75rem;color:var(--text-light);"><?= $tomorrow ?></div></div></div>
        <div class="tl-item"><div class="tl-dot pend">🏠</div><div><b style="font-size:.86rem;">Хүргэгдэх</b><div style="font-size:.75rem;color:var(--success);font-weight:700;"><?= $dayAfter ?> гэхэд</div></div></div>
        <div style="background:#fff;border-radius:8px;padding:9px;margin-top:6px;font-size:.78rem;display:flex;justify-content:space-between;">
            <span style="color:var(--text-light);">🔍 Tracking:</span>
            <strong style="color:var(--primary);font-family:'Outfit',sans-serif;">SMN<?= str_pad($orderId, 7, '0', STR_PAD_LEFT) ?></strong>
        </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
        <a href="profile.php"  class="btn-primary"><i class="fas fa-box"></i> Захиалга харах</a>
        <a href="tracking.php" class="btn-outline" style="color:var(--text);border-color:var(--border);">📍 Tracking</a>
        <a href="index.php"    class="btn-outline" style="color:var(--text);border-color:var(--border);">🏠 Нүүр</a>
    </div>
</div>

<?php else: ?>
<!-- ═══════════ FORM ═══════════ -->
<?php if ($errorMsg): ?>
<div class="err-box">⚠️ <?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<div style="margin-bottom:16px;">
    <div class="breadcrumb"><a href="index.php">Нүүр</a> › <a href="cart.php">Сагс</a> › Захиалга</div>
    <h1 style="font-family:'Outfit',sans-serif;font-size:1.4rem;font-weight:800;margin-top:6px;">🔒 Захиалга хийх</h1>
</div>

<div class="steps">
    <div class="step done">  <div class="snum">✓</div><span class="slabel">Сагс</span></div>
    <div class="sline done"></div>
    <div class="step active"><div class="snum">2</div><span class="slabel">Хаяг</span></div>
    <div class="sline"></div>
    <div class="step wait">  <div class="snum">3</div><span class="slabel">Хүргэлт</span></div>
    <div class="sline"></div>
    <div class="step wait">  <div class="snum">4</div><span class="slabel">Төлбөр</span></div>
</div>

<!--
    ЧУХАЛ: place_order болон payment_method хоёулаа
    form-д hidden input-р байна.
    form.submit() дуудагдахад PHP үүнийг зөв хүлээж авна.
-->
<form method="POST" id="co-form">
    <input type="hidden" name="place_order"    value="1">
    <input type="hidden" name="payment_method" id="h-payment" value="qpay">

<div class="co-grid">
<div>

<!-- 1. ХАЯГ -->
<div class="co-card">
    <div class="co-title"><span class="co-num">1</span>Хүргэлтийн хаяг</div>
    <?php if ($user): ?>
    <div style="background:var(--bg);border-radius:8px;padding:9px 12px;margin-bottom:12px;font-size:.84rem;display:flex;justify-content:space-between;align-items:center;">
        <span>👤 <strong><?= htmlspecialchars($user['name']) ?></strong> — <?= htmlspecialchars($user['email']) ?></span>
        <a href="profile.php" style="font-size:.76rem;color:var(--primary);">Өөрчлөх</a>
    </div>
    <?php endif; ?>
    <div class="ag">
        <div class="form-group full">
            <label style="font-weight:700;font-size:.83rem;">Хүлээн авагчийн нэр *</label>
            <input type="text" name="shipping_name" class="form-control" required
                   value="<?= htmlspecialchars($user['name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label style="font-weight:700;font-size:.83rem;">Утасны дугаар *</label>
            <input type="tel" name="shipping_phone" class="form-control" required
                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                   placeholder="9911 2233">
        </div>
        <div class="form-group">
            <label style="font-weight:700;font-size:.83rem;">Дүүрэг *</label>
            <select name="district" class="form-control" required>
                <option value="">— Дүүрэг —</option>
                <?php foreach (['Баянгол','Баянзүрх','Чингэлтэй','Хан-Уул','Сүхбаатар','Сонгинохайрхан','Налайх','Багануур','Багахангай'] as $d): ?>
                <option value="<?= $d ?>"><?= $d ?> дүүрэг</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label style="font-weight:700;font-size:.83rem;">Хороо *</label>
            <select name="khoroo" class="form-control" required>
                <option value="">— Хороо —</option>
                <?php for ($k = 1; $k <= 30; $k++) echo "<option value='$k'>$k-р хороо</option>"; ?>
            </select>
        </div>
        <div class="form-group full">
            <label style="font-weight:700;font-size:.83rem;">Байр, орц, тоот *</label>
            <textarea name="shipping_address" class="form-control" rows="2"
                      required style="resize:none;"
                      placeholder="Жишээ: 3-р хороолол, 7 байр, 15 тоот"></textarea>
        </div>
    </div>
</div>

<!-- 2. ХҮРГЭЛТ -->
<div class="co-card">
    <div class="co-title"><span class="co-num">2</span>Хүргэлтийн хэлбэр</div>
    <label class="dopt sel" onclick="pickDel('express',this)">
        <input type="radio" name="delivery_type" value="express" checked>
        <div style="flex:1">
            <div style="font-weight:700;font-size:.86rem;">⚡ ShopExpress</div>
            <div style="font-size:.76rem;color:var(--success);">📅 <?= $tomorrow ?>-д хүргэгдэнэ</div>
        </div>
        <div style="font-weight:800;color:var(--success);font-family:'Outfit',sans-serif;white-space:nowrap;">
            <?= $subtotal >= 50000 ? 'ҮНЭГҮЙ' : '5,000₮' ?>
        </div>
    </label>
    <label class="dopt" onclick="pickDel('standard',this)">
        <input type="radio" name="delivery_type" value="standard">
        <div style="flex:1">
            <div style="font-weight:700;font-size:.86rem;">📦 Монгол Шуудан</div>
            <div style="font-size:.76rem;color:var(--text-light);">📅 <?= $nextWeek ?>-д хүргэгдэнэ (3-5 өдөр)</div>
        </div>
        <div style="font-weight:800;font-family:'Outfit',sans-serif;">2,500₮</div>
    </label>
    <label class="dopt" onclick="pickDel('pickup',this)">
        <input type="radio" name="delivery_type" value="pickup">
        <div style="flex:1">
            <div style="font-weight:700;font-size:.86rem;">🏪 Дэлгүүрт очиж авах</div>
            <div style="font-size:.76rem;color:var(--text-light);">СБД, Их дэлгүүр 2 давхар — өнөөдөр бэлэн</div>
        </div>
        <div style="font-weight:800;color:var(--success);font-family:'Outfit',sans-serif;">ҮНЭГҮЙ</div>
    </label>
</div>

<!-- 3. ТӨЛБӨР -->
<div class="co-card">
    <div class="co-title"><span class="co-num">3</span>Төлбөрийн хэлбэр</div>
    <div class="pgrid">
        <div class="pcard sel" onclick="pickPay('qpay',this)">
            <span class="plogo">🟦</span>
            <div class="pname">QPay</div>
            <div class="pdesc">QR уншуулж төлөх</div>
        </div>
        <div class="pcard" onclick="pickPay('socialpay',this)">
            <span class="plogo">💙</span>
            <div class="pname">SocialPay</div>
            <div class="pdesc">Хаан банкны апп</div>
        </div>
        <div class="pcard" onclick="pickPay('monpay',this)">
            <span class="plogo">💚</span>
            <div class="pname">Monpay</div>
            <div class="pdesc">Голомт банкны апп</div>
        </div>
        <div class="pcard" onclick="pickPay('khanpay',this)">
            <span class="plogo">🔵</span>
            <div class="pname">Khanpay</div>
            <div class="pdesc">Хаан банкны QR</div>
        </div>
        <div class="pcard" onclick="pickPay('most_money',this)">
            <span class="plogo">🟠</span>
            <div class="pname">Most Money</div>
            <div class="pdesc">Most апп</div>
        </div>
        <div class="pcard" onclick="pickPay('card_visa',this)">
            <span class="plogo">💳</span>
            <div class="pname">Карт</div>
            <div class="pdesc">Visa / Mastercard</div>
        </div>
        <div class="pcard wide" onclick="pickPay('cash',this)">
            <span style="font-size:1.8rem;">💵</span>
            <div>
                <div class="pname">Бэлнээр төлөх</div>
                <div class="pdesc">Хүргэлтийн жолоочид бэлнээр</div>
            </div>
        </div>
    </div>

    <div class="hint-qr" id="hint-qr">
        <span>ℹ️</span>
        <div id="hint-qr-text"><strong>QPay:</strong> Захиалга баталгаажуулсны дараа QR код гарна. Аль ч банкны апп ашиглан уншуулна уу.</div>
    </div>
    <div class="hint-cash" id="hint-cash">
        ⚠️ <strong>Бэлэн мөнгө:</strong> Жолоочид <strong><?= number_format($total,0,'.',',') ?>₮</strong> бэлдэж байна уу.
    </div>
</div>

</div><!-- /left col -->

<!-- SUMMARY -->
<div class="sum-card">
    <div style="font-family:'Outfit',sans-serif;font-weight:700;margin-bottom:12px;">Захиалгын хураангуй</div>
    <div class="sum-items">
        <?php foreach ($cartRows as $item): ?>
        <div class="sum-item">
            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt=""
                 onerror="this.src='https://via.placeholder.com/46'">
            <div style="flex:1;min-width:0;">
                <div style="font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($item['name']) ?>
                </div>
                <div style="font-size:.72rem;color:var(--text-light);">× <?= $item['quantity'] ?></div>
            </div>
            <div style="font-size:.84rem;font-weight:700;flex-shrink:0;">
                <?= formatPrice($item['price'] * $item['quantity']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Купон -->
    <div style="display:flex;gap:7px;margin-bottom:12px;">
        <input type="text" id="coupon-inp" placeholder="Купон код"
               class="form-control" style="font-size:.8rem;padding:7px 10px;">
        <button type="button" onclick="applyCoupon()"
                style="background:var(--secondary);color:#fff;border:none;border-radius:8px;
                       padding:7px 12px;cursor:pointer;font-size:.8rem;font-weight:700;white-space:nowrap;">
            Ашиглах
        </button>
    </div>

    <div class="sum-row">
        <span style="color:var(--text-light);">Бараа (<?= count($cartRows) ?>)</span>
        <span><?= formatPrice($subtotal) ?></span>
    </div>
    <div class="sum-row">
        <span style="color:var(--text-light);">Хүргэлт</span>
        <span id="d-cost">
            <?= $shipping === 0
                ? '<span style="color:var(--success);font-weight:700;">ҮНЭГҮЙ</span>'
                : formatPrice($shipping) ?>
        </span>
    </div>
    <div class="sum-total">
        <span>Нийт дүн</span>
        <span style="color:var(--primary);" id="total-disp"><?= formatPrice($total) ?></span>
    </div>

    <button type="submit"
            style="width:100%;margin-top:14px;padding:13px;font-size:.92rem;
                   background:linear-gradient(135deg,var(--primary),#e85d2f);
                   color:#fff;border:none;border-radius:10px;cursor:pointer;
                   font-weight:700;font-family:'Outfit',sans-serif;display:flex;
                   align-items:center;justify-content:center;gap:6px;">
        🔒 Захиалга баталгаажуулах
    </button>
    <p style="font-size:.7rem;color:var(--text-light);text-align:center;margin-top:7px;line-height:1.5;">
        Захиалга өгснөөр <a href="#" style="color:var(--primary);">үйлчилгээний нөхцөл</a>-ийг зөвшөөрнө
    </p>
</div>

</div>
</form>

<div class="qr-overlay" id="qr-modal">
    <div class="qr-box">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span id="qr-emoji" style="font-size:1.5rem;">🟦</span>
                <div>
                    <div id="qr-title" style="font-family:'Outfit',sans-serif;font-weight:800;font-size:1rem;">QPay</div>
                    <div style="font-size:.72rem;color:var(--text-light);">QR кодыг уншуулж төлнө үү</div>
                </div>
            </div>
            <button onclick="closeQR()"
                    style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:var(--text-light);">✕</button>
        </div>

        <div class="qr-svg-wrap">
            <svg viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg">
                <rect width="21" height="21" fill="white"/>
                <rect x="0" y="0" width="7" height="7" fill="black"/>
                <rect x="1" y="1" width="5" height="5" fill="white"/>
                <rect x="2" y="2" width="3" height="3" fill="black"/>
                <rect x="14" y="0" width="7" height="7" fill="black"/>
                <rect x="15" y="1" width="5" height="5" fill="white"/>
                <rect x="16" y="2" width="3" height="3" fill="black"/>
                <rect x="0" y="14" width="7" height="7" fill="black"/>
                <rect x="1" y="15" width="5" height="5" fill="white"/>
                <rect x="2" y="16" width="3" height="3" fill="black"/>
                <rect x="8"  y="0"  width="1" height="1" fill="black"/>
                <rect x="10" y="0"  width="2" height="1" fill="black"/>
                <rect x="13" y="0"  width="1" height="1" fill="black"/>
                <rect x="9"  y="2"  width="2" height="1" fill="black"/>
                <rect x="12" y="2"  width="2" height="1" fill="black"/>
                <rect x="8"  y="4"  width="3" height="1" fill="black"/>
                <rect x="12" y="4"  width="2" height="1" fill="black"/>
                <rect x="7"  y="6"  width="2" height="1" fill="black"/>
                <rect x="10" y="6"  width="2" height="1" fill="black"/>
                <rect x="8"  y="8"  width="2" height="2" fill="black"/>
                <rect x="11" y="8"  width="1" height="3" fill="black"/>
                <rect x="13" y="8"  width="3" height="1" fill="black"/>
                <rect x="16" y="8"  width="3" height="2" fill="black"/>
                <rect x="9"  y="10" width="1" height="2" fill="black"/>
                <rect x="14" y="10" width="3" height="1" fill="black"/>
                <rect x="7"  y="12" width="2" height="2" fill="black"/>
                <rect x="10" y="12" width="1" height="3" fill="black"/>
                <rect x="13" y="12" width="2" height="2" fill="black"/>
                <rect x="16" y="12" width="2" height="3" fill="black"/>
                <rect x="8"  y="14" width="1" height="3" fill="black"/>
                <rect x="12" y="14" width="1" height="2" fill="black"/>
                <rect x="14" y="16" width="2" height="1" fill="black"/>
                <rect x="18" y="16" width="3" height="2" fill="black"/>
                <rect x="7"  y="18" width="2" height="2" fill="black"/>
                <rect x="11" y="18" width="3" height="2" fill="black"/>
            </svg>
        </div>

        <div class="qr-timer" id="qr-countdown">15:00</div>
        <div style="font-size:.76rem;color:var(--text-light);margin-bottom:8px;">Банкны аппаа нээгээд QR уншуулна уу</div>

        <div class="qr-banks">
            <?php foreach (['ХХБ','ТДБ','Голомт','Хаан','Өргөн','Капитрон','Ариг','МБанк','БСГ','Хас','Нэшнл','Прайм'] as $b): ?>
            <span class="qr-chip"><?= $b ?></span>
            <?php endforeach; ?>
        </div>

        <div style="font-family:'Outfit',sans-serif;font-size:.95rem;font-weight:800;color:var(--primary);margin:8px 0;">
            Нийт: <?= formatPrice($total) ?>
        </div>

        <!--
            confirmPayment() дуудагдахад:
            1. Modal хаана
            2. co-form-д place_order=1, payment_method=<сонгосон> гэж байгаа
            3. form.submit() → PHP POST handler ажиллана
            submit event listener дахин ажиллахгүй (QR modal нэгдэх ч алга)
        -->
        <button type="button" onclick="confirmPayment()"
                style="width:100%;padding:11px;background:linear-gradient(135deg,var(--success),#059669);
                       color:#fff;border:none;border-radius:10px;cursor:pointer;
                       font-weight:700;font-size:.88rem;font-family:'Outfit',sans-serif;">
            ✅ Төлбөр амжилттай болсон
        </button>
        <p style="font-size:.65rem;color:var(--text-light);margin-top:5px;">
            * Бодит QPay: merchant account шаардлагатай
        </p>
    </div>
</div>

<?php endif; ?>
</div><!-- /co-wrap -->

<script>
const QR_METHODS = ['qpay','socialpay','monpay','khanpay','most_money'];
let qrTimer      = null;
let qrConfirmed  = false;   //  энэ флаг submit loop-г зогсооно

/* Хүргэлтийн арга */
function pickDel(type, el) {
    document.querySelectorAll('.dopt').forEach(o => o.classList.remove('sel'));
    el.classList.add('sel');
    const costs = { express: <?= $subtotal >= 50000 ? 0 : 5000 ?>, standard: 2500, pickup: 0 };
    const c = costs[type] || 0;
    document.getElementById('d-cost').innerHTML =
        c === 0 ? '<span style="color:var(--success);font-weight:700;">ҮНЭГҮЙ</span>'
                : c.toLocaleString() + '₮';
    document.getElementById('total-disp').textContent =
        (<?= $subtotal ?> + c).toLocaleString() + '₮';
}

/*  Төлбөрийн арга */
function pickPay(method, el) {
    document.querySelectorAll('.pcard').forEach(c => c.classList.remove('sel'));
    el.classList.add('sel');
    document.getElementById('h-payment').value = method;

    const isQR = QR_METHODS.includes(method);
    document.getElementById('hint-qr').style.display   = (isQR || method === 'card_visa') ? 'flex' : 'none';
    document.getElementById('hint-cash').style.display  = method === 'cash' ? 'block' : 'none';

    const texts = {
        qpay:       '<strong>QPay:</strong> 16+ банкны апп ашиглан QR уншуулж төлнө.',
        socialpay:  '<strong>SocialPay:</strong> Хаан банкны SocialPay апп ашиглана.',
        monpay:     '<strong>Monpay:</strong> Голомт банкны Monpay апп ашиглана.',
        khanpay:    '<strong>Khanpay:</strong> Хаан банкны QR код ашиглана.',
        most_money: '<strong>Most Money:</strong> Худалдаа хөгжлийн банкны Most апп.',
        card_visa:  '<strong>Карт:</strong> Visa / Mastercard — 3D Secure баталгаажуулалт.',
    };
    if (texts[method]) {
        document.getElementById('hint-qr-text').innerHTML = texts[method];
        document.getElementById('hint-qr').style.display = 'flex';
    }
}

/* QR Modal нээх */
function openQR(method) {
    const info = {
        qpay: { emoji:'🟦', title:'QPay' },
        socialpay:  { emoji:'💙', title:'SocialPay' },
        monpay:     { emoji:'💚', title:'Monpay' },
        khanpay:    { emoji:'🔵', title:'Khanpay' },
        most_money: { emoji:'🟠', title:'Most Money' },
    };
    const i = info[method] || { emoji:'💳', title:'Төлбөр' };
    document.getElementById('qr-emoji').textContent = i.emoji;
    document.getElementById('qr-title').textContent = i.title;
    document.getElementById('qr-modal').classList.add('show');

    let sec = 900;
    clearInterval(qrTimer);
    qrTimer = setInterval(() => {
        sec--;
        const m = String(Math.floor(sec / 60)).padStart(2, '0');
        const s = String(sec % 60).padStart(2, '0');
        document.getElementById('qr-countdown').textContent = m + ':' + s;
        if (sec <= 0) { clearInterval(qrTimer); closeQR(); }
    }, 1000);
}

function closeQR() {
    document.getElementById('qr-modal').classList.remove('show');
    clearInterval(qrTimer);
    qrTimer = null;
}

/* "Төлбөр болсон" — флаг тавиад form submit */
function confirmPayment() {
    closeQR();
    qrConfirmed = true;            // флаг асаана → submit listener блоклохгүй болно
    document.getElementById('co-form').submit();
}

/* Form submit listener */
document.getElementById('co-form').addEventListener('submit', function(e) {
    const method = document.getElementById('h-payment').value;

    // QR арга + флаг тавигдаагүй → modal харуулна, submit зогсооно
    if (QR_METHODS.includes(method) && !qrConfirmed) {
        e.preventDefault();
        openQR(method);
        return;
    }
    // cash / card_visa → шууд submit
    // QR + qrConfirmed=true  → шууд submit → PHP-д place_order=1 ирнэ
});

/* Купон */
function applyCoupon() {
    const code = document.getElementById('coupon-inp')?.value.trim().toUpperCase();
    if (!code) return;
    const ok = { WELCOME10: '10%', SHOPMN20: '20%', FLASH30: '30%' };
    if (typeof Toast !== 'undefined') {
        ok[code]
            ? Toast.show('🎉 ' + ok[code] + ' хөнгөлөлт хэрэглэгдлээ!', 'success')
            : Toast.show('❌ Купон код буруу байна', 'error');
    }
}
</script>

<?php include 'includes/footer.php'; ?>