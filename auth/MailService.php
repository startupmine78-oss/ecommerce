<?php

require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/config.php';

class MailService
{
    //  OTP Үүсгэх 
    public static function generateOTP(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    //  OTP Хадгалах 
    public static function saveOTP(mysqli $conn, string $email, string $code, string $purpose = 'login'): bool
    {
        $email   = mysqli_real_escape_string($conn, $email);
        $expires = date('Y-m-d H:i:s', time() + OTP_EXPIRE_MINUTES * 60);

        mysqli_query($conn, "DELETE FROM otp_codes WHERE email='$email' AND purpose='$purpose'");

        $result = mysqli_query($conn, "
            INSERT INTO otp_codes (email, code, purpose, expires_at)
            VALUES ('$email', '$code', '$purpose', '$expires')
        ");

        return $result !== false;
    }

    //  OTP Шалгах 
    public static function verifyOTP(mysqli $conn, string $email, string $code, string $purpose = 'login'): array
    {
        $email = mysqli_real_escape_string($conn, $email);
        $code  = mysqli_real_escape_string($conn, $code);

        $row = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM otp_codes
             WHERE email='$email' AND purpose='$purpose' AND verified=0
             ORDER BY id DESC LIMIT 1"
        ));

        if (!$row) return ['success' => false, 'error' => 'OTP олдсонгүй.'];

        if (strtotime($row['expires_at']) < time()) {
            return ['success' => false, 'error' => 'OTP хугацаа дууссан. Дахин илгээнэ үү.'];
        }

        if ($row['attempts'] >= OTP_MAX_ATTEMPTS) {
            return ['success' => false, 'error' => 'Хэт олон оролдлого. Дахин илгээнэ үү.'];
        }

        if ($row['code'] !== $code) {
            mysqli_query($conn, "UPDATE otp_codes SET attempts=attempts+1 WHERE id={$row['id']}");
            $left = OTP_MAX_ATTEMPTS - $row['attempts'] - 1;
            return ['success' => false, 'error' => "Код буруу байна. $left оролдлого үлдлээ."];
        }

        mysqli_query($conn, "UPDATE otp_codes SET verified=1 WHERE id={$row['id']}");
        return ['success' => true];
    }

    //  OTP Имэйл Илгээх 
    public static function sendOTP(string $email, string $code, string $purpose = 'login'): array
    {
        $purposeLabel = match($purpose) {
            'register' => 'Бүртгүүлэх',
            'reset'    => 'Нууц үг сэргээх',
            default    => 'Нэвтрэх',
        };

        $subject = "[$purposeLabel] ShopMN - Таны OTP код: $code";
        $html    = self::buildOTPEmail($code, $purposeLabel, OTP_EXPIRE_MINUTES);

        return self::sendMail($email, $subject, $html);
    }

    //  PHPMailer ашиглан илгээх 
    public static function sendMail(string $to, string $subject, string $html): array
    {
        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = strip_tags($html);

            $mail->send();

            return ['success' => true];

        } catch (Exception $e) {
            error_log("Mail Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Имэйл илгээхэд алдаа гарлаа.'];
        }
    }

    //  HTML Template 
    private static function buildOTPEmail(string $code, string $purpose, int $minutes): string
    {
        $digits = str_split($code);
        $boxes  = implode('', array_map(
            fn($d) => "<span style='display:inline-block;width:52px;height:64px;line-height:64px;
                        background:#f8f9fa;border:2px solid #FF6B35;border-radius:12px;
                        font-size:28px;font-weight:900;color:#1A1A2E;margin:0 4px;
                        font-family:Courier,monospace;text-align:center;'>$d</span>",
            $digits
        ));

        return <<<HTML
<!DOCTYPE html>
<html lang="mn">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F4F6F8;font-family:'Helvetica Neue',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;">
<tr><td align="center">
  <table width="560" cellpadding="0" cellspacing="0" style="background:white;border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.1);">
    <!-- Header -->
    <tr>
      <td style="background:linear-gradient(135deg,#1A1A2E,#16213E);padding:32px;text-align:center;">
        <div style="font-size:32px;font-weight:900;color:white;letter-spacing:-1px;">
          Shop<span style="color:#FF6B35;">MN</span>
        </div>
        <div style="color:rgba(255,255,255,0.7);font-size:14px;margin-top:6px;">
          Монголын дэлхийн зах зээл
        </div>
      </td>
    </tr>
    <!-- Body -->
    <tr>
      <td style="padding:40px 48px;">
        <div style="text-align:center;margin-bottom:28px;">
          <div style="font-size:48px;margin-bottom:12px;">🔐</div>
          <h2 style="margin:0 0 8px;font-size:22px;color:#1A1A2E;">{$purpose} OTP код</h2>
          <p style="margin:0;color:#6B7280;font-size:15px;">
            Доорх 6 оронтой кодыг {$minutes} минутын дотор оруулна уу
          </p>
        </div>

        <!-- OTP Code boxes -->
        <div style="text-align:center;margin:32px 0;padding:24px;background:#fff8f5;border-radius:16px;border:2px dashed #FF6B35;">
          {$boxes}
          <div style="margin-top:16px;font-size:12px;color:#999;">
            ⏰ {$minutes} минутад хүчинтэй
          </div>
        </div>

        <!-- Security notice -->
        <div style="background:#FEF3C7;border-radius:12px;padding:16px;margin-bottom:24px;">
          <p style="margin:0;font-size:13px;color:#92400E;line-height:1.6;">
            ⚠️ <strong>Аюулгүй байдлын анхааруулга:</strong>
            Энэ кодыг хэн нэгэнтэй хуваалцахгүй байна уу.
            ShopMN ажилчид таны OTP-г асуухгүй.
          </p>
        </div>

        <!-- Action notice -->
        <p style="color:#6B7280;font-size:14px;line-height:1.6;text-align:center;">
          Хэрэв та энэ үйлдлийг хийгээгүй бол энэ имэйлийг үл тоомсорлоно уу.<br>
          Таны бүртгэл аюулгүй хэвээр байна.
        </p>
      </td>
    </tr>
    <!-- Footer -->
    <tr>
      <td style="background:#F9FAFB;padding:24px 48px;text-align:center;border-top:1px solid #E5E7EB;">
        <div style="font-size:12px;color:#9CA3AF;line-height:1.8;">
          © 2024 ShopMN — Монгол улс, Улаанбаатар<br>
          📞 7000-1234 &nbsp;|&nbsp; 📧 support@shopmn.mn<br>
          <a href="#" style="color:#FF6B35;text-decoration:none;">Бүртгэлгүй болгох</a>
        </div>
      </td>
    </tr>
  </table>
</td></tr>
</table>
</body>
</html>
HTML;
    }
}