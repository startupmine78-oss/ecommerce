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
$orders = mysqli_query($conn, "SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = {$user['id']} GROUP BY o.id ORDER BY o.created_at DESC LIMIT 10");

// Update profile
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    mysqli_query($conn, "UPDATE users SET name='$name', phone='$phone', address='$address' WHERE id={$user['id']}");
    $success = 'Мэдээлэл амжилттай шинэчлэгдлээ!';
    $user = getCurrentUser();
}

$statusColors = ['pending'=>'#F59E0B','processing'=>'#3B82F6','shipped'=>'#8B5CF6','delivered'=>'#10B981','cancelled'=>'#EF4444'];
$statusLabels = ['pending'=>'Хүлээгдэж байна','processing'=>'Боловсруулж байна','shipped'=>'Хүргэж байна','delivered'=>'Хүргэгдсэн','cancelled'=>'Цуцлагдсан'];

$pageTitle = 'Профайл - ShopMN';
include 'includes/header.php';
?>

<div class="container" style="padding:30px 24px 60px;">
    <div style="display:grid;grid-template-columns:280px 1fr;gap:24px;align-items:start;">
        <!-- Sidebar -->
        <div>
            <div style="background:white;border-radius:var(--radius);padding:28px;box-shadow:var(--shadow);text-align:center;margin-bottom:16px;">
                <div style="width:80px;height:80px;background:linear-gradient(135deg,var(--primary),#FF8C60);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;color:white;margin:0 auto 14px;font-family:'Outfit',sans-serif;font-weight:800;">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <h3 style="font-family:'Outfit',sans-serif;font-weight:700;"><?= htmlspecialchars($user['name']) ?></h3>
                <p style="color:var(--text-light);font-size:0.88rem;"><?= htmlspecialchars($user['email']) ?></p>
                <p style="color:var(--text-light);font-size:0.8rem;margin-top:6px;">Гишүүн: <?= date('Y.m.d', strtotime($user['created_at'])) ?>-аас</p>
                <a href="profile.php?logout=1" style="display:inline-block;margin-top:16px;color:var(--danger);font-size:0.88rem;text-decoration:none;font-weight:600;">
                    <i class="fas fa-sign-out-alt"></i> Гарах
                </a>
            </div>
        </div>

        <!-- Content -->
        <div>
            <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
            <?php endif; ?>

            <!-- Edit Profile -->
            <div style="background:white;border-radius:var(--radius);padding:28px;box-shadow:var(--shadow);margin-bottom:20px;">
                <h3 style="font-family:'Outfit',sans-serif;font-weight:700;margin-bottom:20px;">Хувийн мэдээлэл засах</h3>
                <form method="POST">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label>Нэр</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Утас</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="grid-column:1/-1;">
                            <label>Хаяг</label>
                            <textarea name="address" class="form-control" rows="2" style="resize:vertical;"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Хадгалах</button>
                </form>
            </div>

            <!-- Orders -->
            <div style="background:white;border-radius:var(--radius);padding:28px;box-shadow:var(--shadow);">
                <h3 style="font-family:'Outfit',sans-serif;font-weight:700;margin-bottom:20px;">Миний захиалгууд</h3>
                <?php if (mysqli_num_rows($orders) === 0): ?>
                <p style="color:var(--text-light);text-align:center;padding:30px;">Захиалга байхгүй байна. <a href="products.php" style="color:var(--primary);">Худалдаа хийх</a></p>
                <?php endif; ?>
                <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                <div style="border:2px solid var(--border);border-radius:var(--radius-sm);padding:16px;margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                        <div>
                            <strong style="font-family:'Outfit',sans-serif;">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                            <span style="color:var(--text-light);font-size:0.85rem;margin-left:10px;"><?= date('Y.m.d', strtotime($order['created_at'])) ?></span>
                        </div>
                        <span style="background:<?= $statusColors[$order['status']] ?>20;color:<?= $statusColors[$order['status']] ?>;padding:4px 12px;border-radius:20px;font-size:0.82rem;font-weight:700;">
                            <?= $statusLabels[$order['status']] ?>
                        </span>
                    </div>
                    <div style="margin-top:8px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="color:var(--text-light);font-size:0.88rem;"><?= $order['item_count'] ?> бүтээгдэхүүн</span>
                        <strong style="font-family:'Outfit',sans-serif;color:var(--primary);"><?= formatPrice($order['total_amount']) ?></strong>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>