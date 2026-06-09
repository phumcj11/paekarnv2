<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\AuditLog;

class CouponCampaignController extends Controller
{
    private static function tableOk(): bool
    {
        try {
            Database::fetch('SELECT 1 FROM coupon_campaigns LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function index(): void
    {
        if (!self::tableOk()) {
            View::render('admin/coupon_campaigns/missing-table', [
                'page_title' => 'แคมเปญคูปอง',
            ], 'layouts/admin');

            return;
        }
        $rows = Database::fetchAll('SELECT * FROM coupon_campaigns ORDER BY id DESC');
        View::render('admin/coupon_campaigns/index', [
            'page_title' => 'แคมเปญคูปอง',
            'rows'       => $rows,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/coupon-campaigns'));
        }
        View::render('admin/coupon_campaigns/form', [
            'page_title' => 'เพิ่มแคมเปญคูปอง',
            'row'        => null,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/coupon-campaigns'));
        }
        $data = $this->validate([
            'code'        => 'required|max:64',
            'name'        => 'required|max:180',
            'face_value'  => 'required|numeric',
            'sale_price'  => 'required|numeric',
        ]);
        $code = strtoupper(trim((string)preg_replace('/\s+/', '', trim($data['code']))));
        if ($code === '' || !preg_match('/^[A-Z0-9_-]+$/', $code)) {
            Session::flash('error', 'รหัสแคมเปญใช้ได้เฉพาะ A–Z ตัวเลข _ และ -');
            Session::withOld($_POST);
            redirect(url('/admin/coupon-campaigns/create'));
        }
        if (Database::fetch('SELECT id FROM coupon_campaigns WHERE code = :c LIMIT 1', ['c' => $code])) {
            Session::flash('error', 'รหัสแคมเปญซ้ำ');
            Session::withOld($_POST);
            redirect(url('/admin/coupon-campaigns/create'));
        }
        $face = max(0, (float)$data['face_value']);
        $sale = max(0, (float)$data['sale_price']);
        $ins = [
            'code'       => $code,
            'name'       => trim($data['name']),
            'face_value' => $face,
            'sale_price' => $sale,
            'starts_at'  => self::dtOrNull($_POST['starts_at'] ?? ''),
            'ends_at'    => self::dtOrNull($_POST['ends_at'] ?? ''),
            'is_active'  => !empty($_POST['is_active']) ? 1 : 0,
        ];
        $id = (int)Database::insert('coupon_campaigns', $ins);
        AuditLog::record('coupon_campaign_created', ['id' => $id, 'code' => $code], 'coupon_campaign', $id);
        Session::flash('success', 'บันทึกแคมเปญเรียบร้อย');

        redirect(url('/admin/coupon-campaigns'));
    }

    public function edit(int $id): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/coupon-campaigns'));
        }
        $row = Database::fetch('SELECT * FROM coupon_campaigns WHERE id = :id', ['id' => $id]);
        if (!$row) {
            http_response_code(404);
            View::render('errors/404');

            return;
        }
        View::render('admin/coupon_campaigns/form', [
            'page_title' => 'แก้ไขแคมเปญคูปอง',
            'row'        => $row,
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/coupon-campaigns'));
        }
        $prev = Database::fetch('SELECT * FROM coupon_campaigns WHERE id = :id', ['id' => $id]);
        if (!$prev) {
            http_response_code(404);
            View::render('errors/404');

            return;
        }
        $data = $this->validate([
            'code'       => 'required|max:64',
            'name'       => 'required|max:180',
            'face_value' => 'required|numeric',
            'sale_price' => 'required|numeric',
        ]);
        $code = strtoupper(trim((string)preg_replace('/\s+/', '', trim($data['code']))));
        if ($code === '' || !preg_match('/^[A-Z0-9_-]+$/', $code)) {
            Session::flash('error', 'รหัสแคมเปญใช้ได้เฉพาะ A–Z ตัวเลข _ และ -');
            redirect(url('/admin/coupon-campaigns/' . $id . '/edit'));
        }
        $dup = Database::fetch(
            'SELECT id FROM coupon_campaigns WHERE code = :c AND id <> :id LIMIT 1',
            ['c' => $code, 'id' => $id]
        );
        if ($dup) {
            Session::flash('error', 'รหัสแคมเปญซ้ำกับแถวอื่น');
            redirect(url('/admin/coupon-campaigns/' . $id . '/edit'));
        }
        $upd = [
            'code'       => $code,
            'name'       => trim($data['name']),
            'face_value' => max(0, (float)$data['face_value']),
            'sale_price' => max(0, (float)$data['sale_price']),
            'starts_at'  => self::dtOrNull($_POST['starts_at'] ?? ''),
            'ends_at'    => self::dtOrNull($_POST['ends_at'] ?? ''),
            'is_active'  => !empty($_POST['is_active']) ? 1 : 0,
        ];
        Database::update('coupon_campaigns', $upd, 'id = :id', ['id' => $id]);
        AuditLog::record('coupon_campaign_updated', ['id' => $id, 'from_code' => $prev['code'], 'to_code' => $code], 'coupon_campaign', $id);
        Session::flash('success', 'บันทึกเรียบร้อย');

        redirect(url('/admin/coupon-campaigns'));
    }

    public function delete(int $id): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/coupon-campaigns'));
        }
        $row = Database::fetch('SELECT id, code FROM coupon_campaigns WHERE id = :id', ['id' => $id]);
        if ($row) {
            Database::delete('coupon_campaigns', 'id = :id', ['id' => $id]);
            AuditLog::record('coupon_campaign_deleted', ['code' => $row['code']], 'coupon_campaign', $id);
            Session::flash('success', 'ลบแคมเปญเรียบร้อย');
        }
        redirect(url('/admin/coupon-campaigns'));
    }

    private static function dtOrNull(string $v): ?string
    {
        $v = trim(str_replace('T', ' ', $v));
        if ($v === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v)) {
            return $v . ':00';
        }

        return $v;
    }
}
