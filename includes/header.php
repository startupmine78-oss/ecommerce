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

<style>
/* ══════════════════════════════════════════
   PROFILE DROPDOWN STYLES
══════════════════════════════════════════ */
.profile-dropdown-wrap {
    position: relative;
    display: inline-block;
}

/* Trigger button */
.profile-trigger {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    cursor: pointer;
    background: none;
    border: none;
    color: white;
    font-family: 'DM Sans', sans-serif;
    padding: 4px 6px;
    border-radius: 8px;
    transition: background 0.2s;
    position: relative;
}
.profile-trigger:hover {
    background: rgba(255,255,255,0.08);
}
.profile-trigger-icon {
    font-size: 1.25rem;
    color: #FF6B35;
}
.profile-trigger-label {
    font-size: 0.68rem;
    color: rgba(255,255,255,0.7);
    white-space: nowrap;
}
.profile-trigger-name {
    font-size: 0.78rem;
    font-weight: 700;
    color: #FF6B35;
    white-space: nowrap;
}

/* Dropdown panel */
.profile-dropdown-panel {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 280px;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.18), 0 4px 16px rgba(0,0,0,0.08);
    z-index: 99999;
    overflow: hidden;
    opacity: 0;
    transform: translateY(-8px) scale(0.97);
    pointer-events: none;
    transition: all 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.profile-dropdown-panel.open {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: all;
}

/* Dropdown header — user info */
.pd-user-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 20px 16px;
    border-bottom: 1.5px solid #F1F5F9;
}
.pd-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FF6B35, #FFa070);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Outfit', sans-serif;
    font-size: 1.1rem;
    font-weight: 800;
    color: white;
    flex-shrink: 0;
}
.pd-user-info .pd-name {
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    color: #1E293B;
    line-height: 1.2;
}
.pd-user-info .pd-role {
    font-size: 0.75rem;
    color: #94A3B8;
    margin-top: 2px;
}

