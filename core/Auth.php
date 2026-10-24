<?php

require_once __DIR__ . '/Database.php';

/**
 * Password-based auth for the three internal roles: super_admin, staff, kitchen.
 * Customers never use this — see Otp.php / CustomerSession.php instead.
 */
class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    public static function name(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /index.php?route=login');
            exit;
        }

        // Deactivating an account must take effect immediately, not at that person's next
        // logout — a waiter sent home mid-shift keeps a live session cookie otherwise.
        $stmt = Database::connect()->prepare('SELECT status FROM users WHERE id = ?');
        $stmt->execute([self::id()]);
        if ($stmt->fetchColumn() !== 'active') {
            // Drop the identity but keep the session alive, so the explanation below actually
            // survives the redirect to the login page.
            unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name']);
            session_regenerate_id(true);
            flash_set('error', 'Your account is no longer active. Please contact the restaurant admin.');
            header('Location: /index.php?route=login');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            require __DIR__ . '/../app/views/errors/404.php';
            exit;
        }
    }

    public static function dashboardRoute(string $role): string
    {
        return match ($role) {
            'super_admin' => 'admin/dashboard',
            'staff'       => 'staff/dashboard',
            'kitchen'     => 'kitchen/queue',
            default       => 'login',
        };
    }
}
