<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\ActivityFeaturedCampaign;
use App\Models\ActivityProduct;
use App\Services\ActivityFeaturedService;

class ActivityFeaturedCampaignController extends Controller
{
    private static function tableOk(): bool
    {
        return ActivityFeaturedCampaign::tableReady();
    }

    public function index(): void
    {
        if (!self::tableOk()) {
            View::render('admin/activity_featured/missing-table', [
                'page_title' => 'Featured กิจกรรม',
            ], 'layouts/admin');

            return;
        }

        View::render('admin/activity_featured/index', [
            'page_title' => 'Featured กิจกรรม',
            'rows'       => ActivityFeaturedCampaign::adminAll(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/activity-featured'));
        }

        View::render('admin/activity_featured/form', [
            'page_title' => 'เพิ่มแคมเปญ Featured',
            'row'        => null,
            'products'   => $this->productChoices(),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/activity-featured'));
        }

        $payload = $this->validatedPayload();
        if ($payload === null) {
            return;
        }

        Database::insert('activity_featured_campaigns', $payload);
        ActivityFeaturedService::syncProduct((int)$payload['product_id']);
        Session::flash('success', 'เพิ่มแคมเปญ Featured เรียบร้อย');
        redirect(url('/admin/activity-featured'));
    }

    public function edit(int $id): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/activity-featured'));
        }

        $row = ActivityFeaturedCampaign::find($id);
        if (!$row) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }

        View::render('admin/activity_featured/form', [
            'page_title' => 'แก้ไขแคมเปญ Featured',
            'row'        => $row,
            'products'   => $this->productChoices(),
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/activity-featured'));
        }

        $row = ActivityFeaturedCampaign::find($id);
        if (!$row) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }

        $payload = $this->validatedPayload();
        if ($payload === null) {
            return;
        }

        Database::update('activity_featured_campaigns', $payload, 'id = :id', ['id' => $id]);
        ActivityFeaturedService::syncProduct((int)$payload['product_id']);
        if ((int)$row['product_id'] !== (int)$payload['product_id']) {
            ActivityFeaturedService::syncProduct((int)$row['product_id']);
        }
        Session::flash('success', 'บันทึกแคมเปญ Featured เรียบร้อย');
        redirect(url('/admin/activity-featured'));
    }

    public function delete(int $id): void
    {
        if (!self::tableOk()) {
            redirect(url('/admin/activity-featured'));
        }

        $row = ActivityFeaturedCampaign::find($id);
        if ($row) {
            $pid = (int)$row['product_id'];
            Database::delete('activity_featured_campaigns', 'id = :id', ['id' => $id]);
            ActivityFeaturedService::syncProduct($pid);
        }
        Session::flash('success', 'ลบแคมเปญแล้ว');
        redirect(url('/admin/activity-featured'));
    }

    /** @return list<array<string,mixed>> */
    private function productChoices(): array
    {
        if (!ActivityProduct::tableReady()) {
            return [];
        }

        return Database::fetchAll(
            "SELECT ap.id, ap.title, ap.status, pr.name AS provider_name
             FROM activity_products ap
             LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
             ORDER BY ap.status = 'published' DESC, ap.title ASC
             LIMIT 500"
        );
    }

    /** @return ?array<string,mixed> */
    private function validatedPayload(): ?array
    {
        $data = $this->validate([
            'product_id' => 'required',
        ]);
        $productId = (int)$data['product_id'];
        if ($productId <= 0 || !Database::fetch('SELECT id, provider_id FROM activity_products WHERE id = :id', ['id' => $productId])) {
            Session::flash('error', 'ไม่พบสินค้ากิจกรรม');
            Session::withOld($_POST);
            back();
        }

        $product = Database::fetch('SELECT provider_id FROM activity_products WHERE id = :id', ['id' => $productId]);
        $starts = trim((string)($_POST['starts_at'] ?? ''));
        $ends = trim((string)($_POST['ends_at'] ?? ''));

        return [
            'product_id'     => $productId,
            'provider_id'    => !empty($product['provider_id']) ? (int)$product['provider_id'] : null,
            'title'          => trim((string)($_POST['title'] ?? '')) ?: null,
            'price_paid'     => max(0, (float)($_POST['price_paid'] ?? 0)),
            'priority_boost' => max(0, (int)($_POST['priority_boost'] ?? 50)),
            'starts_at'      => $starts !== '' ? $starts : null,
            'ends_at'        => $ends !== '' ? $ends : null,
            'is_active'      => !empty($_POST['is_active']) ? 1 : 0,
        ];
    }
}
