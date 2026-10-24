<?php

require_once __DIR__ . '/../../core/Database.php';

class MenuCategory
{
    public static function all(): array
    {
        return Database::connect()->query('SELECT * FROM menu_categories ORDER BY display_order, name')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM menu_categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, int $order): int
    {
        $stmt = Database::connect()->prepare('INSERT INTO menu_categories (name, display_order) VALUES (?, ?)');
        $stmt->execute([$name, $order]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, string $name, int $order): void
    {
        $stmt = Database::connect()->prepare('UPDATE menu_categories SET name = ?, display_order = ? WHERE id = ?');
        $stmt->execute([$name, $order, $id]);
    }

    /**
     * True if any order has ever referenced this category — either directly (order_items.category_id)
     * or through one of its menu items (order_items.menu_item_id). order_items has ON DELETE RESTRICT
     * on both columns, so deleting a category with history would otherwise throw an uncaught PDOException.
     */
    public static function hasOrderHistory(int $id): bool
    {
        $stmt = Database::connect()->prepare(
            'SELECT COUNT(*) FROM order_items oi
             LEFT JOIN menu_items mi ON mi.id = oi.menu_item_id
             WHERE oi.category_id = ? OR mi.category_id = ?'
        );
        $stmt->execute([$id, $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connect()->prepare('DELETE FROM menu_categories WHERE id = ?');
        $stmt->execute([$id]);
    }
}
