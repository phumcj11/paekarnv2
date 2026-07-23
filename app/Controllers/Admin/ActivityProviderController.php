<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Models\ActivityProvider;
use App\Models\ActivityProviderSubscription;
use App\Models\ActivityLeadClick;
use App\Models\Property;
use App\Models\User;
use App\Models\VisitorPlace;
use App\Services\NotificationService;

class ActivityProviderController extends Controller
{
    public function index(): void
    {
        View::render('admin/activity_providers/index', [
            'page_title' => 'ผู้ให้บริการกิจกรรม',
            'rows'       => ActivityProvider::adminAll(),
            'types'      => ActivityProvider::TYPES,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        View::render('admin/activity_providers/form', [
            'page_title'      => 'เพิ่มผู้ให้บริการ',
            'provider'        => null,
            'types'           => ActivityProvider::TYPES,
            'districtChoices' => VisitorPlace::DISTRICTS,
            'zoneChoices'     => Property::zonesForSelect(),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        Database::insert('activity_providers', $this->payload());
        Session::flash('success', 'เพิ่มผู้ให้บริการเรียบร้อย');
        redirect(url('/admin/activity-providers'));
    }

    public function edit(int $id): void
    {
        $provider = ActivityProvider::find($id);
        if (!$provider) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }

        View::render('admin/activity_providers/form', [
            'page_title'      => 'แก้ไขผู้ให้บริการ',
            'provider'        => $provider,
            'types'           => ActivityProvider::TYPES,
            'districtChoices' => VisitorPlace::DISTRICTS,
            'zoneChoices'     => Property::zonesForSelect(),
            'subscription'    => ActivityProviderSubscription::tableReady()
                ? ActivityProviderSubscription::activeForProvider($id)
                : null,
            'planOptions'     => ActivityProviderSubscription::PLANS,
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        $provider = ActivityProvider::find($id);
        if (!$provider) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }

        Database::update('activity_providers', $this->payload($provider), 'id = :id', ['id' => $id]);
        Session::flash('success', 'บันทึกผู้ให้บริการเรียบร้อย');
        redirect(url('/admin/activity-providers'));
    }

    public function delete(int $id): void
    {
        $provider = ActivityProvider::find($id);
        if (!$provider) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }

        if ($this->providerHasOrders($id)) {
            Session::flash('error', 'ลบไม่ได้ — มีออเดอร์ที่เกี่ยวข้อง ให้เปลี่ยนสถานะเป็น "ยกเลิก" แทน');
            redirect(url('/admin/activity-providers/' . $id . '/edit'));
        }

        $this->purgeProviderData($id);

        $userId = !empty($provider['user_id']) ? (int)$provider['user_id'] : 0;
        Database::delete('activity_providers', 'id = :id', ['id' => $id]);

        if ($userId > 0) {
            $user = User::find($userId);
            if ($user && ($user['role'] ?? '') === 'provider') {
                User::destroy($userId);
            }
        }

        Session::flash('success', 'ลบผู้ให้บริการเรียบร้อย');
        redirect(url('/admin/activity-providers'));
    }

    private function providerHasOrders(int $providerId): bool
    {
        if (!Database::tableHasColumn('activity_orders', 'id')
            || !Database::tableHasColumn('activity_products', 'provider_id')
        ) {
            return false;
        }

        $count = (int)(Database::fetch(
            "SELECT COUNT(*) AS c FROM activity_orders ao
             INNER JOIN activity_products ap ON ap.id = ao.product_id
             WHERE ap.provider_id = :pid",
            ['pid' => $providerId]
        )['c'] ?? 0);

        return $count > 0;
    }

    private function purgeProviderData(int $providerId): void
    {
        if (Database::tableHasColumn('activity_products', 'provider_id')) {
            $productIds = Database::fetchAll(
                'SELECT id FROM activity_products WHERE provider_id = :pid',
                ['pid' => $providerId]
            );
            foreach ($productIds as $row) {
                $productId = (int)($row['id'] ?? 0);
                if ($productId <= 0) {
                    continue;
                }
                if (Database::tableHasColumn('activity_featured_campaigns', 'product_id')) {
                    Database::delete('activity_featured_campaigns', 'product_id = :pid', ['pid' => $productId]);
                }
                if (Database::tableHasColumn('activity_options', 'product_id')) {
                    Database::delete('activity_options', 'product_id = :pid', ['pid' => $productId]);
                }
            }
            Database::delete('activity_products', 'provider_id = :pid', ['pid' => $providerId]);
        }

        if (ActivityProviderSubscription::tableReady()) {
            Database::delete('activity_provider_subscriptions', 'provider_id = :pid', ['pid' => $providerId]);
        }
        if (Database::tableHasColumn('activity_featured_campaigns', 'provider_id')) {
            Database::delete('activity_featured_campaigns', 'provider_id = :pid', ['pid' => $providerId]);
        }
        if (ActivityLeadClick::tableReady()) {
            Database::delete('activity_lead_clicks', 'provider_id = :pid', ['pid' => $providerId]);
        }
    }

    public function partnerStatus(int $id): void
    {
        $provider = ActivityProvider::find($id);
        if (!$provider) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }

        $status = (string)($_POST['partner_status'] ?? '');
        if (!in_array($status, ['pending', 'active', 'paused', 'terminated'], true)) {
            Session::flash('error', 'สถานะไม่ถูกต้อง');
            back();
        }

        $payload = ['partner_status' => $status];
        if (Database::tableHasColumn('activity_providers', 'approved_at')) {
            $payload['approved_at'] = $status === 'active' ? date('Y-m-d H:i:s') : null;
        }
        if (Database::tableHasColumn('activity_providers', 'status')) {
            $payload['status'] = $status === 'active' ? 'active' : 'inactive';
        }

        Database::update('activity_providers', $payload, 'id = :id', ['id' => $id]);

        if ($status === 'active' && !empty($provider['user_id'])) {
            try {
                NotificationService::send(
                    (int)$provider['user_id'],
                    'provider_approved',
                    'บัญชีผู้ให้บริการได้รับการอนุมัติ',
                    'คุณสามารถสร้างสินค้าและรับออเดอร์ได้แล้ว',
                    '/provider'
                );
            } catch (\Throwable $e) {
            }
        }

        Session::flash('success', 'อัปเดตสถานะพาร์ทเนอร์เป็น ' . ActivityProvider::partnerStatusLabel($status));
        redirect(url('/admin/activity-providers/' . $id . '/edit'));
    }

    public function saveSubscription(int $id): void
    {
        if (!ActivityProvider::find($id)) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }
        if (!ActivityProviderSubscription::tableReady()) {
            Session::flash('error', 'ยังไม่ได้ติดตั้งตาราง subscription — รัน patch monetization');
            back();
        }

        $plan = (string)($_POST['plan_key'] ?? 'partner');
        if (!array_key_exists($plan, ActivityProviderSubscription::PLANS)) {
            $plan = 'partner';
        }

        Database::update(
            'activity_provider_subscriptions',
            ['is_active' => 0],
            'provider_id = :pid AND is_active = 1',
            ['pid' => $id]
        );

        if (!empty($_POST['clear_subscription'])) {
            Session::flash('success', 'ยกเลิกแพ็ก subscription แล้ว');
            back();
        }

        $override = trim((string)($_POST['commission_override'] ?? ''));
        $starts = trim((string)($_POST['starts_at'] ?? ''));
        $ends = trim((string)($_POST['ends_at'] ?? ''));

        Database::insert('activity_provider_subscriptions', [
            'provider_id'         => $id,
            'plan_key'            => $plan,
            'price_paid'          => max(0, (float)($_POST['price_paid'] ?? 0)),
            'commission_override' => $override !== '' ? max(0, (float)$override) : null,
            'featured_slots'      => max(0, (int)($_POST['featured_slots'] ?? 0)),
            'starts_at'           => $starts !== '' ? $starts : null,
            'ends_at'             => $ends !== '' ? $ends : null,
            'notes'               => trim((string)($_POST['subscription_notes'] ?? '')) ?: null,
            'is_active'           => 1,
        ]);

        Session::flash('success', 'บันทึกแพ็ก subscription เรียบร้อย');
        redirect(url('/admin/activity-providers/' . $id . '/edit'));
    }

    /** @param array<string,mixed>|null $existing
     *  @return array<string,mixed> */
    private function payload(?array $existing = null): array
    {
        $data = $this->validate([
            'name' => 'required|max:180',
        ]);

        $type = (string)($_POST['type'] ?? 'tour_operator');
        if (!array_key_exists($type, ActivityProvider::TYPES)) {
            $type = 'tour_operator';
        }

        $district = trim((string)($_POST['district'] ?? ''));
        if ($district !== '' && !in_array($district, VisitorPlace::DISTRICTS, true)) {
            Session::flash('error', 'กรุณาเลือกอำเภอจากรายการ');
            Session::withOld($_POST);
            back();
        }

        $commissionType = (string)($_POST['commission_type'] ?? 'percent');
        if (!in_array($commissionType, ['percent', 'fixed'], true)) {
            $commissionType = 'percent';
        }

        $payload = [
            'name'             => trim((string)$data['name']),
            'type'             => $type,
            'contact_name'     => trim((string)($_POST['contact_name'] ?? '')) ?: null,
            'phone'            => trim((string)($_POST['phone'] ?? '')) ?: null,
            'line_id'          => trim((string)($_POST['line_id'] ?? '')) ?: null,
            'email'            => trim((string)($_POST['email'] ?? '')) ?: null,
            'district'         => $district !== '' ? $district : null,
            'zone'             => trim((string)($_POST['zone'] ?? '')) ?: null,
            'address'          => trim((string)($_POST['address'] ?? '')) ?: null,
            'commission_type'  => $commissionType,
            'commission_value' => max(0, (float)($_POST['commission_value'] ?? 0)),
            'status'           => !empty($_POST['status']) && $_POST['status'] === 'inactive' ? 'inactive' : 'active',
            'notes'            => trim((string)($_POST['notes'] ?? '')) ?: null,
        ];

        if (Database::tableHasColumn('activity_providers', 'logo_image')) {
            $payload['logo_image'] = $this->resolveLogoImage($existing);
        }

        return $payload;
    }

    /** @param array<string,mixed>|null $existing */
    private function resolveLogoImage(?array $existing): ?string
    {
        $logo = isset($existing['logo_image']) ? (trim((string)$existing['logo_image']) ?: null) : null;

        if (!empty($_POST['remove_logo'])) {
            return null;
        }

        $logoUrl = trim((string)($_POST['logo_image_url'] ?? ''));
        if ($logoUrl !== '') {
            return $logoUrl;
        }

        if (!empty($_FILES['logo_image']['tmp_name'])) {
            try {
                $uploaded = Upload::image('logo_image', 'activity-providers');
                if ($uploaded) {
                    return $uploaded;
                }
            } catch (\Throwable $e) {
                Session::flash('error', $e->getMessage());
                Session::withOld($_POST);
                back();
            }
        }

        return $logo;
    }
}
