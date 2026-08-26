<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Upload;

final class Group extends Model
{
    public static function find(int $id): ?array
    {
        return self::db()->first('SELECT * FROM `groups` WHERE id = ? LIMIT 1', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return self::db()->first('SELECT * FROM `groups` WHERE slug = ? LIMIT 1', [$slug]);
    }

    /** Resolve a /groups/{handle} segment, which may be a slug or an id. */
    public static function findByHandle(string $handle): ?array
    {
        return ctype_digit($handle) ? self::find((int) $handle) : self::findBySlug($handle);
    }

    public static function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));
        if ($ids === []) {
            return [];
        }
        [$ph, $params] = Database::inClause($ids);
        $rows = self::db()->all("SELECT * FROM `groups` WHERE id IN ($ph)", $params);
        return array_column($rows, null, 'id');
    }

    public static function create(int $creatorId, string $name, string $description, string $privacy): int
    {
        $slug = self::uniqueSlug($name);
        $id   = self::db()->insert(
            'INSERT INTO `groups` (name, slug, description, privacy, creator_id) VALUES (?, ?, ?, ?, ?)',
            [$name, $slug, $description ?: null, $privacy, $creatorId]
        );
        self::db()->execute(
            'INSERT INTO group_members (group_id, user_id, role, status) VALUES (?, ?, "admin", "member")',
            [$id, $creatorId]
        );
        return $id;
    }

    public static function update(int $id, array $fields): void
    {
        self::db()->execute(
            'UPDATE `groups` SET name = ?, description = ?, privacy = ? WHERE id = ?',
            [$fields['name'], $fields['description'] ?: null, $fields['privacy'], $id]
        );
    }

    public static function setCover(int $id, string $path): void
    {
        $old = self::db()->value('SELECT cover FROM `groups` WHERE id = ?', [$id]);
        self::db()->execute('UPDATE `groups` SET cover = ? WHERE id = ?', [$path, $id]);
        if ($old) {
            Upload::delete((string) $old);
        }
    }

    public static function delete(int $id): void
    {
        foreach (self::db()->column('SELECT id FROM posts WHERE group_id = ?', [$id]) as $postId) {
            Post::delete((int) $postId);
        }
        $cover = self::db()->value('SELECT cover FROM `groups` WHERE id = ?', [$id]);
        if ($cover) {
            Upload::delete((string) $cover);
        }
        self::db()->execute('DELETE FROM `groups` WHERE id = ?', [$id]);
    }

    private static function uniqueSlug(string $name): string
    {
        $base = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9]+/', '-', strtolower($name))), '-') ?: 'group';
        $base = substr($base, 0, 120);
        $slug = $base;
        $n    = 0;
        while (self::db()->value('SELECT 1 FROM `groups` WHERE slug = ? LIMIT 1', [$slug])) {
            $slug = $base . '-' . (++$n);
        }
        return $slug;
    }

    // ---- membership -------------------------------------------------------

    public static function membership(int $groupId, int $userId): ?array
    {
        return self::db()->first(
            'SELECT * FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1',
            [$groupId, $userId]
        );
    }

    public static function isMember(int $groupId, int $userId): bool
    {
        $m = self::membership($groupId, $userId);
        return $m !== null && $m['status'] === 'member';
    }

    public static function isAdmin(int $groupId, int $userId): bool
    {
        $m = self::membership($groupId, $userId);
        return $m !== null && $m['status'] === 'member' && $m['role'] === 'admin';
    }

    public static function isModerator(int $groupId, int $userId): bool
    {
        $m = self::membership($groupId, $userId);
        return $m !== null && $m['status'] === 'member' && in_array($m['role'], ['admin', 'moderator'], true);
    }

    /** Join a public group directly, or raise a join request for a private one. */
    public static function join(int $groupId, int $userId): string
    {
        $group = self::find($groupId);
        if (!$group) {
            return 'missing';
        }
        $existing = self::membership($groupId, $userId);
        if ($existing && $existing['status'] === 'member') {
            return 'member';
        }
        if ($existing && $existing['status'] === 'invited') {
            self::db()->execute(
                'UPDATE group_members SET status = "member", joined_at = NOW() WHERE group_id = ? AND user_id = ?',
                [$groupId, $userId]
            );
            return 'member';
        }

        $status = $group['privacy'] === 'public' ? 'member' : 'pending';
        self::db()->execute(
            'INSERT INTO group_members (group_id, user_id, role, status) VALUES (?, ?, "member", ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status)',
            [$groupId, $userId, $status]
        );
        return $status;
    }

    public static function leave(int $groupId, int $userId): void
    {
        // The last admin cannot leave without handing the group to someone else.
        if (self::isAdmin($groupId, $userId) && self::adminCount($groupId) <= 1) {
            $successor = self::db()->value(
                'SELECT user_id FROM group_members WHERE group_id = ? AND user_id <> ? AND status = "member" ORDER BY joined_at LIMIT 1',
                [$groupId, $userId]
            );
            if ($successor) {
                self::setRole($groupId, (int) $successor, 'admin');
            }
        }
        self::db()->execute('DELETE FROM group_members WHERE group_id = ? AND user_id = ?', [$groupId, $userId]);
    }

    public static function adminCount(int $groupId): int
    {
        return (int) self::db()->value(
            'SELECT COUNT(*) FROM group_members WHERE group_id = ? AND role = "admin" AND status = "member"',
            [$groupId],
            0
        );
    }

    public static function invite(int $groupId, int $userId): bool
    {
        if (self::membership($groupId, $userId)) {
            return false;
        }
        self::db()->execute(
            'INSERT INTO group_members (group_id, user_id, role, status) VALUES (?, ?, "member", "invited")',
            [$groupId, $userId]
        );
        return true;
    }

    public static function approve(int $groupId, int $userId): bool
    {
        return self::db()->execute(
            'UPDATE group_members SET status = "member", joined_at = NOW() WHERE group_id = ? AND user_id = ? AND status = "pending"',
            [$groupId, $userId]
        ) > 0;
    }

    public static function reject(int $groupId, int $userId): bool
    {
        return self::db()->execute(
            'DELETE FROM group_members WHERE group_id = ? AND user_id = ? AND status = "pending"',
            [$groupId, $userId]
        ) > 0;
    }

    public static function removeMember(int $groupId, int $userId): void
    {
        self::db()->execute('DELETE FROM group_members WHERE group_id = ? AND user_id = ?', [$groupId, $userId]);
    }

    public static function setRole(int $groupId, int $userId, string $role): void
    {
        if (!in_array($role, ['admin', 'moderator', 'member'], true)) {
            return;
        }
        self::db()->execute(
            'UPDATE group_members SET role = ? WHERE group_id = ? AND user_id = ? AND status = "member"',
            [$role, $groupId, $userId]
        );
    }

    /** @return array<int,array> */
    public static function members(int $groupId, string $status = 'member', int $limit = 200): array
    {
        return self::db()->all(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . ', gm.role, gm.status, gm.joined_at
             FROM group_members gm JOIN users u ON u.id = gm.user_id
             WHERE gm.group_id = ? AND gm.status = ? AND u.is_active = 1
             ORDER BY FIELD(gm.role, "admin", "moderator", "member"), gm.joined_at
             LIMIT ?',
            [$groupId, $status, $limit]
        );
    }

    public static function memberCount(int $groupId): int
    {
        return (int) self::db()->value(
            'SELECT COUNT(*) FROM group_members WHERE group_id = ? AND status = "member"',
            [$groupId],
            0
        );
    }

    public static function pendingCount(int $groupId): int
    {
        return (int) self::db()->value(
            'SELECT COUNT(*) FROM group_members WHERE group_id = ? AND status = "pending"',
            [$groupId],
            0
        );
    }

    /** @return array<int,array> Groups the user belongs to. */
    public static function forUser(int $userId, int $limit = 50): array
    {
        return self::db()->all(
            'SELECT g.*, gm.role, (SELECT COUNT(*) FROM group_members x WHERE x.group_id = g.id AND x.status = "member") AS member_count
             FROM group_members gm JOIN `groups` g ON g.id = gm.group_id
             WHERE gm.user_id = ? AND gm.status = "member"
             ORDER BY g.name LIMIT ?',
            [$userId, $limit]
        );
    }

    /** @return array<int,array> Open invitations waiting on the user. */
    public static function invitesFor(int $userId): array
    {
        return self::db()->all(
            'SELECT g.*, (SELECT COUNT(*) FROM group_members x WHERE x.group_id = g.id AND x.status = "member") AS member_count
             FROM group_members gm JOIN `groups` g ON g.id = gm.group_id
             WHERE gm.user_id = ? AND gm.status = "invited"
             ORDER BY gm.joined_at DESC',
            [$userId]
        );
    }

    /** @return array<int,array> Public groups the user has not joined. */
    public static function discover(int $userId, int $limit = 12): array
    {
        return self::db()->all(
            'SELECT g.*, (SELECT COUNT(*) FROM group_members x WHERE x.group_id = g.id AND x.status = "member") AS member_count
             FROM `groups` g
             WHERE NOT EXISTS (SELECT 1 FROM group_members gm WHERE gm.group_id = g.id AND gm.user_id = ?)
             ORDER BY member_count DESC, g.created_at DESC
             LIMIT ?',
            [$userId, $limit]
        );
    }

    public static function search(string $term, int $limit = 20): array
    {
        return self::db()->all(
            'SELECT g.*, (SELECT COUNT(*) FROM group_members x WHERE x.group_id = g.id AND x.status = "member") AS member_count
             FROM `groups` g WHERE g.name LIKE ? OR g.description LIKE ?
             ORDER BY member_count DESC LIMIT ?',
            ['%' . $term . '%', '%' . $term . '%', $limit]
        );
    }

    /** Users who can still be invited to this group. */
    public static function invitableFriends(int $groupId, int $userId): array
    {
        return self::db()->all(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . '
             FROM friendships f
             JOIN users u ON u.id = CASE WHEN f.requester_id = ? THEN f.addressee_id ELSE f.requester_id END
             WHERE f.status = "accepted" AND (f.requester_id = ? OR f.addressee_id = ?)
               AND u.is_active = 1
               AND NOT EXISTS (SELECT 1 FROM group_members gm WHERE gm.group_id = ? AND gm.user_id = u.id)
             ORDER BY u.first_name',
            [$userId, $userId, $userId, $groupId]
        );
    }
}
