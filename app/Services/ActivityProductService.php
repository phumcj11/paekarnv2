<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Models\ActivityProduct;
use App\Models\VisitorPlace;

/**
 * Shared product payload + default option sync for admin and provider portals.
 */
class ActivityProductService
{
    /**
     * @param array<string,mixed>|null $existing
     * @param array{providerId?:int|null,forceDraft?:bool,allowStatus?:bool} $opts
     * @return array<string,mixed>
     */
    public static function buildPayload(?array $existing, array $opts = []): array
    {
        $data = array_merge($_GET, $_POST);
        $title = trim((string)($data['title'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));
        if ($title === '') {
            Session::flash('error', 'กรุณากรอกชื่อสินค้า/บริการ');
            Session::withOld($data);
            back();
        }
        if ($slug === '' || !preg_match('#^[a-z0-9]+(?:-[a-z0-9]+)*$#', $slug)) {
            Session::flash('error', 'Slug ใช้ได้เฉพาะ a-z 0-9 และขีดกลาง');
            Session::withOld($data);
            back();
        }
        $dup = Database::fetch('SELECT id FROM activity_products WHERE slug = :s LIMIT 1', ['s' => $slug]);
        if ($dup && (int)$dup['id'] !== (int)($existing['id'] ?? 0)) {
            Session::flash('error', 'Slug นี้มีในระบบแล้ว');
            Session::withOld($data);
            back();
        }

        $category = (string)($data['category'] ?? 'tour');
        if (!array_key_exists($category, ActivityProduct::CATEGORIES)) {
            $category = 'tour';
        }
        $district = trim((string)($data['district'] ?? ''));
        if ($district !== '' && !in_array($district, VisitorPlace::DISTRICTS, true)) {
            Session::flash('error', 'กรุณาเลือกอำเภอจากรายการ');
            Session::withOld($data);
            back();
        }

        $cover = $existing['cover_image'] ?? null;
        $coverUrl = trim((string)($data['cover_image_url'] ?? ''));
        if ($coverUrl !== '') {
            $cover = $coverUrl;
        } elseif (!empty($_FILES['cover_image']['tmp_name'])) {
            try {
                $cover = Upload::image('cover_image', 'activities');
            } catch (\Throwable $e) {
                Session::flash('error', $e->getMessage());
                Session::withOld($data);
                back();
            }
        }

        $providerId = array_key_exists('providerId', $opts)
            ? ($opts['providerId'] !== null ? (int)$opts['providerId'] : null)
            : (!empty($data['provider_id']) ? (int)$data['provider_id'] : null);

        $status = (string)($existing['status'] ?? 'draft');
        if (!empty($opts['forceDraft'])) {
            if ($status === 'published') {
                $status = 'pending_review';
            } elseif (!in_array($status, ['draft', 'pending_review'], true)) {
                $status = 'draft';
            }
        } elseif (!empty($opts['allowStatus'])) {
            $posted = (string)($data['status'] ?? '');
            if (in_array($posted, ['draft', 'pending_review', 'published', 'archived'], true)) {
                $status = $posted;
            }
        }

        $payload = [
            'provider_id'         => $providerId,
            'place_id'            => !empty($data['place_id']) ? (int)$data['place_id'] : null,
            'slug'                => $slug,
            'title'               => mb_substr($title, 0, 220),
            'category'            => $category,
            'district'            => $district !== '' ? $district : null,
            'zone'                => trim((string)($data['zone'] ?? '')) ?: null,
            'excerpt'             => trim((string)($data['excerpt'] ?? '')) ?: null,
            'description'         => trim((string)($data['description'] ?? '')) ?: null,
            'cover_image'         => $cover,
            'base_price'          => max(0, (float)($data['base_price'] ?? 0)),
            'compare_at_price'    => max(0, (float)($data['compare_at_price'] ?? 0)),
            'duration_label'      => trim((string)($data['duration_label'] ?? '')) ?: null,
            'meeting_point'       => trim((string)($data['meeting_point'] ?? '')) ?: null,
            'included'            => trim((string)($data['included'] ?? '')) ?: null,
            'excluded'            => trim((string)($data['excluded'] ?? '')) ?: null,
            'cancellation_policy' => trim((string)($data['cancellation_policy'] ?? '')) ?: null,
            'status'              => $status,
            'meta_title'          => trim((string)($data['meta_title'] ?? '')) ?: null,
            'meta_description'    => trim((string)($data['meta_description'] ?? '')) ?: null,
        ];

        if (!empty($opts['allowStatus']) || empty($opts['forceDraft'])) {
            $mode = (string)($data['booking_mode'] ?? ($existing['booking_mode'] ?? 'lead'));
            if (!array_key_exists($mode, ActivityProduct::BOOKING_MODES)) {
                $mode = 'lead';
            }
            $payload['booking_mode'] = $mode;
            $payload['is_featured'] = !empty($data['is_featured']) ? 1 : 0;
            $payload['priority'] = (int)($data['priority'] ?? ($existing['priority'] ?? 0));
        } else {
            $payload['booking_mode'] = (string)($existing['booking_mode'] ?? 'lead');
        }

        return $payload;
    }

    public static function syncDefaultOption(int $productId): void
    {
        if (!Database::tableHasColumn('activity_options', 'id')) {
            return;
        }
        $name = trim((string)($_POST['option_name'] ?? ''));
        $price = max(0, (float)($_POST['option_price'] ?? ($_POST['base_price'] ?? 0)));
        if ($name === '') {
            $name = 'แพ็กเกจหลัก';
        }
        $existing = Database::fetch(
            'SELECT id FROM activity_options WHERE product_id = :p ORDER BY sort_order ASC, id ASC LIMIT 1',
            ['p' => $productId]
        );
        $payload = [
            'product_id'       => $productId,
            'name'             => $name,
            'description'      => trim((string)($_POST['option_description'] ?? '')) ?: null,
            'price'            => $price,
            'compare_at_price' => max(0, (float)($_POST['option_compare_at_price'] ?? 0)),
            'min_qty'          => max(1, (int)($_POST['option_min_qty'] ?? 1)),
            'max_qty'          => max(1, (int)($_POST['option_max_qty'] ?? 20)),
            'sort_order'       => 0,
            'is_active'        => !empty($_POST['option_is_active']) ? 1 : 1,
        ];
        if ($existing) {
            unset($payload['product_id']);
            Database::update('activity_options', $payload, 'id = :id', ['id' => (int)$existing['id']]);
        } else {
            Database::insert('activity_options', $payload);
        }
    }
}
