<?php
namespace App\Models;

final class SavedPost extends Model
{
    public static function toggle(int $userId, int $postId): bool
    {
        if (self::exists($userId, $postId)) {
            self::db()->execute('DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?', [$userId, $postId]);
            return false;
        }
        self::db()->execute(
            'INSERT INTO saved_posts (user_id, post_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_at = NOW()',
            [$userId, $postId]
        );
        return true;
    }

    public static function exists(int $userId, int $postId): bool
    {
        return (bool) self::db()->value(
            'SELECT 1 FROM saved_posts WHERE user_id = ? AND post_id = ? LIMIT 1',
            [$userId, $postId]
        );
    }

    public static function count(int $userId): int
    {
        return (int) self::db()->value('SELECT COUNT(*) FROM saved_posts WHERE user_id = ?', [$userId], 0);
    }
}
