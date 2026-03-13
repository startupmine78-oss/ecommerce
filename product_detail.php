<?php
require_once 'db.php';

$id = intval($_GET['id'] ?? 0);
$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = $id"));

if (!$product) { header('Location: products.php'); exit; }

// Related products
$related = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = {$product['category_id']} AND p.id != $id LIMIT 4");

// Reviews
$reviews = mysqli_query($conn, "SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.product_id = $id ORDER BY r.created_at DESC LIMIT 5");

// Handle review submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submit']) && isLoggedIn()) {
    $rating = intval($_POST['rating']);
    $comment = sanitize($_POST['comment']);
    $userId = $_SESSION['user_id'];
    mysqli_query($conn, "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES ($id, $userId, $rating, '$comment')");
    mysqli_query($conn, "UPDATE products SET rating = (SELECT AVG(rating) FROM reviews WHERE product_id = $id), reviews_count = (SELECT COUNT(*) FROM reviews WHERE product_id = $id) WHERE id = $id");
    header("Location: product_detail.php?id=$id");
    exit;
}

$pageTitle = htmlspecialchars($product['name']) . ' - ShopMN';
include 'includes/header.php';
$discount = $product['original_price'] ? round((1 - $product['price']/$product['original_price'])*100) : 0;
?>

