<?php
namespace App\Models;

final class Follow extends Model
{
    public static function add(int $follower, int $followee): void
    {
        if ($follower === $followee) {
            return;
        }
        self::db()->execute(
            'INSERT INTO follows (follower_id, followee_id) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE created_at = created_at',
            [$follower, $followee]
        );
    }

    public static function remove(int $follower, int $followee): void
    {
        self::db()->execute('DELETE FROM follows WHERE follower_id = ? AND followee_id = ?', [$follower, $followee]);
    }

    public static function toggle(int $follower, int $followee): bool
    {
        if (self::exists($follower, $followee)) {
            self::remove($follower, $followee);
            return false;
        }
        self::add($follower, $followee);
        return true;
    }

    public static function exists(int $follower, int $followee): bool
    {
        return (bool) self::db()->value(
            'SELECT 1 FROM follows WHERE follower_id = ? AND followee_id = ? LIMIT 1',
            [$follower, $followee]
        );
    }

    public static function followerCount(int $userId): int
    {
        return (int) self::db()->value('SELECT COUNT(*) FROM follows WHERE followee_id = ?', [$userId], 0);
    }

    public static function followingCount(int $userId): int
    {
        return (int) self::db()->value('SELECT COUNT(*) FROM follows WHERE follower_id = ?', [$userId], 0);
    }

    /** @return array<int,array> */
    public static function followers(int $userId, int $limit = 100): array
    {
        return self::db()->all(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . '
             FROM follows f JOIN users u ON u.id = f.follower_id
             WHERE f.followee_id = ? AND u.is_active = 1
             ORDER BY f.created_at DESC LIMIT ?',
            [$userId, $limit]
        );
    }

    /** @return array<int,array> */
    public static function following(int $userId, int $limit = 100): array
    {
        return self::db()->all(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . '
             FROM follows f JOIN users u ON u.id = f.followee_id
             WHERE f.follower_id = ? AND u.is_active = 1
             ORDER BY f.created_at DESC LIMIT ?',
            [$userId, $limit]
        );
    }
}
