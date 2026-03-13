<?php
// includes/header.php
$cartCount = getCartCount();
$user = getCurrentUser();
$wishlistCount = count($_SESSION['wishlist'] ?? []);
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'ShopMN - Монголын дэлхийн зах зээл' ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="dynamic.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- CART OVERLAY -->
<div id="cart-overlay" onclick="CartDrawer.close()"></div>

<!-- CART DRAWER -->
<div id="cart-drawer">
    <div class="cd-header">
        <h3>🛒 Таны сагс <span id="drawerCartCount" style="background:var(--primary);color:white;border-radius:50%;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:0.8rem;margin-left:4px;"><?= $cartCount ?></span></h3>
        <button class="cd-close" onclick="CartDrawer.close()">✕</button>
    </div>
    <div id="cart-drawer-body"><div style="padding:40px;text-align:center;color:#999;">Ачаалж байна...</div></div>
    <div id="cart-drawer-footer"></div>
</div>

<!-- QUICK VIEW MODAL -->
<div id="quick-view-modal" onclick="if(event.target===this)QuickView.close()">
    <div class="qv-card">
        <button class="qv-close" onclick="QuickView.close()">✕</button>
        <div class="qv-body" id="qv-body"></div>
    </div>
</div>

<!-- BACK TO TOP -->
<button id="back-to-top" title="Дээш гарах">↑</button>

<header>
    <div class="header-top">
        ⚡ Flash Sale:
        <strong id="flash-countdown" style="font-family:'Outfit',sans-serif;font-size:1rem;color:var(--accent);letter-spacing:2px;">04:32:17</strong>
        &nbsp;|&nbsp; 🚚 50,000₮+ захиалгад үнэгүй хүргэлт &nbsp;|&nbsp; 📞 7000-1234
    </div>
    <div class="header-main">
        <a href="index.php" class="logo">Shop<span>MN</span></a>

        <form class="search-bar" action="products.php" method="GET" autocomplete="off">
            <select name="category">
                <option value="">Бүх ангилал</option>
                <?php
                $cats = mysqli_query($conn, "SELECT * FROM categories");
                while ($cat = mysqli_fetch_assoc($cats)):
                ?>
                <option value="<?= $cat['slug'] ?>" <?= (($_GET['category'] ?? '') === $cat['slug']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
                <?php endwhile; ?>
            </select>
            <input type="text" name="q" id="search-input"
                placeholder="Бүтээгдэхүүн хайх..."
                value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>

        <div class="header-actions">
            <a href="wishlist.php" class="header-action" style="position:relative;" title="Дуртай">
                <span style="font-size:1.3rem;line-height:1;">🤍</span>
                <?php if ($wishlistCount > 0): ?>
                <span class="wishlist-count-badge" id="wishlist-count"><?= $wishlistCount ?></span>
                <?php endif; ?>
            </a>

            <?php if ($user): ?>
            <a href="profile.php" class="header-action">
                <span style="font-size:0.7rem;color:#aaa;">Тавтай морил,</span>
                <span style="font-weight:700;"><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span>
            </a>
            <?php else: ?>
            <a href="login.php" class="header-action">
                <span style="font-size:0.7rem;color:#aaa;">Нэвтрэх /</span>
                <span style="font-weight:700;">Бүртгүүлэх</span>
            </a>
            <?php endif; ?>

            <a href="cart.php" class="cart-btn" id="cart-trigger">
                <i class="fas fa-shopping-cart"></i> Сагс
                <span class="cart-count" id="cartCount"><?= $cartCount ?></span>
            </a>
        </div>
    </div>

    <nav>
        <div class="nav-inner">
            <a href="index.php"><i class="fas fa-home"></i> Нүүр</a>
            <?php
            $navCats = mysqli_query($conn, "SELECT * FROM categories LIMIT 8");
            while ($nc = mysqli_fetch_assoc($navCats)):
            ?>
            <a href="products.php?category=<?= $nc['slug'] ?>"
               class="<?= (($_GET['category'] ?? '') === $nc['slug']) ? 'active' : '' ?>">
                <i class="<?= $nc['icon'] ?>"></i> <?= htmlspecialchars($nc['name']) ?>
            </a>
            <?php endwhile; ?>
            <a href="products.php" style="margin-left:auto;color:var(--primary)!important;font-weight:700;">
                <i class="fas fa-th"></i> Бүгд
            </a>
        </div>
    </nav>
</header>

<script src="dynamic.js"></script>