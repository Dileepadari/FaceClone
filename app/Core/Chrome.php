<?php
namespace App\Core;

use App\Models\Notification;
use App\Models\Message;
use App\Models\Friendship;

/**
 * Counters the persistent top bar needs on every page. Cached per request so
 * a page render never queries these twice.
 */
final class Chrome
{
    private static array $cache = [];

    public static function forUser(int $userId): array
    {
        if (isset(self::$cache[$userId])) {
            return self::$cache[$userId];
        }
        return self::$cache[$userId] = [
            'unread_notifications' => Notification::unreadCount($userId),
            'unread_messages'      => Message::unreadCount($userId),
            'friend_requests'      => Friendship::pendingCount($userId),
        ];
    }
}
