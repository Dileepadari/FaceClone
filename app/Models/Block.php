<?php
namespace App\Models;

final class Block extends Model
{
    public static function exists(int $userId, int $blockedId): bool
    {
        return (bool) self::db()->value(
            'SELECT 1 FROM blocks WHERE user_id = ? AND blocked_id = ? LIMIT 1',
            [$userId, $blockedId]
        );
    }

    /** Blocking also tears down the friendship and both follow edges. */
    public static function add(int $userId, int $blockedId): void
    {
        if ($userId === $blockedId) {
            return;
        }
        self::db()->execute(
            'INSERT INTO blocks (user_id, blocked_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_at = created_at',
            [$userId, $blockedId]
        );
        Friendship::unfriend($userId, $blockedId);
        self::db()->execute(
            'DELETE FROM friendships WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)',
            [$userId, $blockedId, $blockedId, $userId]
        );
        Follow::remove($userId, $blockedId);
        Follow::remove($blockedId, $userId);
    }

    public static function remove(int $userId, int $blockedId): void
    {
        self::db()->execute('DELETE FROM blocks WHERE user_id = ? AND blocked_id = ?', [$userId, $blockedId]);
    }

    /** @return array<int,array> */
    public static function listFor(int $userId): array
    {
        return self::db()->all(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . ', b.created_at AS blocked_at
             FROM blocks b JOIN users u ON u.id = b.blocked_id
             WHERE b.user_id = ? ORDER BY b.created_at DESC',
            [$userId]
        );
    }

    /** @return array<int,int> Everyone hidden from $userId in either direction. */
    public static function hiddenIds(int $userId): array
    {
        return array_map('intval', self::db()->column(
            'SELECT blocked_id FROM blocks WHERE user_id = ?
             UNION SELECT user_id FROM blocks WHERE blocked_id = ?',
            [$userId, $userId]
        ));
    }
}
