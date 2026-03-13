<?php
require_once 'auth.php';
requireAdmin();
$pageTitle = 'Хэрэглэгч удирдах';
$msg = '';

// ── DELETE ──
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM users WHERE id=$did");
    $msg = 'success:Хэрэглэгч устгагдлаа.';
}

// ── TOGGLE VERIFIED ──
if (isset($_GET['verify'])) {
    $vid = intval($_GET['verify']);
    mysqli_query($conn, "UPDATE users SET email_verified = 1 - email_verified WHERE id=$vid");
    $msg = 'success:Баталгаажуулалт өөрчлөгдлөө.';
}

$page    = max(1, intval($_GET['p'] ?? 1));
$perPage = 20;
$search  = sanitize($_GET['q'] ?? '');

$where = "1=1";
if ($search) $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";

$total  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE $where"))['c'];
$pages  = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$users = mysqli_query($conn,
    "SELECT u.*, COUNT(o.id) order_count, COALESCE(SUM(o.total_amount),0) total_spent
     FROM users u LEFT JOIN orders o ON u.id=o.user_id
     WHERE $where GROUP BY u.id ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset"
);

include '_layout.php';
?>

<?php if ($msg): [$t,$txt] = explode(':',$msg,2); ?>
<div class="alert alert-<?= $t==='success'?'success':'error' ?>"><?= htmlspecialchars($txt) ?></div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <div>
        <div class="breadcrumb"><a href="index.php">Dashboard</a> › Хэрэглэгчид</div>
        <h2 style="font-family:'Outfit',sans-serif;font-size:1.2rem;font-weight:800">Нийт <?= number_format($total) ?> хэрэглэгч</h2>
    </div>
</div>

<form method="GET" style="display:flex;gap:10px;margin-bottom:16px">
    <div class="search-box" style="flex:1;max-width:360px">
        <i class="fas fa-search"></i>
        <input type="text" name="q" placeholder="Нэр, имэйл, утасны дугаараар..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <button class="btn btn-secondary" type="submit"><i class="fas fa-search"></i> Хайх</button>
</form>

<div class="table-card">
    <table>
        <thead><tr>
            <th>#</th><th>Нэр</th><th>Имэйл</th><th>Утас</th><th>Баталгаажуулалт</th><th>Захиалга</th><th>Нийт зарцуулалт</th><th>Огноо</th><th>Үйлдэл</th>
        </tr></thead>
        <tbody>
        <?php while ($u = mysqli_fetch_assoc($users)): ?>
        <tr>
            <td style="color:#94A3B8;font-size:.78rem"><?= $u['id'] ?></td>
            <td>
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:32px;height:32px;background:linear-gradient(135deg,#FF6B35,#e85d2f);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:.8rem;flex-shrink:0">
                        <?= mb_strtoupper(mb_substr($u['name'],0,1)) ?>
                    </div>
                    <span style="font-weight:600"><?= htmlspecialchars($u['name']) ?></span>
                </div>
            </td>
            <td style="font-size:.82rem;color:#475569"><?= htmlspecialchars($u['email']) ?></td>
            <td style="font-size:.82rem"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
            <td>
                <span class="badge <?= $u['email_verified']?'badge-success':'badge-warning' ?>">
                    <?= $u['email_verified']?'✓ Баталгаажсан':'Баталгаажаагүй' ?>
                </span>
            </td>
            <td><span class="badge badge-info"><?= $u['order_count'] ?></span></td>
            <td class="price-cell"><?= number_format($u['total_spent']) ?>₮</td>
            <td style="font-size:.78rem;color:#94A3B8"><?= date('Y.m.d', strtotime($u['created_at'])) ?></td>
            <td>
                <div style="display:flex;gap:4px">
                    <a href="?verify=<?= $u['id'] ?>&q=<?= urlencode($search) ?>&p=<?= $page ?>"
                       class="btn-icon" title="<?= $u['email_verified']?'Болиулах':'Баталгаажуулах' ?>">
                        <i class="fas fa-<?= $u['email_verified']?'check-circle':'times-circle' ?>"
                           style="color:<?= $u['email_verified']?'#10B981':'#F59E0B' ?>"></i>
                    </a>
                    <a href="orders.php?q=<?= urlencode($u['name']) ?>" class="btn-icon" title="Захиалгууд">
                        <i class="fas fa-shopping-bag"></i>
                    </a>
                    <a href="?delete=<?= $u['id'] ?>&q=<?= urlencode($search) ?>"
                       class="btn-icon" onclick="return confirm('Устгах уу?')" style="text-decoration:none" title="Устгах">
                        <i class="fas fa-trash" style="color:#EF4444"></i>
                    </a>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php if ($page>1): ?><a href="?p=<?=$page-1?>&q=<?=urlencode($search)?>" class="page-btn"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
        <?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?>
        <a href="?p=<?=$i?>&q=<?=urlencode($search)?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
        <?php endfor; ?>
        <?php if($page<$pages): ?><a href="?p=<?=$page+1?>&q=<?=urlencode($search)?>" class="page-btn"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
        <span class="page-info"><?=$page?>/<?=$pages?> &nbsp;|&nbsp; <?=number_format($total)?> хэрэглэгч</span>
    </div>
</div>

<?php include '_layout_end.php'; ?>
