<?php

require_once __DIR__ . '/../../core/Database.php';

class Customer
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM customers WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM customers WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Find-or-create by email — customers are recognized across visits, no password. */
    public static function findOrCreate(string $name, string $email): array
    {
        $existing = self::findByEmail($email);
        if ($existing) {
            return $existing;
        }

        $stmt = Database::connect()->prepare('INSERT INTO customers (name, email) VALUES (?, ?)');
        $stmt->execute([$name, $email]);
        return self::find((int)Database::connect()->lastInsertId());
    }
}
