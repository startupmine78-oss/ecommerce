<?php
require_once '../db.php';
require_once '../config/config.php';
require_once '../auth/MailService.php';

header('Content-Type: application/json');

$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$code    = trim($_POST['code'] ?? '');
$purpose = $_POST['purpose'] ?? 'login';

if (!$email || strlen($code) !== 6) {
    echo json_encode(['success' => false, 'error' => 'Мэдээлэл дутуу байна.']);
    exit;
}

$result = MailService::verifyOTP($conn, $email, $code, $purpose);

if (!$result['success']) {
    echo json_encode($result);
    exit;
}

// Баталгаажсан — purpose-р өөр өөр үйлдэл хийх
if ($purpose === 'login') {
    $user = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM users WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'"
    ));
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        mysqli_query($conn, "UPDATE users SET last_login=NOW(), email_verified=1 WHERE id={$user['id']}");

        // Guest cart шилжүүлэх
        $session_id = session_id();
        mysqli_query($conn, "UPDATE cart SET user_id={$user['id']}, session_id=NULL WHERE session_id='$session_id'");

        echo json_encode(['success' => true, 'redirect' => 'index.php']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Хэрэглэгч олдсонгүй.']);
    }
} elseif ($purpose === 'register') {
    // Бүртгэлийн session-д хадгалах (register.php дараагийн алхамд ашиглана)
    $_SESSION['otp_verified_email'] = $email;
    echo json_encode(['success' => true, 'redirect' => '../register.php?step=2&email=' . urlencode($email)]);
} elseif ($purpose === 'reset') {
    $_SESSION['reset_verified_email'] = $email;
    echo json_encode(['success' => true, 'redirect' => '../auth/reset_password.php?email=' . urlencode($email)]);
}