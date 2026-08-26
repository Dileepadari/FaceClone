<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Upload;

/**
 * Stories expire 24 hours after they are posted. Nothing deletes them on a
 * timer - every read filters on expires_at, and prune() cleans up on demand.
 */
final class Story extends Model
{
    public static function create(int $userId, array $data): int
    {
        return self::db()->insert(
            'INSERT INTO stories (user_id, type, media, text, background, expires_at)
             VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))',
            [
                $userId,
                $data['type'],
                $data['media']      ?? null,
                $data['text']       ?? null,
                $data['background'] ?? null,
            ]
        );
    }

    public static function find(int $id): ?array
    {
        return self::db()->first('SELECT * FROM stories WHERE id = ? AND expires_at > NOW() LIMIT 1', [$id]);
    }

    public static function delete(int $id): void
    {
        $row = self::db()->first('SELECT * FROM stories WHERE id = ?', [$id]);
        if ($row) {
            Upload::delete($row['media'] ?? null);
            self::db()->execute('DELETE FROM stories WHERE id = ?', [$id]);
        }
    }

    /**
     * Story trays for the feed: the viewer first, then friends who have an
     * unexpired story, ordered so unseen trays come first.
     *
     * @return array<int, array{user:array, stories:array, all_seen:bool}>
     */
    public static function trays(int $viewerId): array
    {
        $ids   = Friendship::friendIds($viewerId);
        $ids[] = $viewerId;
        [$ph, $params] = Database::inClause(array_unique($ids));

        $rows = self::db()->all(
            "SELECT s.*, EXISTS(SELECT 1 FROM story_views v WHERE v.story_id = s.id AND v.user_id = ?) AS seen
             FROM stories s
             WHERE s.user_id IN ($ph) AND s.expires_at > NOW()
             ORDER BY s.created_at ASC",
            [$viewerId, ...$params]
        );
        if ($rows === []) {
            return [];
        }

        $authors = User::findMany(array_column($rows, 'user_id'));

        $trays = [];
        foreach ($rows as $row) {
            $uid = (int) $row['user_id'];
            $trays[$uid]['user']      ??= $authors[$uid] ?? null;
            $trays[$uid]['stories'][]   = $row;
        }

        foreach ($trays as $uid => &$tray) {
            $tray['all_seen'] = !in_array(0, array_map(static fn($s) => (int) $s['seen'], $tray['stories']), true);
            $tray['is_self']  = $uid === $viewerId;
        }
        unset($tray);

        // Own tray first, then unseen, then seen; newest activity wins within a group.
        uasort($trays, static function (array $a, array $b): int {
            if ($a['is_self'] !== $b['is_self']) {
                return $a['is_self'] ? -1 : 1;
            }
            if ($a['all_seen'] !== $b['all_seen']) {
                return $a['all_seen'] ? 1 : -1;
            }
            return strcmp(end($b['stories'])['created_at'], end($a['stories'])['created_at']);
        });

        return array_values($trays);
    }

    /** Every live story by one user, oldest first, for the story viewer. */
    public static function forUser(int $userId, int $viewerId): array
    {
        return self::db()->all(
            'SELECT s.*, EXISTS(SELECT 1 FROM story_views v WHERE v.story_id = s.id AND v.user_id = ?) AS seen
             FROM stories s WHERE s.user_id = ? AND s.expires_at > NOW()
             ORDER BY s.created_at ASC',
            [$viewerId, $userId]
        );
    }

    public static function markSeen(int $storyId, int $viewerId): void
    {
        self::db()->execute(
            'INSERT INTO story_views (story_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE viewed_at = NOW()',
            [$storyId, $viewerId]
        );
    }

    /** @return array<int,array> Who has seen a story, for the author's viewer list. */
    public static function viewers(int $storyId): array
    {
        return self::db()->all(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . ', v.viewed_at
             FROM story_views v JOIN users u ON u.id = v.user_id
             WHERE v.story_id = ? ORDER BY v.viewed_at DESC',
            [$storyId]
        );
    }

    public static function viewCount(int $storyId): int
    {
        return (int) self::db()->value('SELECT COUNT(*) FROM story_views WHERE story_id = ?', [$storyId], 0);
    }

    /** Remove expired stories and their media. Safe to call on every page load. */
    public static function prune(): int
    {
        $stale = self::db()->all('SELECT id, media FROM stories WHERE expires_at <= NOW() LIMIT 200');
        foreach ($stale as $row) {
            Upload::delete($row['media'] ?? null);
        }
        if ($stale === []) {
            return 0;
        }
        [$ph, $params] = Database::inClause(array_map('intval', array_column($stale, 'id')));
        return self::db()->execute("DELETE FROM stories WHERE id IN ($ph)", $params);
    }
}
