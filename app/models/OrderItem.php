<?php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/Order.php';

class OrderItem
{
    public static function createMany(int $orderId, array $cartLines): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, menu_item_id, category_id, quantity, price_at_order, notes, status, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, "placed", ?)'
        );
        $now = date('Y-m-d H:i:s');
        foreach ($cartLines as $line) {
            $stmt->execute([
                $orderId,
                $line['menu_item_id'],
                $line['category_id'],
                $line['quantity'],
                $line['price'],
                $line['notes'] ?? null,
                $now,
            ]);
        }
    }

    public static function allByOrder(int $orderId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT oi.*, mi.name, mi.is_veg FROM order_items oi
             JOIN menu_items mi ON mi.id = oi.menu_item_id
             WHERE oi.order_id = ?'
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Active board: everything not yet ready, grouped by category.
     * Scoped to sessions that are still open — a session staff force-closed (or that
     * finished and got paid) must drop off the board, otherwise its un-prepared items
     * would sit in the kitchen queue forever with no way to clear them.
     */
    public static function activeQueueByCategory(): array
    {
        $rows = Database::connect()->query(
            "SELECT oi.*, mi.name, mi.is_veg, mc.name AS category_name, mc.display_order,
                    t.table_number, o.table_session_id
             FROM order_items oi
             JOIN menu_items mi ON mi.id = oi.menu_item_id
             JOIN menu_categories mc ON mc.id = oi.category_id
             JOIN orders o ON o.id = oi.order_id
             JOIN table_sessions ts ON ts.id = o.table_session_id
             JOIN restaurant_tables t ON t.id = ts.table_id
             WHERE oi.status IN ('placed','preparing') AND ts.status = 'active'
             ORDER BY mc.display_order, oi.updated_at"
        )->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['category_id']]['category_name'] = $row['category_name'];
            $grouped[$row['category_id']]['items'][] = $row;
        }
        return $grouped;
    }

    public static function recentlyReady(int $limit = 15): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT oi.*, mi.name, t.table_number
             FROM order_items oi
             JOIN menu_items mi ON mi.id = oi.menu_item_id
             JOIN orders o ON o.id = oi.order_id
             JOIN table_sessions ts ON ts.id = o.table_session_id
             JOIN restaurant_tables t ON t.id = ts.table_id
             WHERE oi.status = 'ready' AND ts.status = 'active'
             ORDER BY oi.updated_at DESC
             LIMIT " . (int)$limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM order_items WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    private const NEXT_STATUS = ['placed' => 'preparing', 'preparing' => 'ready'];

    /** True when this item's table session is still open (a closed session's items are frozen). */
    private static function belongsToActiveSession(int $id): bool
    {
        $stmt = Database::connect()->prepare(
            "SELECT COUNT(*) FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             JOIN table_sessions ts ON ts.id = o.table_session_id
             WHERE oi.id = ? AND ts.status = 'active'"
        );
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Advance one step forward (placed -> preparing -> ready); returns false if already at 'ready'. */
    public static function advanceStatus(int $id): bool
    {
        $item = self::find($id);
        if (!$item || !isset(self::NEXT_STATUS[$item['status']])) {
            return false;
        }
        // A board left open in a browser tab can still POST after staff force-closed the
        // session; don't let it mutate a session that's already been settled/closed.
        if (!self::belongsToActiveSession($id)) {
            return false;
        }

        $next = self::NEXT_STATUS[$item['status']];
        $stmt = Database::connect()->prepare('UPDATE order_items SET status = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$next, date('Y-m-d H:i:s'), $id]);

        Order::refreshStatusFromItems((int)$item['order_id']);

        return true;
    }
}
