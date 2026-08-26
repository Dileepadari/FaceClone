<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Upload;

final class Post extends Model
{
    /** Columns selected for every feed query. */
    private const SELECT = 'p.id, p.user_id, p.group_id, p.shared_post_id, p.content, p.background,
                            p.feeling, p.location, p.privacy, p.created_at, p.edited_at';

    public static function find(int $id): ?array
    {
        return self::db()->first('SELECT * FROM posts WHERE id = ? LIMIT 1', [$id]);
    }

    public static function create(array $data): int
    {
        return self::db()->insert(
            'INSERT INTO posts (user_id, group_id, shared_post_id, content, background, feeling, location, privacy)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $data['group_id']       ?: null,
                $data['shared_post_id'] ?: null,
                $data['content']        ?: null,
                $data['background']     ?: null,
                $data['feeling']        ?: null,
                $data['location']       ?: null,
                $data['privacy']        ?? 'friends',
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        self::db()->execute(
            'UPDATE posts SET content = ?, background = ?, feeling = ?, location = ?, privacy = ?, edited_at = NOW() WHERE id = ?',
            [
                $data['content']    ?: null,
                $data['background'] ?: null,
                $data['feeling']    ?: null,
                $data['location']   ?: null,
                $data['privacy']    ?? 'friends',
                $id,
            ]
        );
    }

    /** Delete a post and every upload it owns. */
    public static function delete(int $id): void
    {
        foreach (self::db()->column('SELECT path FROM post_media WHERE post_id = ?', [$id]) as $path) {
            Upload::delete((string) $path);
        }
        foreach (self::db()->column('SELECT image FROM comments WHERE post_id = ? AND image IS NOT NULL', [$id]) as $path) {
            Upload::delete((string) $path);
        }
        self::db()->execute('DELETE FROM reactions WHERE target_type = "post" AND target_id = ?', [$id]);
        self::db()->execute('DELETE FROM posts WHERE id = ?', [$id]);
    }

    public static function addMedia(int $postId, string $path, string $type, int $sort = 0): void
    {
        self::db()->insert(
            'INSERT INTO post_media (post_id, path, type, sort) VALUES (?, ?, ?, ?)',
            [$postId, $path, $type, $sort]
        );
    }

    /**
     * The home feed. Includes the viewer's own posts, friends' posts, public
     * posts from people they follow, and posts in groups they belong to.
     */
    public static function feed(int $viewerId, int $limit = 10, int $offset = 0): array
    {
        $rows = self::db()->all(
            'SELECT ' . self::SELECT . '
             FROM posts p
             JOIN users u ON u.id = p.user_id AND u.is_active = 1
             LEFT JOIN `groups` g ON g.id = p.group_id
             WHERE p.user_id NOT IN (SELECT blocked_id FROM blocks WHERE user_id = ?)
               AND p.user_id NOT IN (SELECT user_id FROM blocks WHERE blocked_id = ?)
               AND (
                 p.user_id = ?
                 OR (
                   p.group_id IS NOT NULL
                   AND EXISTS (SELECT 1 FROM group_members gm WHERE gm.group_id = p.group_id AND gm.user_id = ? AND gm.status = "member")
                 )
                 OR (
                   p.group_id IS NULL AND p.privacy <> "only_me"
                   AND (
                     EXISTS (
                       SELECT 1 FROM friendships f
                       WHERE f.status = "accepted"
                         AND ((f.requester_id = ? AND f.addressee_id = p.user_id) OR (f.addressee_id = ? AND f.requester_id = p.user_id))
                     )
                     OR (p.privacy = "public" AND EXISTS (SELECT 1 FROM follows fo WHERE fo.follower_id = ? AND fo.followee_id = p.user_id))
                   )
                 )
               )
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT ? OFFSET ?',
            [$viewerId, $viewerId, $viewerId, $viewerId, $viewerId, $viewerId, $viewerId, $limit, $offset]
        );

        return self::hydrate($rows, $viewerId);
    }

    /** Posts on someone's profile, filtered by what the viewer may see. */
    public static function forProfile(int $authorId, int $viewerId, int $limit = 10, int $offset = 0): array
    {
        $visible = self::visibilityClause($authorId, $viewerId);

        $rows = self::db()->all(
            'SELECT ' . self::SELECT . '
             FROM posts p
             WHERE p.user_id = ? AND p.group_id IS NULL AND ' . $visible . '
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT ? OFFSET ?',
            [$authorId, $limit, $offset]
        );

        return self::hydrate($rows, $viewerId);
    }

    public static function forGroup(int $groupId, int $viewerId, int $limit = 10, int $offset = 0): array
    {
        $rows = self::db()->all(
            'SELECT ' . self::SELECT . '
             FROM posts p
             JOIN users u ON u.id = p.user_id AND u.is_active = 1
             WHERE p.group_id = ?
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT ? OFFSET ?',
            [$groupId, $limit, $offset]
        );
        return self::hydrate($rows, $viewerId);
    }

    /** Posts carrying a video, used by the Watch page. */
    public static function videoFeed(int $viewerId, int $limit = 10, int $offset = 0): array
    {
        $rows = self::db()->all(
            'SELECT ' . self::SELECT . '
             FROM posts p
             JOIN users u ON u.id = p.user_id AND u.is_active = 1
             WHERE EXISTS (SELECT 1 FROM post_media m WHERE m.post_id = p.id AND m.type = "video")
               AND p.privacy = "public" AND p.group_id IS NULL
               AND p.user_id NOT IN (SELECT blocked_id FROM blocks WHERE user_id = ?)
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?',
            [$viewerId, $limit, $offset]
        );
        return self::hydrate($rows, $viewerId);
    }

    /** Every photo the viewer may see on a profile, newest first. */
    public static function photosOf(int $authorId, int $viewerId, int $limit = 60): array
    {
        $visible = self::visibilityClause($authorId, $viewerId);
        return self::db()->all(
            'SELECT m.id, m.path, m.type, m.post_id, p.created_at
             FROM post_media m
             JOIN posts p ON p.id = m.post_id
             WHERE p.user_id = ? AND m.type = "image" AND ' . $visible . '
             ORDER BY p.created_at DESC, m.sort
             LIMIT ?',
            [$authorId, $limit]
        );
    }

    public static function groupPhotos(int $groupId, int $limit = 60): array
    {
        return self::db()->all(
            'SELECT m.id, m.path, m.type, m.post_id, p.created_at
             FROM post_media m JOIN posts p ON p.id = m.post_id
             WHERE p.group_id = ? AND m.type = "image"
             ORDER BY p.created_at DESC LIMIT ?',
            [$groupId, $limit]
        );
    }

    public static function saved(int $viewerId, int $limit = 30, int $offset = 0): array
    {
        $rows = self::db()->all(
            'SELECT ' . self::SELECT . '
             FROM saved_posts s
             JOIN posts p ON p.id = s.post_id
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC
             LIMIT ? OFFSET ?',
            [$viewerId, $limit, $offset]
        );
        return self::hydrate($rows, $viewerId);
    }

    public static function search(string $term, int $viewerId, int $limit = 20, int $offset = 0): array
    {
        $rows = self::db()->all(
            'SELECT ' . self::SELECT . '
             FROM posts p
             JOIN users u ON u.id = p.user_id AND u.is_active = 1
             WHERE p.content LIKE ?
               AND p.group_id IS NULL
               AND (p.privacy = "public" OR p.user_id = ?
                    OR (p.privacy = "friends" AND EXISTS (
                          SELECT 1 FROM friendships f WHERE f.status = "accepted"
                          AND ((f.requester_id = ? AND f.addressee_id = p.user_id) OR (f.addressee_id = ? AND f.requester_id = p.user_id))
                       )))
               AND p.user_id NOT IN (SELECT blocked_id FROM blocks WHERE user_id = ?)
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?',
            ['%' . $term . '%', $viewerId, $viewerId, $viewerId, $viewerId, $limit, $offset]
        );
        return self::hydrate($rows, $viewerId);
    }

    /** Load a single post with everything a card needs, honouring privacy. */
    public static function findVisible(int $postId, int $viewerId): ?array
    {
        $row = self::db()->first('SELECT ' . self::SELECT . ' FROM posts p WHERE p.id = ? LIMIT 1', [$postId]);
        if (!$row) {
            return null;
        }
        if (!self::canView($row, $viewerId)) {
            return null;
        }
        $hydrated = self::hydrate([$row], $viewerId);
        return $hydrated[0] ?? null;
    }

    public static function canView(array $post, int $viewerId): bool
    {
        $authorId = (int) $post['user_id'];
        if ($authorId === $viewerId) {
            return true;
        }
        if (Block::exists($viewerId, $authorId) || Block::exists($authorId, $viewerId)) {
            return false;
        }
        if (!empty($post['group_id'])) {
            $group = Group::find((int) $post['group_id']);
            if (!$group) {
                return false;
            }
            return $group['privacy'] === 'public' || Group::isMember((int) $group['id'], $viewerId);
        }
        return match ($post['privacy']) {
            'public'  => true,
            'friends' => Friendship::areFriends($viewerId, $authorId),
            'only_me' => false,
            default   => false,
        };
    }

    public static function canEdit(array $post, int $viewerId): bool
    {
        if ((int) $post['user_id'] === $viewerId) {
            return true;
        }
        // Group admins and moderators may remove posts in their group.
        if (!empty($post['group_id'])) {
            return Group::isModerator((int) $post['group_id'], $viewerId);
        }
        return false;
    }

    private static function visibilityClause(int $authorId, int $viewerId): string
    {
        if ($authorId === $viewerId) {
            return '1 = 1';
        }
        if (Friendship::areFriends($viewerId, $authorId)) {
            return 'p.privacy IN ("public", "friends")';
        }
        return 'p.privacy = "public"';
    }

    /**
     * Attach authors, media, reaction summaries, comment counts, saved state and
     * the original post for shares. Three queries regardless of page size.
     */
    public static function hydrate(array $rows, int $viewerId): array
    {
        if ($rows === []) {
            return [];
        }

        $postIds  = array_map('intval', array_column($rows, 'id'));
        $userIds  = array_column($rows, 'user_id');
        $groupIds = array_filter(array_column($rows, 'group_id'));
        $sharedIds = array_filter(array_column($rows, 'shared_post_id'));

        [$ph, $params] = Database::inClause($postIds);

        $media = [];
        foreach (self::db()->all("SELECT * FROM post_media WHERE post_id IN ($ph) ORDER BY sort, id", $params) as $m) {
            $media[(int) $m['post_id']][] = $m;
        }

        $reactions = Reaction::summaryFor('post', $postIds, $viewerId);

        $commentCounts = [];
        foreach (self::db()->all("SELECT post_id, COUNT(*) AS n FROM comments WHERE post_id IN ($ph) GROUP BY post_id", $params) as $c) {
            $commentCounts[(int) $c['post_id']] = (int) $c['n'];
        }

        $shareCounts = [];
        foreach (self::db()->all("SELECT shared_post_id, COUNT(*) AS n FROM posts WHERE shared_post_id IN ($ph) GROUP BY shared_post_id", $params) as $s) {
            $shareCounts[(int) $s['shared_post_id']] = (int) $s['n'];
        }

        $savedIds = array_map('intval', self::db()->column(
            "SELECT post_id FROM saved_posts WHERE user_id = ? AND post_id IN ($ph)",
            [$viewerId, ...$params]
        ));

        $authors = User::findMany($userIds);
        $groups  = $groupIds ? Group::findMany($groupIds) : [];

        // Shared originals get a shallow hydration (author + media only) to avoid recursion.
        $shared = [];
        if ($sharedIds) {
            [$sph, $sparams] = Database::inClause(array_map('intval', $sharedIds));
            $originals = self::db()->all('SELECT ' . self::SELECT . " FROM posts p WHERE p.id IN ($sph)", $sparams);
            $originalAuthors = User::findMany(array_column($originals, 'user_id'));
            $originalMedia = [];
            foreach (self::db()->all("SELECT * FROM post_media WHERE post_id IN ($sph) ORDER BY sort, id", $sparams) as $m) {
                $originalMedia[(int) $m['post_id']][] = $m;
            }
            foreach ($originals as $o) {
                $o['author'] = $originalAuthors[(int) $o['user_id']] ?? null;
                $o['media']  = $originalMedia[(int) $o['id']] ?? [];
                $shared[(int) $o['id']] = $o;
            }
        }

        foreach ($rows as &$row) {
            $id                    = (int) $row['id'];
            $row['author']         = $authors[(int) $row['user_id']] ?? null;
            $row['group']          = $row['group_id'] ? ($groups[(int) $row['group_id']] ?? null) : null;
            $row['media']          = $media[$id] ?? [];
            $row['reactions']      = $reactions[$id] ?? ['total' => 0, 'top' => [], 'mine' => null];
            $row['comment_count']  = $commentCounts[$id] ?? 0;
            $row['share_count']    = $shareCounts[$id] ?? 0;
            $row['is_saved']       = in_array($id, $savedIds, true);
            $row['shared']         = $row['shared_post_id'] ? ($shared[(int) $row['shared_post_id']] ?? null) : null;
            $row['can_edit']       = self::canEdit($row, $viewerId);
        }
        unset($row);

        return $rows;
    }

    public static function countBy(int $userId): int
    {
        return (int) self::db()->value('SELECT COUNT(*) FROM posts WHERE user_id = ?', [$userId], 0);
    }
}
