<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\ReviewFacebookPost;

class ReviewFacebookPostController extends Controller
{
    public function index(): void
    {
        View::render('admin/review_facebook_posts/index', [
            'page_title' => 'โพสต์ Facebook (แนะนำ)',
            'rows'       => ReviewFacebookPost::adminAll(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        View::render('admin/review_facebook_posts/form', [
            'page_title' => 'เพิ่มโพสต์ Facebook',
            'row'        => null,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $parsed = $this->validatedPayload(null);
        if ($parsed === null) {
            redirect(url('/admin/review-facebook-posts/create'));
        }
        Database::insert('review_facebook_posts', $parsed);
        Session::flash('success', 'เพิ่มโพสต์เรียบร้อย');
        redirect(url('/admin/review-facebook-posts'));
    }

    public function edit(int $id): void
    {
        $row = ReviewFacebookPost::find($id);
        if (!$row) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');

            return;
        }
        View::render('admin/review_facebook_posts/form', [
            'page_title' => 'แก้ไขโพสต์ Facebook',
            'row'        => $row,
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        $existing = ReviewFacebookPost::find($id);
        if (!$existing) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');

            return;
        }
        $parsed = $this->validatedPayload((int)$existing['id']);
        if ($parsed === null) {
            redirect(url('/admin/review-facebook-posts/' . $id . '/edit'));
        }
        Database::update('review_facebook_posts', $parsed, 'id = :id', ['id' => $id]);
        Session::flash('success', 'บันทึกเรียบร้อย');
        redirect(url('/admin/review-facebook-posts'));
    }

    public function delete(int $id): void
    {
        Database::delete('review_facebook_posts', 'id = :id', ['id' => $id]);
        Session::flash('success', 'ลบเรียบร้อย');
        redirect(url('/admin/review-facebook-posts'));
    }

    /** @return ?array<string,mixed> */
    private function validatedPayload(?int $ignoreIdForDup): ?array
    {
        $urlRaw = trim((string)($_POST['post_url'] ?? ''));
        $norm   = ReviewFacebookPost::normalizePostUrl($urlRaw);
        if ($norm === null) {
            Session::flash('error', 'ลิงก์ไม่ถูกต้อง — ใช้ permalink จาก facebook.com (โพสต์สาธารณะ)');
            Session::withOld($_POST);

            return null;
        }

        $dup = Database::fetch(
            'SELECT id FROM review_facebook_posts WHERE post_url = :u LIMIT 1',
            ['u' => $norm]
        );
        if ($dup && (int)$dup['id'] !== (int)($ignoreIdForDup ?? 0)) {
            Session::flash('error', 'โพสต์นี้มีในระบบแล้ว');
            Session::withOld($_POST);

            return null;
        }

        $title = trim((string)($_POST['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 200) {
            Session::flash('error', 'หัวข้อจำเป็น และไม่เกิน 200 ตัวอักษร');
            Session::withOld($_POST);

            return null;
        }

        return [
            'post_url'    => $norm,
            'title'       => $title,
            'description' => trim((string)($_POST['description'] ?? '')) ?: null,
            'sort_order'  => max(0, (int)($_POST['sort_order'] ?? 0)),
            'is_active'   => !empty($_POST['is_active']) ? 1 : 0,
        ];
    }
}
