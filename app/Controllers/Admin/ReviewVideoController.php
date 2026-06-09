<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\ReviewVideo;

class ReviewVideoController extends Controller
{
    public function index(): void
    {
        View::render('admin/review_videos/index', [
            'page_title' => 'วิดีโอแนะนำ',
            'rows'       => ReviewVideo::adminAll(),
            'categories' => ReviewVideo::CATEGORIES,
            'platforms'  => ReviewVideo::PLATFORMS,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        View::render('admin/review_videos/form', [
            'page_title'  => 'เพิ่มวิดีโอ',
            'row'         => null,
            'categories'  => ReviewVideo::CATEGORIES,
            'properties'  => $this->propertyChoices(),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $parsed = $this->validatedPayload(null);
        if ($parsed === null) {
            redirect(url('/admin/review-videos/create'));
        }
        Database::insert('review_videos', $parsed);
        Session::flash('success', 'เพิ่มวิดีโอเรียบร้อย');
        redirect(url('/admin/review-videos'));
    }

    public function edit(int $id): void
    {
        $row = ReviewVideo::find($id);
        if (!$row) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }
        View::render('admin/review_videos/form', [
            'page_title'  => 'แก้ไขวิดีโอ',
            'row'         => $row,
            'categories'  => ReviewVideo::CATEGORIES,
            'properties'  => $this->propertyChoices(),
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        $existing = ReviewVideo::find($id);
        if (!$existing) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }
        $parsed = $this->validatedPayload((int)$existing['id']);
        if ($parsed === null) {
            redirect(url('/admin/review-videos/' . $id . '/edit'));
        }
        Database::update('review_videos', $parsed, 'id = :id', ['id' => $id]);
        Session::flash('success', 'บันทึกเรียบร้อย');
        redirect(url('/admin/review-videos'));
    }

    public function delete(int $id): void
    {
        Database::delete('review_videos', 'id = :id', ['id' => $id]);
        Session::flash('success', 'ลบเรียบร้อย');
        redirect(url('/admin/review-videos'));
    }

    /** @return list<array{id:int,name:string}> */
    private function propertyChoices(): array
    {
        return Database::fetchAll(
            'SELECT id, name FROM properties WHERE status IN (\'published\',\'pending\') ORDER BY name ASC LIMIT 500'
        );
    }

    /**
     * @return ?array<string,mixed>
     */
    private function validatedPayload(?int $ignoreIdForDup): ?array
    {
        $urlRaw = trim((string)($_POST['video_url'] ?? $_POST['youtube_input'] ?? ''));
        $parsed = ReviewVideo::parseVideoUrl($urlRaw);
        if ($parsed === null) {
            Session::flash('error', 'ไม่รู้จักลิงก์ — ใช้ YouTube / Shorts / TikTok / Instagram Reels');
            Session::withOld($_POST);

            return null;
        }

        $dupSql = ReviewVideo::hasSocialColumns()
            ? 'SELECT id FROM review_videos WHERE platform = :p AND external_id = :e LIMIT 1'
            : 'SELECT id FROM review_videos WHERE youtube_id = :e LIMIT 1';
        $dupParams = ReviewVideo::hasSocialColumns()
            ? ['p' => $parsed['platform'], 'e' => $parsed['external_id']]
            : ['e' => $parsed['external_id']];
        $dup = Database::fetch($dupSql, $dupParams);
        if ($dup && (int)$dup['id'] !== (int)($ignoreIdForDup ?? 0)) {
            Session::flash('error', 'คลิปนี้มีในระบบแล้ว');
            Session::withOld($_POST);

            return null;
        }

        $title = trim((string)($_POST['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 200) {
            Session::flash('error', 'หัวข้อจำเป็น และไม่เกิน 200 ตัวอักษร');
            Session::withOld($_POST);

            return null;
        }

        $category = (string)($_POST['category'] ?? 'general');
        if (!array_key_exists($category, ReviewVideo::CATEGORIES)) {
            $category = 'general';
        }

        $propRaw = $_POST['related_property_id'] ?? '';
        $propId  = $propRaw === '' || $propRaw === null ? null : (int)$propRaw;
        if ($propId !== null && $propId <= 0) {
            $propId = null;
        }

        $orientation = !empty($_POST['orientation_portrait']) ? 'portrait' : 'landscape';
        if (empty($_POST['orientation_override'])) {
            $orientation = $parsed['orientation'];
        }

        $data = [
            'youtube_id'          => ($parsed['platform'] === 'youtube') ? $parsed['external_id'] : null,
            'title'               => $title,
            'description'         => trim((string)($_POST['description'] ?? '')) ?: null,
            'related_property_id' => $propId,
            'category'            => $category,
            'sort_order'          => max(0, (int)($_POST['sort_order'] ?? 0)),
            'is_active'           => !empty($_POST['is_active']) ? 1 : 0,
        ];

        if (ReviewVideo::hasSocialColumns()) {
            $data['platform']    = $parsed['platform'];
            $data['external_id'] = $parsed['external_id'];
            $data['source_url']  = $parsed['source_url'];
            $data['orientation'] = $orientation;
        }

        return $data;
    }
}
