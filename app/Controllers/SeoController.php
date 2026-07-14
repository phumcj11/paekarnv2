<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Controller;
use App\Core\Database;

class SeoController extends Controller
{
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        $base = Application::$publicUrl;

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin/',
            'Disallow: /owner/',
            'Disallow: /provider/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /account/',
            'Disallow: /booking/create/',
            'Disallow: /activity/checkout/',
            'Disallow: /api/',
            '',
            "Sitemap: {$base}/sitemap.xml",
        ];

        echo implode("\n", $lines) . "\n";
        exit;
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        $base = rtrim(Application::$publicUrl, '/');

        $urls = [];
        $urls[] = ['loc' => $base . '/', 'changefreq' => 'daily'];
        $urls[] = ['loc' => $base . '/properties', 'changefreq' => 'daily'];
        $urls[] = ['loc' => $base . '/ai-search', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => $base . '/rafts', 'changefreq' => 'daily'];
        $urls[] = ['loc' => $base . '/resorts', 'changefreq' => 'daily'];
        $urls[] = ['loc' => $base . '/hotels', 'changefreq' => 'daily'];
        $urls[] = ['loc' => $base . '/stays', 'changefreq' => 'daily'];
        $urls[] = ['loc' => $base . '/pool-villas', 'changefreq' => 'daily'];
        $urls[] = ['loc' => $base . '/camping', 'changefreq' => 'daily'];
        $urls[] = ['loc' => $base . '/activities', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => $base . '/blog', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => $base . '/videos', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => $base . '/reviews', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => $base . '/places', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => $base . '/coupons', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => $base . '/about', 'changefreq' => 'monthly'];
        $urls[] = ['loc' => $base . '/contact', 'changefreq' => 'monthly'];

        $props = Database::fetchAll("SELECT slug, updated_at FROM properties WHERE status='published'");
        foreach ($props as $p) {
            $slug = rawurlencode((string)$p['slug']);
            $urls[] = [
                'loc'     => $base . '/property/' . $slug,
                'lastmod' => substr((string)($p['updated_at'] ?? ''), 0, 10),
            ];
        }

        if (Database::tableHasColumn('activity_products', 'slug')) {
            $activities = Database::fetchAll(
                "SELECT slug, updated_at FROM activity_products WHERE status='published'"
            );
            foreach ($activities as $a) {
                $slug = rawurlencode((string)$a['slug']);
                $urls[] = [
                    'loc'     => $base . '/activities/' . $slug,
                    'lastmod' => substr((string)($a['updated_at'] ?? ''), 0, 10),
                ];
            }
        }

        $posts = Database::fetchAll("SELECT slug, updated_at FROM blog_posts WHERE status='published'");
        foreach ($posts as $b) {
            $slug = rawurlencode((string)$b['slug']);
            $urls[] = [
                'loc'     => $base . '/blog/' . $slug,
                'lastmod' => substr((string)($b['updated_at'] ?? ''), 0, 10),
            ];
        }

        if (Database::tableHasColumn('visitor_places', 'slug')) {
            $visitorPlaces = Database::fetchAll(
                "SELECT slug, updated_at FROM visitor_places WHERE is_active = 1"
            );
            foreach ($visitorPlaces as $vp) {
                $slug = rawurlencode((string)$vp['slug']);
                $urls[] = [
                    'loc'     => $base . '/places/' . $slug,
                    'lastmod' => substr((string)($vp['updated_at'] ?? ''), 0, 10),
                ];
            }
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc>';
            if (!empty($u['lastmod'])) {
                echo '<lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</lastmod>';
            }
            if (!empty($u['changefreq'])) {
                echo '<changefreq>' . htmlspecialchars($u['changefreq'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</changefreq>';
            }
            echo "</url>\n";
        }
        echo '</urlset>';
        exit;
    }
}
