<?php
namespace App\Models;

use App\Core\Database;

final class Reaction extends Model
{
    public const TYPES = ['like', 'love', 'care', 'haha', 'wow', 'sad', 'angry'];

    /**
     * Apply a reaction. Sending the same type again removes it, which is what
     * clicking an already-lit Like button does on Facebook.
     *
     * @return array{action:string, type:?string}
     */
    public static function toggle(int $userId, string $targetType, int $targetId, string $type): array
    {
        if (!in_array($type, self::TYPES, true)) {
            $type = 'like';
        }
        $existing = self::db()->first(
            'SELECT * FROM reactions WHERE user_id = ? AND target_type = ? AND target_id = ? LIMIT 1',
            [$userId, $targetType, $targetId]
        );

        if (!$existing) {
            self::db()->insert(
                'INSERT INTO reactions (user_id, target_type, target_id, type) VALUES (?, ?, ?, ?)',
                [$userId, $targetType, $targetId, $type]
            );
            return ['action' => 'added', 'type' => $type];
        }

        if ($existing['type'] === $type) {
            self::db()->execute('DELETE FROM reactions WHERE id = ?', [$existing['id']]);
            return ['action' => 'removed', 'type' => null];
        }

        self::db()->execute('UPDATE reactions SET type = ?, created_at = NOW() WHERE id = ?', [$type, $existing['id']]);
        return ['action' => 'changed', 'type' => $type];
    }

    /**
     * Reaction summary for many targets at once.
     *
     * @return array<int, array{total:int, top:array<int,string>, mine:?string, counts:array<string,int>}>
     */
    public static function summaryFor(string $targetType, array $targetIds, int $viewerId): array
    {
        $targetIds = array_values(array_unique(array_map('intval', $targetIds)));
        if ($targetIds === []) {
            return [];
        }
        [$ph, $params] = Database::inClause($targetIds);

        $summary = [];
        $rows = self::db()->all(
            "SELECT target_id, type, COUNT(*) AS n
             FROM reactions
             WHERE target_type = ? AND target_id IN ($ph)
             GROUP BY target_id, type",
            [$targetType, ...$params]
        );
        foreach ($rows as $row) {
            $id = (int) $row['target_id'];
            $summary[$id]['counts'][$row['type']] = (int) $row['n'];
            $summary[$id]['total'] = ($summary[$id]['total'] ?? 0) + (int) $row['n'];
        }

        $mine = self::db()->all(
            "SELECT target_id, type FROM reactions WHERE target_type = ? AND target_id IN ($ph) AND user_id = ?",
            [$targetType, ...$params, $viewerId]
        );
        $mineMap = array_column($mine, 'type', 'target_id');

        $out = [];
        foreach ($targetIds as $id) {
            $counts = $summary[$id]['counts'] ?? [];
            arsort($counts);
            $out[$id] = [
                'total'  => $summary[$id]['total'] ?? 0,
                'counts' => $counts,
                'top'    => array_slice(array_keys($counts), 0, 3),
                'mine'   => $mineMap[$id] ?? null,
            ];
        }
        return $out;
    }

    /** @return array<int,array> Everyone who reacted, with their reaction type. */
    public static function reactors(string $targetType, int $targetId, ?string $filterType = null, int $limit = 100): array
    {
        $sql = 'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . ', r.type
                FROM reactions r JOIN users u ON u.id = r.user_id
                WHERE r.target_type = ? AND r.target_id = ? AND u.is_active = 1';
        $params = [$targetType, $targetId];
        if ($filterType && in_array($filterType, self::TYPES, true)) {
            $sql .= ' AND r.type = ?';
            $params[] = $filterType;
        }
        $sql .= ' ORDER BY r.created_at DESC LIMIT ?';
        $params[] = $limit;
        return self::db()->all($sql, $params);
    }

    public static function totalFor(string $targetType, int $targetId): int
    {
        return (int) self::db()->value(
            'SELECT COUNT(*) FROM reactions WHERE target_type = ? AND target_id = ?',
            [$targetType, $targetId],
            0
        );
    }
}
