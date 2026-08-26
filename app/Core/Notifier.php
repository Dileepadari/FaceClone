<?php
namespace App\Core;

use App\Models\Notification;
use App\Models\UserSetting;

/**
 * Single entry point for raising notifications. Respects the recipient's
 * notification preferences and never notifies someone about their own action.
 */
final class Notifier
{
    public static function send(int $recipientId, int $actorId, string $type, ?string $entityType = null, ?int $entityId = null, ?string $url = null): void
    {
        if ($recipientId === $actorId || $recipientId <= 0) {
            return;
        }
        if (!self::wants($recipientId, $type)) {
            return;
        }
        Notification::create($recipientId, $actorId, $type, $entityType, $entityId, $url);
    }

    private static function wants(int $userId, string $type): bool
    {
        $settings = UserSetting::forUser($userId);
        return match (true) {
            str_starts_with($type, 'reaction')            => (bool) $settings['notify_reactions'],
            str_starts_with($type, 'comment')             => (bool) $settings['notify_comments'],
            str_starts_with($type, 'friend')              => (bool) $settings['notify_friends'],
            str_starts_with($type, 'message')             => (bool) $settings['notify_messages'],
            str_starts_with($type, 'group')               => (bool) $settings['notify_groups'],
            default                                       => true,
        };
    }
}
