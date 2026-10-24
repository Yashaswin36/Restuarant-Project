<?php

require_once __DIR__ . '/../../core/Database.php';

class TableSession
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM table_sessions WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function activeForTable(int $tableId): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT * FROM table_sessions WHERE table_id = ? AND status = 'active' LIMIT 1"
        );
        $stmt->execute([$tableId]);
        return $stmt->fetch() ?: null;
    }

    /** Only ever matches an ACTIVE session on the given table — codes from closed sessions can't be reused. */
    public static function findActiveByTableAndCode(int $tableId, string $code): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT * FROM table_sessions WHERE table_id = ? AND session_code = ? AND status = 'active' LIMIT 1"
        );
        $stmt->execute([$tableId, strtoupper($code)]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $tableId, int $hostCustomerId): int
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'INSERT INTO table_sessions (table_id, host_customer_id, session_code, status, started_at)
             VALUES (?, ?, ?, "active", ?)'
        );
        $stmt->execute([$tableId, $hostCustomerId, self::generateCode(), date('Y-m-d H:i:s')]);
        return (int)$pdo->lastInsertId();
    }

    public static function close(int $id): void
    {
        $stmt = Database::connect()->prepare(
            'UPDATE table_sessions SET status = "closed", closed_at = ? WHERE id = ?'
        );
        $stmt->execute([date('Y-m-d H:i:s'), $id]);
    }

    public static function allActiveWithDetails(): array
    {
        return Database::connect()->query(
            "SELECT ts.*, t.table_number, t.zone,
                    (SELECT COUNT(*) FROM session_members sm WHERE sm.table_session_id = ts.id) AS member_count
             FROM table_sessions ts
             JOIN restaurant_tables t ON t.id = ts.table_id
             WHERE ts.status = 'active'
             ORDER BY t.table_number"
        )->fetchAll();
    }

    public static function findWithTable(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT ts.*, t.table_number, t.zone FROM table_sessions ts
             JOIN restaurant_tables t ON t.id = ts.table_id
             WHERE ts.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    private static function generateCode(): string
    {
        // Unambiguous alphabet (no 0/O/1/I) — read aloud at a table, so it needs to be easy to say and hear.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $code;
    }
}
