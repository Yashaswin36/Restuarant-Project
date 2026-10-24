<?php

require_once __DIR__ . '/../../core/Database.php';

class MenuItem
{
    public static function allWithCategory(): array
    {
        return Database::connect()->query(
            'SELECT mi.*, mc.name AS category_name FROM menu_items mi
             JOIN menu_categories mc ON mc.id = mi.category_id
             ORDER BY mc.display_order, mi.name'
        )->fetchAll();
    }

    /** Grouped by category, available items only, for the customer-facing menu. */
    public static function availableGroupedByCategory(): array
    {
        $rows = Database::connect()->query(
            "SELECT mi.*, mc.name AS category_name FROM menu_items mi
             JOIN menu_categories mc ON mc.id = mi.category_id
             WHERE mi.is_available = 1
             ORDER BY mc.display_order, mi.name"
        )->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['category_id']]['category_name'] = $row['category_name'];
            $grouped[$row['category_id']]['items'][] = $row;
        }
        return $grouped;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM menu_items WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findAvailable(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM menu_items WHERE id = ? AND is_available = 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO menu_items (category_id, name, description, price, is_veg, is_available)
             VALUES (:category_id, :name, :description, :price, :is_veg, :is_available)'
        );
        $stmt->execute($data);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = Database::connect()->prepare(
            'UPDATE menu_items SET category_id = :category_id, name = :name, description = :description,
                    price = :price, is_veg = :is_veg, is_available = :is_available
             WHERE id = :id'
        );
        $stmt->execute($data);
    }

    /**
     * True if this item has ever been ordered. order_items.menu_item_id is ON DELETE RESTRICT,
     * so deleting an item with history would otherwise throw an uncaught PDOException.
     */
    public static function hasOrderHistory(int $id): bool
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM order_items WHERE menu_item_id = ?');
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connect()->prepare('DELETE FROM menu_items WHERE id = ?');
        $stmt->execute([$id]);
    }
}
