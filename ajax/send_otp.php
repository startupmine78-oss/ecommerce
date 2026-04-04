<?php
require_once '../db.php';
require_once '../config/config.php';
require_once '../auth/MailService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$purpose = in_array($_POST['purpose'] ?? '', ['login', 'register', 'reset'])
           ? $_POST['purpose'] : 'login';

if (!$email) {
    echo json_encode(['success' => false, 'error' => 'И-мэйл хаяг буруу байна.']);
    exit;
}

// Давтамж шалгах — 60 секундэд нэг л удаа
$recentOtp = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT created_at FROM otp_codes
     WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'
       AND purpose = '$purpose'
     ORDER BY id DESC LIMIT 1"
));

if ($recentOtp && (time() - strtotime($recentOtp['created_at'])) < OTP_RESEND_SECONDS) {
    $wait = OTP_RESEND_SECONDS - (time() - strtotime($recentOtp['created_at']));
    echo json_encode(['success' => false, 'error' => "{$wait} секундийн дараа дахин илгээнэ үү."]);
    exit;
}

// Register purpose: имэйл бүртгэлтэй эсэхийг шалгах
if ($purpose === 'register') {
    $exists = mysqli_num_rows(mysqli_query($conn,
        "SELECT id FROM users WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'"
    ));
    if ($exists) {
        echo json_encode(['success' => false, 'error' => 'Энэ имэйл хаяг бүртгэлтэй байна.']);
        exit;
    }
}

// Login/reset purpose: имэйл байгаа эсэхийг шалгах
if (in_array($purpose, ['login', 'reset'])) {
    $exists = mysqli_num_rows(mysqli_query($conn,
        "SELECT id FROM users WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'"
    ));
    if (!$exists) {
        echo json_encode(['success' => false, 'error' => 'Энэ имэйл бүртгэлгүй байна.']);
        exit;
    }
}

// OTP үүсгэх + хадгалах
$code = MailService::generateOTP();
$saved = MailService::saveOTP($conn, $email, $code, $purpose);

if (!$saved) {
    echo json_encode(['success' => false, 'error' => 'OTP хадгалахад алдаа гарлаа.']);
    exit;
}

// Имэйл илгээх
$result = MailService::sendOTP($email, $code, $purpose);

if ($result['success']) {
    // Хөгжүүлэлтийн горим: код-г response-д буцаах (production-д хасна!)
    $devMode = defined('DEV_MODE') && DEV_MODE;
    echo json_encode([
        'success'    => true,
        'message'    => "OTP код {$email} хаяг руу илгээгдлээ.",
        'expires_in' => OTP_EXPIRE_MINUTES * 60,
        'dev_code'   => $devMode ? $code : null  
    ]);
} else {
    echo json_encode([
        'success'  => true,
        'message'  => "⚠️ SMTP тохируулаагүй. Dev mode: код = <strong>$code</strong>",
        'dev_code' => $code,  
        'expires_in' => OTP_EXPIRE_MINUTES * 60
    ]);
}