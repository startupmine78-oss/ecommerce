<?php
require_once 'db.php';

// Filters
$category = sanitize($_GET['category'] ?? '');
$search = sanitize($_GET['q'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'popular');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build WHERE clause
$where = "WHERE 1=1";
if ($category) $where .= " AND c.slug = '$category'";
if ($search) $where .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";

// Order
$order = match($sort) {
    'price_asc' => "p.price ASC",
    'price_desc' => "p.price DESC",
    'rating' => "p.rating DESC",
    'new' => "p.created_at DESC",
    default => "p.reviews_count DESC"
};

$sql = "SELECT p.*, c.name as cat_name, c.slug as cat_slug FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $where ORDER BY $order LIMIT $perPage OFFSET $offset";
$products = mysqli_query($conn, $sql);

// Count total
$countSql = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id $where";
$countResult = mysqli_query($conn, $countSql);
$totalCount = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalCount / $perPage);

// Current category
$currentCat = null;
if ($category) {
    $catResult = mysqli_query($conn, "SELECT * FROM categories WHERE slug = '$category'");
    $currentCat = mysqli_fetch_assoc($catResult);
}

$pageTitle = ($currentCat ? $currentCat['name'] . ' - ' : '') . ($search ? "\"$search\" - " : '') . 'ShopMN';
include 'includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php">Нүүр</a> <span>›</span>
        <?php if ($currentCat): ?>
        <a href="products.php"><?= htmlspecialchars($currentCat['name']) ?></a>
        <?php else: ?>
        Бүх бүтээгдэхүүн
        <?php endif; ?>
        <?php if ($search): ?> <span>›</span> "<?= htmlspecialchars($search) ?>" хайлт<?php endif; ?>
    </div>

    <!-- Page Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:800;">
                <?= $currentCat ? htmlspecialchars($currentCat['name']) : ($search ? "\"" . htmlspecialchars($search) . "\" хайлтын үр дүн" : 'Бүх бүтээгдэхүүн') ?>
            </h1>
            <p style="color:var(--text-light);font-size:0.9rem;"><?= $totalCount ?> бүтээгдэхүүн олдлоо</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:0.9rem;color:var(--text-light);">Эрэмбэлэх:</span>
            <select onchange="window.location.href='?<?= http_build_query(array_merge($_GET, ['sort' => '__VAL__'])) ?>'.replace('__VAL__', this.value)" style="padding:8px 14px;border:2px solid var(--border);border-radius:8px;outline:none;cursor:pointer;">
                <option value="popular" <?= $sort=='popular'?'selected':'' ?>>Хамгийн их зарагдсан</option>
                <option value="new" <?= $sort=='new'?'selected':'' ?>>Шинэ</option>
                <option value="price_asc" <?= $sort=='price_asc'?'selected':'' ?>>Үнэ: Бага → Их</option>
                <option value="price_desc" <?= $sort=='price_desc'?'selected':'' ?>>Үнэ: Их → Бага</option>
                <option value="rating" <?= $sort=='rating'?'selected':'' ?>>Үнэлгээ</option>
            </select>
        </div>
    </div>

    <!-- Category Tabs -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;">
        <a href="products.php<?= $search ? '?q='.urlencode($search) : '' ?>" 
           style="padding:8px 16px;border-radius:20px;text-decoration:none;font-size:0.88rem;font-weight:600;background:<?= !$category?'var(--primary)':'var(--border)' ?>;color:<?= !$category?'white':'var(--text)' ?>;">
            Бүгд
        </a>
        <?php
        $allCats = mysqli_query($conn, "SELECT * FROM categories");
        while ($cat = mysqli_fetch_assoc($allCats)):
            $active = $category == $cat['slug'];
        ?>
        <a href="products.php?category=<?= $cat['slug'] ?><?= $search ? '&q='.urlencode($search) : '' ?>" 
           style="padding:8px 16px;border-radius:20px;text-decoration:none;font-size:0.88rem;font-weight:600;background:<?= $active?'var(--primary)':'white' ?>;color:<?= $active?'white':'var(--text)' ?>;border:2px solid <?= $active?'var(--primary)':'var(--border)' ?>;">
            <i class="<?= $cat['icon'] ?>"></i> <?= htmlspecialchars($cat['name']) ?>
        </a>
        <?php endwhile; ?>
    </div>

    <!-- Products Grid -->
    <?php if (mysqli_num_rows($products) === 0): ?>
    <div style="text-align:center;padding:60px 20px;background:white;border-radius:var(--radius);box-shadow:var(--shadow);">
        <i class="fas fa-search" style="font-size:3rem;color:var(--text-light);margin-bottom:16px;display:block;"></i>
        <h3 style="font-family:'Outfit',sans-serif;margin-bottom:8px;">Бүтээгдэхүүн олдсонгүй</h3>
        <p style="color:var(--text-light);">Өөр түлхүүр үгээр хайж үзнэ үү</p>
        <a href="products.php" class="btn-primary" style="margin-top:20px;display:inline-flex;">Бүгдийг харах</a>
    </div>
    <?php else: ?>
    <div class="products-grid">
        <?php while ($p = mysqli_fetch_assoc($products)): ?>
        <?php include 'includes/product_card.php'; ?>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>" class="page-btn">
            <i class="fas fa-chevron-left"></i>
        </a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
           class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>" class="page-btn">
            <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>