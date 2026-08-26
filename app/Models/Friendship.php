<?php
namespace App\Models;

use App\Core\Database;

/**
 * Friendships are stored once per pair, with the requester on the left.
 * Every read normalises the direction so callers never care who asked first.
 */
final class Friendship extends Model
{
    public const NONE      = 'none';
    public const FRIENDS   = 'friends';
    public const SENT      = 'sent';
    public const RECEIVED  = 'received';
    public const SELF      = 'self';
    public const BLOCKED   = 'blocked';

    public static function between(int $a, int $b): ?array
    {
        return self::db()->first(
            'SELECT * FROM friendships
             WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)
             LIMIT 1',
            [$a, $b, $b, $a]
        );
    }

    /** Relationship of $other as seen by $viewer. */
    public static function status(int $viewer, int $other): string
    {
        if ($viewer === $other) {
            return self::SELF;
        }
        if (Block::exists($viewer, $other) || Block::exists($other, $viewer)) {
            return self::BLOCKED;
        }
        $row = self::between($viewer, $other);
        if (!$row) {
            return self::NONE;
        }
        if ($row['status'] === 'accepted') {
            return self::FRIENDS;
        }
        if ($row['status'] === 'pending') {
            return (int) $row['requester_id'] === $viewer ? self::SENT : self::RECEIVED;
        }
        return self::NONE;
    }

    public static function areFriends(int $a, int $b): bool
    {
        return self::status($a, $b) === self::FRIENDS;
    }

    /** Send a request. Returns true when a new pending row was created. */
    public static function request(int $requester, int $addressee): bool
    {
        if ($requester === $addressee || Block::exists($addressee, $requester)) {
            return false;
        }
        $existing = self::between($requester, $addressee);
        if ($existing) {
            if ($existing['status'] === 'accepted') {
                return false;
            }
            // A previously declined pair can request again, from either side.
            self::db()->execute(
                'UPDATE friendships SET requester_id = ?, addressee_id = ?, status = "pending" WHERE id = ?',
                [$requester, $addressee, $existing['id']]
            );
            return true;
        }
        self::db()->insert(
            'INSERT INTO friendships (requester_id, addressee_id, status) VALUES (?, ?, "pending")',
            [$requester, $addressee]
        );
        return true;
    }

    /** Accept a request that $addressee received. Returns the requester id. */
    public static function accept(int $addressee, int $requester): bool
    {
        $rows = self::db()->execute(
            'UPDATE friendships SET status = "accepted"
             WHERE requester_id = ? AND addressee_id = ? AND status = "pending"',
            [$requester, $addressee]
        );
        if ($rows > 0) {
            Follow::add($addressee, $requester);
            Follow::add($requester, $addressee);
            return true;
        }
        return false;
    }

    public static function decline(int $addressee, int $requester): bool
    {
        return self::db()->execute(
            'UPDATE friendships SET status = "declined"
             WHERE requester_id = ? AND addressee_id = ? AND status = "pending"',
            [$requester, $addressee]
        ) > 0;
    }

    public static function cancel(int $requester, int $addressee): bool
    {
        return self::db()->execute(
            'DELETE FROM friendships WHERE requester_id = ? AND addressee_id = ? AND status = "pending"',
            [$requester, $addressee]
        ) > 0;
    }

    public static function unfriend(int $a, int $b): bool
    {
        $removed = self::db()->execute(
            'DELETE FROM friendships
             WHERE ((requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?))
               AND status = "accepted"',
            [$a, $b, $b, $a]
        );
        if ($removed > 0) {
            Follow::remove($a, $b);
            Follow::remove($b, $a);
        }
        return $removed > 0;
    }

    /** @return array<int,int> Ids of everyone $userId is friends with. */
    public static function friendIds(int $userId): array
    {
        return array_map('intval', self::db()->column(
            'SELECT CASE WHEN requester_id = ? THEN addressee_id ELSE requester_id END
             FROM friendships
             WHERE status = "accepted" AND (requester_id = ? OR addressee_id = ?)',
            [$userId, $userId, $userId]
        ));
    }

    /** @return array<int,array> Friend user rows. */
    public static function friends(int $userId, int $limit = 100, int $offset = 0): array
    {
        return self::db()->all(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . '
             FROM friendships f
             JOIN users u ON u.id = CASE WHEN f.requester_id = ? THEN f.addressee_id ELSE f.requester_id END
             WHERE f.status = "accepted" AND (f.requester_id = ? OR f.addressee_id = ?) AND u.is_active = 1
             ORDER BY u.first_name, u.last_name
             LIMIT ? OFFSET ?',
            [$userId, $userId, $userId, $limit, $offset]
        );
    }

    public static function friendCount(int $userId): int
    {
        return (int) self::db()->value(
            'SELECT COUNT(*) FROM friendships
             WHERE status = "accepted" AND (requester_id = ? OR addressee_id = ?)',
            [$userId, $userId],
            0
        );
    }

    /** @return array<int,array> Incoming requests waiting on $userId. */
    public static function pendingRequests(int $userId, int $limit = 50): array
    {
        return self::db()->all(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . ', f.created_at AS requested_at
             FROM friendships f
             JOIN users u ON u.id = f.requester_id
             WHERE f.addressee_id = ? AND f.status = "pending" AND u.is_active = 1
             ORDER BY f.created_at DESC
             LIMIT ?',
            [$userId, $limit]
        );
    }

    /** @return array<int,array> Requests $userId has sent that are still pending. */
    public static function sentRequests(int $userId, int $limit = 50): array
    {
        return self::db()->all(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . ', f.created_at AS requested_at
             FROM friendships f
             JOIN users u ON u.id = f.addressee_id
             WHERE f.requester_id = ? AND f.status = "pending" AND u.is_active = 1
             ORDER BY f.created_at DESC
             LIMIT ?',
            [$userId, $limit]
        );
    }

    public static function pendingCount(int $userId): int
    {
        return (int) self::db()->value(
            'SELECT COUNT(*) FROM friendships WHERE addressee_id = ? AND status = "pending"',
            [$userId],
            0
        );
    }

    /**
     * People You May Know: friends-of-friends first, ranked by how many mutual
     * friends they share, then padded with any other active users.
     */
    public static function suggestions(int $userId, int $limit = 12): array
    {
        $rows = self::db()->all(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . ', COUNT(*) AS mutuals
             FROM friendships f1
             JOIN friendships f2
               ON  (CASE WHEN f1.requester_id = ? THEN f1.addressee_id ELSE f1.requester_id END)
                 = (CASE WHEN f2.requester_id = ? THEN f2.addressee_id ELSE f2.requester_id END)
             JOIN users u
               ON u.id = CASE WHEN f2.requester_id = (CASE WHEN f1.requester_id = ? THEN f1.addressee_id ELSE f1.requester_id END)
                              THEN f2.addressee_id ELSE f2.requester_id END
             WHERE f1.status = "accepted" AND (f1.requester_id = ? OR f1.addressee_id = ?)
               AND f2.status = "accepted"
               AND u.id <> ? AND u.is_active = 1
               AND NOT EXISTS (
                     SELECT 1 FROM friendships x
                     WHERE (x.requester_id = ? AND x.addressee_id = u.id)
                        OR (x.requester_id = u.id AND x.addressee_id = ?)
                   )
               AND NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.user_id = ? AND b.blocked_id = u.id) OR (b.user_id = u.id AND b.blocked_id = ?))
             GROUP BY u.id
             ORDER BY mutuals DESC, u.first_name
             LIMIT ?',
            [$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $limit]
        );

        if (count($rows) >= $limit) {
            return $rows;
        }

        $have = array_map('intval', array_column($rows, 'id'));
        $have[] = $userId;
        [$ph, $params] = Database::inClause($have);
        $filler = self::db()->all(
            'SELECT ' . User::PUBLIC_COLUMNS . ', 0 AS mutuals
             FROM users u
             WHERE u.is_active = 1
               AND u.id NOT IN (' . $ph . ')
               AND NOT EXISTS (
                     SELECT 1 FROM friendships x
                     WHERE (x.requester_id = ? AND x.addressee_id = u.id)
                        OR (x.requester_id = u.id AND x.addressee_id = ?)
                   )
               AND NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.user_id = ? AND b.blocked_id = u.id) OR (b.user_id = u.id AND b.blocked_id = ?))
             ORDER BY u.created_at DESC
             LIMIT ?',
            [...$params, $userId, $userId, $userId, $userId, $limit - count($rows)]
        );

        return array_merge($rows, $filler);
    }

    /** How many friends $a and $b have in common. */
    public static function mutualCount(int $a, int $b): int
    {
        $mine   = self::friendIds($a);
        $theirs = self::friendIds($b);
        return count(array_intersect($mine, $theirs));
    }

    /** @return array<int,array> The actual mutual friend rows. */
    public static function mutualFriends(int $a, int $b, int $limit = 6): array
    {
        $shared = array_intersect(self::friendIds($a), self::friendIds($b));
        if ($shared === []) {
            return [];
        }
        $shared = array_slice(array_values($shared), 0, $limit);
        [$ph, $params] = Database::inClause($shared);
        return self::db()->all(
            'SELECT ' . User::PUBLIC_COLUMNS . " FROM users WHERE id IN ($ph) AND is_active = 1",
            $params
        );
    }
}
