<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\PageCache;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Models\Banner;

class BannerController extends Controller
{
    public function index(): void
    {
        $rows = Database::fetchAll(
            "SELECT * FROM banners ORDER BY slot ASC, sort_order ASC, id ASC"
        );
        $bySlot = [];
        foreach ($rows as $r) {
            $bySlot[$r['slot']][] = $r;
        }
        View::render('admin/banners/index', [
            'page_title' => 'Banner หน้าเว็บ',
            'bySlot'     => $bySlot,
            'labels'     => Banner::labels(),
            'recommendedSpecs' => Banner::recommendedImageSpecs(),
            'anchorLinks' => self::anchorLinksBySlot(),
            'screenBadges' => Banner::screenBadges(),
            'placementHints' => Banner::placementHints(),
            'allSlots'   => Banner::allSlots(),
            'homeSlots'  => Banner::HOME_SLOTS,
            'placesSlots'=> Banner::PLACES_SLOTS,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        View::render('admin/banners/form', [
            'page_title' => 'เพิ่ม Banner',
            'banner'     => null,
            'labels'     => Banner::labels(),
            'recommendedSpecs' => Banner::recommendedImageSpecs(),
            'anchorLinks' => self::anchorLinksBySlot(),
            'screenBadges' => Banner::screenBadges(),
            'placementHints' => Banner::placementHints(),
            'allSlots'   => Banner::allSlots(),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $data = $this->validatedBannerInput();
        try {
            $data['image_path'] = $this->resolveImageCreate();
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Session::withOld($_POST);
            redirect(url('/admin/banners/create'));
        }
        Database::insert('banners', $data);
        PageCache::flush();
        Session::flash('success', 'เพิ่ม Banner เรียบร้อย');
        redirect(url('/admin/banners'));
    }

    public function edit(int $id): void
    {
        $banner = Database::fetch("SELECT * FROM banners WHERE id = :i", ['i' => $id]);
        if (!$banner) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        View::render('admin/banners/form', [
            'page_title' => 'แก้ไข Banner',
            'banner'     => $banner,
            'labels'     => Banner::labels(),
            'recommendedSpecs' => Banner::recommendedImageSpecs(),
            'anchorLinks' => self::anchorLinksBySlot(),
            'screenBadges' => Banner::screenBadges(),
            'placementHints' => Banner::placementHints(),
            'allSlots'   => Banner::allSlots(),
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        $banner = Database::fetch("SELECT * FROM banners WHERE id = :i", ['i' => $id]);
        if (!$banner) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        $data = $this->validatedBannerInput();
        try {
            $path = $this->resolveImageUpdate();
            if ($path !== null) {
                $data['image_path'] = $path;
            }
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            redirect(url('/admin/banners/' . $id . '/edit'));
        }
        Database::update('banners', $data, 'id = :id', ['id' => $id]);
        PageCache::flush();
        Session::flash('success', 'บันทึกเรียบร้อย');
        redirect(url('/admin/banners'));
    }

    public function delete(int $id): void
    {
        Database::delete('banners', 'id = :i', ['i' => $id]);
        PageCache::flush();
        Session::flash('success', 'ลบเรียบร้อย');
        redirect(url('/admin/banners'));
    }

    /** @throws \RuntimeException */
    private function resolveImageCreate(): string
    {
        $slot = (string)($_POST['slot'] ?? '');
        $url  = trim((string)($_POST['image_url'] ?? ''));
        if ($url !== '') {
            return $url;
        }
        if (!empty($_FILES['image']['tmp_name'])) {
            return Upload::image('image', 'banners');
        }
        if ($slot === 'places_promo_deal') {
            return '';
        }
        throw new \RuntimeException('กรุณาอัปโหลดรูปภาพ หรือใส่ URL รูป');
    }

    /** คืน null = เก็บรูปเดิม */
    private function resolveImageUpdate(): ?string
    {
        $url = trim((string)($_POST['image_url'] ?? ''));
        if ($url !== '') {
            return $url;
        }
        if (!empty($_FILES['image']['tmp_name'])) {
            return Upload::image('image', 'banners');
        }
        return null;
    }

    private function validatedBannerInput(): array
    {
        $slot = $_POST['slot'] ?? '';
        if (!in_array($slot, Banner::allSlots(), true)) {
            Session::flash('error', 'ตำแหน่งไม่ถูกต้อง');
            redirect(url('/admin/banners'));
        }
        return [
            'slot'         => $slot,
            'title'        => trim((string)($_POST['title'] ?? '')),
            'subtitle'     => trim((string)($_POST['subtitle'] ?? '')) ?: null,
            'link_url'     => trim((string)($_POST['link_url'] ?? '')) ?: null,
            'button_text'  => trim((string)($_POST['button_text'] ?? '')) ?: null,
            'sort_order'   => (int)($_POST['sort_order'] ?? 0),
            'is_active'    => !empty($_POST['is_active']) ? 1 : 0,
            'starts_at'    => self::dtOrNull($_POST['starts_at'] ?? ''),
            'ends_at'      => self::dtOrNull($_POST['ends_at'] ?? ''),
        ];
    }

    private static function dtOrNull(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        $v = str_replace('T', ' ', $v);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v)) {
            return $v . ':00';
        }
        return $v;
    }

    private static function anchorLinksBySlot(): array
    {
        $out = [];
        foreach (Banner::allSlots() as $slot) {
            $base = Banner::isPlacesSlot($slot) ? url('/places') : url('/');
            $out[$slot] = array_map(
                static fn (array $row): array => [
                    'label' => $row['label'],
                    'url' => $base . $row['fragment'],
                ],
                Banner::homeAnchorFragments($slot)
            );
        }
        return $out;
    }
}
