<?php

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\Property;

/** จัดการหลังพัก (units) ใช้ view เดียวกับ Owner — layout แอดมิน */
class UnitController extends \App\Controllers\Owner\UnitController
{
    protected function layout(): string
    {
        return 'layouts/admin';
    }

    protected function unitsPathPrefix(): string
    {
        return '/admin/properties';
    }

    private function findUnitForProperty(int $propertyId, int $unitId): ?array
    {
        return Database::fetch(
            'SELECT id, property_id FROM property_units WHERE id = :u AND property_id = :p LIMIT 1',
            ['u' => $unitId, 'p' => $propertyId]
        );
    }

    public function approve(int $id, int $uid): void
    {
        $unit = $this->findUnitForProperty($id, $uid);
        if (!$unit) {
            http_response_code(404);
            View::render('errors/404');

            return;
        }

        Database::update('property_units', ['moderation_status' => 'published'], 'id = :id', ['id' => $uid]);
        Property::recalcMinPrice($id);
        AuditLog::record('property_unit_status_changed', ['to' => 'published'], 'property_unit', $uid);
        Session::flash('success', 'อนุมัติยูนิตเรียบร้อย');
        redirect(url('/admin/properties/' . $id . '/units'));
    }

    public function reject(int $id, int $uid): void
    {
        $unit = $this->findUnitForProperty($id, $uid);
        if (!$unit) {
            http_response_code(404);
            View::render('errors/404');

            return;
        }

        Database::update('property_units', ['moderation_status' => 'rejected'], 'id = :id', ['id' => $uid]);
        Property::recalcMinPrice($id);
        AuditLog::record('property_unit_status_changed', ['to' => 'rejected'], 'property_unit', $uid);
        Session::flash('success', 'ปฏิเสธยูนิตเรียบร้อย');
        redirect(url('/admin/properties/' . $id . '/units'));
    }
}
