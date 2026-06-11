<?php

namespace App\Controllers\Owner;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Services\AdminApprovalNotifyService;

class UnitController extends Controller
{
    private const UNIT_IMAGE_MAX = 5;

    protected function layout(): string
    {
        return 'layouts/owner';
    }

    /** เช่น /owner/properties หรือ /admin/properties */
    protected function unitsPathPrefix(): string
    {
        return '/owner/properties';
    }

    protected function propertyEditUrl(int $propertyId): string
    {
        return url($this->unitsPathPrefix() . '/' . $propertyId . '/edit');
    }

    protected function unitsUrl(string $suffix): string
    {
        return url(rtrim($this->unitsPathPrefix(), '/') . '/' . ltrim($suffix, '/'));
    }

    /** @param array<string,mixed> $data ต้องมี key property */
    protected function renderUnitsView(string $view, array $data): void
    {
        $pid = (int)($data['property']['id'] ?? 0);
        $data['units_path_prefix'] = $this->unitsPathPrefix();
        $data['property_edit_url'] = $this->propertyEditUrl($pid);
        View::render($view, $data, $this->layout());
    }

    private function findOwnProperty(int $id): ?array
    {
        $p = Property::find($id);
        if (!$p) {
            return null;
        }
        if (Auth::isAdmin()) {
            return $p;
        }
        $oid = Auth::ownerId();

        return ($oid && (int)$p['owner_id'] === (int)$oid) ? $p : null;
    }


    /** @return array<string,mixed> */
    private function buildUnitPricingFields(): array
    {
        $fields = [];
        $includes = trim((string)($_POST['price_includes_guests'] ?? ''));
        if (Database::tableHasColumn('property_units', 'price_includes_guests')) {
            $fields['price_includes_guests'] = $includes !== ''
                ? max(1, (int)$includes)
                : null;
        }
        if (Auth::isAdmin() && Database::tableHasColumn('property_units', 'is_featured')) {
            $fields['is_featured'] = !empty($_POST['is_featured']) ? 1 : 0;
            $fields['homepage_priority'] = (int)($_POST['homepage_priority'] ?? 0);
        }

        return $fields;
    }
    private function findOwnUnit(int $propertyId, int $unitId): ?array
    {
        $p = $this->findOwnProperty($propertyId);
        if (!$p) {
            return null;
        }
        $u = PropertyUnit::find($unitId);

        return ($u && (int)$u['property_id'] === (int)$propertyId) ? $u : null;
    }

    /** เข้าจัดการห้องแบบคลิกครั้งเดียวจากเมนู — ที่พักเดียว redirect เข้าหน้าห้องทันที */
    public function hub(): void
    {
        if (Auth::isAdmin()) {
            redirect(url('/admin/properties'));

            return;
        }

        $ownerId = Auth::ownerId();
        if (!$ownerId) {
            http_response_code(403);
            View::render('errors/403', [], 'layouts/owner');

            return;
        }

        $rows = Database::fetchAll(
            "SELECT p.id, p.name, p.zone, p.cover_image,
                    (SELECT COUNT(*) FROM property_units u WHERE u.property_id = p.id) AS unit_count
             FROM properties p WHERE p.owner_id = :oid ORDER BY p.created_at DESC",
            ['oid' => $ownerId]
        );

        if ($rows === []) {
            Session::flash('error', 'ยังไม่มีที่พักในระบบ — เพิ่มที่พักจากเมนู «ที่พักของฉัน» ก่อน แล้วค่อยกลับมาจัดการห้อง');
            redirect(url('/owner/properties'));

            return;
        }

        if (count($rows) === 1) {
            redirect(url('/owner/properties/' . (int)$rows[0]['id'] . '/units'));

            return;
        }

        View::render('owner/units/hub', [
            'page_title' => 'จัดการห้อง / ยูนิต',
            'properties' => $rows,
        ], $this->layout());
    }

    public function index(int $id): void
    {
        $property = $this->findOwnProperty($id);
        if (!$property) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/owner');

            return;
        }

