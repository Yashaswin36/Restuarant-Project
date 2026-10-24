<?php

require_once __DIR__ . '/../../core/Database.php';

class Bill
{
    private const TAX_RATE = 0.05;

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM bills WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function allBySession(int $tableSessionId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT b.*, sm.display_name FROM bills b
             LEFT JOIN session_members sm ON sm.id = b.session_member_id
             WHERE b.table_session_id = ?
             ORDER BY b.id'
        );
        $stmt->execute([$tableSessionId]);
        return $stmt->fetchAll();
    }

    public static function existsForSession(int $tableSessionId): bool
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM bills WHERE table_session_id = ?');
        $stmt->execute([$tableSessionId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function subtotalForSession(int $tableSessionId, ?int $sessionMemberId = null): float
    {
        $sql = 'SELECT COALESCE(SUM(oi.quantity * oi.price_at_order), 0)
                FROM order_items oi JOIN orders o ON o.id = oi.order_id
                WHERE o.table_session_id = ?';
        $params = [$tableSessionId];
        if ($sessionMemberId !== null) {
            $sql .= ' AND o.session_member_id = ?';
            $params[] = $sessionMemberId;
        }
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    public static function generateCombined(int $tableSessionId, int $staffUserId): int
    {
        $subtotal = self::subtotalForSession($tableSessionId);
        return self::insert($tableSessionId, 'combined', null, $subtotal, $staffUserId);
    }

    /** One bill per member who has at least one order; returns the new bill IDs. */
    public static function generateSplit(int $tableSessionId, int $staffUserId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT DISTINCT session_member_id FROM orders WHERE table_session_id = ?'
        );
        $stmt->execute([$tableSessionId]);
        $memberIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $billIds = [];
        foreach ($memberIds as $memberId) {
            $subtotal = self::subtotalForSession($tableSessionId, (int)$memberId);
            if ($subtotal > 0) {
                $billIds[] = self::insert($tableSessionId, 'split', (int)$memberId, $subtotal, $staffUserId);
            }
        }
        return $billIds;
    }

    private static function insert(int $tableSessionId, string $type, ?int $memberId, float $subtotal, int $staffUserId): int
    {
        $tax = round($subtotal * self::TAX_RATE, 2);
        $total = round($subtotal + $tax, 2);

        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'INSERT INTO bills (table_session_id, bill_type, session_member_id, subtotal, tax_amount, total_amount, status, generated_by, generated_at)
             VALUES (?, ?, ?, ?, ?, ?, "unpaid", ?, ?)'
        );
        $stmt->execute([$tableSessionId, $type, $memberId, $subtotal, $tax, $total, $staffUserId, date('Y-m-d H:i:s')]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Settle one bill. Scoped to its own session and to status='unpaid', so two staff
     * tablets clicking "Mark Paid" on the same bill can't both record a payment (the
     * second UPDATE matches no rows and returns false).
     */
    public static function markPaid(int $id, int $tableSessionId): bool
    {
        $stmt = Database::connect()->prepare(
            'UPDATE bills SET status = "paid", paid_at = ?
             WHERE id = ? AND table_session_id = ? AND status = "unpaid"'
        );
        $stmt->execute([date('Y-m-d H:i:s'), $id, $tableSessionId]);
        return $stmt->rowCount() > 0;
    }

    public static function unpaidCountForSession(int $tableSessionId): int
    {
        $stmt = Database::connect()->prepare(
            'SELECT COUNT(*) FROM bills WHERE table_session_id = ? AND status = "unpaid"'
        );
        $stmt->execute([$tableSessionId]);
        return (int)$stmt->fetchColumn();
    }

    public static function isFullyPaid(int $tableSessionId): bool
    {
        return self::unpaidCountForSession($tableSessionId) === 0;
    }

    public static function revenueStats(): array
    {
        $pdo = Database::connect();
        return [
            'total_revenue' => (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE status = 'paid'")->fetchColumn(),
            'unpaid_amount' => (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE status = 'unpaid'")->fetchColumn(),
            'bill_count'    => (int)$pdo->query('SELECT COUNT(*) FROM bills')->fetchColumn(),
        ];
    }

    public static function popularItems(int $limit = 8): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT mi.name, mc.name AS category_name, SUM(oi.quantity) AS total_qty,
                    SUM(oi.quantity * oi.price_at_order) AS total_revenue
             FROM order_items oi
             JOIN menu_items mi ON mi.id = oi.menu_item_id
             JOIN menu_categories mc ON mc.id = oi.category_id
             GROUP BY oi.menu_item_id
             ORDER BY total_qty DESC
             LIMIT ' . (int)$limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
