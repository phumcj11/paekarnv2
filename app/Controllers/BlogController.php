<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\BlogPost;

class BlogController extends Controller
{
    public function index(): void
    {
        $perPage = (int)config('app.paginate.blog', 9);
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $rows  = BlogPost::published($perPage, $offset);
        $total = (int)Database::fetch("SELECT COUNT(*) c FROM blog_posts WHERE status='published'")['c'];
        $totalPages = max(1, (int)ceil($total / $perPage));

        $blogTitle = 'คู่มือที่พักและทริปกาญจนบุรี — แพกาญ.com';
        if ($page > 1) {
            $blogTitle .= ' · หน้า ' . $page;
        }
        $blogCanonical = $page > 1 ? url('/blog?' . http_build_query(['page' => $page])) : url('/blog');

        $this->view('blog/index', [
            'meta_title'       => $blogTitle,
            'meta_description' => 'รวมไกด์ท่องเที่ยว ที่พัก และทริปกาญจนบุรีจากแพกาญ.com — ที่พักตรวจสอบจริง รีวิวจริง ใช้คูปองลดค่าที่พักได้',
            'meta_canonical'   => $blogCanonical,
            'rows' => $rows, 'page' => $page, 'totalPages' => $totalPages,
        ]);
    }

    public function show(string $slug): void
    {
        $post = BlogPost::findBySlug($slug);
        if (!$post) { http_response_code(404); $this->view('errors/404'); return; }
        Database::query("UPDATE blog_posts SET view_count = view_count + 1 WHERE id = :id", ['id' => $post['id']]);
        $related = Database::fetchAll(
            "SELECT * FROM blog_posts WHERE status='published' AND id <> :id ORDER BY published_at DESC LIMIT 3",
            ['id' => $post['id']]
        );
        $cover   = (string)($post['cover_image'] ?? '');
        $metaOg  = $cover !== '' ? upload_url($cover) : '';
        $pageUrl = url('/blog/' . $post['slug']);

        $schema = [
            '@context'         => 'https://schema.org',
            '@type'            => 'Article',
            'headline'         => $post['title'],
            'datePublished'    => $post['published_at'],
            'dateModified'     => $post['updated_at'] ?? $post['published_at'],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $pageUrl],
        ];
        if ($cover !== '') {
            $schema['image'] = [$metaOg];
        }

        $this->view('blog/show', [
            'meta_title'       => $post['meta_title'] ?: $post['title'],
            'meta_description' => $post['meta_description'] ?: $post['excerpt'],
            'meta_og_image'    => $metaOg,
            'meta_canonical'   => $pageUrl,
            'og_type'          => 'article',
            'schema_org_json'  => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            'post' => $post, 'related' => $related,
        ]);
    }
}