        $units = Database::fetchAll(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM bookings b WHERE b.unit_id = u.id) AS booking_count
             FROM property_units u WHERE u.property_id = :p ORDER BY u.sort_order, u.id",
            ['p' => $id]
        );

        $this->renderUnitsView('owner/units/index', [
            'page_title' => $property['name'] . ' — ห้องพัก',
            'property'   => $property,
            'units'      => $units,
        ]);
    }

    public function create(int $id): void
    {
        $property = $this->findOwnProperty($id);
        if (!$property) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/owner');

            return;
        }
        $amenities = Database::fetchAll('SELECT * FROM amenities ORDER BY sort_order');
        $this->renderUnitsView('owner/units/form', [
            'page_title'        => 'เพิ่มห้อง',
            'property'          => $property,
            'unit'              => null,
            'amenities'         => $amenities,
            'selectedAmenities' => [],
            'unit_gallery'      => [],
            'unit_image_max'    => self::UNIT_IMAGE_MAX,
        ]);
    }

    public function store(int $id): void
    {
        $property = $this->findOwnProperty($id);
        if (!$property) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/owner');

            return;
        }

        $data = $this->validate([
            'name'         => 'required|max:160',
            'capacity_min' => 'required|integer',
            'capacity_max' => 'required|integer',
            'bedrooms'     => 'required|integer',
            'bathrooms'    => 'required|integer',
            'price'        => 'required|numeric',
        ]);

        try {
            $paths = $this->collectUploadedUnitPaths(0);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            back();
        }
        $cover = $paths[0] ?? null;

        try {
            $uid = PropertyUnit::create([
                'property_id'      => $id,
                'name'             => $data['name'],
                'name_en'          => $_POST['name_en'] ?? null,
                'code'             => $_POST['code'] ?? null,
                'description'      => $_POST['description'] ?? null,
                'cover_image'      => $cover,
                'capacity_min'     => (int)$data['capacity_min'],
                'capacity_max'     => (int)$data['capacity_max'],
                'bedrooms'         => (int)$data['bedrooms'],
                'bathrooms'        => (int)$data['bathrooms'],
                'bed_type'         => $_POST['bed_type'] ?? null,
                'area_sqm'         => $_POST['area_sqm'] !== '' ? (int)$_POST['area_sqm'] : null,
                'price'            => (float)$data['price'],
                'price_weekend'    => (float)($_POST['price_weekend'] ?? 0),
                'price_holiday'    => (float)($_POST['price_holiday'] ?? 0),
                'price_low'        => (float)($_POST['price_low'] ?? 0),
                'price_high'       => (float)($_POST['price_high'] ?? 0),
                'extra_person_fee' => (float)($_POST['extra_person_fee'] ?? 0),
                'total_units'      => (int)($_POST['total_units'] ?? 1),
                'is_active'        => !empty($_POST['is_active']) ? 1 : 0,
                'moderation_status'=> Auth::isAdmin() ? 'published' : 'pending',
                'sort_order'       => (int)($_POST['sort_order'] ?? 0),
            ] + $this->buildUnitPricingFields());
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            back();
        }

        try {
            $this->appendGalleryImages($id, $uid, $paths);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            redirect($this->unitsUrl($id . '/units/' . $uid . '/edit'));

            return;
        }

        $this->syncAmenities($uid, $_POST['amenities'] ?? []);
        Property::recalcMinPrice($id);

        if (!Auth::isAdmin()) {
            try {
                AdminApprovalNotifyService::unitCreatedPendingReview(
                    $id,
                    (string) $property['name'],
                    $uid,
                    $data['name']
                );
            } catch (\Throwable $e) {
            }
        }

        Session::flash('success', 'เพิ่มห้องพักเรียบร้อย');
        redirect($this->unitsUrl($id . '/units/' . $uid . '/edit'));
    }

    public function edit(int $id, int $uid): void
    {
        $unit = $this->findOwnUnit($id, $uid);
        if (!$unit) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/owner');

            return;
        }
        $property = $this->findOwnProperty($id);
        if (!$property) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/owner');

            return;
        }
        $amenities = Database::fetchAll('SELECT * FROM amenities ORDER BY sort_order');
        $selected = array_column(Database::fetchAll('SELECT amenity_id FROM unit_amenities WHERE unit_id = :u', ['u' => $uid]), 'amenity_id');

        $this->ensureLegacyCoverInGalleryRow($id, $unit);
        $unitGallery = Property::unitGalleryForUnit($id, $uid);

        $this->renderUnitsView('owner/units/form', [
            'page_title'        => 'แก้ไข: ' . $unit['name'],
            'property'          => $property,
            'unit'              => $unit,
            'amenities'         => $amenities,
            'selectedAmenities' => $selected,
            'unit_gallery'      => $unitGallery,
            'unit_image_max'    => self::UNIT_IMAGE_MAX,
        ]);
    }

    public function update(int $id, int $uid): void
    {
        $unit = $this->findOwnUnit($id, $uid);
        if (!$unit) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/owner');

            return;
        }
        $property = $this->findOwnProperty($id);
        if (!$property) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/owner');

            return;
        }

        $data = $this->validate([
            'name'         => 'required|max:160',
            'capacity_min' => 'required|integer',
            'capacity_max' => 'required|integer',
            'price'        => 'required|numeric',
        ]);

        $update = [
            'name'             => $data['name'],
            'name_en'          => $_POST['name_en'] ?? null,
            'code'             => $_POST['code'] ?? null,
            'description'      => $_POST['description'] ?? null,
            'capacity_min'     => (int)$data['capacity_min'],
            'capacity_max'     => (int)$data['capacity_max'],
            'bedrooms'         => (int)($_POST['bedrooms'] ?? 1),
            'bathrooms'        => (int)($_POST['bathrooms'] ?? 1),
            'bed_type'         => $_POST['bed_type'] ?? null,
            'area_sqm'         => $_POST['area_sqm'] !== '' ? (int)$_POST['area_sqm'] : null,
            'price'            => (float)$data['price'],
            'price_weekend'    => (float)($_POST['price_weekend'] ?? 0),
            'price_holiday'    => (float)($_POST['price_holiday'] ?? 0),
            'price_low'        => (float)($_POST['price_low'] ?? 0),
            'price_high'       => (float)($_POST['price_high'] ?? 0),
            'extra_person_fee' => (float)($_POST['extra_person_fee'] ?? 0),
            'total_units'      => (int)($_POST['total_units'] ?? 1),
            'is_active'        => !empty($_POST['is_active']) ? 1 : 0,
            'sort_order'       => (int)($_POST['sort_order'] ?? 0),
        ] + $this->buildUnitPricingFields();
        if (!Auth::isAdmin()) {
            $update['moderation_status'] = 'pending';
        }

        $this->ensureLegacyCoverInGalleryRow($id, $unit);

        try {
            $existingCount = 0;
            if (Database::tableHasColumn('property_images', 'unit_id')) {
                $existingCount = (int)Database::fetch(
                    'SELECT COUNT(*) AS c FROM property_images WHERE property_id = :p AND unit_id = :u',
                    ['p' => $id, 'u' => $uid]
                )['c'];
            }
            $paths = $this->collectUploadedUnitPaths($existingCount);
            if ($paths !== []) {
                $this->appendGalleryImages($id, $uid, $paths);
            }
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            back();
        }

        PropertyUnit::update($uid, $update);
        $this->syncAmenities($uid, $_POST['amenities'] ?? []);
        Property::recalcMinPrice($id);

        $wasPublished = (string) ($unit['moderation_status'] ?? '') === 'published';
        if (!Auth::isAdmin() && $wasPublished) {
            try {
                AdminApprovalNotifyService::unitEditedNeedsReapproval(
                    $id,
                    (string) $property['name'],
                    $uid,
                    $data['name']
                );
            } catch (\Throwable $e) {
            }
        }

        Session::flash('success', 'บันทึกการเปลี่ยนแปลงเรียบร้อย');
        redirect($this->unitsUrl($id . '/units/' . $uid . '/edit'));
    }

    public function delete(int $id, int $uid): void
    {
        $unit = $this->findOwnUnit($id, $uid);
        if (!$unit) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/owner');

            return;
        }
        PropertyUnit::destroy($uid);
        Property::recalcMinPrice($id);
        Session::flash('success', 'ลบห้องพักเรียบร้อย');
        redirect($this->unitsUrl($id . '/units'));
    }

    public function deleteUnitImage(int $id, int $uid, int $img): void
    {
        $unit = $this->findOwnUnit($id, $uid);
        if (!$unit) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/owner');

            return;
        }
        if (!Database::tableHasColumn('property_images', 'unit_id')) {
            Session::flash('error', 'ฐานข้อมูลยังไม่มีคอลัมน์ unit_id — อัปเดตตามไฟล์ database/migrations/20260212_property_images_unit_id.sql');
            redirect($this->unitsUrl($id . '/units/' . $uid . '/edit'));

            return;
        }
        $row = Database::fetch(
            'SELECT * FROM property_images WHERE id = :i AND property_id = :p AND unit_id = :u',
            ['i' => $img, 'p' => $id, 'u' => $uid]
        );
        if ($row) {
            Database::delete('property_images', 'id = :i', ['i' => $img]);
            $this->deletePhysicalImageIfLocal((string)$row['path']);
            $this->normalizeUnitGallerySortAndCover($id, $uid);
            $this->refreshUnitCoverFromGallery($id, $uid);
            Session::flash('success', 'ลบรูปเรียบร้อย');
        } else {
            Session::flash('error', 'ไม่พบรูป');
        }
        redirect($this->unitsUrl($id . '/units/' . $uid . '/edit'));
    }

    /**
     * แปลง $_FILES['key'][] (multiple) เป็นรายการแถวไฟล์ — อยู่ใน controller เพื่อไม่พึ่ง Upload::normalizeIndexedFiles บนเซิร์ฟเวอร์ที่ไฟล์เก่า
     *
     * @return list<array{name:string,type:string,tmp_name:string,error:int,size:int}>
     */
    private static function indexedUploadEntries(string $key): array
    {
        if (empty($_FILES[$key]) || !isset($_FILES[$key]['name']) || !is_array($_FILES[$key]['name'])) {
            return [];
        }
        $out = [];
        foreach ($_FILES[$key]['name'] as $i => $_name) {
            $out[] = [
                'name'     => (string)($_FILES[$key]['name'][$i] ?? ''),
                'type'     => (string)($_FILES[$key]['type'][$i] ?? ''),
                'tmp_name' => (string)($_FILES[$key]['tmp_name'][$i] ?? ''),
                'error'    => (int)($_FILES[$key]['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                'size'     => (int)($_FILES[$key]['size'][$i] ?? 0),
            ];
        }

        return $out;
    }

    /** @return list<string> */
    private function collectUploadedUnitPaths(int $existingGalleryCount): array
    {
        $maxAdd = max(0, self::UNIT_IMAGE_MAX - $existingGalleryCount);
        if ($maxAdd <= 0) {
            return [];
        }
        $paths = [];
        foreach (self::indexedUploadEntries('unit_images') as $file) {
            if (count($paths) >= $maxAdd) {
                break;
            }
            $p = Upload::imageFromEntry($file, 'units');
            if ($p !== null) {
                $paths[] = $p;
            }
        }
        if (count($paths) < $maxAdd) {
            $c = Upload::image('cover_image', 'units');
            if ($c !== null) {
                $paths[] = $c;
            }
        }

        return $paths;
    }

    /** @param list<string> $paths */
    private function appendGalleryImages(int $propertyId, int $unitId, array $paths): void
    {
        if ($paths === []) {
            return;
        }
        if (!Database::tableHasColumn('property_images', 'unit_id')) {
            throw new \RuntimeException(
                'ฐานข้อมูลยังไม่มีคอลัมน์ unit_id ในตาราง property_images — รัน SQL ใน database/migrations/20260212_property_images_unit_id.sql แล้วลองอัปโหลดรูปใหม่'
            );
        }
        $row = Database::fetch(
            'SELECT COALESCE(MAX(sort_order), -1) AS m FROM property_images WHERE property_id = :p AND unit_id = :u',
            ['p' => $propertyId, 'u' => $unitId]
        );
        $next = (int)($row['m'] ?? -1) + 1;
        foreach ($paths as $i => $path) {
            Database::insert('property_images', [
                'property_id' => $propertyId,
                'unit_id'     => $unitId,
                'path'        => $path,
                'caption'     => null,
                'is_cover'    => 0,
                'sort_order'  => $next + $i,
            ]);
        }
        $this->normalizeUnitGallerySortAndCover($propertyId, $unitId);
        $this->refreshUnitCoverFromGallery($propertyId, $unitId);
    }

    private function normalizeUnitGallerySortAndCover(int $propertyId, int $unitId): void
    {
        if (!Database::tableHasColumn('property_images', 'unit_id')) {
            return;
        }
        $rows = Database::fetchAll(
            'SELECT id FROM property_images WHERE property_id = :p AND unit_id = :u ORDER BY is_cover DESC, sort_order ASC, id ASC',
            ['p' => $propertyId, 'u' => $unitId]
        );
        foreach ($rows as $i => $r) {
            Database::update('property_images', [
                'sort_order' => $i,
                'is_cover'   => $i === 0 ? 1 : 0,
            ], 'id = :id', ['id' => (int)$r['id']]);
        }
    }

    private function refreshUnitCoverFromGallery(int $propertyId, int $unitId): void
    {
        if (!Database::tableHasColumn('property_images', 'unit_id')) {
            return;
        }
        $first = Database::fetch(
            'SELECT path FROM property_images WHERE property_id = :p AND unit_id = :u ORDER BY is_cover DESC, sort_order ASC, id ASC LIMIT 1',
            ['p' => $propertyId, 'u' => $unitId]
        );
        $cover = $first ? (string)$first['path'] : null;
        PropertyUnit::update($unitId, ['cover_image' => $cover]);
    }

    /** @param array<string,mixed> $unit */
    private function ensureLegacyCoverInGalleryRow(int $propertyId, array $unit): void
    {
        if (!Database::tableHasColumn('property_images', 'unit_id')) {
            return;
        }
        try {
            $uid = (int)$unit['id'];
            $c = (int)Database::fetch(
                'SELECT COUNT(*) AS c FROM property_images WHERE property_id = :p AND unit_id = :u',
                ['p' => $propertyId, 'u' => $uid]
            )['c'];
            if ($c > 0) {
                return;
            }
            $path = trim((string)($unit['cover_image'] ?? ''));
            if ($path === '') {
                return;
            }
            Database::insert('property_images', [
                'property_id' => $propertyId,
                'unit_id'     => $uid,
                'path'        => $path,
                'caption'     => null,
                'is_cover'    => 1,
                'sort_order'  => 0,
            ]);
        } catch (\PDOException $e) {
            error_log('[Paekan] ensureLegacyCoverInGalleryRow: ' . $e->getMessage());
        }
    }

    private function deletePhysicalImageIfLocal(string $path): void
    {
        if ($path === '' || str_starts_with($path, 'http')) {
            return;
        }
        $full = \App\Core\Application::$basePath . '/public/uploads/' . $path;
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function syncAmenities(int $unitId, array $amenityIds): void
    {
        Database::delete('unit_amenities', 'unit_id = :u', ['u' => $unitId]);
        foreach ($amenityIds as $aid) {
            $aid = (int)$aid;
            if ($aid > 0) {
                Database::insert('unit_amenities', ['unit_id' => $unitId, 'amenity_id' => $aid]);
            }
        }
    }
}
