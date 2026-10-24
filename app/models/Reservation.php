<?php

require_once __DIR__ . '/../../core/Database.php';

class Reservation
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM reservations WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $customerId, string $date, string $time, int $partySize, ?string $requests): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO reservations (customer_id, reservation_date, reservation_time, party_size, status, special_requests)
             VALUES (?, ?, ?, ?, "pending", ?)'
        );
        $stmt->execute([$customerId, $date, $time, $partySize, $requests]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function allByCustomer(int $customerId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT r.*, t.table_number FROM reservations r
             LEFT JOIN restaurant_tables t ON t.id = r.table_id
             WHERE r.customer_id = ?
             ORDER BY r.reservation_date DESC, r.reservation_time DESC'
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public static function belongsToCustomer(int $id, int $customerId): bool
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM reservations WHERE id = ? AND customer_id = ?');
        $stmt->execute([$id, $customerId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function allWithDetails(): array
    {
        return Database::connect()->query(
            'SELECT r.*, c.name AS customer_name, c.email AS customer_email, t.table_number
             FROM reservations r
             JOIN customers c ON c.id = r.customer_id
             LEFT JOIN restaurant_tables t ON t.id = r.table_id
             ORDER BY r.reservation_date, r.reservation_time'
        )->fetchAll();
    }

    public static function todayQueue(string $today): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT r.*, c.name AS customer_name, c.email AS customer_email, t.table_number
             FROM reservations r
             JOIN customers c ON c.id = r.customer_id
             LEFT JOIN restaurant_tables t ON t.id = r.table_id
             WHERE r.reservation_date = ? AND r.status NOT IN ('completed','cancelled')
             ORDER BY r.reservation_time"
        );
        $stmt->execute([$today]);
        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connect()->prepare('UPDATE reservations SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function seatAtTable(int $id, int $tableId): void
    {
        $stmt = Database::connect()->prepare('UPDATE reservations SET table_id = ?, status = "seated" WHERE id = ?');
        $stmt->execute([$tableId, $id]);
    }

    /** Any OTHER reservation already seated at this table (excludes $excludeId, so re-confirming the same one isn't blocked). */
    public static function otherSeatedAtTable(int $tableId, int $excludeId): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT * FROM reservations WHERE table_id = ? AND status = 'seated' AND id != ? LIMIT 1"
        );
        $stmt->execute([$tableId, $excludeId]);
        return $stmt->fetch() ?: null;
    }

    public static function countByStatus(string $status): int
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM reservations WHERE status = ?');
        $stmt->execute([$status]);
        return (int)$stmt->fetchColumn();
    }
}
