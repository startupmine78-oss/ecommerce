<?php
if (!function_exists('getCurrentAdmin')) {
    require_once __DIR__ . '/auth.php';
}
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?? 'Admin' ?> — ShopMN Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:#F1F5F9;color:#1E293B;min-height:100vh;display:flex}

.sidebar{width:240px;background:linear-gradient(180deg,#1A1A2E 0%,#16213E 100%);min-height:100vh;position:fixed;top:0;left:0;z-index:100;display:flex;flex-direction:column;transition:transform .3s}
.sidebar-logo{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.08)}
.sidebar-logo h2{font-family:'Outfit',sans-serif;font-size:1.5rem;font-weight:900;color:white}
.sidebar-logo h2 span{color:#FF6B35}
.sidebar-logo p{color:rgba(255,255,255,.5);font-size:.75rem;margin-top:2px}
.sidebar-nav{flex:1;padding:16px 0;overflow-y:auto}
.nav-group{margin-bottom:4px}
.nav-label{padding:8px 20px 4px;font-size:.68rem;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:1px}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:rgba(255,255,255,.65);text-decoration:none;font-size:.875rem;font-weight:500;transition:all .15s;border-left:3px solid transparent}
.nav-item:hover{background:rgba(255,255,255,.06);color:white}
.nav-item.active{background:rgba(255,107,53,.15);color:#FF6B35;border-left-color:#FF6B35}
.nav-item .icon{width:18px;text-align:center;font-size:.9rem}
.nav-item .badge-num{margin-left:auto;background:#FF6B35;color:white;border-radius:10px;padding:1px 7px;font-size:.7rem;font-weight:700}
.sidebar-footer{padding:16px 20px;border-top:1px solid rgba(255,255,255,.08)}
.sidebar-footer a{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.5);text-decoration:none;font-size:.82rem;padding:8px 0}
.sidebar-footer a:hover{color:white}

.main{margin-left:240px;flex:1;display:flex;flex-direction:column;min-height:100vh}
.topbar{background:white;padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #E2E8F0;position:sticky;top:0;z-index:50}
.topbar-left{display:flex;align-items:center;gap:12px}
.topbar-title{font-family:'Outfit',sans-serif;font-size:1.1rem;font-weight:700;color:#1E293B}
.topbar-right{display:flex;align-items:center;gap:14px}
.topbar-btn{background:none;border:none;cursor:pointer;color:#64748B;font-size:1rem;padding:6px;border-radius:8px;transition:background .15s}
.topbar-btn:hover{background:#F1F5F9;color:#1E293B}
.admin-avatar{width:34px;height:34px;background:linear-gradient(135deg,#FF6B35,#e85d2f);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:.85rem;cursor:pointer}
.content{padding:24px 28px;flex:1}

.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:white;border-radius:14px;padding:20px;box-shadow:0 1px 8px rgba(0,0,0,.06);display:flex;flex-direction:column;gap:8px;border-left:4px solid transparent}
.stat-card.orange{border-left-color:#FF6B35}
.stat-card.blue{border-left-color:#3B82F6}
.stat-card.green{border-left-color:#10B981}
.stat-card.purple{border-left-color:#8B5CF6}
.stat-card.red{border-left-color:#EF4444}
.stat-card.yellow{border-left-color:#F59E0B}
.stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem}
.stat-card.orange .stat-icon{background:#FFF5F0;color:#FF6B35}
.stat-card.blue .stat-icon{background:#EFF6FF;color:#3B82F6}
.stat-card.green .stat-icon{background:#F0FDF4;color:#10B981}
.stat-card.purple .stat-icon{background:#F5F3FF;color:#8B5CF6}
.stat-card.red .stat-icon{background:#FEF2F2;color:#EF4444}
.stat-card.yellow .stat-icon{background:#FFFBEB;color:#F59E0B}
.stat-val{font-family:'Outfit',sans-serif;font-size:1.6rem;font-weight:800;color:#1E293B}
.stat-label{font-size:.78rem;color:#64748B;font-weight:500}
.stat-change{font-size:.75rem;font-weight:600}
.stat-change.up{color:#10B981}
.stat-change.down{color:#EF4444}

.table-card{background:white;border-radius:14px;box-shadow:0 1px 8px rgba(0,0,0,.06);overflow:hidden}
.table-header{padding:18px 22px;border-bottom:1px solid #F1F5F9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.table-title{font-family:'Outfit',sans-serif;font-weight:700;font-size:1rem;color:#1E293B}
.search-box{display:flex;align-items:center;gap:8px;background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:8px;padding:7px 12px;font-size:.875rem}
.search-box input{border:none;background:none;outline:none;font-family:'DM Sans',sans-serif;font-size:.875rem;width:200px;color:#1E293B}
.search-box i{color:#94A3B8}
table{width:100%;border-collapse:collapse}
thead tr{background:#F8FAFC}
th{padding:11px 16px;text-align:left;font-size:.75rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #F1F5F9}
td{padding:12px 16px;border-bottom:1px solid #F8FAFC;font-size:.875rem;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#FAFBFF}
.product-cell{display:flex;align-items:center;gap:10px}
.product-img{width:40px;height:40px;object-fit:cover;border-radius:8px;flex-shrink:0}
.product-name{font-weight:600;font-size:.875rem;color:#1E293B}
.product-cat{font-size:.75rem;color:#94A3B8;margin-top:1px}

.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700}
.badge-success{background:#D1FAE5;color:#065F46}
.badge-warning{background:#FEF3C7;color:#92400E}
.badge-danger {background:#FEE2E2;color:#991B1B}
.badge-info   {background:#DBEAFE;color:#1E40AF}
.badge-gray   {background:#F1F5F9;color:#475569}
.badge-orange {background:#FFF5F0;color:#C2410C}

.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s;font-family:'DM Sans',sans-serif}
.btn-primary{background:#FF6B35;color:white}
.btn-primary:hover{background:#e85d2f}
.btn-secondary{background:#F1F5F9;color:#475569}
.btn-secondary:hover{background:#E2E8F0}
.btn-danger{background:#FEE2E2;color:#DC2626}
.btn-danger:hover{background:#FECACA}
.btn-success{background:#D1FAE5;color:#065F46}
.btn-sm{padding:5px 10px;font-size:.78rem}
.btn-icon{padding:6px 8px;background:#F1F5F9;color:#64748B;border-radius:6px;border:none;cursor:pointer;transition:background .15s}
.btn-icon:hover{background:#E2E8F0}

.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group.full{grid-column:1/-1}
.form-label{font-size:.82rem;font-weight:600;color:#374151}
.form-control{padding:9px 12px;border:1.5px solid #E2E8F0;border-radius:8px;font-size:.875rem;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s;color:#1E293B;background:white}
.form-control:focus{border-color:#FF6B35}
select.form-control{cursor:pointer}
.form-hint{font-size:.75rem;color:#94A3B8}

.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center;backdrop-filter:blur(3px)}
.modal-overlay.show{display:flex}
.modal{background:white;border-radius:16px;padding:28px;width:90%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,.3)}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.modal-title{font-family:'Outfit',sans-serif;font-weight:700;font-size:1.1rem}
.modal-close{background:none;border:none;cursor:pointer;color:#94A3B8;font-size:1.2rem;padding:4px}
.modal-close:hover{color:#1E293B}
.modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid #F1F5F9}

.pagination{display:flex;align-items:center;gap:6px;padding:16px 22px;border-top:1px solid #F1F5F9}
.page-btn{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:6px;border:1.5px solid #E2E8F0;color:#475569;text-decoration:none;font-size:.82rem;font-weight:600;transition:all .15s}
.page-btn:hover,.page-btn.active{background:#FF6B35;border-color:#FF6B35;color:white}
.page-info{color:#94A3B8;font-size:.82rem;margin-left:auto}

.chart-wrap{background:white;border-radius:14px;padding:22px;box-shadow:0 1px 8px rgba(0,0,0,.06)}
.chart-header{display:flex;justify-content:space-between;margin-bottom:16px}
.chart-title{font-family:'Outfit',sans-serif;font-weight:700}
.chart-bar-row{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.chart-bar-label{width:80px;font-size:.78rem;color:#64748B;text-align:right}
.chart-bar-track{flex:1;height:12px;background:#F1F5F9;border-radius:6px;overflow:hidden}
.chart-bar-fill{height:100%;background:linear-gradient(90deg,#FF6B35,#ffaa88);border-radius:6px;transition:width 1s ease}
.chart-bar-val{width:70px;font-size:.78rem;font-weight:600;color:#1E293B}

.stock-low{color:#EF4444;font-weight:700}
.stock-ok {color:#10B981;font-weight:600}
.stock-med{color:#F59E0B;font-weight:600}
.price-cell{font-family:'Outfit',sans-serif;font-weight:700;color:#FF6B35}
.alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.875rem}
.alert-success{background:#D1FAE5;color:#065F46}
.alert-error  {background:#FEE2E2;color:#991B1B}
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:.82rem;color:#94A3B8;margin-bottom:4px}
.breadcrumb a{color:#FF6B35;text-decoration:none}
.empty-state{text-align:center;padding:60px 20px;color:#94A3B8}
.empty-state i{font-size:3rem;margin-bottom:12px;display:block}

@media(max-width:900px){
    .sidebar{transform:translateX(-240px)}
    .sidebar.open{transform:translateX(0)}
    .main{margin-left:0}
    .form-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <h2>Shop<span>MN</span></h2>
        <p>Хяналтын самбар</p>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-group">
            <div class="nav-label">Үндсэн</div>
            <a href="index.php" class="nav-item <?= $currentPage==='index'?'active':'' ?>">
                <i class="icon fas fa-chart-pie"></i> Хяналтын самбар
            </a>
        </div>
        <div class="nav-group">
            <div class="nav-label">Каталог</div>
            <a href="products.php" class="nav-item <?= $currentPage==='products'?'active':'' ?>">
                <i class="icon fas fa-box"></i> Бүтээгдэхүүн
            </a>
            <a href="categories.php" class="nav-item <?= $currentPage==='categories'?'active':'' ?>">
                <i class="icon fas fa-tags"></i> Ангилал
            </a>
        </div>
        <div class="nav-group">
            <div class="nav-label">Борлуулалт</div>
            <a href="orders.php" class="nav-item <?= $currentPage==='orders'?'active':'' ?>">
                <i class="icon fas fa-shopping-bag"></i> Захиалгууд
                <?php
                global $conn;
                $pend = @mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM orders WHERE status='pending'"))['c'] ?? 0;
                if ($pend > 0) echo "<span class='badge-num'>$pend</span>";
                ?>
            </a>
            <a href="users.php" class="nav-item <?= $currentPage==='users'?'active':'' ?>">
                <i class="icon fas fa-users"></i> Хэрэглэгчид
            </a>

<a href="reports.php" class="nav-item <?= $currentPage==='reports'?'active':'' ?>">
    <i class="icon fas fa-chart-bar"></i> Тайлан & Шинжилгээ
</a>

<a href="export_excel.php?sheet=full" class="nav-item">
    <i class="icon fas fa-file-excel" style="color:#217346"></i> Excel гаргах
</a>
        </div>
        <div class="nav-group">
            <div class="nav-label">Систем</div>
            <a href="../index.php" class="nav-item" target="_blank">
                <i class="icon fas fa-external-link-alt"></i> Вэб харах
            </a>
            <a href="logout.php" class="nav-item">
                <i class="icon fas fa-sign-out-alt"></i> Гарах
            </a>
        </div>
    </nav>
    <div class="sidebar-footer">
        <a href="#"><i class="fas fa-user-circle"></i> <?= getCurrentAdmin() ?></a>
    </div>
</aside>

<div class="main">
<header class="topbar">
    <div class="topbar-left">
        <button class="topbar-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">
            <i class="fas fa-bars"></i>
        </button>
        <span class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></span>
    </div>
    <div class="topbar-right">
        <a href="../index.php" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Вэб</a>
        <div class="admin-avatar" title="<?= getCurrentAdmin() ?>">A</div>
    </div>
</header>
<div class="content">