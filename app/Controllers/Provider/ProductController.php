<?php

declare(strict_types=1);

namespace App\Controllers\Provider;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\ActivityProduct;
use App\Models\Property;
use App\Models\VisitorPlace;
use App\Services\ActivityProductService;
use App\Services\NotificationService;

class ProductController extends Controller
{
    public function index(): void
    {
        $pid = Auth::providerId();
        View::render('provider/products/index', [
            'page_title' => 'สินค้า / บริการของฉัน',
            'rows'       => $pid ? ActivityProduct::forProvider($pid) : [],
            'statuses'   => ActivityProduct::STATUSES,
            'isActive'   => Auth::providerIsActive(),
        ], 'layouts/provider');
    }

    public function create(): void
    {
        $this->requireActive();
        $this->form(null);
    }

    public function store(): void
    {
        $this->requireActive();
        $pid = Auth::providerId();
        if (!$pid) {
            back();
        }

        $payload = ActivityProductService::buildPayload(null, [
            'providerId'  => $pid,
            'forceDraft'  => true,
        ]);
        $id = Database::insert('activity_products', $payload);
        ActivityProductService::syncDefaultOption($id);
        Session::flash('success', 'สร้างรายการเรียบร้อย — กดส่งตรวจเมื่อพร้อม');
        redirect(url('/provider/products/' . $id . '/edit'));
    }

    public function edit(int $id): void
    {
        $product = $this->findOwned($id);
        if (!$product) {
            return;
        }
        $this->form($product);
    }

    public function update(int $id): void
    {
        $this->requireActive();
        $product = $this->findOwned($id);
        if (!$product) {
            return;
        }
        if ($product['status'] === 'published') {
            Session::flash('error', 'รายการที่เผยแพร่แล้ว แก้ไขผ่านทีมงานหรือส่งเป็นฉบับร่างใหม่');
            back();
        }

        $payload = ActivityProductService::buildPayload($product, [
            'providerId' => Auth::providerId(),
            'forceDraft' => true,
        ]);
        Database::update('activity_products', $payload, 'id = :id', ['id' => $id]);
        ActivityProductService::syncDefaultOption($id);
        Session::flash('success', 'บันทึกเรียบร้อย');
        redirect(url('/provider/products/' . $id . '/edit'));
    }

    public function submitReview(int $id): void
    {
        $this->requireActive();
        $product = $this->findOwned($id);
        if (!$product) {
            return;
        }
        if (!in_array($product['status'], ['draft', 'pending_review'], true)) {
            Session::flash('error', 'ไม่สามารถส่งตรวจรายการนี้ได้');
            back();
        }

        Database::update('activity_products', [
            'status'      => 'pending_review',
            'review_note' => null,
        ], 'id = :id', ['id' => $id]);

        try {
            NotificationService::sendToRole(
                'admin',
                'activity_product_review',
                'มีสินค้ารอตรวจสอบ',
                (string)$product['title'],
                '/admin/activity-products/' . $id . '/edit'
            );
        } catch (\Throwable $e) {
        }

        Session::flash('success', 'ส่งตรวจสอบแล้ว — ทีมงานจะตรวจและเผยแพร่');
        redirect(url('/provider/products'));
    }

    private function form(?array $product): void
    {
        $options = $product ? ActivityProduct::options((int)$product['id'], false) : [];
        View::render('provider/products/form', [
            'page_title'      => $product ? 'แก้ไขรายการ' : 'เพิ่มรายการใหม่',
            'product'         => $product,
            'options'         => $options,
            'categories'      => ActivityProduct::CATEGORIES,
            'districtChoices' => VisitorPlace::DISTRICTS,
            'zoneChoices'     => Property::zonesForSelect(),
            'statuses'        => ActivityProduct::STATUSES,
            'isActive'        => Auth::providerIsActive(),
        ], 'layouts/provider');
    }

    /** @return array<string,mixed>|null */
    private function findOwned(int $id): ?array
    {
        $pid = Auth::providerId();
        if (!$pid) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/provider');
            return null;
        }
        $product = ActivityProduct::findForProvider($id, $pid);
        if (!$product) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/provider');
            return null;
        }

        return $product;
    }

    private function requireActive(): void
    {
        if (!Auth::providerIsActive()) {
            Session::flash('error', 'บัญชียังไม่ได้รับการอนุมัติ — ไม่สามารถจัดการสินค้าได้');
            redirect(url('/provider'));
        }
    }
}
