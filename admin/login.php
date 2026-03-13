<?php
require_once __DIR__ . '/../db.php';

// Default admin: admin / admin123 — өөрчлөхийг зөвлөж байна!
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'admin123';

if (adminLoggedIn()) { header('Location: index.php'); exit; }

function adminLoggedIn() { return isset($_SESSION['admin_id']); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');
    if ($u === $ADMIN_USER && $p === $ADMIN_PASS) {
        $_SESSION['admin_id']   = 1;
        $_SESSION['admin_name'] = 'Администратор';
        header('Location: index.php'); exit;
    }
    $error = 'Нэвтрэх нэр эсвэл нууц үг буруу байна.';
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — ShopMN</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;background:linear-gradient(135deg,#1A1A2E 0%,#16213E 50%,#0F3460 100%);display:flex;align-items:center;justify-content:center;font-family:'Outfit',sans-serif}
.card{background:white;border-radius:20px;padding:40px;width:100%;max-width:400px;box-shadow:0 30px 80px rgba(0,0,0,0.4)}
.logo{text-align:center;margin-bottom:28px}
.logo h1{font-size:2rem;font-weight:900;color:#1A1A2E}
.logo h1 span{color:#FF6B35}
.logo p{color:#6B7280;font-size:.9rem;margin-top:4px}
.form-group{margin-bottom:16px}
label{display:block;font-weight:600;font-size:.88rem;color:#374151;margin-bottom:6px}
input{width:100%;padding:12px 14px;border:2px solid #E5E7EB;border-radius:10px;font-size:.95rem;font-family:'Outfit',sans-serif;outline:none;transition:border-color .2s}
input:focus{border-color:#FF6B35}
.btn{width:100%;padding:14px;background:linear-gradient(135deg,#FF6B35,#e85d2f);color:white;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;transition:opacity .2s}
.btn:hover{opacity:.9}
.error{background:#FEE2E2;color:#DC2626;padding:10px 14px;border-radius:8px;font-size:.88rem;margin-bottom:14px}
.badge{background:#EEF2FF;color:#4338CA;padding:8px 14px;border-radius:8px;font-size:.82rem;text-align:center;margin-top:16px}
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <h1>Shop<span>MN</span></h1>
        <p>🔐 Админ хяналтын самбар</p>
    </div>
    <?php if ($error): ?><div class="error">⚠️ <?= $error ?></div><?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Нэвтрэх нэр</label>
            <input type="text" name="username" placeholder="admin" autocomplete="username" required>
        </div>
        <div class="form-group">
            <label>Нууц үг</label>
            <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
        </div>
        <button class="btn" type="submit">Нэвтрэх →</button>
    </form>
    <div class="badge">🔑 Үндсэн: admin / admin123</div>
</div>
</body>
</html>
