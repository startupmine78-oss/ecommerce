<?php
require_once 'auth.php';
requireAdmin();
$pageTitle = 'Бүтээгдэхүүн удирдах';

$msg = '';

// ── DELETE ──
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM products WHERE id=$did");
    $msg = 'success:Бүтээгдэхүүн устгагдлаа.';
}

// ── SAVE (add/edit) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $id    = intval($_POST['id'] ?? 0);
    $name  = sanitize($_POST['name']);
    $cat   = intval($_POST['category_id']);
    $desc  = sanitize($_POST['description']);
    $price = intval($_POST['price']);
    $orig  = $_POST['original_price'] !== '' ? intval($_POST['original_price']) : 'NULL';
    $stock = intval($_POST['stock']);
    $img   = sanitize($_POST['image_url']);
    $badge = $_POST['badge'] !== '' ? "'".sanitize($_POST['badge'])."'" : 'NULL';
    $rating= floatval($_POST['rating']);
    $slug  = sanitize($_POST['slug']);

    // Auto-slug if empty
    if (!$slug) {
        $slug = preg_replace('/[^a-z0-9]+/','-',strtolower($name)).'-'.time();
    }

    if ($id) {
        mysqli_query($conn, "UPDATE products SET
            name='$name', category_id=$cat, description='$desc',
            price=$price, original_price=$orig, stock=$stock,
            image_url='$img', badge=$badge, rating=$rating, slug='$slug'
            WHERE id=$id");
        $msg = 'success:Бүтээгдэхүүн амжилттай шинэчлэгдлээ.';
    } else {
        mysqli_query($conn, "INSERT INTO products
            (name,category_id,description,price,original_price,stock,image_url,badge,rating,slug)
            VALUES ('$name',$cat,'$desc',$price,$orig,$stock,'$img',$badge,$rating,'$slug')");
        $msg = 'success:Шинэ бүтээгдэхүүн нэмэгдлээ.';
    }
}

// ── BULK DELETE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $ids = array_map('intval', $_POST['selected'] ?? []);
    if ($ids) {
        $idList = implode(',', $ids);
        mysqli_query($conn, "DELETE FROM products WHERE id IN ($idList)");
        $msg = 'success:'.count($ids).' бүтээгдэхүүн устгагдлаа.';
    }
}

