<?php
namespace App\Models;

use App\Core\Upload;

final class Event extends Model
{
    public static function find(int $id): ?array
    {
        return self::db()->first('SELECT * FROM events WHERE id = ? LIMIT 1', [$id]);
    }

    public static function create(int $hostId, array $data): int
    {
        return self::db()->insert(
            'INSERT INTO events (host_id, title, description, location, starts_at, cover) VALUES (?, ?, ?, ?, ?, ?)',
            [$hostId, $data['title'], $data['description'] ?: null, $data['location'] ?: null, $data['starts_at'], $data['cover'] ?? null]
        );
    }

    public static function update(int $id, array $data): void
    {
        self::db()->execute(
            'UPDATE events SET title = ?, description = ?, location = ?, starts_at = ? WHERE id = ?',
            [$data['title'], $data['description'] ?: null, $data['location'] ?: null, $data['starts_at'], $id]
        );
    }

    public static function delete(int $id): void
    {
        $cover = self::db()->value('SELECT cover FROM events WHERE id = ?', [$id]);
        if ($cover) {
            Upload::delete((string) $cover);
        }
        self::db()->execute('DELETE FROM events WHERE id = ?', [$id]);
    }

    /** @return array<int,array> Upcoming events, with the viewer's RSVP attached. */
    public static function upcoming(int $viewerId, int $limit = 30): array
    {
        return self::db()->all(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id AND a.status = "going") AS going_count,
                    (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id AND a.status = "interested") AS interested_count,
                    (SELECT a.status FROM event_attendees a WHERE a.event_id = e.id AND a.user_id = ?) AS my_status
             FROM events e
             WHERE e.starts_at >= NOW()
             ORDER BY e.starts_at ASC LIMIT ?',
            [$viewerId, $limit]
        );
    }

    public static function past(int $viewerId, int $limit = 20): array
    {
        return self::db()->all(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id AND a.status = "going") AS going_count,
                    (SELECT a.status FROM event_attendees a WHERE a.event_id = e.id AND a.user_id = ?) AS my_status
             FROM events e WHERE e.starts_at < NOW()
             ORDER BY e.starts_at DESC LIMIT ?',
            [$viewerId, $limit]
        );
    }

    public static function hostedBy(int $userId): array
    {
        return self::db()->all('SELECT * FROM events WHERE host_id = ? ORDER BY starts_at DESC', [$userId]);
    }

    public static function rsvp(int $eventId, int $userId, string $status): void
    {
        if ($status === 'none') {
            self::db()->execute('DELETE FROM event_attendees WHERE event_id = ? AND user_id = ?', [$eventId, $userId]);
            return;
        }
        if (!in_array($status, ['going', 'interested'], true)) {
            return;
        }
        self::db()->execute(
            'INSERT INTO event_attendees (event_id, user_id, status) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status)',
            [$eventId, $userId, $status]
        );
    }

    public static function attendees(int $eventId, string $status = 'going'): array
    {
        return self::db()->all(
            'SELECT u.' . str_replace(', ', ', u.', User::PUBLIC_COLUMNS) . '
             FROM event_attendees a JOIN users u ON u.id = a.user_id
             WHERE a.event_id = ? AND a.status = ? AND u.is_active = 1',
            [$eventId, $status]
        );
    }

    public static function withCounts(int $id, int $viewerId): ?array
    {
        return self::db()->first(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id AND a.status = "going") AS going_count,
                    (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id AND a.status = "interested") AS interested_count,
                    (SELECT a.status FROM event_attendees a WHERE a.event_id = e.id AND a.user_id = ?) AS my_status
             FROM events e WHERE e.id = ? LIMIT 1',
            [$viewerId, $id]
        );
    }
}
