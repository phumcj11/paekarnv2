<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Zone;

class ZoneController extends Controller
{
    public function index(): void
    {
        if (!Zone::tableExists()) {
            View::render('admin/zones/missing-table', [
                'page_title' => 'โซนที่พัก — ติดตั้งฐานข้อมูล',
            ], 'layouts/admin');

            return;
        }

        View::render('admin/zones/index', [
            'page_title' => 'โซนที่พัก',
            'rows'       => Zone::adminRowsWithUsage(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        if (!Zone::tableExists()) {
            redirect(url('/admin/zones'));
        }

        View::render('admin/zones/form', [
            'page_title' => 'เพิ่มโซน',
            'zone'       => null,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        if (!Zone::tableExists()) {
            redirect(url('/admin/zones'));
        }

        $data = $this->validate([
            'name' => 'required|max:80',
        ]);
        $name = trim($data['name']);
        $dup = Database::fetch('SELECT id FROM zones WHERE name = :n LIMIT 1', ['n' => $name]);
        if ($dup) {
            Session::flash('error', 'มีโซนชื่อนี้แล้ว');

            redirect(url('/admin/zones/create'));
        }

        $sort = (int)($_POST['sort_order'] ?? 0);
        Zone::create([
            'name'       => $name,
            'sort_order' => $sort,
        ]);
        Session::flash('success', 'เพิ่มโซนเรียบร้อย');

        redirect(url('/admin/zones'));
    }

    public function edit(int $id): void
    {
        if (!Zone::tableExists()) {
            redirect(url('/admin/zones'));
        }

        $zone = Zone::find($id);
        if (!$zone) {
            http_response_code(404);
            View::render('errors/404');

            return;
        }

        View::render('admin/zones/form', [
            'page_title' => 'แก้ไขโซน',
            'zone'       => $zone,
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        if (!Zone::tableExists()) {
            redirect(url('/admin/zones'));
        }

        $row = Zone::find($id);
        if (!$row) {
            http_response_code(404);
            View::render('errors/404');

            return;
        }

        $data = $this->validate([
            'name' => 'required|max:80',
        ]);
        $newName = trim($data['name']);
        $oldName = trim((string)$row['name']);

        $dup = Database::fetch('SELECT id FROM zones WHERE name = :n AND id <> :id LIMIT 1', ['n' => $newName, 'id' => $id]);
        if ($dup) {
            Session::flash('error', 'มีโซนชื่อนี้อยู่แล้ว (ซ้ำกับแถวอื่น)');

            redirect(url('/admin/zones/' . $id . '/edit'));
        }

        if ($newName !== $oldName) {
            Zone::applyRenameEverywhere($oldName, $newName);
        }

        Zone::update($id, [
            'name'       => $newName,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ]);

        Session::flash('success', 'อัปเดตโซนเรียบร้อย — ชื่อถูกอัปเดตในที่พัก / ที่เที่ยว และรูปปกโซนหน้าแรก (ถ้ามี) แล้ว');

        redirect(url('/admin/zones'));
    }

    public function delete(int $id): void
    {
        if (!Zone::tableExists()) {
            redirect(url('/admin/zones'));
        }

        $row = Zone::find($id);
        if (!$row) {
            Session::flash('error', 'ไม่พบโซน');

            redirect(url('/admin/zones'));
        }

        $name = trim((string)$row['name']);
        $u = Zone::usageCountsForName($name);
        if (($u['properties'] ?? 0) > 0 || ($u['visitor_places'] ?? 0) > 0) {
            Session::flash('error', 'ลบไม่ได้ — ยังมีที่พักหรือที่เที่ยวที่ใช้ชื่อโซนนี้อยู่ (' . (int)$u['properties'] . ' / ' . (int)$u['visitor_places'] . ')');

            redirect(url('/admin/zones'));
        }

        Zone::destroy($id);
        Session::flash('success', 'ลบโซนออกจากรายการมาตรฐานแล้ว (ข้อมูลที่พักที่มีชื่อโซนเดียวกันไม่ถูกลบ)');

        redirect(url('/admin/zones'));
    }
}
