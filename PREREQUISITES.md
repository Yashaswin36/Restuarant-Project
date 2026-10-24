# Prerequisites & Setup — Restaurant Table Reservation & Ordering (Windows)

Everything below is Windows-specific. This project is plain PHP (custom MVC) with Composer dependencies for email (OTP delivery) and QR code generation.

## 1. Install XAMPP

1. Download XAMPP with **PHP 8.1 or newer** from https://www.apachefriends.org (default install path `C:\xampp`).
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Add `C:\xampp\php` and `C:\xampp\mysql\bin` to your system `Path`, then confirm `php -v` / `mysql --version` in a new terminal.

## 2. Install Composer

Download and run `Composer-Setup.exe` from https://getcomposer.org/download/, then confirm with `composer -V`.

## 3. Enable required PHP extensions

Needed for `phpmailer/phpmailer` (OTP email) and `chillerlan/php-qrcode` (QR image generation, which needs `gd`):

1. Edit `C:\xampp\php\php.ini`.
2. Uncomment:
   ```
   extension=gd
   ```
3. Save and restart Apache from the XAMPP Control Panel.

## 4. Configure MySQL/MariaDB port

`config/database.php` is committed with `port => 3306` — XAMPP's own default MariaDB port, so no change should be needed on a standard XAMPP install. Only edit `config/database.php` if your local MySQL runs on a different port.

## 5. Install project dependencies

```
composer install
```

## 6. Database setup

```
mysql -u root -P 3306 < database/schema.sql
```
(adjust `-P` to your port). Creates the `restaurant_table_reservation_ordering` database, tables, and seed data.

## 7. Configure outgoing email (required — customer login is OTP-only)

This project's Customer persona logs in via **email OTP, not a password**, so SMTP must work for the app to be demoable end-to-end.

1. Sign up for a free test inbox at https://mailtrap.io (or any test SMTP provider).
2. Edit `config/mail.php` with that inbox's SMTP host/port/username/password.

Until configured, `core/Mailer.php` fails fast after an 8-second timeout — the OTP is still generated and stored, but never reaches an inbox, so the booking/table-join flows can't be demoed without this step.

## 8. Verify everything

```
php -v
composer -V
mysql -u root -h 127.0.0.1 -P 3306 -e "SELECT 1;"
```
Ready — see `HOW-TO-RUN.md` to start the app.
