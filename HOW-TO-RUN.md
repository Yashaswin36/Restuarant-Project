# How to Run — Restaurant Table Reservation & Ordering

Assumes prerequisites in `PREREQUISITES.md` are already done.

## One-time setup

```
composer install
mysql -u root -P 3306 < database/schema.sql
```

To wipe the seeded data and start over, drop the database first, then re-run the import above (re-running it directly without dropping first fails with "table already exists"):
```
mysql -u root -P 3306 -e "DROP DATABASE IF EXISTS restaurant_table_reservation_ordering;"
```

Then configure `config/mail.php` with SMTP credentials (e.g. Mailtrap) — **required**, since the Customer persona logs in via emailed OTP rather than a password.

## Start MySQL

Open **XAMPP Control Panel** and make sure **MySQL** is running.

## Run the app

From this project's folder:
```
php -S localhost:8004 -t public public/index.php
```
(`-t public` is required for static assets to load.)

## Open it

- **Guest/customer flow**: visit http://localhost:8004/ — the homepage is the guest-facing landing page (book a table / find a booking).
- **Staff/kitchen/admin login**: click "Staff Login" on the homepage, or go directly to http://localhost:8004/index.php?route=login, and sign in with the demo credentials in `README.md` under "Default Login Credentials".
- **Try the QR flow**: log in as Super Admin → Manage Tables → open a table's QR code / join link in a new tab to simulate a guest scanning it.

## Stop the app

`Ctrl+C` in the terminal running the `php -S` command.
