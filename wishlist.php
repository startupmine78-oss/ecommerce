<?php
require_once 'db.php';
$pageTitle = 'Дуртай бүтээгдэхүүн - ShopMN';

$wishlist = $_SESSION['wishlist'] ?? [];
$products = [];

if (!empty($wishlist)) {
    $ids = implode(',', array_map('intval', $wishlist));
    $result = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id IN ($ids)");
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding:30px 24px 60px;">
    <div class="breadcrumb">
        <a href="index.php">Нүүр</a> <span>›</span> Дуртай бүтээгдэхүүн
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
        <h1 style="font-family:'Outfit',sans-serif;font-size:1.8rem;font-weight:800;">
            ❤️ Дуртай бүтээгдэхүүн
            <span style="font-size:1rem;color:var(--text-light);font-weight:400;">(<?= count($products) ?>)</span>
        </h1>
        <?php if (!empty($products)): ?>
        <button onclick="addAllToCart()" class="btn-primary">
            🛒 Бүгдийг сагсанд нэмэх
        </button>
        <?php endif; ?>
    </div>

    <?php if (empty($products)): ?>
    <div style="text-align:center;padding:80px 20px;background:white;border-radius:var(--radius);box-shadow:var(--shadow);">
        <div style="font-size:4rem;margin-bottom:16px;">🤍</div>
        <h3 style="font-family:'Outfit',sans-serif;margin-bottom:8px;">Дуртай бүтээгдэхүүн байхгүй байна</h3>
        <p style="color:var(--text-light);margin-bottom:24px;">Бүтээгдэхүүн дээрх 🤍 дарж дуртайдаа нэмнэ үү</p>
        <a href="products.php" class="btn-primary">Дэлгүүр хийх</a>
    </div>
    <?php else: ?>
    <div class="products-grid">
        <?php foreach ($products as $p): ?>
        <?php include 'includes/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function addAllToCart() {
    const ids = <?= json_encode(array_column($products, 'id')) ?>;
    let added = 0;
    ids.forEach(id => {
        fetch('ajax/add_to_cart.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'product_id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                added++;
                updateCartCount(data.count);
                if (added === ids.length) {
                    Toast.show(`${added} бүтээгдэхүүн сагсанд нэмэгдлээ!`, 'cart');
                }
            }
        });
    });
}
</script>

<?php include 'includes/footer.php'; ?>