<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class BlogPost extends Model
{
    protected static string $table = 'blog_posts';

    public static function published(int $limit = 9, int $offset = 0): array
    {
        $limit  = (int)$limit;
        $offset = (int)$offset;
        return Database::fetchAll(
            "SELECT * FROM blog_posts WHERE status='published'
             ORDER BY published_at DESC LIMIT $limit OFFSET $offset"
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch(
            "SELECT * FROM blog_posts WHERE slug = :s AND status='published' LIMIT 1",
            ['s' => $slug]
        );
    }
}
