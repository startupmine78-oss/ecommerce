<?php
// ajax/register.php
require_once '../db.php';
header('Content-Type: application/json');

$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$name     = sanitize($_POST['name'] ?? '');
$password = $_POST['password'] ?? '';
$phone    = sanitize($_POST['phone'] ?? '');

if (!$email) { echo json_encode(['success'=>false,'error'=>'И-мэйл хаяг буруу.']); exit; }
if (strlen($name) < 2) { echo json_encode(['success'=>false,'error'=>'Нэр хэт богино.']); exit; }
if (strlen($password) < 8) { echo json_encode(['success'=>false,'error'=>'Нууц үг 8+ тэмдэгт байх ёстой.']); exit; }

// OTP баталгаажсан эсэхийг шалгах
$verifiedEmail = mysqli_real_escape_string($conn, $email);
$otp = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM otp_codes WHERE email='$verifiedEmail' AND purpose='register' AND verified=1 ORDER BY id DESC LIMIT 1"
));
if (!$otp) {
    echo json_encode(['success'=>false,'error'=>'Gmail баталгаажаагүй байна. OTP кодоо шалгана уу.']);
    exit;
}

// Давхар бүртгэл
$emailEsc = mysqli_real_escape_string($conn, $email);
if (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE email='$emailEsc'")) > 0) {
    echo json_encode(['success'=>false,'error'=>'Энэ имэйл бүртгэлтэй байна.']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
mysqli_query($conn, "INSERT INTO users (name, email, phone, password, email_verified) VALUES ('$name', '$emailEsc', '$phone', '$hash', 1)");
$userId = mysqli_insert_id($conn);

$_SESSION['user_id'] = $userId;
$session_id = session_id();
mysqli_query($conn, "UPDATE cart SET user_id=$userId WHERE session_id='$session_id'");

echo json_encode(['success'=>true, 'redirect'=>'../index.php']);