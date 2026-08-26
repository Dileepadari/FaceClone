<?php
namespace App\Models;

use App\Core\Upload;

final class User extends Model
{
    public const PUBLIC_COLUMNS = 'id, first_name, last_name, username, email, avatar, cover, bio, work, education, city, hometown, relationship, website, dob, gender, is_active, last_seen, created_at';

    public static function find(int $id): ?array
    {
        return self::db()->first('SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1', [$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        return self::db()->first('SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1', [$username]);
    }

    /** Resolve a /u/{handle} segment, which may be a username or a numeric id. */
    public static function findByHandle(string $handle): ?array
    {
        if (ctype_digit($handle)) {
            return self::find((int) $handle);
        }
        return self::findByUsername($handle);
    }

    public static function findByEmailOrUsername(string $identifier): ?array
    {
        return self::db()->first(
            'SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1',
            [$identifier, $identifier]
        );
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT 1 FROM users WHERE email = ?';
        $params = [$email];
        if ($exceptId) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (bool) self::db()->value($sql . ' LIMIT 1', $params);
    }

    public static function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT 1 FROM users WHERE username = ?';
        $params = [$username];
        if ($exceptId) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        return (bool) self::db()->value($sql . ' LIMIT 1', $params);
    }

    public static function create(array $data): int
    {
        $id = self::db()->insert(
            'INSERT INTO users (first_name, last_name, username, email, password_hash, dob, gender)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['first_name'],
                $data['last_name'],
                $data['username'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['dob'] ?: null,
                $data['gender'] ?? 'custom',
            ]
        );
        UserSetting::ensure($id);
        return $id;
    }

    /** Derive a unique username from a person's name. */
    public static function suggestUsername(string $first, string $last): string
    {
        $base = preg_replace('/[^a-z0-9.]/', '', strtolower($first . '.' . $last)) ?: 'user';
        $base = trim(substr($base, 0, 30), '.');
        $candidate = $base;
        $n = 0;
        while (self::usernameExists($candidate)) {
            $n++;
            $candidate = $base . $n;
        }
        return $candidate;
    }

    public static function updateProfile(int $id, array $fields): void
    {
        $allowed = ['first_name', 'last_name', 'username', 'bio', 'work', 'education', 'city', 'hometown', 'relationship', 'website', 'dob', 'gender'];
        $set     = [];
        $params  = [];
        foreach ($allowed as $column) {
            if (array_key_exists($column, $fields)) {
                $set[]    = "`$column` = ?";
                $params[] = $fields[$column] === '' ? null : $fields[$column];
            }
        }
        if ($set === []) {
            return;
        }
        $params[] = $id;
        self::db()->execute('UPDATE users SET ' . implode(', ', $set) . ' WHERE id = ?', $params);
    }

    public static function updateEmail(int $id, string $email): void
    {
        self::db()->execute('UPDATE users SET email = ? WHERE id = ?', [$email, $id]);
    }

    public static function updatePassword(int $id, string $plain): void
    {
        self::db()->execute(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [password_hash($plain, PASSWORD_DEFAULT), $id]
        );
    }

    /** Replace the avatar or cover image, removing the old file. */
    public static function setImage(int $id, string $column, string $path): void
    {
        if (!in_array($column, ['avatar', 'cover'], true)) {
            return;
        }
        $old = self::db()->value("SELECT `$column` FROM users WHERE id = ?", [$id]);
        self::db()->execute("UPDATE users SET `$column` = ? WHERE id = ?", [$path, $id]);
        if ($old) {
            Upload::delete((string) $old);
        }
    }

    public static function touchLastSeen(int $id): void
    {
        self::db()->execute('UPDATE users SET last_seen = NOW() WHERE id = ?', [$id]);
    }

    public static function deactivate(int $id): void
    {
        self::db()->execute('UPDATE users SET is_active = 0 WHERE id = ?', [$id]);
    }

    public static function reactivate(int $id): void
    {
        self::db()->execute('UPDATE users SET is_active = 1 WHERE id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        self::db()->execute('DELETE FROM users WHERE id = ?', [$id]);
    }

    /** Considered online when seen in the last 5 minutes and sharing presence. */
    public static function isOnline(array $user): bool
    {
        if (empty($user['last_seen'])) {
            return false;
        }
        $settings = UserSetting::forUser((int) $user['id']);
        if (!$settings['show_online']) {
            return false;
        }
        return strtotime($user['last_seen']) > time() - 300;
    }

    /** @return array<int,array> Users matching a free-text search. */
    public static function search(string $term, int $viewerId, int $limit = 20, int $offset = 0): array
    {
        $like = '%' . $term . '%';
        return self::db()->all(
            'SELECT ' . self::PUBLIC_COLUMNS . '
             FROM users
             WHERE is_active = 1
               AND id <> ?
               AND id NOT IN (SELECT blocked_id FROM blocks WHERE user_id = ?)
               AND id NOT IN (SELECT user_id FROM blocks WHERE blocked_id = ?)
               AND (CONCAT(first_name, " ", last_name) LIKE ? OR username LIKE ? OR email = ?)
             ORDER BY
               CASE WHEN CONCAT(first_name, " ", last_name) LIKE ? THEN 0 ELSE 1 END,
               first_name
             LIMIT ? OFFSET ?',
            [$viewerId, $viewerId, $viewerId, $like, $like, $term, $term . '%', $limit, $offset]
        );
    }

    /** @return array<int,array> Hydrate a list of ids in one query, keyed by id. */
    public static function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }
        [$placeholders, $params] = \App\Core\Database::inClause($ids);
        $rows = self::db()->all(
            'SELECT ' . self::PUBLIC_COLUMNS . " FROM users WHERE id IN ($placeholders)",
            $params
        );
        return array_column($rows, null, 'id');
    }

    public static function countAll(): int
    {
        return (int) self::db()->value('SELECT COUNT(*) FROM users WHERE is_active = 1', [], 0);
    }
}
