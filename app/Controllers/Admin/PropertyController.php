<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\Property;
use App\Services\OwnerPropertyLimit;
use App\Support\PropertyBookingCapabilities;

class PropertyController extends Controller
{
    /** @return array<int,array<string,mixed>> */
    private function ownersForSelect(): array
    {
        return Database::fetchAll(
            'SELECT o.id, o.business_name, u.name, u.email FROM owners o
             JOIN users u ON u.id = o.user_id
             ORDER BY COALESCE(NULLIF(TRIM(o.business_name), \'\'), u.name), u.email'
        );
    }

    private function propertyPayload(bool $isCreate): array
    {
        $data = $this->validate([
            'name'         => 'required|max:180',
            'type'         => 'required|in:raft,resort,homestay,house,pool_villa,hotel,camping',
            'zone'         => 'required|max:80',
            'phone'        => 'phone',
        ]);

        $payload = [
            'name'             => $data['name'],
            'name_en'          => $_POST['name_en'] ?? null,
            'type'             => $data['type'],
            'province'         => trim((string)($_POST['province'] ?? '')) ?: 'กาญจนบุรี',
            'district'         => trim((string)($_POST['district'] ?? '')) ?: null,
            'zone'             => $data['zone'],
            'address'          => $_POST['address'] ?? null,
            'latitude'         => $_POST['latitude'] !== '' ? $_POST['latitude'] : null,
            'longitude'        => $_POST['longitude'] !== '' ? $_POST['longitude'] : null,
            'description'      => $_POST['description'] ?? null,
            'rules'            => $_POST['rules'] ?? null,
            'check_in'         => $_POST['check_in'] ?? '14:00:00',
            'check_out'        => $_POST['check_out'] ?? '12:00:00',
            'pet_policy'       => $_POST['pet_policy'] ?? 'not_allowed',
            'deposit_amount'   => (float)($_POST['deposit_amount'] ?? 0),
            'deposit_note'     => $_POST['deposit_note'] ?? null,
            'phone'            => $_POST['phone'] ?? null,
            'line_id'          => $_POST['line_id'] ?? null,
            'facebook_url'     => $_POST['facebook_url'] ?? null,
            'contact_email'    => trim((string)($_POST['contact_email'] ?? '')) ?: null,
            'website_url'      => trim((string)($_POST['website_url'] ?? '')) ?: null,
            'raft_variant'     => Property::normalizeRaftVariant($data['type'], $_POST['raft_variant'] ?? ''),
            'owner_intake'     => Property::encodeOwnerIntakeFromPost($_POST),
            'meta_title'       => $_POST['meta_title'] ?? null,
            'meta_description' => $_POST['meta_description'] ?? null,
            'priority'         => max(0, min(9999, (int)($_POST['priority'] ?? 0))),
            'is_featured'      => !empty($_POST['is_featured']) ? 1 : 0,
        ];

        if (Database::tableHasColumn('properties', 'membership_featured_applied')) {
            $payload['membership_featured_applied'] = 0;
        }

        if (!$isCreate) {
            $payload['is_verified'] = !empty($_POST['is_verified']) ? 1 : 0;
        }

        return $payload;
    }

    /** @param array<string,mixed> $payload */
    private function mergeCouponContractSignedIntoPayload(array &$payload): void
    {
        if (!Database::tableHasColumn('properties', 'coupon_contract_signed_at')) {
            return;
        }
        $raw = trim((string)($_POST['coupon_contract_signed_at'] ?? ''));
        if ($raw !== '') {
            $raw = str_replace('T', ' ', $raw);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw)) {
                $raw .= ':00';
            }
            $payload['coupon_contract_signed_at'] = $raw;
        } else {
            $payload['coupon_contract_signed_at'] = null;
        }
    }

    /** @param array<string,mixed> $payload */
    private function mergeBookingCapabilitiesIntoPayload(array &$payload): void
    {
        $cap = PropertyBookingCapabilities::payloadFromPost($_POST);
        if ($cap === null) {
            Session::flash('error', 'เลือกความสามารถการจองอย่างน้อย 1 ข้อ (ติดต่อ / คูปอง / จองออนไลน์)');
            back();
        }
        foreach ($cap as $k => $v) {
            $payload[$k] = $v;
        }
        if (Database::tableHasColumn('properties', 'show_line_contact')) {
            $payload['show_line_contact'] = !empty($_POST['show_line_contact']) ? 1 : 0;
        }
    }

    /** คะแนนแสดงบนเว็บ + ล็อกไม่ให้คำนวณจากรีวิว */
    private function adminDisplayPayload(): array
    {
        return [
            'rating_avg'     => round(max(0, min(5, (float)($_POST['admin_rating_avg'] ?? 0))), 2),
            'rating_count'   => max(0, (int)($_POST['admin_rating_count'] ?? 0)),
            'rating_locked'  => !empty($_POST['rating_locked']) ? 1 : 0,
        ];
    }

