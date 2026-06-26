<?php
namespace App\Controllers\Owner;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Models\Property;
use App\Support\PropertyBookingCapabilities;
use App\Services\AdminApprovalNotifyService;
use App\Services\OwnerFeatureGate;
use App\Services\OwnerPropertyLimit;
use App\Services\OwnerTier;
use App\Services\PropertyLineService;

class PropertyController extends Controller
{
    /** ตรวจสอบว่า property นี้เป็นของ owner ที่ login อยู่ */
    private function findOwn(int $id): ?array
    {
        $p = Property::find($id);
        if (!$p) return null;
        $ownerId = Auth::ownerId();
        if (Auth::isAdmin()) return $p;
        if ($ownerId && (int)$p['owner_id'] === (int)$ownerId) return $p;
        return null;
    }

    public function index(): void
    {
        $ownerId = Auth::ownerId();
        $where = "1=1"; $params = [];
        if ($ownerId) { $where = "owner_id = :oid"; $params['oid'] = $ownerId; }

        $rows = Database::fetchAll(
            "SELECT p.*, (SELECT COUNT(*) FROM property_units u WHERE u.property_id=p.id) AS unit_count,
                    (SELECT COUNT(*) FROM bookings b WHERE b.property_id=p.id) AS booking_count
             FROM properties p WHERE $where ORDER BY p.created_at DESC", $params);

        $canAdd      = !$ownerId || OwnerPropertyLimit::canAddProperty($ownerId);
        $isOverQuota = $ownerId && OwnerPropertyLimit::isOverQuota($ownerId);
        $maxProps    = $ownerId ? OwnerPropertyLimit::maxProperties($ownerId) : 1;

        View::render('owner/properties/index', [
            'page_title'  => 'ที่พักของฉัน',
            'rows'        => $rows,
            'canAdd'      => $canAdd,
            'isOverQuota' => $isOverQuota,
            'maxProps'    => $maxProps,
        ], 'layouts/owner');
    }

    public function create(): void
    {
        $ownerId = Auth::ownerId();
        if ($ownerId && !OwnerPropertyLimit::canAddProperty($ownerId)) {
            Session::flash('error', 'ถึงโควต้าที่พักแล้ว — ติดต่อแอดมินเพื่อขอเพิ่มโควต้า');
            redirect(url('/owner/properties'));
            return;
        }
        $amenities = Database::fetchAll("SELECT * FROM amenities ORDER BY sort_order");
        View::render('owner/properties/form', [
            'page_title' => 'เพิ่มที่พักใหม่',
            'property' => null, 'amenities' => $amenities, 'selectedAmenities' => [],
        ], 'layouts/owner');
    }

    public function store(): void
    {
        $ownerId = Auth::ownerId();
        if ($ownerId && !OwnerPropertyLimit::canAddProperty($ownerId)) {
            Session::flash('error', 'ถึงโควต้าที่พักแล้ว — ติดต่อแอดมินเพื่อขอเพิ่มโควต้า');
            redirect(url('/owner/properties'));
            return;
        }
        $data = $this->validate([
            'name'         => 'required|max:180',
            'type'         => 'required|in:raft,resort,homestay,house,pool_villa,hotel,camping',
            'zone'         => 'required|max:80',
            'phone'        => 'phone',
        ]);

        $cover = null;
        try { $cover = Upload::image('cover_image', 'properties'); } catch (\Throwable $e) { Session::flash('error', $e->getMessage()); back(); }

        $nameEn = trim((string)($_POST['name_en'] ?? '')) ?: null;
        $slug = Property::uniqueSlug($data['name'], $nameEn);
        $id = Property::create([
            'owner_id'        => Auth::ownerId(),
            'slug'            => $slug,
            'name'            => $data['name'],
            'name_en'         => $_POST['name_en'] ?? null,
            'type'            => $data['type'],
            'province'        => trim((string)($_POST['province'] ?? '')) ?: 'กาญจนบุรี',
            'district'        => trim((string)($_POST['district'] ?? '')) ?: null,
            'zone'            => $data['zone'],
            'address'         => $_POST['address'] ?? null,
            'latitude'        => $_POST['latitude'] !== '' ? $_POST['latitude'] : null,
            'longitude'       => $_POST['longitude'] !== '' ? $_POST['longitude'] : null,
            'cover_image'     => $cover,
            'description'     => $_POST['description'] ?? null,
            'rules'           => $_POST['rules'] ?? null,
            'check_in'        => $_POST['check_in'] ?? '14:00:00',
            'check_out'       => $_POST['check_out'] ?? '12:00:00',
            'pet_policy'      => $_POST['pet_policy'] ?? 'not_allowed',
            'deposit_amount'  => (float)($_POST['deposit_amount'] ?? 0),
            'deposit_note'    => $_POST['deposit_note'] ?? null,
            'phone'           => $_POST['phone'] ?? null,
            'line_id'         => $_POST['line_id'] ?? null,
            'facebook_url'    => $_POST['facebook_url'] ?? null,
            'contact_email'   => trim((string)($_POST['contact_email'] ?? '')) ?: null,
            'website_url'     => trim((string)($_POST['website_url'] ?? '')) ?: null,
            'raft_variant'    => Property::normalizeRaftVariant($data['type'], $_POST['raft_variant'] ?? ''),
            'owner_intake'    => Property::encodeOwnerIntakeFromPost($_POST),
            ...(Database::tableHasColumn('properties', 'instagram_url') ? ['instagram_url' => trim((string)($_POST['instagram_url'] ?? '')) ?: null] : []),
            ...(Database::tableHasColumn('properties', 'tiktok_url')    ? ['tiktok_url'    => trim((string)($_POST['tiktok_url'] ?? '')) ?: null]    : []),
            // โหมดการจองและคูปอง — แอดมินเป็นผู้กำหนดหลังอนุมัติ (ไม่รับค่าจากฟอร์มเจ้าของ)
            'booking_mode'    => 'info_only',
            'allow_contact'   => 1,
            'coupon_enabled'  => 0,
            'allow_online_booking' => 0,
            'booking_requires_payment' => 0,
            'status'          => 'pending', // เจ้าของแพสร้าง = รอ admin approve
            'meta_title'      => $_POST['meta_title'] ?? null,
            'meta_description'=> $_POST['meta_description'] ?? null,
        ]);

        Property::syncPropertyAmenities($id, $_POST['amenities'] ?? []);
        Property::recalcMinPrice($id);

        try {
            AdminApprovalNotifyService::propertyPendingReview((int) $id, (string) $data['name']);
        } catch (\Throwable $e) {
        }

        Session::flash('success', 'สร้างที่พักเรียบร้อย รอ Admin อนุมัติ · ขั้นถัดไป: เพิ่มห้องหรือแพแต่ละลำ (ปุ่ม «จัดการห้อง/แพ» ด้านบน)');
        redirect(url('/owner/properties/' . $id . '/edit'));
    }

    public function edit(int $id): void
    {
        $property = $this->findOwn($id);
        if (!$property) { http_response_code(404); View::render('errors/404', [], 'layouts/owner'); return; }

        $amenities = Database::fetchAll("SELECT * FROM amenities ORDER BY sort_order");
        $selected = array_column(Database::fetchAll("SELECT amenity_id FROM property_amenities WHERE property_id = :id", ['id' => $id]), 'amenity_id');
        $hasUnitImg = Database::tableHasColumn('property_images', 'unit_id');
        $images = Database::fetchAll(
            $hasUnitImg
                ? "SELECT * FROM property_images WHERE property_id = :id AND unit_id IS NULL ORDER BY sort_order, id"
                : "SELECT * FROM property_images WHERE property_id = :id ORDER BY sort_order, id",
            ['id' => $id]
        );
        $units  = Database::fetchAll("SELECT * FROM property_units WHERE property_id = :id ORDER BY sort_order, id", ['id' => $id]);
        $lineContacts = Database::tableHasColumn('properties', 'line_messaging_enabled')
            ? Database::fetchAll(
                "SELECT line_user_id, display_name, last_seen_at FROM property_line_contacts
                 WHERE property_id = :id AND unfollowed_at IS NULL
                 ORDER BY last_seen_at DESC LIMIT 50",
                ['id' => $id]
              )
            : [];

        View::render('owner/properties/form', [
            'page_title' => 'แก้ไข: ' . $property['name'],
            'property' => $property, 'amenities' => $amenities,
            'selectedAmenities' => $selected, 'images' => $images, 'units' => $units,
            'lineContacts' => $lineContacts,
        ], 'layouts/owner');
    }

    public function update(int $id): void
    {
        $property = $this->findOwn($id);
        if (!$property) { http_response_code(404); View::render('errors/404', [], 'layouts/owner'); return; }

        $data = $this->validate([
            'name' => 'required|max:180',
            'type' => 'required|in:raft,resort,homestay,house,pool_villa,hotel,camping',
            'zone' => 'required|max:80',
        ]);

        $nameEn = trim((string)($_POST['name_en'] ?? '')) ?: null;

        $update = [
            'name'            => $data['name'],
            'name_en'         => $nameEn,
            'type'            => $data['type'],
            'province'        => trim((string)($_POST['province'] ?? '')) ?: 'กาญจนบุรี',
            'district'        => trim((string)($_POST['district'] ?? '')) ?: null,
            'zone'            => $data['zone'],
            'address'         => $_POST['address'] ?? null,
            'latitude'        => $_POST['latitude'] !== '' ? $_POST['latitude'] : null,
            'longitude'       => $_POST['longitude'] !== '' ? $_POST['longitude'] : null,
            'description'     => $_POST['description'] ?? null,
            'rules'           => $_POST['rules'] ?? null,
            'check_in'        => $_POST['check_in'] ?? '14:00:00',
            'check_out'       => $_POST['check_out'] ?? '12:00:00',
            'pet_policy'      => $_POST['pet_policy'] ?? 'not_allowed',
            'deposit_amount'  => (float)($_POST['deposit_amount'] ?? 0),
            'deposit_note'    => $_POST['deposit_note'] ?? null,
            'phone'           => $_POST['phone'] ?? null,
            'line_id'         => $_POST['line_id'] ?? null,
            'facebook_url'    => $_POST['facebook_url'] ?? null,
            'contact_email'   => trim((string)($_POST['contact_email'] ?? '')) ?: null,
            'website_url'     => trim((string)($_POST['website_url'] ?? '')) ?: null,
            'raft_variant'    => Property::normalizeRaftVariant($data['type'], $_POST['raft_variant'] ?? ''),
            'owner_intake'    => Property::encodeOwnerIntakeFromPost($_POST),
            ...(Database::tableHasColumn('properties', 'instagram_url') ? ['instagram_url' => trim((string)($_POST['instagram_url'] ?? '')) ?: null] : []),
            ...(Database::tableHasColumn('properties', 'tiktok_url')    ? ['tiktok_url'    => trim((string)($_POST['tiktok_url'] ?? '')) ?: null]    : []),
            'meta_title'      => $_POST['meta_title'] ?? null,
            'meta_description'=> $_POST['meta_description'] ?? null,
        ];

        $status = (string)($property['status'] ?? '');
        $nameChanged = $data['name'] !== (string)($property['name'] ?? '');
        $prevNameEn = trim((string)($property['name_en'] ?? ''));
        $nameEnChanged = ($nameEn ?? '') !== $prevNameEn;
        if (in_array($status, ['draft', 'pending'], true) && ($nameChanged || $nameEnChanged)) {
            $update['slug'] = Property::uniqueSlug($data['name'], $nameEn, $id);
        }

        if (Auth::isAdmin()) {
            $cap = PropertyBookingCapabilities::payloadFromPost($_POST);
            if ($cap === null) {
                Session::flash('error', 'เลือกความสามารถการจองอย่างน้อย 1 ข้อ (ติดต่อ / คูปอง / จองออนไลน์)');
                back();
            }
            foreach ($cap as $k => $v) {
                $update[$k] = $v;
            }
        } elseif (in_array($property['status'] ?? '', ['published', 'archived'], true)) {
            $vis = (string)($_POST['listing_visibility'] ?? '');
            if ($vis === 'published' || $vis === 'archived') {
                $update['status'] = $vis;
            }
        }

        try {
            $cover = Upload::image('cover_image', 'properties');
            if ($cover) $update['cover_image'] = $cover;
        } catch (\Throwable $e) { Session::flash('error', $e->getMessage()); back(); }

        // LINE OA per-property fields (ส่งมาจาก section ในฟอร์มแก้ไขที่พัก)
        if (Database::tableHasColumn('properties', 'line_messaging_enabled') && isset($_POST['line_settings_sent'])) {
            $update['line_messaging_enabled'] = isset($_POST['line_messaging_enabled']) ? 1 : 0;
            if (array_key_exists('line_channel_access_token', $_POST)) {
                $tok = trim((string)$_POST['line_channel_access_token']);
                $update['line_channel_access_token'] = $tok !== '' ? $tok : null;
            }
            if (array_key_exists('line_channel_secret', $_POST)) {
                $sec = trim((string)$_POST['line_channel_secret']);
                $update['line_channel_secret'] = $sec !== '' ? $sec : null;
            }
        }

        Property::update($id, $update);
        Property::syncPropertyAmenities($id, $_POST['amenities'] ?? []);
        Property::recalcMinPrice($id);

        Session::flash('success', 'บันทึกการเปลี่ยนแปลงเรียบร้อย');
        redirect(url('/owner/properties/' . $id . '/edit'));
    }

    /** POST /owner/properties/{id}/line-test — test push ไป LINE User ID */
    public function lineTest(int $id): void
    {
        if (!OwnerFeatureGate::denyJson($this, OwnerTier::FEATURE_LINE_HUB, 'LINE Hub ต้องสมัครแพ็กเกจ Starter ขึ้นไป')) {
            return;
        }
        $property = $this->findOwn($id);
        if (!$property) { $this->json(['ok' => false, 'message' => 'ไม่พบที่พัก'], 403); }

        $lineUserId = trim((string)($_POST['line_user_id'] ?? ''));
        if (!$lineUserId) { $this->json(['ok' => false, 'message' => 'กรุณากรอก LINE User ID']); }

        $tokenOverride = trim((string)($_POST['line_channel_access_token'] ?? '')) ?: null;

        $bot = PropertyLineService::botInfo($id, $tokenOverride);
        if (!$bot['ok']) {
            $this->json([
                'ok'      => false,
                'message'   => $bot['code'] === 401
                    ? 'Channel Access Token ไม่ถูกต้องหรือหมดอายุ — ไป LINE Developers กด Issue Token ใหม่'
                    : 'ตรวจสอบ Token ไม่ผ่าน (HTTP ' . $bot['code'] . ')',
            ]);
        }

        $botName = (string)($bot['data']['displayName'] ?? '');
        $botId   = (string)($bot['data']['basicId'] ?? $bot['data']['userId'] ?? '');

        $profile = PropertyLineService::userProfile($id, $lineUserId, $tokenOverride);
        $guestName = (string)($profile['data']['displayName'] ?? $lineUserId);
        $profileWarn = '';
        if (!$profile['ok']) {
            $known = Database::fetch(
                "SELECT id FROM property_line_contacts WHERE property_id = :p AND line_user_id = :l AND unfollowed_at IS NULL LIMIT 1",
                ['p' => $id, 'l' => $lineUserId]
            );
            if ($profile['code'] === 404 && !$known) {
                $this->json([
                    'ok'      => false,
                    'message' => "Token เป็นของ OA «{$botName}» ({$botId}) แล้ว — แต่ลูกค้ายังไม่ได้ Add Friend OA นี้\n"
                        . "ให้มือถือ Add Friend {$botId} ({$botName}) → ทักข้อความ 1 ครั้ง → เลือกจาก dropdown แล้วส่งทดสอบอีกครั้ง",
                ]);
            }
            if ($profile['code'] !== 404 || !$known) {
                $profileWarn = $profile['code'] === 404 ? ' (โปรไฟล์ไม่พบ แต่ลองส่งต่อ)' : '';
            }
        }

        $result = PropertyLineService::pushResult($id, $lineUserId, [[
            'type' => 'text',
            'text' => "ทดสอบ LINE OA ของแพ/ที่พัก: {$property['name']}\nส่งจากระบบ Paekarn.com ✅",
        ]], $tokenOverride);

        $message = "ส่งสำเร็จ! ถึง {$guestName} ผ่าน OA «{$botName}» — เช็ค LINE ได้เลย{$profileWarn}";
        if (!$result['ok']) {
            $lineErr = PropertyLineService::parseLineError($result['detail']);
            $message = match ($result['code']) {
                401     => 'Channel Access Token หมดอายุ — Issue Token ใหม่แล้วบันทึก',
                400     => $lineErr !== ''
                    ? "ส่งไม่ได้: {$lineErr} (OA: {$botName})"
                    : "ส่งไม่ได้ — Token ของ «{$botName}» อาจไม่ตรงกับ OA ที่ลูกค้า Add Friend",
                403     => 'Token ไม่มีสิทธิ์ส่งข้อความ',
                0       => $result['detail'],
                default => 'ส่งไม่สำเร็จ (HTTP ' . $result['code'] . ')' . ($lineErr ? " — {$lineErr}" : ''),
            };
        }

        $this->json([
            'ok'      => $result['ok'],
            'message' => $message,
        ]);
    }

    /** POST /owner/properties/{id}/line-rich-menu — สร้าง/ลบ Rich Menu */
    public function lineRichMenu(int $id): void
    {
        if (!OwnerFeatureGate::denyJson($this, OwnerTier::FEATURE_LINE_HUB, 'LINE Hub ต้องสมัครแพ็กเกจ Starter ขึ้นไป')) {
            return;
        }
        $property = $this->findOwn($id);
        if (!$property) { $this->json(['ok' => false, 'message' => 'ไม่พบที่พัก'], 403); }

        $action = trim((string)($_POST['action'] ?? 'create'));

        if ($action === 'delete') {
            $ok = PropertyLineService::deleteRichMenu($id);
            $this->json([
                'ok'      => $ok,
                'message' => $ok ? 'ลบ Rich Menu สำเร็จ' : 'ลบ Rich Menu ไม่สำเร็จ (อาจยังไม่มี Rich Menu)',
            ]);
        }

        // create
        $result = PropertyLineService::createPropertyRichMenu($id, (string)$property['name']);
        if (!$result['ok']) {
            $lineErr = PropertyLineService::parseLineError($result['detail']);
            $detail  = $lineErr ?: $result['detail'];
            $this->json([
                'ok'      => false,
                'message' => 'สร้าง Rich Menu ไม่สำเร็จ: ' . $detail,
            ]);
        }

        $setResult = PropertyLineService::setDefaultRichMenu($id, $result['richMenuId']);
        if (!$setResult['ok']) {
            $lineErr = PropertyLineService::parseLineError($setResult['detail']);
            $detail  = $lineErr ?: $setResult['detail'];
            $this->json([
                'ok'         => false,
                'message'    => "สร้าง Rich Menu สำเร็จ (ID: {$result['richMenuId']}) แต่ตั้งเป็น default ไม่ได้ (HTTP {$setResult['code']}): {$detail}",
                'richMenuId' => $result['richMenuId'],
            ]);
        }
        $this->json([
            'ok'         => true,
            'message'    => 'สร้างและตั้งค่า Rich Menu สำเร็จ ✅',
            'richMenuId' => $result['richMenuId'],
        ]);
    }

    public function delete(int $id): void
    {
        $property = $this->findOwn($id);
        if (!$property) { http_response_code(404); View::render('errors/404', [], 'layouts/owner'); return; }
        Property::destroy($id);
        Session::flash('success', 'ลบที่พักเรียบร้อย');
        redirect(url('/owner/properties'));
    }

    public function uploadImage(int $id): void
    {
        $property = $this->findOwn($id);
        if (!$property) { http_response_code(404); View::render('errors/404', [], 'layouts/owner'); return; }
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
        redirect(url('/owner/properties/' . $id . '/edit'));
    }

    public function deleteImage(int $id, int $img): void
    {
        $property = $this->findOwn($id);
        if (!$property) { http_response_code(404); View::render('errors/404', [], 'layouts/owner'); return; }
        $hasUnitImg = Database::tableHasColumn('property_images', 'unit_id');
        $row = Database::fetch(
            $hasUnitImg
                ? 'SELECT * FROM property_images WHERE id = :i AND property_id = :p AND unit_id IS NULL'
                : 'SELECT * FROM property_images WHERE id = :i AND property_id = :p',
            ['i' => $img, 'p' => $id]
        );
        if ($row) {
            Database::delete('property_images', 'id = :i', ['i' => $img]);
            // optional: delete physical file
            $full = \App\Core\Application::$basePath . '/public/uploads/' . $row['path'];
            if (is_file($full) && !str_starts_with($row['path'], 'http')) @unlink($full);
        }
        Session::flash('success', 'ลบรูปภาพเรียบร้อย');
        redirect(url('/owner/properties/' . $id . '/edit'));
    }
}
