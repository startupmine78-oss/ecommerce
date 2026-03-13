<?php
require_once 'auth.php';
requireAdmin();
$pageTitle = 'Ангилал удирдах';
$msg = '';

// ── SAVE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cat'])) {
    $id   = intval($_POST['id'] ?? 0);
    $name = sanitize($_POST['name']);
    $slug = sanitize($_POST['slug']) ?: preg_replace('/[^a-z0-9]+/','-',strtolower($name));
    $icon = sanitize($_POST['icon']);
    if ($id) {
        mysqli_query($conn, "UPDATE categories SET name='$name',slug='$slug',icon='$icon' WHERE id=$id");
        $msg = 'success:Ангилал шинэчлэгдлээ.';
    } else {
        mysqli_query($conn, "INSERT INTO categories (name,slug,icon) VALUES ('$name','$slug','$icon')");
        $msg = 'success:Шинэ ангилал нэмэгдлээ.';
    }
}

// ── DELETE ──
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    $cnt = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE category_id=$did"))['c'];
    if ($cnt > 0) {
        $msg = 'error:Энэ ангилалд '.$cnt.' бараа байгаа тул устгах боломжгүй.';
    } else {
        mysqli_query($conn, "DELETE FROM categories WHERE id=$did");
        $msg = 'success:Ангилал устгагдлаа.';
    }
}

$cats = mysqli_query($conn,
    "SELECT c.*, COUNT(p.id) prod_count FROM categories c
     LEFT JOIN products p ON c.id=p.category_id GROUP BY c.id ORDER BY c.name"
);

$icons = ['fas fa-laptop','fas fa-mobile-alt','fas fa-tshirt','fas fa-home','fas fa-dumbbell',
          'fas fa-book','fas fa-gamepad','fas fa-spa','fas fa-car','fas fa-pizza-slice',
          'fas fa-baby','fas fa-tools','fas fa-guitar','fas fa-paw','fas fa-camera'];

include '_layout.php';
?>

<?php if ($msg): [$t,$txt] = explode(':',$msg,2); ?>
<div class="alert alert-<?= $t==='success'?'success':'error' ?>"><?= htmlspecialchars($txt) ?></div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
    <div>
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Ангилал</div>
        <h2 style="font-family:'Outfit',sans-serif;font-size:1.2rem;font-weight:800">Ангилал удирдах</h2>
    </div>
    <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Шинэ ангилал</button>
</div>

<div class="table-card">
    <table>
        <thead><tr>
            <th>Дүрс</th><th>Нэр</th><th>Slug</th><th>Баркод (icon)</th><th>Бараа</th><th>Үйлдэл</th>
        </tr></thead>
        <tbody>
        <?php while ($c = mysqli_fetch_assoc($cats)): ?>
        <tr>
            <td><div style="width:36px;height:36px;background:#FFF5F0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#FF6B35"><i class="<?= htmlspecialchars($c['icon']) ?>"></i></div></td>
            <td style="font-weight:700"><?= htmlspecialchars($c['name']) ?></td>
            <td style="color:#94A3B8;font-size:.82rem"><?= htmlspecialchars($c['slug']) ?></td>
            <td style="font-size:.82rem;color:#64748B"><?= htmlspecialchars($c['icon']) ?></td>
            <td><span class="badge badge-info"><?= $c['prod_count'] ?> бараа</span></td>
            <td>
                <div style="display:flex;gap:4px">
                    <button class="btn-icon" onclick='openEdit(<?= json_encode($c) ?>)'><i class="fas fa-pen"></i></button>
                    <?php if ($c['prod_count'] == 0): ?>
                    <a href="?delete=<?= $c['id'] ?>" class="btn-icon" onclick="return confirm('Устгах уу?')" style="text-decoration:none">
                        <i class="fas fa-trash" style="color:#EF4444"></i>
                    </a>
                    <?php else: ?>
                    <button class="btn-icon" disabled title="Бараатай ангилал устгах боломжгүй" style="opacity:.4"><i class="fas fa-trash"></i></button>
                    <?php endif; ?>
                    <a href="../products.php?category=<?= $c['slug'] ?>" target="_blank" class="btn-icon"><i class="fas fa-eye"></i></a>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="cat-modal">
<div class="modal" style="max-width:480px">
    <div class="modal-header">
        <span class="modal-title" id="modal-title">Шинэ ангилал</span>
        <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
        <input type="hidden" name="id" id="f-id" value="0">
        <input type="hidden" name="save_cat" value="1">
        <div class="form-grid" style="grid-template-columns:1fr">
            <div class="form-group">
                <label class="form-label">Монгол нэр *</label>
                <input type="text" name="name" id="f-name" class="form-control" required placeholder="Жишээ: Электроник">
            </div>
            <div class="form-group">
                <label class="form-label">Slug (URL)</label>
                <input type="text" name="slug" id="f-slug" class="form-control" placeholder="electronics">
                <span class="form-hint">Хоосон орхивол автоматаар үүснэ</span>
            </div>
            <div class="form-group">
                <label class="form-label">Icon class</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <select name="icon" id="f-icon" class="form-control" onchange="document.getElementById('icon-preview').className=this.value">
                        <?php foreach ($icons as $ic): ?>
                        <option value="<?= $ic ?>"><?= $ic ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="width:40px;height:40px;background:#FFF5F0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#FF6B35;flex-shrink:0">
                        <i id="icon-preview" class="fas fa-laptop"></i>
                    </div>
                </div>
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
function openModal(){
    document.getElementById('modal-title').textContent='Шинэ ангилал';
    document.getElementById('f-id').value=0;
    document.getElementById('f-name').value='';
    document.getElementById('f-slug').value='';
    document.getElementById('cat-modal').classList.add('show');
}
function openEdit(c){
    document.getElementById('modal-title').textContent='Ангилал засах';
    document.getElementById('f-id').value=c.id;
    document.getElementById('f-name').value=c.name||'';
    document.getElementById('f-slug').value=c.slug||'';
    document.getElementById('f-icon').value=c.icon||'fas fa-tag';
    document.getElementById('icon-preview').className=c.icon||'fas fa-tag';
    document.getElementById('cat-modal').classList.add('show');
}
function closeModal(){document.getElementById('cat-modal').classList.remove('show');}
document.getElementById('cat-modal').addEventListener('click',function(e){if(e.target===this)closeModal();});
</script>

<?php include '_layout_end.php'; ?>
