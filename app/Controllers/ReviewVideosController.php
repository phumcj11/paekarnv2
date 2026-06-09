<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\ReviewVideo;

class ReviewVideosController extends Controller
{
    public function index(): void
    {
        $perPage = 12;
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $total = (int) Database::fetch('SELECT COUNT(*) c FROM review_videos WHERE is_active = 1')['c'];
        $rows  = ReviewVideo::activeOrdered($perPage, $offset);
        $pages = max(1, (int) ceil($total / $perPage));

        $baseUrl = url('/videos');
        $listItems = [];
        foreach ($rows as $i => $v) {
            $listItems[] = [
                '@type'    => 'ListItem',
                'position' => $offset + $i + 1,
                'url'      => $baseUrl . '#video-' . $v['id'],
                'name'     => $v['title'],
            ];
        }

        $videoObjects = [];
        foreach ($rows as $v) {
            $thumb = ReviewVideo::thumbnailUrlFor($v);
            $videoObjects[] = [
                '@type'        => 'VideoObject',
                'name'         => $v['title'],
                'description'  => $v['description'] ?: $v['title'],
                'thumbnailUrl' => $thumb ? [$thumb] : [],
                'uploadDate'   => date('c', strtotime((string)$v['created_at'])),
                'embedUrl'     => ReviewVideo::embedUrlFor($v),
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@graph'   => array_merge(
                [
                    [
                        '@type'           => 'ItemList',
                        'name'            => 'วิดีโอแนะนำ แพที่พักและที่เที่ยวกาญจนบุรี',
                        'numberOfItems'   => $total,
                        'itemListElement' => $listItems,
                    ],
                ],
                $videoObjects
            ),
        ];

        $this->view('videos/index', [
            'meta_title'       => 'วิดีโอแนะนำ — แพกาญ.com',
            'meta_description' => 'รวมคลิปรีวิวแพ ที่พัก และที่เที่ยวกาญจนบุรีจาก YouTube Shorts TikTok และ Instagram Reels',
            'meta_canonical'   => $page > 1 ? url('/videos?page=' . $page) : url('/videos'),
            'schema_org_json'  => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'rows'             => $rows,
            'page'             => $page,
            'totalPages'       => $pages,
        ]);
    }
}
