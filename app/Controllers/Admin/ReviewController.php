<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Review;

class ReviewController extends Controller
{
    public function index(): void
    {
        $rows = Database::fetchAll(
            "SELECT r.*, p.name AS property_name FROM reviews r
             JOIN properties p ON p.id=r.property_id
             ORDER BY r.created_at DESC LIMIT 200"
        );
        View::render('admin/reviews/index', [
            'page_title' => 'รีวิว',
            'rows'       => $rows,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        View::render('admin/reviews/form', [
            'page_title' => 'เพิ่มรีวิว',
            'row'        => null,
            'properties' => $this->propertyChoices(),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $payload = $this->reviewPayloadFromPost();
        if ($payload === null) {
            redirect(url('/admin/reviews/create'));

            return;
        }
        $payload['customer_id'] = null;
        $payload['booking_id'] = null;
        Database::insert('reviews', $payload);
        Review::recalcProperty((int)$payload['property_id']);
        Session::flash('success', 'เพิ่มรีวิวเรียบร้อย');
        redirect(url('/admin/reviews'));
    }

    public function edit(int $id): void
    {
        $row = Review::find($id);
        if (!$row) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');

            return;
        }
        View::render('admin/reviews/form', [
            'page_title' => 'แก้ไขรีวิว',
            'row'        => $row,
            'properties' => $this->propertyChoices(),
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        $existing = Review::find($id);
        if (!$existing) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');

            return;
        }
        $payload = $this->reviewPayloadFromPost();
        if ($payload === null) {
            redirect(url('/admin/reviews/' . $id . '/edit'));

            return;
        }
        $oldPid = (int)$existing['property_id'];
        $newPid = (int)$payload['property_id'];

        Database::update('reviews', $payload, 'id = :id', ['id' => $id]);
        Review::recalcProperty($oldPid);
        if ($newPid !== $oldPid) {
            Review::recalcProperty($newPid);
        }
        Session::flash('success', 'บันทึกการแก้ไขเรียบร้อย');
        redirect(url('/admin/reviews'));
    }

    public function approve(int $id): void
    {
        $review = Review::find($id);
        if (!$review) {
            Session::flash('error', 'ไม่พบ');
            back();
        }
        Database::update('reviews', ['is_approved' => 1], 'id = :id', ['id' => $id]);
        Review::recalcProperty((int)$review['property_id']);
        Session::flash('success', 'อนุมัติเรียบร้อย');
        back();
    }

    public function delete(int $id): void
    {
        $review = Review::find($id);
        if ($review) {
            Database::delete('reviews', 'id = :id', ['id' => $id]);
            Review::recalcProperty((int)$review['property_id']);
        }
        Session::flash('success', 'ลบเรียบร้อย');
        back();
    }

    /** @return list<array{id:int,name:string}> */
    private function propertyChoices(): array
    {
        return Database::fetchAll(
            "SELECT id, name FROM properties WHERE status IN ('published','pending','draft')
             ORDER BY name ASC LIMIT 800"
        );
    }

    /**
     * ฟิลด์ที่แก้จากฟอร์มเท่านั้น — ไม่แตะ customer_id / booking_id (ใช้ตอน update)
     *
     * @return ?array<string,mixed>
     */
    private function reviewPayloadFromPost(): ?array
    {
        $data = $this->validate([
            'property_id'   => 'required|integer',
            'reviewer_name' => 'required|max:120',
            'rating'        => 'required|integer',
            'title'         => 'max:160',
        ]);

        $pid = (int)$data['property_id'];
        if (!Database::fetch('SELECT id FROM properties WHERE id = :id', ['id' => $pid])) {
            Session::flash('error', 'ไม่พบที่พักที่เลือก');
            Session::withOld($_POST);

            return null;
        }

        $rating = max(1, min(5, (int)$data['rating']));
        $title = trim((string)($data['title'] ?? ''));
        $content = trim((string)($_POST['content'] ?? ''));

        return [
            'property_id'   => $pid,
            'reviewer_name' => trim((string)$data['reviewer_name']),
            'rating'        => $rating,
            'title'         => $title !== '' ? $title : null,
            'content'       => $content !== '' ? $content : null,
            'is_verified'   => !empty($_POST['is_verified']) ? 1 : 0,
            'is_approved'   => !empty($_POST['is_approved']) ? 1 : 0,
        ];
    }
}
