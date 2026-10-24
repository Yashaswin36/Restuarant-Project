<?php

require_once __DIR__ . '/../../core/Database.php';

class Order
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $tableSessionId, int $sessionMemberId): int
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'INSERT INTO orders (table_session_id, session_member_id, status, placed_at) VALUES (?, ?, "placed", ?)'
        );
        $stmt->execute([$tableSessionId, $sessionMemberId, date('Y-m-d H:i:s')]);
        return (int)$pdo->lastInsertId();
    }

    public static function allBySessionMember(int $sessionMemberId): array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM orders WHERE session_member_id = ? ORDER BY placed_at DESC');
        $stmt->execute([$sessionMemberId]);
        return $stmt->fetchAll();
    }

    public static function allBySessionWithMember(int $tableSessionId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT o.*, sm.display_name FROM orders o
             JOIN session_members sm ON sm.id = o.session_member_id
             WHERE o.table_session_id = ?
             ORDER BY o.placed_at'
        );
        $stmt->execute([$tableSessionId]);
        return $stmt->fetchAll();
    }

    /** Recompute this order's overall status from its items: ready only once every item is ready. */
    public static function refreshStatusFromItems(int $orderId): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT status FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($statuses)) {
            return;
        }

        if (count(array_unique($statuses)) === 1 && $statuses[0] === 'ready') {
            $newStatus = 'ready';
        } elseif (in_array('preparing', $statuses, true) || in_array('ready', $statuses, true)) {
            $newStatus = 'preparing';
        } else {
            $newStatus = 'placed';
        }

        $current = self::find($orderId);
        if ($current && $current['status'] === 'served') {
            return; // served is a manual staff step past "ready" — don't regress it
        }

        $upd = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $upd->execute([$newStatus, $orderId]);
    }

    /** Scoped to the session the staff member is actually looking at, so an id from another table can't be served from here. */
    public static function markServed(int $orderId, int $tableSessionId): bool
    {
        $stmt = Database::connect()->prepare(
            'UPDATE orders SET status = "served" WHERE id = ? AND table_session_id = ? AND status = "ready"'
        );
        $stmt->execute([$orderId, $tableSessionId]);
        return $stmt->rowCount() > 0;
    }
}