    /** ราคาเริ่มต้นเมื่อยังไม่มียูนิตเปิดขาย */
    private function applyAdminDisplayMinPrice(int $propertyId): void
    {
        $n = (int)Database::fetch(
            'SELECT COUNT(*) AS c FROM property_units WHERE property_id = :id AND ' . Property::publicUnitCondition(''),
            ['id' => $propertyId]
        )['c'];
        if ($n > 0) {
            return;
        }
        if (trim((string)($_POST['admin_display_min_price'] ?? '')) === '') {
            return;
        }
        Database::update(
            'properties',
            ['min_price' => max(0, (float)$_POST['admin_display_min_price'])],
            'id = :id',
            ['id' => $propertyId]
        );
    }

    public function index(): void
    {
        $perPage = (int)config('app.paginate.admin', 20);
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $where = "1=1"; $params = [];
        if (!empty($_GET['status'])) { $where .= " AND p.status = :st"; $params['st'] = $_GET['status']; }
        if (!empty($_GET['q']))      { $where .= " AND (p.name LIKE :q OR p.zone LIKE :q)"; $params['q'] = '%'.$_GET['q'].'%'; }

        $rows = Database::fetchAll(
            "SELECT p.*, o.business_name AS owner_name FROM properties p
             LEFT JOIN owners o ON o.id=p.owner_id WHERE $where
             ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset", $params);
        $total = (int)Database::fetch("SELECT COUNT(*) c FROM properties p WHERE $where", $params)['c'];

        View::render('admin/properties/index', [
            'page_title' => 'จัดการที่พัก',
            'rows' => $rows, 'total' => $total, 'page' => $page,
            'totalPages' => max(1, (int)ceil($total/$perPage)),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $amenities = Database::fetchAll('SELECT * FROM amenities ORDER BY sort_order');
        View::render('owner/properties/form', [
            'page_title'                 => 'เพิ่มที่พัก (Admin)',
            'property'                   => null,
            'amenities'                  => $amenities,
            'selectedAmenities'          => [],
            'route_prefix'               => 'admin',
            'owners_list'                => $this->ownersForSelect(),
            'units'                      => [],
            'show_coupon_contract_field' => Database::tableHasColumn('properties', 'coupon_contract_signed_at'),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'         => 'required|max:180',
            'type'         => 'required|in:raft,resort,homestay,house,pool_villa,hotel,camping',
            'zone'         => 'required|max:80',
            'owner_id'     => 'required|integer',
            'status'       => 'required|in:draft,pending,published,rejected,archived',
            'phone'        => 'phone',
        ]);

        $oid = (int)$data['owner_id'];
        if (!Database::fetch('SELECT id FROM owners WHERE id = :id', ['id' => $oid])) {
            Session::flash('error', 'ไม่พบเจ้าของแพที่เลือก');
            back();
        }

        if (!OwnerPropertyLimit::canAddProperty($oid)) {
            $max = OwnerPropertyLimit::maxProperties($oid);
            Session::flash('error', "เจ้าของคนนี้ถึงโควต้าแล้ว (สูงสุด {$max} ที่พัก) — ไปเพิ่ม max_properties ใน /admin/owners/{$oid}/edit ก่อน");
            back();
        }

        $cover = null;
        try {
            $cover = Upload::image('cover_image', 'properties');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            back();
        }

        $nameEn = trim((string)($_POST['name_en'] ?? '')) ?: null;
        $payload = array_merge($this->propertyPayload(true), $this->adminDisplayPayload());
        $payload['owner_id'] = $oid;
        $payload['slug'] = Property::uniqueSlug($data['name'], $nameEn);
        $payload['status'] = $data['status'];
        $payload['cover_image'] = $cover;
        $payload['min_price'] = trim((string)($_POST['admin_display_min_price'] ?? '')) !== ''
            ? max(0, (float)$_POST['admin_display_min_price'])
            : 0.0;
        $this->mergeCouponContractSignedIntoPayload($payload);
        $this->mergeBookingCapabilitiesIntoPayload($payload);
        if ($payload['status'] === 'published') {
            $payload['is_verified'] = !empty($_POST['mark_verified']) ? 1 : 0;
        } else {
            $payload['is_verified'] = 0;
        }

        $id = Property::create($payload);
        Property::syncPropertyAmenities($id, $_POST['amenities'] ?? []);
        Property::recalcMinPrice($id);
        $this->applyAdminDisplayMinPrice($id);

        AuditLog::record('property_created', ['name' => $payload['name'], 'status' => $payload['status']], 'property', $id);

        Session::flash('success', 'สร้างที่พักเรียบร้อย');
        redirect(url('/admin/properties/' . $id . '/edit'));
    }

    public function edit(int $id): void
    {
        $property = Database::fetch('SELECT * FROM properties WHERE id = :id', ['id' => $id]);
        if (!$property) { http_response_code(404); View::render('errors/404'); return; }

        $amenities = Database::fetchAll('SELECT * FROM amenities ORDER BY sort_order');
        $selected = array_column(Database::fetchAll(
            'SELECT amenity_id FROM property_amenities WHERE property_id = :id',
            ['id' => $id]
        ), 'amenity_id');
        $hasUnitImg = Database::tableHasColumn('property_images', 'unit_id');
        $images = Database::fetchAll(
            $hasUnitImg
                ? 'SELECT * FROM property_images WHERE property_id = :id AND unit_id IS NULL ORDER BY sort_order, id'
                : 'SELECT * FROM property_images WHERE property_id = :id ORDER BY sort_order, id',
            ['id' => $id]
        );
        $units = Database::fetchAll(
            'SELECT * FROM property_units WHERE property_id = :id ORDER BY sort_order, id',
            ['id' => $id]
        );

        View::render('owner/properties/form', [
            'page_title'                 => 'แก้ไขที่พัก (Admin): ' . $property['name'],
            'property'                   => $property,
            'amenities'                  => $amenities,
            'selectedAmenities'          => $selected,
            'images'                     => $images,
            'units'                      => $units,
            'route_prefix'               => 'admin',
            'owners_list'                => $this->ownersForSelect(),
            'show_coupon_contract_field' => Database::tableHasColumn('properties', 'coupon_contract_signed_at'),
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        $property = Database::fetch('SELECT * FROM properties WHERE id = :id', ['id' => $id]);
        if (!$property) { http_response_code(404); View::render('errors/404'); return; }

        $data = $this->validate([
            'name'         => 'required|max:180',
            'type'         => 'required|in:raft,resort,homestay,house,pool_villa,hotel,camping',
            'zone'         => 'required|max:80',
            'owner_id'     => 'required|integer',
            'status'       => 'required|in:draft,pending,published,rejected,archived',
            'phone'        => 'phone',
        ]);

        $oid = (int)$data['owner_id'];
        if (!Database::fetch('SELECT id FROM owners WHERE id = :id', ['id' => $oid])) {
            Session::flash('error', 'ไม่พบเจ้าของแพที่เลือก');
            back();
        }

        $nameEn = trim((string)($_POST['name_en'] ?? '')) ?: null;
        $useCustomSlug = !empty($_POST['slug_custom']);
        $slug = Property::resolveSlugForSave(
            $data['name'],
            $nameEn,
            $_POST['slug'] ?? null,
            $useCustomSlug,
            $id
        );

        $payload = array_merge($this->propertyPayload(false), $this->adminDisplayPayload());
        $payload['owner_id'] = $oid;
        $payload['slug'] = $slug;
        $payload['status'] = $data['status'];
        $this->mergeCouponContractSignedIntoPayload($payload);
        $this->mergeBookingCapabilitiesIntoPayload($payload);

        try {
            $cover = Upload::image('cover_image', 'properties');
            if ($cover) {
                $payload['cover_image'] = $cover;
            }
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            back();
        }

        Property::update($id, $payload);
        Property::syncPropertyAmenities($id, $_POST['amenities'] ?? []);
        Property::recalcMinPrice($id);
        $this->applyAdminDisplayMinPrice($id);

        $commercialChanges = [];
        foreach (['booking_mode', 'coupon_enabled', 'allow_contact', 'show_line_contact', 'allow_online_booking', 'booking_requires_payment'] as $k) {
            $from = $property[$k] ?? null;
            $to = $payload[$k] ?? null;
            if ((string) $from !== (string) $to) {
                $commercialChanges[$k] = ['from' => $from, 'to' => $to];
            }
        }
        if ($commercialChanges !== []) {
            AuditLog::record('property_commercial_flags_changed', $commercialChanges, 'property', $id);
        }

        if (Database::tableHasColumn('properties', 'coupon_contract_signed_at')) {
            $fromCc = $property['coupon_contract_signed_at'] ?? null;
            $toCc = $payload['coupon_contract_signed_at'] ?? null;
            if ((string)($fromCc ?? '') !== (string)($toCc ?? '')) {
                AuditLog::record(
                    'property_coupon_contract_signed_changed',
                    ['from' => $fromCc, 'to' => $toCc],
                    'property',
                    $id
                );
            }
        }

        AuditLog::record('property_updated', ['name' => $payload['name'], 'status' => $payload['status']], 'property', $id);

        Session::flash('success', 'บันทึกการเปลี่ยนแปลงเรียบร้อย');
        redirect(url('/admin/properties/' . $id . '/edit'));
    }

    public function delete(int $id): void
    {
        $property = Database::fetch('SELECT id FROM properties WHERE id = :id', ['id' => $id]);
        if (!$property) { http_response_code(404); View::render('errors/404'); return; }
        Property::destroy($id);
        Session::flash('success', 'ลบที่พักเรียบร้อย');
        redirect(url('/admin/properties'));
    }

    public function uploadImage(int $id): void
    {
        $property = Database::fetch('SELECT id FROM properties WHERE id = :id', ['id' => $id]);
        if (!$property) { http_response_code(404); View::render('errors/404'); return; }
        try {
            $path = Upload::image('image', 'properties');
            if ($path) {
                $ins = ['property_id' => $id, 'path' => $path, 'sort_order' => 0];
                if (Database::tableHasColumn('property_images', 'unit_id')) {
                    $ins['unit_id'] = null;
                }
                Database::insert('property_images', $ins);
                Session::flash('success', 'อัปโหลดรูปภาพเรียบร้อย');
            } else {
                Session::flash('error', 'ไม่ได้เลือกไฟล์');
            }
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        redirect(url('/admin/properties/' . $id . '/edit'));
    }

    public function deleteImage(int $id, int $img): void
    {
        $property = Database::fetch('SELECT id FROM properties WHERE id = :id', ['id' => $id]);
        if (!$property) { http_response_code(404); View::render('errors/404'); return; }
        $hasUnitImg = Database::tableHasColumn('property_images', 'unit_id');
        $row = Database::fetch(
            $hasUnitImg
                ? 'SELECT * FROM property_images WHERE id = :i AND property_id = :p AND unit_id IS NULL'
                : 'SELECT * FROM property_images WHERE id = :i AND property_id = :p',
            ['i' => $img, 'p' => $id]
        );
        if ($row) {
            Database::delete('property_images', 'id = :i', ['i' => $img]);
            $full = \App\Core\Application::$basePath . '/public/uploads/' . $row['path'];
            if (is_file($full) && !str_starts_with((string)$row['path'], 'http')) {
                @unlink($full);
            }
        }
        Session::flash('success', 'ลบรูปภาพเรียบร้อย');
        redirect(url('/admin/properties/' . $id . '/edit'));
    }

    public function show(int $id): void
    {
        $property = Database::fetch('SELECT * FROM properties WHERE id = :id', ['id' => $id]);
        if (!$property) { http_response_code(404); View::render('errors/404'); return; }
        $units = Database::fetchAll(
            'SELECT * FROM property_units WHERE property_id = :id ORDER BY sort_order',
            ['id' => $id]
        );
        $hasUnitImg = Database::tableHasColumn('property_images', 'unit_id');
        $images = Database::fetchAll(
            $hasUnitImg
                ? 'SELECT * FROM property_images WHERE property_id = :id AND unit_id IS NULL ORDER BY sort_order'
                : 'SELECT * FROM property_images WHERE property_id = :id ORDER BY sort_order',
            ['id' => $id]
        );
        View::render('admin/properties/show', [
            'page_title' => 'รายละเอียดที่พัก',
            'property' => $property, 'units' => $units, 'images' => $images,
        ], 'layouts/admin');
    }

    public function approve(int $id): void
    {
        Database::update('properties', ['status' => 'published', 'is_verified' => 1], 'id = :id', ['id' => $id]);
        AuditLog::record('property_status_changed', ['to' => 'published', 'is_verified' => 1], 'property', $id);
        Session::flash('success', 'อนุมัติที่พักเรียบร้อย');
        redirect(url('/admin/properties/' . $id));
    }

    public function reject(int $id): void
    {
        Database::update('properties', ['status' => 'rejected'], 'id = :id', ['id' => $id]);
        AuditLog::record('property_status_changed', ['to' => 'rejected'], 'property', $id);
        Session::flash('success', 'ปฏิเสธที่พักเรียบร้อย');
        redirect(url('/admin/properties/' . $id));
    }

    public function feature(int $id): void
    {
        $row = Database::fetch('SELECT id, is_featured FROM properties WHERE id = :id', ['id' => $id]);
        if (!$row) {
            http_response_code(404);
            View::render('errors/404');

            return;
        }
        $new = (int) $row['is_featured'] ? 0 : 1;
        $upd = ['is_featured' => $new];
        if (Database::tableHasColumn('properties', 'membership_featured_applied')) {
            $upd['membership_featured_applied'] = 0;
        }
        Database::update('properties', $upd, 'id = :id', ['id' => $id]);
        AuditLog::record('property_feature_toggled', ['is_featured' => $new], 'property', $id);
        Session::flash('success', 'อัปเดต Featured เรียบร้อย');
        back();
    }
}
