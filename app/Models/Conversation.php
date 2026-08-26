<?php
namespace App\Models;

final class Conversation extends Model
{
    public static function find(int $id): ?array
    {
        return self::db()->first('SELECT * FROM conversations WHERE id = ? LIMIT 1', [$id]);
    }

    public static function isParticipant(int $conversationId, int $userId): bool
    {
        return (bool) self::db()->value(
            'SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND user_id = ? LIMIT 1',
            [$conversationId, $userId]
        );
    }

    /** Find the 1:1 conversation between two people, creating it if needed. */
    public static function between(int $a, int $b): int
    {
        $existing = self::db()->value(
            'SELECT c.id FROM conversations c
             JOIN conversation_participants p1 ON p1.conversation_id = c.id AND p1.user_id = ?
             JOIN conversation_participants p2 ON p2.conversation_id = c.id AND p2.user_id = ?
             WHERE c.is_group = 0
             LIMIT 1',
            [$a, $b]
        );
        if ($existing) {
            return (int) $existing;
        }

        return self::db()->transaction(static function () use ($a, $b): int {
            $id = self::db()->insert('INSERT INTO conversations (is_group) VALUES (0)');
            self::db()->execute(
                'INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)',
                [$id, $a, $id, $b]
            );
            return $id;
        });
    }

    public static function createGroup(string $name, array $userIds): int
    {
        return self::db()->transaction(static function () use ($name, $userIds): int {
            $id = self::db()->insert('INSERT INTO conversations (is_group, name) VALUES (1, ?)', [$name]);
            foreach (array_unique($userIds) as $uid) {
                self::db()->execute(
                    'INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)',
                    [$id, (int) $uid]
                );
            }
            return $id;
        });
    }

    /** @return array<int,array> Everyone in a conversation. */
    public static function participants(int $conversationId): array
    {
        return self::db()->all(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . ', p.last_read_at
             FROM conversation_participants p JOIN users u ON u.id = p.user_id
             WHERE p.conversation_id = ?',
            [$conversationId]
        );
    }

    /** The other person in a 1:1 conversation. */
    public static function partner(int $conversationId, int $viewerId): ?array
    {
        return self::db()->first(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . '
             FROM conversation_participants p JOIN users u ON u.id = p.user_id
             WHERE p.conversation_id = ? AND p.user_id <> ? LIMIT 1',
            [$conversationId, $viewerId]
        );
    }

    /**
     * Inbox list: newest activity first, with the last message, the other
     * party and an unread flag on each row.
     */
    public static function inbox(int $userId, int $limit = 40): array
    {
        $rows = self::db()->all(
            'SELECT c.id, c.is_group, c.name, c.updated_at,
                    p.last_read_at,
                    (SELECT m.body       FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body,
                    (SELECT m.attachment FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_attachment,
                    (SELECT m.sender_id  FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_sender_id,
                    (SELECT m.created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_at,
                    (SELECT COUNT(*) FROM messages m
                       WHERE m.conversation_id = c.id AND m.sender_id <> ?
                         AND (p.last_read_at IS NULL OR m.created_at > p.last_read_at)) AS unread
             FROM conversation_participants p
             JOIN conversations c ON c.id = p.conversation_id
             WHERE p.user_id = ?
             HAVING last_at IS NOT NULL OR c.is_group = 1
             ORDER BY COALESCE(last_at, c.created_at) DESC
             LIMIT ?',
            [$userId, $userId, $limit]
        );

        foreach ($rows as &$row) {
            $row['unread']  = (int) $row['unread'];
            $row['partner'] = $row['is_group'] ? null : self::partner((int) $row['id'], $userId);
            $row['title']   = $row['is_group'] ? ($row['name'] ?: 'Group chat') : full_name($row['partner']);
        }
        unset($row);

        return $rows;
    }

    public static function markRead(int $conversationId, int $userId): void
    {
        self::db()->execute(
            'UPDATE conversation_participants SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?',
            [$conversationId, $userId]
        );
    }

    public static function touch(int $conversationId): void
    {
        self::db()->execute('UPDATE conversations SET updated_at = NOW() WHERE id = ?', [$conversationId]);
    }
}
