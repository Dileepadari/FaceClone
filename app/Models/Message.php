<?php
namespace App\Models;

final class Message extends Model
{
    public static function send(int $conversationId, int $senderId, ?string $body, ?string $attachment = null): int
    {
        $id = self::db()->insert(
            'INSERT INTO messages (conversation_id, sender_id, body, attachment) VALUES (?, ?, ?, ?)',
            [$conversationId, $senderId, $body ?: null, $attachment]
        );
        Conversation::touch($conversationId);
        Conversation::markRead($conversationId, $senderId);
        return $id;
    }

    public static function find(int $id): ?array
    {
        return self::db()->first('SELECT * FROM messages WHERE id = ? LIMIT 1', [$id]);
    }

    /**
     * Thread messages. Pass $afterId to fetch only what arrived since the last
     * poll, which is how the open thread stays live.
     */
    public static function thread(int $conversationId, int $limit = 60, int $afterId = 0): array
    {
        if ($afterId > 0) {
            $rows = self::db()->all(
                'SELECT * FROM messages WHERE conversation_id = ? AND id > ? ORDER BY id ASC LIMIT ?',
                [$conversationId, $afterId, $limit]
            );
        } else {
            $rows = array_reverse(self::db()->all(
                'SELECT * FROM messages WHERE conversation_id = ? ORDER BY id DESC LIMIT ?',
                [$conversationId, $limit]
            ));
        }

        $senders = User::findMany(array_column($rows, 'sender_id'));
        foreach ($rows as &$row) {
            $row['sender'] = $senders[(int) $row['sender_id']] ?? null;
        }
        unset($row);

        return $rows;
    }

    /** Number of conversations with something unread, matching Facebook's badge. */
    public static function unreadCount(int $userId): int
    {
        return (int) self::db()->value(
            'SELECT COUNT(*) FROM (
                SELECT c.id
                FROM conversation_participants p
                JOIN conversations c ON c.id = p.conversation_id
                JOIN messages m ON m.conversation_id = c.id
                WHERE p.user_id = ? AND m.sender_id <> ?
                  AND (p.last_read_at IS NULL OR m.created_at > p.last_read_at)
                GROUP BY c.id
             ) t',
            [$userId, $userId],
            0
        );
    }

    /** Who has read up to this point, for the small read-receipt avatars. */
    public static function readReceipts(int $conversationId, int $viewerId): array
    {
        return self::db()->all(
            'SELECT u.id, u.first_name, u.last_name, u.avatar, u.username, p.last_read_at
             FROM conversation_participants p JOIN users u ON u.id = p.user_id
             WHERE p.conversation_id = ? AND p.user_id <> ? AND p.last_read_at IS NOT NULL',
            [$conversationId, $viewerId]
        );
    }
}
