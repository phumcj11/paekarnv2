<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Models\Property;
use App\Models\VisitorPlace;

class VisitorPlaceController extends Controller
{
    public function index(): void
    {
        View::render('admin/visitor_places/index', [
            'page_title' => 'ที่เที่ยว / สถานที่',
            'rows'       => VisitorPlace::adminAll(),
            'categories' => VisitorPlace::CATEGORIES,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        View::render('admin/visitor_places/form', [
            'page_title'       => 'เพิ่มสถานที่',
            'place'            => null,
            'categories'       => VisitorPlace::CATEGORIES,
            'districtChoices'  => VisitorPlace::DISTRICTS,
            'zoneChoices'      => Property::zonesForSelect(),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $data = $this->validatePayload(null);
        if ($data === null) {
            redirect(url('/admin/visitor-places/create'));
        }
        try {
            $data['cover_image']    = $this->resolveCoverCreate();
            $data['gallery_images'] = $this->resolveGalleryUpload();
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Session::withOld($_POST);
            redirect(url('/admin/visitor-places/create'));
        }
        Database::insert('visitor_places', $data);
        Session::flash('success', 'เพิ่มสถานที่เรียบร้อย');
        redirect(url('/admin/visitor-places'));
    }

    public function edit(int $id): void
    {
        $place = VisitorPlace::find($id);
        if (!$place) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }
        View::render('admin/visitor_places/form', [
            'page_title'       => 'แก้ไขสถานที่',
            'place'            => $place,
            'categories'       => VisitorPlace::CATEGORIES,
            'districtChoices'  => VisitorPlace::DISTRICTS,
            'zoneChoices'      => Property::zonesForSelect(),
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        $place = VisitorPlace::find($id);
        if (!$place) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }
        $data = $this->validatePayload($id);
        if ($data === null) {
            redirect(url('/admin/visitor-places/' . $id . '/edit'));
        }
        try {
            $path = $this->resolveCoverUpdate();
            if ($path !== null) {
                $data['cover_image'] = $path;
            }
            $gallery = $this->resolveGalleryUpload();
            if ($gallery !== null) {
                $data['gallery_images'] = $gallery;
            } elseif (!empty($_POST['gallery_images_keep'])) {
                // keep existing — don't overwrite
            } else {
                // "ลบแกลเลอรี" checkbox was not sent → keep existing unless explicit clear
                // If user sent "clear_gallery=1" we clear it
                if (!empty($_POST['clear_gallery'])) {
                    $data['gallery_images'] = null;
                }
            }
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            redirect(url('/admin/visitor-places/' . $id . '/edit'));
        }
        Database::update('visitor_places', $data, 'id = :id', ['id' => $id]);
        Session::flash('success', 'บันทึกเรียบร้อย');
        redirect(url('/admin/visitor-places'));
    }

    public function delete(int $id): void
    {
        Database::delete('visitor_places', 'id = :id', ['id' => $id]);
        Session::flash('success', 'ลบเรียบร้อย');
        redirect(url('/admin/visitor-places'));
    }

    /** @throws \RuntimeException */
    private function resolveCoverCreate(): ?string
    {
        $url = trim((string)($_POST['cover_image_url'] ?? ''));
        if ($url !== '') {
            return $url;
        }
        if (!empty($_FILES['cover_image']['tmp_name'])) {
            return Upload::image('cover_image', 'places');
        }
        return null;
    }

    /** @throws \RuntimeException */
    private function resolveCoverUpdate(): ?string
    {
        $url = trim((string)($_POST['cover_image_url'] ?? ''));
        if ($url !== '') {
            return $url;
        }
        if (!empty($_FILES['cover_image']['tmp_name'])) {
            return Upload::image('cover_image', 'places');
        }
        return null;
    }

    /**
     * Handle gallery_images[] multi-file upload.
     * Returns JSON string of filenames, or null if no files uploaded.
     *
     * @throws \RuntimeException
     */
    private function resolveGalleryUpload(): ?string
    {
        if (empty($_FILES['gallery_images']['name'][0])) {
            return null;
        }

        $files   = $_FILES['gallery_images'];
        $count   = count($files['name']);
        $paths   = [];

        for ($i = 0; $i < $count; $i++) {
            $err = (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $entry = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $path = Upload::imageFromEntry($entry, 'places');
            if ($path !== null) {
                $paths[] = $path;
            }
        }

        return empty($paths) ? null : json_encode($paths, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return ?array<string,mixed>
     */
    private function validatePayload(?int $ignoreId): ?array
    {
        $data = $this->validate([
            'name' => 'required|max:200',
            'slug' => 'required|max:180',
        ]);

        $slug = trim((string)$data['slug']);
        if (!preg_match('#^[a-z0-9]+(?:-[a-z0-9]+)*$#', $slug)) {
            Session::flash('error', 'Slug ใช้ได้เฉพาะ a-z 0-9 และขีดกลาง (เช่น cafe-meena-mae-klong)');
            Session::withOld($_POST);
            return null;
        }

        $dup = Database::fetch('SELECT id FROM visitor_places WHERE slug = :s LIMIT 1', ['s' => $slug]);
        if ($dup && (int)$dup['id'] !== (int)($ignoreId ?? 0)) {
            Session::flash('error', 'Slug นี้มีในระบบแล้ว');
            Session::withOld($_POST);
            return null;
        }

        $cat = (string)($_POST['category'] ?? 'attraction');
        if (!array_key_exists($cat, VisitorPlace::CATEGORIES)) {
            $cat = 'attraction';
        }

        $zone = trim((string)($_POST['zone'] ?? ''));
        $zone = $zone === '' ? null : $zone;

        $districtIn = trim((string)($_POST['district'] ?? ''));
        $district   = $districtIn === '' ? null : $districtIn;
        if ($district !== null && !in_array($district, VisitorPlace::DISTRICTS, true)) {
            Session::flash('error', 'กรุณาเลือกอำเภอจากรายการที่กำหนด');
            Session::withOld($_POST);
            return null;
        }

        $latIn = trim((string)($_POST['latitude'] ?? ''));
        $lngIn = trim((string)($_POST['longitude'] ?? ''));
        $lat   = $latIn === '' ? null : round((float)$latIn, 7);
        $lng   = $lngIn === '' ? null : round((float)$lngIn, 7);
        $ratingIn = trim((string)($_POST['rating_avg'] ?? ''));
        $rating   = $ratingIn === '' ? null : max(0, min(5, round((float)$ratingIn, 2)));
        $tags = trim((string)($_POST['tags'] ?? ''));
        $tags = $tags === '' ? null : (function_exists('mb_substr') ? mb_substr($tags, 0, 500) : substr($tags, 0, 500));

        return [
            'slug'             => $slug,
            'name'             => trim((string)$data['name']),
            'excerpt'          => trim((string)($_POST['excerpt'] ?? '')) ?: null,
            'description'      => trim((string)($_POST['description'] ?? '')) ?: null,
            'category'         => $cat,
            'district'         => $district,
            'zone'             => $zone,
            'latitude'         => $lat,
            'longitude'        => $lng,
            'address'          => trim((string)($_POST['address'] ?? '')) ?: null,
            'google_maps_url'  => trim((string)($_POST['google_maps_url'] ?? '')) ?: null,
            'rating_avg'       => $rating,
            'rating_count'     => max(0, (int)($_POST['rating_count'] ?? 0)),
            'opening_hours'    => trim((string)($_POST['opening_hours'] ?? '')) ?: null,
            'tags'             => $tags,
            'is_open_now'      => !empty($_POST['is_open_now']) ? 1 : 0,
            'is_pet_friendly'  => !empty($_POST['is_pet_friendly']) ? 1 : 0,
            'is_photo_spot'    => !empty($_POST['is_photo_spot']) ? 1 : 0,
            'sort_order'       => max(0, (int)($_POST['sort_order'] ?? 0)),
            'is_active'        => !empty($_POST['is_active']) ? 1 : 0,
            'meta_title'       => trim((string)($_POST['meta_title'] ?? '')) ?: null,
            'meta_description' => trim((string)($_POST['meta_description'] ?? '')) ?: null,
        ];
    }
}
