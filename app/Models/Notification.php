<?php
namespace App\Models;

final class Notification extends Model
{
    public static function create(int $userId, int $actorId, string $type, ?string $entityType, ?int $entityId, ?string $url): int
    {
        return self::db()->insert(
            'INSERT INTO notifications (user_id, actor_id, type, entity_type, entity_id, url) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $actorId, $type, $entityType, $entityId, $url]
        );
    }

    /** @return array<int,array> */
    public static function forUser(int $userId, int $limit = 30, int $offset = 0): array
    {
        $rows = self::db()->all(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$userId, $limit, $offset]
        );
        $actors = User::findMany(array_column($rows, 'actor_id'));
        foreach ($rows as &$row) {
            $row['actor'] = $actors[(int) $row['actor_id']] ?? null;
            $row['text']  = self::describe($row);
        }
        unset($row);
        return $rows;
    }

    public static function unreadCount(int $userId): int
    {
        return (int) self::db()->value(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId],
            0
        );
    }

    public static function markRead(int $id, int $userId): void
    {
        self::db()->execute('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function markAllRead(int $userId): void
    {
        self::db()->execute('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0', [$userId]);
    }

    public static function delete(int $id, int $userId): void
    {
        self::db()->execute('DELETE FROM notifications WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function find(int $id, int $userId): ?array
    {
        return self::db()->first('SELECT * FROM notifications WHERE id = ? AND user_id = ? LIMIT 1', [$id, $userId]);
    }

    /** Human sentence for a notification row. */
    private static function describe(array $row): string
    {
        $actor = full_name($row['actor'] ?? null);
        return match ($row['type']) {
            'reaction_post'     => "$actor reacted to your post.",
            'reaction_comment'  => "$actor reacted to your comment.",
            'comment_post'      => "$actor commented on your post.",
            'comment_reply'     => "$actor replied to your comment.",
            'comment_also'      => "$actor also commented on a post you commented on.",
            'friend_request'    => "$actor sent you a friend request.",
            'friend_accepted'   => "$actor accepted your friend request.",
            'post_share'        => "$actor shared your post.",
            'group_invite'      => "$actor invited you to a group.",
            'group_join_request'=> "$actor asked to join your group.",
            'group_approved'    => "$actor approved your request to join a group.",
            'group_post'        => "$actor posted in a group you are in.",
            'message_new'       => "$actor sent you a message.",
            'story_reaction'    => "$actor reacted to your story.",
            'event_invite'      => "$actor invited you to an event.",
            default             => "$actor interacted with your content.",
        };
    }

    /** Icon key used by the notification row. */
    public static function iconFor(string $type): string
    {
        return match (true) {
            str_starts_with($type, 'reaction') => 'like',
            str_starts_with($type, 'comment')  => 'comment',
            str_starts_with($type, 'friend')   => 'friends',
            str_starts_with($type, 'group')    => 'groups',
            str_starts_with($type, 'message')  => 'messenger',
            str_starts_with($type, 'event')    => 'calendar',
            $type === 'post_share'             => 'share',
            default                            => 'bell',
        };
    }
}
