<?php
namespace App\Models;

final class UserSetting extends Model
{
    private static array $cache = [];

    private const DEFAULTS = [
        'theme'            => 'light',
        'default_privacy'  => 'friends',
        'who_can_friend'   => 'everyone',
        'who_can_message'  => 'everyone',
        'show_online'      => 1,
        'notify_reactions' => 1,
        'notify_comments'  => 1,
        'notify_friends'   => 1,
        'notify_messages'  => 1,
        'notify_groups'    => 1,
    ];

    public static function ensure(int $userId): void
    {
        self::db()->execute(
            'INSERT INTO user_settings (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id = user_id',
            [$userId]
        );
    }

    public static function forUser(int $userId): array
    {
        if (isset(self::$cache[$userId])) {
            return self::$cache[$userId];
        }
        $row = self::db()->first('SELECT * FROM user_settings WHERE user_id = ? LIMIT 1', [$userId]);
        if (!$row) {
            self::ensure($userId);
            $row = ['user_id' => $userId] + self::DEFAULTS;
        }
        return self::$cache[$userId] = $row;
    }

    public static function update(int $userId, array $fields): void
    {
        self::ensure($userId);
        $set    = [];
        $params = [];
        foreach (self::DEFAULTS as $column => $_) {
            if (array_key_exists($column, $fields)) {
                $set[]    = "`$column` = ?";
                $params[] = $fields[$column];
            }
        }
        if ($set === []) {
            return;
        }
        $params[] = $userId;
        self::db()->execute('UPDATE user_settings SET ' . implode(', ', $set) . ' WHERE user_id = ?', $params);
        unset(self::$cache[$userId]);
    }
}
