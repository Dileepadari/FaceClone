<?php
namespace App\Models;

/**
 * Password reset tokens. Only the SHA-256 hash is stored, so a database read
 * does not hand out working reset links.
 */
final class PasswordReset extends Model
{
    /** Issue a token and return the plain value to put in the link. */
    public static function issue(int $userId): string
    {
        self::db()->execute('DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL', [$userId]);
        $token = bin2hex(random_bytes(32));
        self::db()->insert(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))',
            [$userId, hash('sha256', $token)]
        );
        return $token;
    }

    public static function resolve(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        return self::db()->first(
            'SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1',
            [hash('sha256', $token)]
        );
    }

    public static function consume(int $id): void
    {
        self::db()->execute('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [$id]);
    }
}
