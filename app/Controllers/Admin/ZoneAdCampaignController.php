<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Models\AuditLog;

class ZoneAdCampaignController extends Controller
{
    private static function tableOk(): bool
    {
        try {
            Database::fetch('SELECT 1 FROM zone_ad_campaigns LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return list<string> */
    private function zoneNameHints(): array
    {
        try {
            $rows = Database::fetchAll(
                "SELECT DISTINCT TRIM(zone) AS z FROM properties
                 WHERE zone IS NOT NULL AND TRIM(zone) <> ''
                 ORDER BY z ASC LIMIT 300"
            );

            return array_values(array_filter(array_column($rows, 'z')));
        } catch (\Throwable) {
            return [];
        }
    }

    public function index(): void
    {
        if (!self::tableOk()) {
            View::render('admin/zone_ads/missing-table', [
                'page_title' => 'โฆษณาโซน',
            ], 'layouts/admin');

            return;
        }
        $rows = Database::fetchAll(
            'SELECT z.*, p.name AS property_name FROM zone_ad_campaigns z
             LEFT JOIN properties p ON p.id = z.property_id
             ORDER BY z.zone ASC, z.sort_order ASC, z.id ASC'
        );
        View::render('admin/zone_ads/index', [
            'page_title' => 'โฆษณาโซน',
            'rows'       => $rows,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/zone-ads'));
        }
        View::render('admin/zone_ads/form', [
            'page_title'  => 'เพิ่มโฆษณาโซน',
            'row'         => null,
            'zone_hints'  => $this->zoneNameHints(),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/zone-ads'));
        }
        $data = $this->validate([
            'zone' => 'required|max:80',
        ]);
        $zone = trim($data['zone']);
        $pid = trim((string)($_POST['property_id'] ?? ''));
        $propertyId = $pid === '' ? null : (int)$pid;
        if ($propertyId !== null && $propertyId > 0 && !Database::fetch('SELECT id FROM properties WHERE id = :id', ['id' => $propertyId])) {
            Session::flash('error', 'ไม่พบที่พักสำหรับผู้ซื้อโฆษณา');
            Session::withOld($_POST);
            redirect(url('/admin/zone-ads/create'));
        }
        if ($propertyId !== null && $propertyId <= 0) {
            $propertyId = null;
        }
        try {
            $imagePath = $this->resolveImageCreate();
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Session::withOld($_POST);
            redirect(url('/admin/zone-ads/create'));
        }
        $ins = [
            'zone'        => $zone,
            'title'       => trim((string)($_POST['title'] ?? '')) ?: null,
            'image_path'  => $imagePath,
            'link_url'    => trim((string)($_POST['link_url'] ?? '')) ?: null,
            'property_id' => $propertyId,
            'starts_at'   => self::dateOrNull($_POST['starts_at'] ?? ''),
            'ends_at'     => self::dateOrNull($_POST['ends_at'] ?? ''),
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
            'is_active'   => !empty($_POST['is_active']) ? 1 : 0,
        ];
        $id = (int)Database::insert('zone_ad_campaigns', $ins);
        AuditLog::record('zone_ad_campaign_created', ['id' => $id, 'zone' => $zone], 'zone_ad_campaign', $id);
        Session::flash('success', 'บันทึกโฆษณาเรียบร้อย');

        redirect(url('/admin/zone-ads'));
    }

    public function edit(int $id): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/zone-ads'));
        }
        $row = Database::fetch('SELECT * FROM zone_ad_campaigns WHERE id = :id', ['id' => $id]);
        if (!$row) {
            http_response_code(404);
            View::render('errors/404');

            return;
        }
        View::render('admin/zone_ads/form', [
            'page_title' => 'แก้ไขโฆษณาโซน',
            'row'        => $row,
            'zone_hints' => $this->zoneNameHints(),
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/zone-ads'));
        }
        $prev = Database::fetch('SELECT * FROM zone_ad_campaigns WHERE id = :id', ['id' => $id]);
        if (!$prev) {
            http_response_code(404);
            View::render('errors/404');

            return;
        }
        $data = $this->validate([
            'zone' => 'required|max:80',
        ]);
        $zone = trim($data['zone']);
        $pid = trim((string)($_POST['property_id'] ?? ''));
        $propertyId = $pid === '' ? null : (int)$pid;
        if ($propertyId !== null && $propertyId > 0 && !Database::fetch('SELECT id FROM properties WHERE id = :id', ['id' => $propertyId])) {
            Session::flash('error', 'ไม่พบที่พักสำหรับผู้ซื้อโฆษณา');
            redirect(url('/admin/zone-ads/' . $id . '/edit'));
        }
        if ($propertyId !== null && $propertyId <= 0) {
            $propertyId = null;
        }
        try {
            $imgNew = $this->resolveImageUpdate((string)($prev['image_path'] ?? ''));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            redirect(url('/admin/zone-ads/' . $id . '/edit'));
        }
        $upd = [
            'zone'        => $zone,
            'title'       => trim((string)($_POST['title'] ?? '')) ?: null,
            'link_url'    => trim((string)($_POST['link_url'] ?? '')) ?: null,
            'property_id' => $propertyId,
            'starts_at'   => self::dateOrNull($_POST['starts_at'] ?? ''),
            'ends_at'     => self::dateOrNull($_POST['ends_at'] ?? ''),
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
            'is_active'   => !empty($_POST['is_active']) ? 1 : 0,
        ];
        if ($imgNew !== null) {
            $upd['image_path'] = $imgNew;
        }
        Database::update('zone_ad_campaigns', $upd, 'id = :id', ['id' => $id]);
        AuditLog::record(
            'zone_ad_campaign_updated',
            ['id' => $id, 'zone_was' => $prev['zone'], 'zone' => $zone],
            'zone_ad_campaign',
            $id
        );
        Session::flash('success', 'บันทึกเรียบร้อย');

        redirect(url('/admin/zone-ads'));
    }

    public function delete(int $id): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/zone-ads'));
        }
        $row = Database::fetch('SELECT id, zone FROM zone_ad_campaigns WHERE id = :id', ['id' => $id]);
        if ($row) {
            Database::delete('zone_ad_campaigns', 'id = :id', ['id' => $id]);
            AuditLog::record('zone_ad_campaign_deleted', ['zone' => $row['zone']], 'zone_ad_campaign', $id);
            Session::flash('success', 'ลบโฆษณาเรียบร้อย');
        }
        redirect(url('/admin/zone-ads'));
    }

    /** @throws \RuntimeException */
    private function resolveImageCreate(): string
    {
        $url = trim((string)($_POST['image_url'] ?? ''));
        if ($url !== '') {
            return $url;
        }
        if (!empty($_FILES['image']['tmp_name'])) {
            return Upload::image('image', 'zone-ads');
        }

        throw new \RuntimeException('กรุณาอัปโหลดรูป หรือใส่ URL รูป');
    }

    /** คืน null = เก็บรูปเดิม */
    private function resolveImageUpdate(string $current): ?string
    {
        $url = trim((string)($_POST['image_url'] ?? ''));
        if ($url !== '') {
            return $url;
        }
        if (!empty($_FILES['image']['tmp_name'])) {
            return Upload::image('image', 'zone-ads');
        }

        return null;
    }

    private static function dateOrNull(string $v): ?string
    {
        $v = trim($v);

        return $v === '' ? null : $v;
    }
}
