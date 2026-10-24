<?php

require_once __DIR__ . '/../../core/Database.php';

class SessionMember
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM session_members WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $tableSessionId, ?int $customerId, string $displayName, bool $isHost): int
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'INSERT INTO session_members (table_session_id, customer_id, display_name, is_host, joined_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$tableSessionId, $customerId, $displayName, $isHost ? 1 : 0, date('Y-m-d H:i:s')]);
        return (int)$pdo->lastInsertId();
    }

    public static function allBySession(int $tableSessionId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT * FROM session_members WHERE table_session_id = ? ORDER BY is_host DESC, joined_at'
        );
        $stmt->execute([$tableSessionId]);
        return $stmt->fetchAll();
    }

    public static function belongsToSession(int $memberId, int $tableSessionId): bool
    {
        $stmt = Database::connect()->prepare(
            'SELECT COUNT(*) FROM session_members WHERE id = ? AND table_session_id = ?'
        );
        $stmt->execute([$memberId, $tableSessionId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