// ── PAGINATION & SEARCH ──
$perPage  = 20;
$page     = max(1, intval($_GET['p'] ?? 1));
$search   = sanitize($_GET['q'] ?? '');
$catFilter= intval($_GET['cat'] ?? 0);
$sort     = in_array($_GET['sort']??'', ['price','stock','rating','name','id']) ? $_GET['sort'] : 'id';
$order    = ($_GET['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

$where = "1=1";
if ($search)    $where .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
if ($catFilter) $where .= " AND p.category_id=$catFilter";

$total   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM products p WHERE $where"))['c'];
$pages   = ceil($total / $perPage);
$offset  = ($page - 1) * $perPage;

$products = mysqli_query($conn,
    "SELECT p.*, c.name as cat_name FROM products p
     LEFT JOIN categories c ON p.category_id=c.id
     WHERE $where ORDER BY p.$sort $order LIMIT $perPage OFFSET $offset"
);

// ── EDIT DATA ──
$editProd = null;
if (isset($_GET['edit'])) {
    $editProd = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=".intval($_GET['edit'])));
}

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
$catArr = [];
while ($c = mysqli_fetch_assoc($categories)) $catArr[] = $c;

include '_layout.php';
?>

<?php if ($msg): [$type,$text] = explode(':',$msg,2); ?>
<div class="alert alert-<?= $type === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($text) ?></div>
<?php endif; ?>

<!-- TOOLBAR -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <div>
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Бүтээгдэхүүн</div>
        <h2 style="font-family:'Outfit',sans-serif;font-size:1.2rem;font-weight:800"><?= number_format($total) ?> бүтээгдэхүүн</h2>
    </div>
    <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Шинэ бараа</button>
</div>

<!-- FILTER BAR -->
<form method="GET" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    <div class="search-box" style="flex:1;min-width:200px">
        <i class="fas fa-search"></i>
        <input type="text" name="q" placeholder="Нэр, тайлбараар хайх..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <select name="cat" class="form-control" style="width:160px" onchange="this.form.submit()">
        <option value="">Бүх ангилал</option>
        <?php foreach ($catArr as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $catFilter==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="sort" class="form-control" style="width:130px" onchange="this.form.submit()">
        <option value="id"     <?= $sort==='id'?'selected':'' ?>>Огноо</option>
        <option value="name"   <?= $sort==='name'?'selected':'' ?>>Нэр</option>
        <option value="price"  <?= $sort==='price'?'selected':'' ?>>Үнэ</option>
        <option value="stock"  <?= $sort==='stock'?'selected':'' ?>>Нөөц</option>
        <option value="rating" <?= $sort==='rating'?'selected':'' ?>>Үнэлгээ</option>
    </select>
    <select name="order" class="form-control" style="width:110px" onchange="this.form.submit()">
        <option value="desc" <?= $order==='desc'?'selected':'' ?>>Буурах</option>
        <option value="asc"  <?= $order==='asc'?'selected':'' ?>>Өсөх</option>
    </select>
    <button class="btn btn-secondary" type="submit"><i class="fas fa-filter"></i></button>
</form>

<!-- TABLE -->
<form method="POST" id="bulk-form">
<div class="table-card">
    <div class="table-header">
        <span class="table-title">Бүтээгдэхүүний жагсаалт</span>
        <div style="display:flex;gap:8px">
            <button type="submit" name="bulk_delete" class="btn btn-danger btn-sm"
                onclick="return confirm('Сонгосон бараануудыг устгах уу?')">
                <i class="fas fa-trash"></i> Сонгосныг устгах
            </button>
        </div>
    </div>
    <table>
        <thead><tr>
            <th><input type="checkbox" id="check-all" onchange="document.querySelectorAll('.row-check').forEach(c=>c.checked=this.checked)"></th>
            <th>Зураг / Нэр</th>
            <th>Ангилал</th>
            <th>Үнэ</th>
            <th>Нөөц</th>
            <th>Үнэлгээ</th>
            <th>Статус</th>
            <th>Үйлдэл</th>
        </tr></thead>
        <tbody>
        <?php while ($p = mysqli_fetch_assoc($products)):
            $stockClass = $p['stock'] < 5 ? 'stock-low' : ($p['stock'] < 20 ? 'stock-med' : 'stock-ok');
        ?>
        <tr>
            <td><input type="checkbox" class="row-check" name="selected[]" value="<?= $p['id'] ?>"></td>
            <td>
                <div class="product-cell">
                    <img class="product-img" src="<?= htmlspecialchars($p['image_url']) ?>" alt="" onerror="this.src='https://via.placeholder.com/40'">
                    <div>
                        <div class="product-name"><?= htmlspecialchars(mb_substr($p['name'],0,35)) ?></div>
                        <div class="product-cat">ID: <?= $p['id'] ?></div>
                    </div>
                </div>
            </td>
            <td><?= htmlspecialchars($p['cat_name'] ?? '—') ?></td>
            <td>
                <div class="price-cell"><?= number_format($p['price']) ?>₮</div>
                <?php if ($p['original_price']): ?>
                <div style="font-size:.72rem;color:#94A3B8;text-decoration:line-through"><?= number_format($p['original_price']) ?>₮</div>
                <?php endif; ?>
            </td>
            <td class="<?= $stockClass ?>"><?= $p['stock'] ?></td>
            <td>⭐ <?= $p['rating'] ?></td>
            <td><?php if ($p['badge']): ?><span class="badge badge-orange"><?= htmlspecialchars($p['badge']) ?></span><?php else: ?>—<?php endif; ?></td>
            <td>
                <div style="display:flex;gap:4px">
                    <button type="button" class="btn-icon" title="Засах"
                        onclick='openEditModal(<?= json_encode($p) ?>)'>
                        <i class="fas fa-pen"></i>
                    </button>
                    <a href="?delete=<?= $p['id'] ?>&q=<?= urlencode($search) ?>&cat=<?= $catFilter ?>&p=<?= $page ?>"
                       class="btn-icon" title="Устгах"
                       onclick="return confirm('Устгах уу?')" style="text-decoration:none">
                        <i class="fas fa-trash" style="color:#EF4444"></i>
                    </a>
                    <a href="../product_detail.php?id=<?= $p['id'] ?>" target="_blank" class="btn-icon" title="Харах">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <!-- PAGINATION -->
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?p=<?= $page-1 ?>&q=<?= urlencode($search) ?>&cat=<?= $catFilter ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php
        $start = max(1, $page-2); $end = min($pages, $page+2);
        for ($i=$start;$i<=$end;$i++):
        ?>
        <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>&cat=<?= $catFilter ?>&sort=<?= $sort ?>&order=<?= $order ?>"
           class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
        <a href="?p=<?= $page+1 ?>&q=<?= urlencode($search) ?>&cat=<?= $catFilter ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
        <span class="page-info"><?= $page ?> / <?= $pages ?> хуудас &nbsp;|&nbsp; <?= number_format($total) ?> бараа</span>
    </div>
</div>
</form>

<!-- ADD / EDIT MODAL -->
<div class="modal-overlay" id="prod-modal">
<div class="modal">
    <div class="modal-header">
        <span class="modal-title" id="modal-title">Шинэ бүтээгдэхүүн</span>
        <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
        <input type="hidden" name="id" id="f-id" value="0">
        <input type="hidden" name="save_product" value="1">
        <div class="form-grid">
            <div class="form-group full">
                <label class="form-label">Нэр *</label>
                <input type="text" name="name" id="f-name" class="form-control" required placeholder="Бүтээгдэхүүний нэр">
            </div>
            <div class="form-group">
                <label class="form-label">Ангилал *</label>
                <select name="category_id" id="f-cat" class="form-control" required>
                    <option value="">— Сонгох —</option>
                    <?php foreach ($catArr as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Slug (URL)</label>
                <input type="text" name="slug" id="f-slug" class="form-control" placeholder="auto-генерэгдэнэ">
            </div>
            <div class="form-group">
                <label class="form-label">Үнэ (₮) *</label>
                <input type="number" name="price" id="f-price" class="form-control" required min="0">
            </div>
            <div class="form-group">
                <label class="form-label">Анхны үнэ (₮)</label>
                <input type="number" name="original_price" id="f-orig" class="form-control" min="0" placeholder="Хөнгөлөлтийн өмнөх үнэ">
            </div>
            <div class="form-group">
                <label class="form-label">Нөөц</label>
                <input type="number" name="stock" id="f-stock" class="form-control" min="0" value="0">
            </div>
            <div class="form-group">
                <label class="form-label">Үнэлгээ (0–5)</label>
                <input type="number" name="rating" id="f-rating" class="form-control" min="0" max="5" step="0.1" value="4.5">
            </div>
            <div class="form-group">
                <label class="form-label">Тэмдэглэгээ (badge)</label>
                <select name="badge" id="f-badge" class="form-control">
                    <option value="">— Байхгүй —</option>
                    <?php foreach (['Bestseller','Шинэ','Sale','Цөөн үлдсэн','-10%','-15%','-20%','-25%','-30%'] as $b): ?>
                    <option value="<?= $b ?>"><?= $b ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label class="form-label">Зургийн URL</label>
                <input type="url" name="image_url" id="f-img" class="form-control" placeholder="https://...">
                <span class="form-hint">Unsplash, Imgur эсвэл өөр URL</span>
            </div>
            <div class="form-group full">
                <label class="form-label">Тайлбар</label>
                <textarea name="description" id="f-desc" class="form-control" rows="3" style="resize:vertical;"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Болих</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Хадгалах</button>
        </div>
    </form>
</div>
</div>

<script>
function openModal() {
    document.getElementById('modal-title').textContent = 'Шинэ бүтээгдэхүүн';
    ['id','name','slug','price','orig','stock','rating','img','desc'].forEach(k=>{
        const el = document.getElementById('f-'+k);
        if (el) el.value = k==='stock'?'0':k==='rating'?'4.5':'';
    });
    document.getElementById('f-cat').value = '';
    document.getElementById('f-badge').value = '';
    document.getElementById('prod-modal').classList.add('show');
}
function openEditModal(p) {
    document.getElementById('modal-title').textContent = 'Бүтээгдэхүүн засах';
    document.getElementById('f-id').value    = p.id;
    document.getElementById('f-name').value  = p.name || '';
    document.getElementById('f-slug').value  = p.slug || '';
    document.getElementById('f-price').value = p.price || '';
    document.getElementById('f-orig').value  = p.original_price || '';
    document.getElementById('f-stock').value = p.stock || 0;
    document.getElementById('f-rating').value= p.rating || 4.5;
    document.getElementById('f-img').value   = p.image_url || '';
    document.getElementById('f-desc').value  = p.description || '';
    document.getElementById('f-cat').value   = p.category_id || '';
    document.getElementById('f-badge').value = p.badge || '';
    document.getElementById('prod-modal').classList.add('show');
}
function closeModal() {
    document.getElementById('prod-modal').classList.remove('show');
}
document.getElementById('prod-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include '_layout_end.php'; ?>
