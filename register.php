<?php
require_once 'db.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($name) < 2) {
        $error = 'Нэр хэт богино байна.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'И-мэйл хаяг буруу байна.';
    } elseif (strlen($password) < 6) {
        $error = 'Нууц үг хамгийн багадаа 6 тэмдэгт байх ёстой.';
    } elseif ($password !== $confirm) {
        $error = 'Нууц үг тохирохгүй байна.';
    } else {
        $existing = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($existing) > 0) {
            $error = 'Энэ и-мэйл бүртгэлтэй байна.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn, "INSERT INTO users (name, email, phone, password) VALUES ('$name', '$email', '$phone', '$hash')");
            $userId = mysqli_insert_id($conn);
            $_SESSION['user_id'] = $userId;
            // Transfer guest cart
            $session_id = session_id();
            mysqli_query($conn, "UPDATE cart SET user_id = $userId WHERE session_id = '$session_id'");
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Бүртгүүлэх - ShopMN';
include 'includes/header.php';
?>

<div class="auth-wrap">
    <div class="auth-card">
        <h2>Шинэ бүртгэл</h2>
        <p>Нэгдэж дэлхийн зах зээлд хүрэх</p>

        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Нэр *</label>
                <input type="text" name="name" class="form-control" placeholder="Бат-Эрдэнэ" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>И-мэйл хаяг *</label>
                <input type="email" name="email" class="form-control" placeholder="example@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Утасны дугаар</label>
                <input type="tel" name="phone" class="form-control" placeholder="9911 2233" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Нууц үг * (6+ тэмдэгт)</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label>Нууц үг давтах *</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
            </div>

            <div style="font-size:0.82rem;color:var(--text-light);margin-bottom:16px;">
                Бүртгүүлснээр та манай <a href="#" style="color:var(--primary);">үйлчилгээний нөхцөл</a>-ийг зөвшөөрнө.
            </div>

            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">
                <i class="fas fa-user-plus"></i> Бүртгүүлэх
            </button>
        </form>

        <div class="auth-divider">эсвэл</div>

        <div style="text-align:center;">
            <p style="color:var(--text-light);font-size:0.9rem;">Бүртгэлтэй юу? <a href="login.php" style="color:var(--primary);font-weight:700;">Нэвтрэх</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>