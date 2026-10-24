<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer
{
    public static function sendOtp(string $toEmail, string $code, string $purposeLabel): bool
    {
        $config = require __DIR__ . '/../config/mail.php';

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->Port = $config['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            // A misconfigured/unreachable SMTP host must fail fast, not hang the
            // request — OTP verification still works via core/Otp.php either way.
            $mail->Timeout = 8;
            $mail->SMTPKeepAlive = false;

            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($toEmail);

            $mail->Subject = "Your TableFare verification code: $code";
            $mail->Body = "Your one-time code to $purposeLabel is: $code\n\nThis code expires in 10 minutes. If you didn't request this, you can ignore this email.";

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log('Mailer error: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
