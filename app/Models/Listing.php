<?php
namespace App\Models;

use App\Core\Upload;

final class Listing extends Model
{
    public const CATEGORIES = [
        'vehicles'    => 'Vehicles',
        'property'    => 'Property Rentals',
        'apparel'     => 'Apparel',
        'electronics' => 'Electronics',
        'home'        => 'Home & Garden',
        'hobbies'     => 'Hobbies',
        'free'        => 'Free Stuff',
        'other'       => 'Other',
    ];

    public static function find(int $id): ?array
    {
        return self::db()->first('SELECT * FROM listings WHERE id = ? LIMIT 1', [$id]);
    }

    public static function create(int $sellerId, array $data): int
    {
        return self::db()->insert(
            'INSERT INTO listings (seller_id, title, description, price, category, location, image) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $sellerId,
                $data['title'],
                $data['description'] ?: null,
                (float) $data['price'],
                array_key_exists($data['category'], self::CATEGORIES) ? $data['category'] : 'other',
                $data['location'] ?: null,
                $data['image'] ?? null,
            ]
        );
    }

    public static function delete(int $id): void
    {
        $image = self::db()->value('SELECT image FROM listings WHERE id = ?', [$id]);
        if ($image) {
            Upload::delete((string) $image);
        }
        self::db()->execute('DELETE FROM listings WHERE id = ?', [$id]);
    }

    public static function markSold(int $id, bool $sold = true): void
    {
        self::db()->execute('UPDATE listings SET is_sold = ? WHERE id = ?', [$sold ? 1 : 0, $id]);
    }

    /** @return array<int,array> */
    public static function browse(?string $category = null, string $search = '', int $limit = 40): array
    {
        $sql    = 'SELECT * FROM listings WHERE is_sold = 0';
        $params = [];
        if ($category && array_key_exists($category, self::CATEGORIES)) {
            $sql     .= ' AND category = ?';
            $params[] = $category;
        }
        if ($search !== '') {
            $sql     .= ' AND (title LIKE ? OR description LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $sql     .= ' ORDER BY created_at DESC LIMIT ?';
        $params[] = $limit;

        $rows    = self::db()->all($sql, $params);
        $sellers = User::findMany(array_column($rows, 'seller_id'));
        foreach ($rows as &$row) {
            $row['seller'] = $sellers[(int) $row['seller_id']] ?? null;
        }
        unset($row);
        return $rows;
    }

    public static function forSeller(int $sellerId): array
    {
        return self::db()->all('SELECT * FROM listings WHERE seller_id = ? ORDER BY created_at DESC', [$sellerId]);
    }

    public static function withSeller(int $id): ?array
    {
        $row = self::find($id);
        if ($row) {
            $row['seller'] = User::find((int) $row['seller_id']);
        }
        return $row;
    }
}
