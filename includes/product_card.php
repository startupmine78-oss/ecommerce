<?php
$badgeClass = '';
if ($p['badge'] === 'Bestseller') $badgeClass = 'bestseller';
elseif ($p['badge'] === 'Шинэ') $badgeClass = 'new';
elseif (strpos($p['badge'] ?? '', '%') !== false || $p['badge'] === 'Sale') $badgeClass = 'sale';
$discount = ($p['original_price'] ?? 0) > 0 ? round((1 - $p['price']/$p['original_price'])*100) : 0;
$inWishlist = in_array($p['id'], $_SESSION['wishlist'] ?? []);
?>
<div class="product-card-wrap">
    <div class="product-card" style="display:block;text-decoration:none;color:inherit;">
        <?php if ($p['badge']): ?>
        <span class="product-badge <?= $badgeClass ?>"><?= htmlspecialchars($p['badge']) ?></span>
        <?php endif; ?>
        <?php if ($discount >= 10): ?>
        <span class="product-badge sale" style="top:10px;left:auto;right:10px;">-<?= $discount ?>%</span>
        <?php endif; ?>

        <div class="product-actions">
            <button class="action-btn wishlist-btn <?= $inWishlist?'active':'' ?>"
                data-wishlist="<?= $p['id'] ?>"
                onclick="event.stopPropagation(); Wishlist.toggle(<?= $p['id'] ?>, this)"
                title="<?= $inWishlist ? 'Дуртайнаас хасах' : 'Дуртайд нэмэх' ?>">
                <?= $inWishlist ? '❤️' : '🤍' ?>
            </button>
            <button class="action-btn" onclick="event.stopPropagation(); QuickView.open(<?= $p['id'] ?>)" title="Хурдан харах">
                👁
            </button>
            <a href="product_detail.php?id=<?= $p['id'] ?>" class="action-btn" title="Дэлгэрэнгүй">
                🔗
            </a>
        </div>

        <a href="product_detail.php?id=<?= $p['id'] ?>" style="display:block;text-decoration:none;">
            <img class="product-img"
                src="<?= htmlspecialchars($p['image_url'] ?? '') ?>"
                alt="<?= htmlspecialchars($p['name']) ?>"
                loading="lazy"
                onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
        </a>

        <div class="product-info">
            <div class="product-category-tag"><?= htmlspecialchars($p['cat_name'] ?? '') ?></div>
            <a href="product_detail.php?id=<?= $p['id'] ?>" class="product-name" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-decoration:none;color:inherit;font-weight:600;font-size:0.92rem;margin-bottom:8px;">
                <?= htmlspecialchars($p['name']) ?>
            </a>
            <div class="product-rating">
                <span class="stars">
                    <?php
                    $r = round($p['rating'] * 2) / 2;
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $r) echo '⭐';
                        else echo '☆';
                    }
                    ?>
                </span>
                <span class="rating-num">(<?= number_format($p['reviews_count']) ?>)</span>
            </div>
            <div class="product-price">
                <span class="price"><?= formatPrice($p['price']) ?></span>
                <?php if ($p['original_price']): ?>
                <span class="price-original"><?= formatPrice($p['original_price']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <button class="add-to-cart" onclick="addToCart(<?= $p['id'] ?>, this)">
            🛒 Сагсанд нэмэх
        </button>
    </div>
</div>