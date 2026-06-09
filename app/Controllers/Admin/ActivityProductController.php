<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\ActivityProduct;
use App\Models\ActivityProvider;
use App\Models\Property;
use App\Models\VisitorPlace;
use App\Services\ActivityProductService;
use App\Services\NotificationService;

class ActivityProductController extends Controller
{
    public function index(): void
    {
        $filter = trim((string)($_GET['status'] ?? ''));
        $rows = ActivityProduct::adminAll();
        if ($filter === 'pending_review') {
            $rows = array_values(array_filter($rows, fn ($r) => ($r['status'] ?? '') === 'pending_review'));
        }

        View::render('admin/activity_products/index', [
            'page_title' => 'สินค้า / กิจกรรม',
            'rows'       => $rows,
            'categories' => ActivityProduct::CATEGORIES,
            'modes'      => ActivityProduct::BOOKING_MODES,
            'statuses'   => ActivityProduct::STATUSES,
            'filter'     => $filter,
            'pendingCount' => ActivityProduct::pendingReviewCount(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function store(): void
    {
        $payload = ActivityProductService::buildPayload(null, ['allowStatus' => true]);
        $id = Database::insert('activity_products', $payload);
        ActivityProductService::syncDefaultOption($id);
        Session::flash('success', 'เพิ่มสินค้า/กิจกรรมเรียบร้อย');
        redirect(url('/admin/activity-products/' . $id . '/edit'));
    }

    public function edit(int $id): void
    {
        $product = ActivityProduct::find($id);
        if (!$product) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }
        $this->form($product);
    }

    public function update(int $id): void
    {
        $product = ActivityProduct::find($id);
        if (!$product) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }

        Database::update('activity_products', ActivityProductService::buildPayload($product, ['allowStatus' => true]), 'id = :id', ['id' => $id]);
        ActivityProductService::syncDefaultOption($id);
        Session::flash('success', 'บันทึกสินค้า/กิจกรรมเรียบร้อย');
        redirect(url('/admin/activity-products/' . $id . '/edit'));
    }

    public function publish(int $id): void
    {
        $product = ActivityProduct::find($id);
        if (!$product) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }

        Database::update('activity_products', [
            'status'      => 'published',
            'review_note' => null,
        ], 'id = :id', ['id' => $id]);

        $this->notifyProviderProduct($product, 'สินค้าได้รับการเผยแพร่แล้ว', 'published');
        Session::flash('success', 'เผยแพร่สินค้าเรียบร้อย');
        redirect(url('/admin/activity-products/' . $id . '/edit'));
    }

    public function reject(int $id): void
    {
        $product = ActivityProduct::find($id);
        if (!$product) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }

        $note = trim((string)($_POST['review_note'] ?? '')) ?: 'กรุณาแก้ไขและส่งตรวจใหม่';
        Database::update('activity_products', [
            'status'      => 'draft',
            'review_note' => mb_substr($note, 0, 500),
        ], 'id = :id', ['id' => $id]);

        $this->notifyProviderProduct($product, 'สินค้าถูกส่งกลับแก้ไข: ' . $note, 'rejected');
        Session::flash('success', 'ส่งกลับเป็นฉบับร่างแล้ว');
        redirect(url('/admin/activity-products/' . $id . '/edit'));
    }

    /** @param array<string,mixed> $product */
    private function notifyProviderProduct(array $product, string $message, string $type): void
    {
        try {
            if (empty($product['provider_id'])) {
                return;
            }
            $pr = Database::fetch(
                'SELECT user_id FROM activity_providers WHERE id = :id LIMIT 1',
                ['id' => (int)$product['provider_id']]
            );
            if ($pr && !empty($pr['user_id'])) {
                NotificationService::send(
                    (int)$pr['user_id'],
                    'activity_product_' . $type,
                    'อัปเดตสินค้า: ' . ($product['title'] ?? ''),
                    $message,
                    '/provider/products/' . (int)$product['id'] . '/edit'
                );
            }
        } catch (\Throwable $e) {
        }
    }

    public function delete(int $id): void
    {
        Database::delete('activity_products', 'id = :id', ['id' => $id]);
        Session::flash('success', 'ลบสินค้า/กิจกรรมเรียบร้อย');
        redirect(url('/admin/activity-products'));
    }

    private function form(?array $product): void
    {
        $places = Database::tableHasColumn('visitor_places', 'id')
            ? Database::fetchAll("SELECT id, name, district, zone FROM visitor_places ORDER BY district ASC, name ASC")
            : [];
        $options = $product ? ActivityProduct::options((int)$product['id'], false) : [];

        View::render('admin/activity_products/form', [
            'page_title'      => $product ? 'แก้ไขสินค้า/กิจกรรม' : 'เพิ่มสินค้า/กิจกรรม',
            'product'         => $product,
            'options'         => $options,
            'providers'       => ActivityProvider::activeForSelect(),
            'places'          => $places,
            'categories'      => ActivityProduct::CATEGORIES,
            'modes'           => ActivityProduct::BOOKING_MODES,
            'statuses'        => ActivityProduct::STATUSES,
            'districtChoices' => VisitorPlace::DISTRICTS,
            'zoneChoices'     => Property::zonesForSelect(),
        ], 'layouts/admin');
    }
}