/* Dropdown menu items */
.pd-menu {
    padding: 8px 0;
}
.pd-divider {
    height: 1px;
    background: #F1F5F9;
    margin: 6px 0;
}
.pd-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 20px;
    text-decoration: none;
    color: #334155;
    font-size: 0.88rem;
    font-weight: 500;
    transition: background 0.15s, color 0.15s;
    cursor: pointer;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    font-family: 'DM Sans', sans-serif;
}
.pd-item:hover {
    background: #FFF5F0;
    color: #FF6B35;
}
.pd-item:hover .pd-item-icon {
    transform: scale(1.12);
}
.pd-item-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    transition: transform 0.2s;
}
.pd-item-icon.orders   { background: #FFF3E0; color: #FF6B35; }
.pd-item-icon.history  { background: #EDE7F6; color: #7C3AED; }
.pd-item-icon.settings { background: #E3F2FD; color: #1565C0; }
.pd-item-icon.address  { background: #E8F5E9; color: #2E7D32; }
.pd-item-icon.logout   { background: #FEE2E2; color: #DC2626; }
.pd-item-text .pd-item-title {
    font-weight: 600;
    font-size: 0.88rem;
    color: inherit;
}
.pd-item-text .pd-item-sub {
    font-size: 0.72rem;
    color: #94A3B8;
    margin-top: 1px;
}

/* Guest (not logged in) panel */
.pd-guest {
    padding: 22px 20px;
    text-align: center;
}
.pd-guest-title {
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 1rem;
    color: #1E293B;
    margin-bottom: 6px;
}
.pd-guest-sub {
    font-size: 0.8rem;
    color: #94A3B8;
    margin-bottom: 16px;
}
.pd-guest-btns {
    display: flex;
    gap: 8px;
}
.pd-btn-primary {
    flex: 1;
    background: #FF6B35;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
    font-family: 'DM Sans', sans-serif;
}
.pd-btn-primary:hover { background: #e85d2f; }
.pd-btn-outline {
    flex: 1;
    background: white;
    color: #475569;
    border: 1.5px solid #E2E8F0;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color 0.15s, color 0.15s;
    font-family: 'DM Sans', sans-serif;
}
.pd-btn-outline:hover { border-color: #FF6B35; color: #FF6B35; }

/* Arrow pointer */
.profile-dropdown-panel::before {
    content: '';
    position: absolute;
    top: -7px;
    right: 22px;
    width: 14px;
    height: 14px;
    background: white;
    transform: rotate(45deg);
    border-radius: 2px;
    box-shadow: -2px -2px 6px rgba(0,0,0,0.04);
}

/* Header cart button adjustment */
.cart-btn-wrap {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    cursor: pointer;
    background: none;
    border: none;
    padding: 4px 6px;
    color: white;
    font-family: 'DM Sans', sans-serif;
    text-decoration: none;
}
.cart-btn-wrap .cw-icon { font-size: 1.2rem; position: relative; }
.cart-btn-wrap .cw-label { font-size: 0.68rem; color: rgba(255,255,255,0.7); }
.cart-btn-wrap .cw-badge {
    position: absolute;
    top: -5px;
    right: -8px;
    background: #FF6B35;
    color: white;
    border-radius: 50%;
    width: 17px;
    height: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    font-weight: 800;
}

/* Wishlist button */
.wishlist-btn-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    cursor: pointer;
    color: white;
    text-decoration: none;
    padding: 4px 6px;
    border-radius: 8px;
    transition: background 0.2s;
    position: relative;
}
.wishlist-btn-wrap:hover { background: rgba(255,255,255,0.08); }
.wishlist-btn-wrap .wh-icon { font-size: 1.2rem; position: relative; }
.wishlist-btn-wrap .wh-label { font-size: 0.68rem; color: rgba(255,255,255,0.7); }
.wishlist-btn-wrap .wh-badge {
    position: absolute;
    top: -1px;
    right: 0;
    background: #EF4444;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    font-weight: 800;
}

/* Notification bell */
.notif-btn-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    cursor: pointer;
    color: white;
    padding: 4px 6px;
    border-radius: 8px;
    transition: background 0.2s;
    border: none;
    background: none;
    font-family: 'DM Sans', sans-serif;
}
.notif-btn-wrap:hover { background: rgba(255,255,255,0.08); }
.notif-btn-wrap .nb-icon { font-size: 1.2rem; }
.notif-btn-wrap .nb-label { font-size: 0.68rem; color: rgba(255,255,255,0.7); }

@media (max-width: 768px) {
    .profile-dropdown-panel { right: -10px; width: 260px; }
}
</style>
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
        <!-- Logo -->
        <a href="index.php" class="logo">Shop<span>MN</span></a>

        <!-- Search bar -->
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

        <!-- Right actions -->
        <div class="header-actions" style="display:flex;align-items:center;gap:6px;">

            <!-- Wishlist -->
            <a href="wishlist.php" class="wishlist-btn-wrap" title="Дуртай">
                <span class="wh-icon">🤍</span>
                <span class="wh-label">Дуртай</span>
                <?php if ($wishlistCount > 0): ?>
                <span class="wh-badge" id="wishlist-count"><?= $wishlistCount ?></span>
                <?php endif; ?>
            </a>

            <!-- Notification bell -->
            <button class="notif-btn-wrap" title="Мэдэгдэл" onclick="Toast.show('Одоогоор мэдэгдэл байхгүй байна','info')">
                <span class="nb-icon"><i class="fas fa-bell"></i></span>
                <span class="nb-label">Мэдэгдэл</span>
            </button>

            <!-- Profile dropdown -->
            <div class="profile-dropdown-wrap" id="profileWrap">
                <button class="profile-trigger" id="profileTrigger" onclick="toggleProfileMenu(event)" aria-expanded="false">
                    <span class="profile-trigger-icon"><i class="fas fa-user-circle"></i></span>
                    <?php if ($user): ?>
                    <span class="profile-trigger-label">Тавтай морил,</span>
                    <span class="profile-trigger-name"><?= htmlspecialchars(mb_substr($user['name'], 0, 10)) ?></span>
                    <?php else: ?>
                    <span class="profile-trigger-label">Нэвтрэх /</span>
                    <span class="profile-trigger-name">Бүртгүүлэх</span>
                    <?php endif; ?>
                </button>

                <!-- Dropdown panel -->
                <div class="profile-dropdown-panel" id="profileDropdown">

                    <?php if ($user): ?>
                    <!-- Logged in view -->
                    <div class="pd-user-header">
                        <div class="pd-avatar">
                            <?= mb_strtoupper(mb_substr($user['name'], 0, 1)) ?>
                        </div>
                        <div class="pd-user-info">
                            <div class="pd-name"><?= htmlspecialchars($user['name']) ?></div>
                            <div class="pd-role">Хувь хүн</div>
                        </div>
                    </div>

                    <div class="pd-menu">
                        <a href="profile.php?tab=orders" class="pd-item">
                            <div class="pd-item-icon orders">📦</div>
                            <div class="pd-item-text">
                                <div class="pd-item-title">Миний захиалгууд</div>
                                <div class="pd-item-sub">Захиалгын түүх, статус харах</div>
                            </div>
                        </a>

                        <a href="profile.php?tab=orders" class="pd-item">
                            <div class="pd-item-icon history">📋</div>
                            <div class="pd-item-text">
                                <div class="pd-item-title">Гүйлгээний түүх</div>
                                <div class="pd-item-sub">Төлбөрийн бүртгэл</div>
                            </div>
                        </a>

                        <a href="profile.php?tab=settings" class="pd-item">
                            <div class="pd-item-icon settings">⚙️</div>
                            <div class="pd-item-text">
                                <div class="pd-item-title">Хувийн тохиргоо</div>
                                <div class="pd-item-sub">Нэр, нууц үг өөрчлөх</div>
                            </div>
                        </a>

                        <a href="profile.php?tab=address" class="pd-item">
                            <div class="pd-item-icon address">📍</div>
                            <div class="pd-item-text">
                                <div class="pd-item-title">Хүргэлтийн хаяг</div>
                                <div class="pd-item-sub">Хадгалсан хаягууд</div>
                            </div>
                        </a>

                        <div class="pd-divider"></div>

                        <a href="profile.php?logout=1" class="pd-item" onclick="return confirm('Системээс гарах уу?')">
                            <div class="pd-item-icon logout">🔒</div>
                            <div class="pd-item-text">
                                <div class="pd-item-title" style="color:#DC2626;">Системээс гарах</div>
                                <div class="pd-item-sub">Аккаунтаас гарах</div>
                            </div>
                        </a>
                    </div>

                    <?php else: ?>
                    <!-- Guest view -->
                    <div class="pd-guest">
                        <div style="font-size:2.5rem;margin-bottom:10px;">👤</div>
                        <div class="pd-guest-title">Нэвтрэх / Бүртгүүлэх</div>
                        <div class="pd-guest-sub">Захиалга өгч, дуртай бүтээгдэхүүнээ хадгалаарай</div>
                        <div class="pd-guest-btns">
                            <a href="login.php" class="pd-btn-primary">Нэвтрэх</a>
                            <a href="login.php" class="pd-btn-outline">Бүртгүүлэх</a>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Cart button -->
            <?php if ($user): ?>
            <a href="cart.php" class="cart-btn-wrap" id="cart-trigger" title="Сагс">
                <span class="cw-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cartCount > 0): ?>
                    <span class="cw-badge" id="cartCount"><?= $cartCount ?></span>
                    <?php else: ?>
                    <span class="cw-badge" id="cartCount" style="display:none">0</span>
                    <?php endif; ?>
                </span>
                <span class="cw-label">Сагс</span>
            </a>
            <?php else: ?>
            <a href="cart.php" class="cart-btn" id="cart-trigger">
                <i class="fas fa-shopping-cart"></i> Сагс
                <span class="cart-count" id="cartCount"><?= $cartCount ?></span>
            </a>
            <?php endif; ?>

        </div><!-- /header-actions -->
    </div><!-- /header-main -->

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
<script>
// ── Profile Dropdown Toggle ──────────────────────────
function toggleProfileMenu(e) {
    e.stopPropagation();
    const panel  = document.getElementById('profileDropdown');
    const trigger = document.getElementById('profileTrigger');
    const isOpen  = panel.classList.contains('open');

    // Close all other dropdowns first
    document.querySelectorAll('.profile-dropdown-panel.open').forEach(p => p.classList.remove('open'));

    if (!isOpen) {
        panel.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
    } else {
        trigger.setAttribute('aria-expanded', 'false');
    }
}

// Close on outside click
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('profileWrap');
    if (wrap && !wrap.contains(e.target)) {
        const panel   = document.getElementById('profileDropdown');
        const trigger = document.getElementById('profileTrigger');
        if (panel)   panel.classList.remove('open');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const panel   = document.getElementById('profileDropdown');
        const trigger = document.getElementById('profileTrigger');
        if (panel)   panel.classList.remove('open');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }
});

// Cart drawer open on click (desktop)
const cartTrigger = document.getElementById('cart-trigger');
if (cartTrigger && window.innerWidth > 768) {
    cartTrigger.addEventListener('click', function(e) {
        e.preventDefault();
        CartDrawer.open();
    });
}
</script>