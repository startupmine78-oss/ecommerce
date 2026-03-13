<?php
require_once 'db.php';

$session_id = session_id();
$user_id = $_SESSION['user_id'] ?? null;

// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_qty'])) {
        $cartId = intval($_POST['cart_id']);
        $qty = max(1, intval($_POST['qty']));
        mysqli_query($conn, "UPDATE cart SET quantity = $qty WHERE id = $cartId");
    }
    if (isset($_POST['remove_item'])) {
        $cartId = intval($_POST['cart_id']);
        mysqli_query($conn, "DELETE FROM cart WHERE id = $cartId");
    }
    header('Location: cart.php');
    exit;
}

// Get cart items
$condition = $user_id ? "c.user_id = $user_id" : "c.session_id = '$session_id'";
$cartItems = mysqli_query($conn, "SELECT c.*, p.name, p.price, p.image_url, p.stock FROM cart c LEFT JOIN products p ON c.product_id = p.id WHERE $condition");

$subtotal = 0;
$cartRows = [];
while ($item = mysqli_fetch_assoc($cartItems)) {
    $cartRows[] = $item;
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping = $subtotal >= 50000 ? 0 : 5000;
$total = $subtotal + $shipping;

$pageTitle = 'Сагс - ShopMN';
include 'includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="index.php">Нүүр</a> <span>›</span> Сагс
    </div>

    <h1 style="font-family:'Outfit',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:24px;">
        <i class="fas fa-shopping-cart" style="color:var(--primary);"></i> Таны сагс
        <?php if (!empty($cartRows)): ?>
        <span style="font-size:1rem;color:var(--text-light);font-weight:400;"> (<?= count($cartRows) ?> бүтээгдэхүүн)</span>
        <?php endif; ?>
    </h1>

    <?php if (empty($cartRows)): ?>
    <div style="text-align:center;padding:80px 20px;background:white;border-radius:var(--radius);box-shadow:var(--shadow);">
        <i class="fas fa-shopping-cart" style="font-size:4rem;color:var(--border);margin-bottom:20px;display:block;"></i>
        <h3 style="font-family:'Outfit',sans-serif;margin-bottom:8px;">Сагс хоосон байна</h3>
        <p style="color:var(--text-light);margin-bottom:24px;">Дуртай бүтээгдэхүүнээ сагсанд нэмэх</p>
        <a href="products.php" class="btn-primary">Худалдаа хийх</a>
    </div>

    <?php else: ?>
    <div class="cart-layout">
        <!-- Cart Items -->
        <div class="cart-table">
            <table>
                <thead>
                    <tr>
                        <th>Бүтээгдэхүүн</th>
                        <th>Үнэ</th>
                        <th>Тоо</th>
                        <th>Нийт</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartRows as $item): ?>
                    <tr>
                        <td>
                            <div class="cart-product">
                                <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.src='https://via.placeholder.com/70?text=?'">
                                <div>
                                    <div class="cart-product-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <?php if ($item['stock'] < $item['quantity']): ?>
                                    <div style="color:var(--danger);font-size:0.8rem;">⚠ Нөөц хүрэлцэхгүй</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="font-weight:600;"><?= formatPrice($item['price']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                <div class="qty-control">
                                    <button type="submit" name="update_qty" onclick="document.querySelector('#qty_<?= $item['id'] ?>').value = Math.max(1, parseInt(document.querySelector('#qty_<?= $item['id'] ?>').value)-1)" class="qty-btn">-</button>
                                    <input type="number" id="qty_<?= $item['id'] ?>" name="qty" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" style="width:50px;text-align:center;border:2px solid var(--border);border-radius:6px;padding:4px;">
                                    <button type="submit" name="update_qty" class="qty-btn">+</button>
                                </div>
                            </form>
                        </td>
                        <td style="font-family:'Outfit',sans-serif;font-weight:800;color:var(--primary);">
                            <?= formatPrice($item['price'] * $item['quantity']) ?>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Устгах уу?')">
                                <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                <button type="submit" name="remove_item" class="remove-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div>
            <div class="cart-summary">
                <h3>Захиалгын дүн</h3>
                <div class="summary-row">
                    <span>Бараа (<?= count($cartRows) ?>)</span>
                    <span><?= formatPrice($subtotal) ?></span>
                </div>
                <div class="summary-row">
                    <span>Хүргэлт</span>
                    <span><?= $shipping === 0 ? '<span style="color:var(--success);font-weight:700;">ҮНЭГҮЙ</span>' : formatPrice($shipping) ?></span>
                </div>
                <?php if ($shipping > 0): ?>
                <div style="background:#FFF9C4;padding:10px 12px;border-radius:8px;font-size:0.82rem;color:#7B6000;margin:8px 0;">
                    <i class="fas fa-info-circle"></i> <?= formatPrice(50000 - $subtotal) ?> дүнд хүрвэл үнэгүй хүргэлт авна.
                </div>
                <?php endif; ?>
                <div class="summary-total">
                    <span>Нийт дүн</span>
                    <span style="color:var(--primary);"><?= formatPrice($total) ?></span>
                </div>
                <a href="checkout.php" class="btn-primary" style="width:100%;margin-top:20px;display:flex;justify-content:center;">
                    <i class="fas fa-lock"></i> Захиалга хийх
                </a>
                <a href="products.php" style="display:block;text-align:center;margin-top:12px;color:var(--text-light);font-size:0.88rem;">
                    ← Хайлт үргэлжлүүлэх
                </a>
            </div>

            <!-- Payment Methods -->
            <div style="background:white;border-radius:var(--radius);padding:20px;margin-top:16px;box-shadow:var(--shadow);">
                <h4 style="font-size:0.9rem;font-weight:700;margin-bottom:12px;">Хүлээн авах төлбөрийн хэрэгсэл</h4>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach (['Visa', 'MasterCard', 'QPay', 'SocialPay', 'MobiPay'] as $pm): ?>
                    <span style="background:var(--bg);padding:4px 10px;border-radius:6px;font-size:0.8rem;font-weight:600;"><?= $pm ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>