<div class="container">
    <div class="breadcrumb">
        <a href="index.php">Нүүр</a> <span>›</span>
        <a href="products.php?category=<?= $product['cat_slug'] ?>"><?= htmlspecialchars($product['cat_name']) ?></a> <span>›</span>
        <?= htmlspecialchars($product['name']) ?>
    </div>

    <div class="product-detail-layout">
        <!-- Image -->
        <div class="product-detail-img">
            <?php if ($product['badge']): ?>
            <div style="position:relative;">
                <span class="product-badge" style="position:absolute;top:14px;left:14px;z-index:1;"><?= htmlspecialchars($product['badge']) ?></span>
            </div>
            <?php endif; ?>
            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width:100%;height:450px;object-fit:cover;border-radius:var(--radius);" onerror="this.src='https://via.placeholder.com/600x450?text=No+Image'">
        </div>

        <!-- Info -->
        <div class="product-detail-info">
            <div class="product-category-tag" style="font-size:0.85rem;color:var(--primary);font-weight:700;margin-bottom:8px;text-transform:uppercase;">
                <i class="fas fa-tag"></i> <?= htmlspecialchars($product['cat_name']) ?>
            </div>
            <h1 style="font-family:'Outfit',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:12px;line-height:1.3;"><?= htmlspecialchars($product['name']) ?></h1>
            
            <!-- Rating -->
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <div class="stars">
                    <?php
                    $r = round($product['rating'] * 2) / 2;
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $r) echo '<i class="fas fa-star"></i>';
                        elseif ($i - 0.5 == $r) echo '<i class="fas fa-star-half-alt"></i>';
                        else echo '<i class="far fa-star"></i>';
                    }
                    ?>
                </div>
                <span style="font-weight:700;"><?= $product['rating'] ?></span>
                <a href="#reviews" style="color:var(--text-light);font-size:0.9rem;">(<?= number_format($product['reviews_count']) ?> үнэлгээ)</a>
            </div>

            <!-- Price -->
            <div style="background:var(--bg);border-radius:var(--radius);padding:20px;margin-bottom:20px;">
                <div style="display:flex;align-items:baseline;gap:12px;margin-bottom:6px;">
                    <span style="font-family:'Outfit',sans-serif;font-size:2rem;font-weight:800;color:var(--primary);"><?= formatPrice($product['price']) ?></span>
                    <?php if ($product['original_price']): ?>
                    <span style="font-size:1.1rem;color:var(--text-light);text-decoration:line-through;"><?= formatPrice($product['original_price']) ?></span>
                    <span style="background:var(--danger);color:white;padding:3px 10px;border-radius:4px;font-size:0.85rem;font-weight:700;">-<?= $discount ?>%</span>
                    <?php endif; ?>
                </div>
                <?php if ($product['original_price']): ?>
                <p style="color:var(--success);font-size:0.9rem;font-weight:600;">
                    <i class="fas fa-tag"></i> <?= formatPrice($product['original_price'] - $product['price']) ?> хэмнэлт
                </p>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <p style="color:var(--text-light);line-height:1.7;margin-bottom:20px;"><?= htmlspecialchars($product['description']) ?></p>

            <!-- Stock -->
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                <?php if ($product['stock'] > 0): ?>
                <span style="color:var(--success);font-weight:700;"><i class="fas fa-check-circle"></i> Нөөцтэй (<?= $product['stock'] ?> ширхэг)</span>
                <?php else: ?>
                <span style="color:var(--danger);font-weight:700;"><i class="fas fa-times-circle"></i> Нөөцгүй</span>
                <?php endif; ?>
            </div>

            <!-- Quantity & Add to Cart -->
            <?php if ($product['stock'] > 0): ?>
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:20px;flex-wrap:wrap;">
                <div class="qty-control">
                    <button class="qty-btn" onclick="changeQty(-1)">-</button>
                    <span class="qty-num" id="qty">1</span>
                    <button class="qty-btn" onclick="changeQty(1)">+</button>
                </div>
                <button class="btn-primary" onclick="addToCartWithQty(<?= $product['id'] ?>)" style="flex:1;">
                    <i class="fas fa-cart-plus"></i> Сагсанд нэмэх
                </button>
                <a href="checkout.php?buy_now=<?= $product['id'] ?>" class="btn-outline" style="color:var(--text);border-color:var(--border);">
                    <i class="fas fa-bolt"></i> Шууд захиалах
                </a>
            </div>
            <?php endif; ?>

            <!-- Features -->
            <div style="border-top:1px solid var(--border);padding-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="display:flex;align-items:center;gap:8px;font-size:0.88rem;color:var(--text-light);">
                    <i class="fas fa-truck" style="color:var(--primary);"></i> Үнэгүй хүргэлт
                </div>
                <div style="display:flex;align-items:center;gap:8px;font-size:0.88rem;color:var(--text-light);">
                    <i class="fas fa-shield-alt" style="color:var(--primary);"></i> 1 жилийн баталгаа
                </div>
                <div style="display:flex;align-items:center;gap:8px;font-size:0.88rem;color:var(--text-light);">
                    <i class="fas fa-undo" style="color:var(--primary);"></i> 30 хоногт буцаах
                </div>
                <div style="display:flex;align-items:center;gap:8px;font-size:0.88rem;color:var(--text-light);">
                    <i class="fas fa-lock" style="color:var(--primary);"></i> Аюулгүй төлбөр
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div id="reviews" style="background:white;border-radius:var(--radius);padding:30px;box-shadow:var(--shadow);margin:30px 0;">
        <h2 style="font-family:'Outfit',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:24px;">Хэрэглэгчдийн үнэлгээ</h2>
        
        <?php if (isLoggedIn()): ?>
        <form method="POST" style="background:var(--bg);border-radius:var(--radius);padding:20px;margin-bottom:24px;">
            <h3 style="font-size:1rem;font-weight:700;margin-bottom:14px;">Үнэлгээ үлдээх</h3>
            <div style="display:flex;gap:8px;margin-bottom:14px;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <label style="cursor:pointer;font-size:1.4rem;color:#ddd;" onclick="setRating(<?= $i ?>)">
                    <i class="fas fa-star" id="star<?= $i ?>"></i>
                </label>
                <?php endfor; ?>
                <input type="hidden" name="rating" id="ratingInput" value="5">
            </div>
            <div class="form-group">
                <textarea name="comment" class="form-control" rows="3" placeholder="Сэтгэгдэл бичих..." style="resize:vertical;"></textarea>
            </div>
            <button type="submit" name="review_submit" class="btn-primary">Илгээх</button>
        </form>
        <?php else: ?>
        <p style="color:var(--text-light);margin-bottom:20px;"><a href="login.php" style="color:var(--primary);">Нэвтрэх</a> үнэлгээ үлдээх</p>
        <?php endif; ?>

        <?php if (mysqli_num_rows($reviews) === 0): ?>
        <p style="color:var(--text-light);text-align:center;padding:20px;">Одоохондоо үнэлгээ байхгүй байна.</p>
        <?php endif; ?>
        
        <?php while ($rev = mysqli_fetch_assoc($reviews)): ?>
        <div style="border-bottom:1px solid var(--border);padding:16px 0;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <strong><?= htmlspecialchars($rev['user_name']) ?></strong>
                <span style="font-size:0.82rem;color:var(--text-light);"><?= date('Y.m.d', strtotime($rev['created_at'])) ?></span>
            </div>
            <div class="stars" style="margin-bottom:6px;font-size:0.85rem;">
                <?= str_repeat('<i class="fas fa-star"></i>', $rev['rating']) ?>
                <?= str_repeat('<i class="far fa-star"></i>', 5 - $rev['rating']) ?>
            </div>
            <p style="font-size:0.92rem;color:var(--text-light);"><?= htmlspecialchars($rev['comment']) ?></p>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Related Products -->
    <?php if (mysqli_num_rows($related) > 0): ?>
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">Төстэй бүтээгдэхүүн</h2>
        </div>
        <div class="products-grid">
            <?php while ($p = mysqli_fetch_assoc($related)): ?>
            <?php include 'includes/product_card.php'; ?>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
let qty = 1;
function changeQty(delta) {
    qty = Math.max(1, qty + delta);
    document.getElementById('qty').textContent = qty;
}

function addToCartWithQty(id) {
    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'product_id=' + id + '&quantity=' + qty
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cartCount').textContent = data.count;
            showToast('Сагсанд нэмэгдлээ! (' + qty + ' ш)');
        }
    });
}

function setRating(val) {
    document.getElementById('ratingInput').value = val;
    for (let i = 1; i <= 5; i++) {
        document.getElementById('star' + i).style.color = i <= val ? '#FFD700' : '#ddd';
    }
}
setRating(5);
</script>

<?php include 'includes/footer.php'; ?>