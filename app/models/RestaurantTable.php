<?php

require_once __DIR__ . '/../../core/Database.php';

class RestaurantTable
{
    public static function all(): array
    {
        return Database::connect()->query('SELECT * FROM restaurant_tables ORDER BY table_number')->fetchAll();
    }

    public static function allActive(): array
    {
        return Database::connect()->query(
            "SELECT * FROM restaurant_tables WHERE status = 'active' ORDER BY table_number"
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM restaurant_tables WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::connect()->prepare("SELECT * FROM restaurant_tables WHERE table_code = ? AND status = 'active'");
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $number, int $capacity, string $zone): int
    {
        $code = self::generateCode();
        $stmt = Database::connect()->prepare(
            'INSERT INTO restaurant_tables (table_number, capacity, zone, table_code, status) VALUES (?, ?, ?, ?, "active")'
        );
        $stmt->execute([$number, $capacity, $zone, $code]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, string $number, int $capacity, string $zone): void
    {
        $stmt = Database::connect()->prepare(
            'UPDATE restaurant_tables SET table_number = ?, capacity = ?, zone = ? WHERE id = ?'
        );
        $stmt->execute([$number, $capacity, $zone, $id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connect()->prepare('UPDATE restaurant_tables SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function numberExists(string $number, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM restaurant_tables WHERE table_number = ? AND id != ?');
            $stmt->execute([$number, $excludeId]);
        } else {
            $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM restaurant_tables WHERE table_number = ?');
            $stmt->execute([$number]);
        }
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function generateCode(): string
    {
        return 'qr-' . bin2hex(random_bytes(8));
    }
}
