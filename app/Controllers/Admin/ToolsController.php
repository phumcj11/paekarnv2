<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\PageCache;
use App\Core\Session;
use App\Core\View;
use App\Support\ImageOptimizer;

class ToolsController extends Controller
{
    public function imageOptimizer(): void
    {
        View::render('admin/tools/image-optimizer', [
            'page_title' => 'Optimize รูปภาพ WebP',
            'webpOk'     => function_exists('imagewebp'),
            'scan'       => ImageOptimizer::scanStats(),
            'runResult'  => null,
        ], 'layouts/admin');
    }

    public function runImageOptimizer(): void
    {
        if (!function_exists('imagewebp')) {
            Session::flash('error', 'เซิร์ฟเวอร์ไม่รองรับ WebP (PHP GD ไม่มี imagewebp) — ติดต่อ hosting ให้เปิด gd + webp');
            redirect(url('/admin/tools/images'));
        }

        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $processed = [];
        $stats     = ImageOptimizer::regenerateAll(static function (string $relative) use (&$processed): void {
            $processed[] = $relative;
        });

        PageCache::flush();

        View::render('admin/tools/image-optimizer', [
            'page_title' => 'Optimize รูปภาพ WebP',
            'webpOk'     => true,
            'scan'       => ImageOptimizer::scanStats(),
            'runResult'  => [
                'stats'     => $stats,
                'processed' => $processed,
            ],
        ], 'layouts/admin');
    }
}
