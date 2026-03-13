<?php
require_once 'db.php';
$pageTitle = 'ShopMN - Монголын дэлхийн зах зээл';
include 'includes/header.php';

$categories = mysqli_query($conn, "SELECT * FROM categories LIMIT 8");
$featured   = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.reviews_count DESC LIMIT 8");
$flashSale  = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.original_price IS NOT NULL ORDER BY RAND() LIMIT 4");
$newArrivals= mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT 4");
?>

<!-- HERO -->
<div class="hero">
    <div class="hero-inner">
        <div class="hero-content">
            <div class="hero-tag">🔥 2024 оны хамгийн том онлайн дэлгүүр</div>
            <h1>Дэлхийн шилдэг <span>бүтээгдэхүүн</span><br>нэг дор</h1>
            <p>10,000+ бүтээгдэхүүн, 500+ брэнд. Хурдан хүргэлт, баталгаатай чанар.</p>
            <div class="hero-btns">
                <a href="products.php" class="btn-primary"><i class="fas fa-shopping-bag"></i> Одоо хайх</a>
                <a href="products.php" class="btn-outline"><i class="fas fa-fire"></i> Flash Sale</a>
            </div>
            <div class="hero-stats">
                <div class="stat"><div class="stat-num">10K+</div><div class="stat-label">Бүтээгдэхүүн</div></div>
                <div class="stat"><div class="stat-num">50K+</div><div class="stat-label">Хэрэглэгч</div></div>
                <div class="stat"><div class="stat-num">4.8★</div><div class="stat-label">Үнэлгээ</div></div>
                <div class="stat"><div class="stat-num">24/7</div><div class="stat-label">Тусламж</div></div>
            </div>
        </div>
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=500&q=80" alt="Featured">
        </div>
    </div>
</div>

<div class="container">
    <!-- Categories -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">Ангилал</h2>
            <a href="products.php" class="view-all">Бүгдийг харах <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="categories-grid">
            <?php
            $catColors = ['#4facfe','#f093fb','#43e97b','#fa709a','#f6d365','#a18cd1','#fda085','#fd1d1d'];
            $i = 0;
            while ($cat = mysqli_fetch_assoc($categories)):
                $color = $catColors[$i % count($catColors)];
            ?>
            <a href="products.php?category=<?= $cat['slug'] ?>" class="category-card">
                <div class="category-icon" style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>99);">
                    <i class="<?= $cat['icon'] ?>"></i>
                </div>
                <p><?= htmlspecialchars($cat['name']) ?></p>
            </a>
            <?php $i++; endwhile; ?>
        </div>
    </div>

    <!-- FLASH SALE with countdown -->
    <div class="section">
        <div class="flash-sale-banner">
            <div class="flash-info">
                <h2>⚡ Flash Sale</h2>
                <p>Хязгаарлагдмал хугацаатай санал — Алдахгүй байгаарай!</p>
            </div>
            <div class="countdown-wrap">
                <div class="countdown-block"><span class="num" id="cd-h">04</span><span class="lbl">цаг</span></div>
                <span class="countdown-sep">:</span>
                <div class="countdown-block"><span class="num" id="cd-m">32</span><span class="lbl">мин</span></div>
                <span class="countdown-sep">:</span>
                <div class="countdown-block"><span class="num" id="cd-s">17</span><span class="lbl">сек</span></div>
            </div>
            <a href="products.php" class="btn-primary">Бүгдийг харах →</a>
        </div>
        <div class="products-grid">
            <?php while ($p = mysqli_fetch_assoc($flashSale)): ?>
            <?php include 'includes/product_card.php'; ?>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Promo Banners -->
    <div class="promo-banners">
        <a href="products.php?category=electronics" class="promo-card">
            <h3>⚡ Электроник</h3><p>Хүртэл 40% хямдрал</p><span class="promo-btn">Авах →</span>
        </a>
        <a href="products.php?category=phones" class="promo-card">
            <h3>📱 Шинэ гар утас</h3><p>2024 оны хамгийн сүүлийн загварууд</p><span class="promo-btn">Харах →</span>
        </a>
        <a href="products.php" class="promo-card">
            <h3>🚀 Үнэгүй хүргэлт</h3><p>50,000₮-аас дээш захиалгад</p><span class="promo-btn">Захиалах →</span>
        </a>
    </div>

    <!-- Featured Products -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">Хамгийн их зарагдсан</h2>
            <a href="products.php" class="view-all">Бүгдийг харах <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="products-grid">
            <?php while ($p = mysqli_fetch_assoc($featured)): ?>
            <?php include 'includes/product_card.php'; ?>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- New Arrivals -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">🆕 Шинэ ирэлт</h2>
            <a href="products.php?sort=new" class="view-all">Бүгдийг харах <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="products-grid">
            <?php while ($p = mysqli_fetch_assoc($newArrivals)): ?>
            <?php include 'includes/product_card.php'; ?>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script>
// Countdown синхрон хийх
(function() {
    let end = new Date(Date.now() + (4*3600 + 32*60 + 17) * 1000);
    function tick() {
        let diff = end - Date.now();
        if (diff < 0) diff = 0;
        let h = String(Math.floor(diff/3600000)).padStart(2,'0');
        let m = String(Math.floor((diff%3600000)/60000)).padStart(2,'0');
        let s = String(Math.floor((diff%60000)/1000)).padStart(2,'0');
        ['flash-countdown','cd-h'].forEach(id => { let el=document.getElementById(id); if(el&&id==='flash-countdown') el.textContent=`${h}:${m}:${s}`; });
        let cdh=document.getElementById('cd-h'), cdm=document.getElementById('cd-m'), cds=document.getElementById('cd-s');
        if(cdh) cdh.textContent=h;
        if(cdm) cdm.textContent=m;
        if(cds) cds.textContent=s;
    }
    tick(); setInterval(tick, 1000);
})();
</script>

<?php include 'includes/footer.php'; ?>