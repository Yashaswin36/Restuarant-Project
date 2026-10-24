<?php

require_once __DIR__ . '/Database.php';

/**
 * Email OTP issuance/verification for the Customer persona (no password).
 * One active code per (email, purpose) at a time; old unverified codes for
 * the same pair are superseded when a new one is requested.
 */
class Otp
{
    private const TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public static function generate(string $email, string $purpose): string
    {
        $code = (string)random_int(100000, 999999);
        $pdo = Database::connect();

        $del = $pdo->prepare('DELETE FROM otp_codes WHERE email = ? AND purpose = ? AND verified_at IS NULL');
        $del->execute([$email, $purpose]);

        $ins = $pdo->prepare(
            'INSERT INTO otp_codes (email, purpose, code, expires_at) VALUES (?, ?, ?, DATE_ADD(?, INTERVAL ' . self::TTL_MINUTES . ' MINUTE))'
        );
        $now = date('Y-m-d H:i:s');
        $ins->execute([$email, $purpose, password_hash($code, PASSWORD_BCRYPT), $now]);

        return $code;
    }

    public static function verify(string $email, string $purpose, string $submittedCode): bool
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT * FROM otp_codes WHERE email = ? AND purpose = ? AND verified_at IS NULL ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$email, $purpose]);
        $row = $stmt->fetch();

        if (!$row || $row['attempts'] >= self::MAX_ATTEMPTS || strtotime($row['expires_at']) < time()) {
            return false;
        }

        if (!password_verify($submittedCode, $row['code'])) {
            $upd = $pdo->prepare('UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?');
            $upd->execute([$row['id']]);
            return false;
        }

        $upd = $pdo->prepare('UPDATE otp_codes SET verified_at = NOW() WHERE id = ?');
        $upd->execute([$row['id']]);

        return true;
    }
}
