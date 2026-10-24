<?php
// Test SMTP (e.g. Mailtrap) for sending real OTP emails — see README setup step.
// SMTP password is not stored here — it's loaded from a gitignored,
// project-local .env (MAIL_USERNAME / MAIL_PASSWORD) so it never gets committed.
$mailEnv = [];
$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
    $mailEnv = parse_ini_file($envFile) ?: [];
}

return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'username'   => $mailEnv['MAIL_USERNAME'] ?? 'your-email@gmail.com',
    'password'   => $mailEnv['MAIL_PASSWORD'] ?? '',
    'encryption' => 'tls',
    'from_email' => $mailEnv['MAIL_USERNAME'] ?? 'your-email@gmail.com',
    'from_name'  => 'TableFare',
];
