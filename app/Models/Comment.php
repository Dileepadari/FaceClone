<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Upload;

final class Comment extends Model
{
    public static function find(int $id): ?array
    {
        return self::db()->first('SELECT * FROM comments WHERE id = ? LIMIT 1', [$id]);
    }

    public static function create(int $postId, int $userId, string $content, ?int $parentId = null, ?string $image = null): int
    {
        // A reply to a reply attaches to the top-level comment, matching Facebook's two-level threads.
        if ($parentId) {
            $parent = self::find($parentId);
            if (!$parent || (int) $parent['post_id'] !== $postId) {
                $parentId = null;
            } elseif ($parent['parent_id']) {
                $parentId = (int) $parent['parent_id'];
            }
        }
        return self::db()->insert(
            'INSERT INTO comments (post_id, user_id, parent_id, content, image) VALUES (?, ?, ?, ?, ?)',
            [$postId, $userId, $parentId, $content, $image]
        );
    }

    public static function update(int $id, string $content): void
    {
        self::db()->execute('UPDATE comments SET content = ? WHERE id = ?', [$content, $id]);
    }

    public static function delete(int $id): void
    {
        $row = self::find($id);
        if (!$row) {
            return;
        }
        foreach (self::db()->column('SELECT image FROM comments WHERE parent_id = ? AND image IS NOT NULL', [$id]) as $img) {
            Upload::delete((string) $img);
        }
        Upload::delete($row['image'] ?? null);
        self::db()->execute('DELETE FROM reactions WHERE target_type = "comment" AND target_id = ?', [$id]);
        self::db()->execute('DELETE FROM comments WHERE id = ?', [$id]);
    }

    /**
     * Top-level comments for a post with their replies nested underneath.
     * @return array<int,array>
     */
    public static function forPost(int $postId, int $viewerId, int $limit = 50): array
    {
        $top = self::db()->all(
            'SELECT * FROM comments WHERE post_id = ? AND parent_id IS NULL ORDER BY created_at ASC LIMIT ?',
            [$postId, $limit]
        );
        if ($top === []) {
            return [];
        }

        $topIds = array_map('intval', array_column($top, 'id'));
        [$ph, $params] = Database::inClause($topIds);
        $replies = self::db()->all(
            "SELECT * FROM comments WHERE parent_id IN ($ph) ORDER BY created_at ASC",
            $params
        );

        $all       = array_merge($top, $replies);
        $allIds    = array_map('intval', array_column($all, 'id'));
        $authors   = User::findMany(array_column($all, 'user_id'));
        $reactions = Reaction::summaryFor('comment', $allIds, $viewerId);

        $byParent = [];
        foreach ($replies as $reply) {
            $reply['author']    = $authors[(int) $reply['user_id']] ?? null;
            $reply['reactions'] = $reactions[(int) $reply['id']] ?? ['total' => 0, 'top' => [], 'mine' => null];
            $reply['can_edit']  = (int) $reply['user_id'] === $viewerId;
            $byParent[(int) $reply['parent_id']][] = $reply;
        }

        foreach ($top as &$comment) {
            $id                  = (int) $comment['id'];
            $comment['author']    = $authors[(int) $comment['user_id']] ?? null;
            $comment['reactions'] = $reactions[$id] ?? ['total' => 0, 'top' => [], 'mine' => null];
            $comment['replies']   = $byParent[$id] ?? [];
            $comment['can_edit']  = (int) $comment['user_id'] === $viewerId;
        }
        unset($comment);

        return $top;
    }

    /** A single hydrated comment, used by the AJAX reply that appends to the DOM. */
    public static function hydrateOne(int $id, int $viewerId): ?array
    {
        $row = self::find($id);
        if (!$row) {
            return null;
        }
        $row['author']    = User::find((int) $row['user_id']);
        $row['reactions'] = ['total' => 0, 'counts' => [], 'top' => [], 'mine' => null];
        $row['replies']   = [];
        $row['can_edit']  = (int) $row['user_id'] === $viewerId;
        return $row;
    }

    public static function countForPost(int $postId): int
    {
        return (int) self::db()->value('SELECT COUNT(*) FROM comments WHERE post_id = ?', [$postId], 0);
    }

    /** The post author plus everyone already in the thread, for notifications. */
    public static function threadParticipants(int $postId): array
    {
        return array_map('intval', self::db()->column(
            'SELECT DISTINCT user_id FROM comments WHERE post_id = ?',
            [$postId]
        ));
    }
}
