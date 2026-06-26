<?php
namespace App\Controllers\Owner;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Property;
use App\Services\OwnerFeatureGate;
use App\Services\OwnerTier;

/**
 * หน้ารวมตั้งค่า LINE OA + Chatbot ต่อที่พัก
 */
class LineHubController extends Controller
{
    private function findOwn(int $id): ?array
    {
        $p = Property::find($id);
        if (!$p) return null;
        if (Auth::isAdmin()) return $p;
        $oid = Auth::ownerId();
        return ($oid && (int)$p['owner_id'] === (int)$oid) ? $p : null;
    }

    /** GET /owner/properties/{id}/line */
    public function index(int $id): void
    {
        if (!OwnerFeatureGate::denyPage(OwnerTier::FEATURE_LINE_HUB, 'LINE Hub ต้องสมัครแพ็กเกจ Starter ขึ้นไป')) {
            return;
        }
        $property = $this->findOwn($id);
        if (!$property) { http_response_code(404); View::render('errors/404', [], 'layouts/owner'); return; }

        $units = Database::fetchAll(
            "SELECT id, name, total_units FROM property_units WHERE property_id = :p AND is_active = 1 ORDER BY sort_order, id",
            ['p' => $id]
        );

        $lineContacts = Database::tableHasColumn('properties', 'line_messaging_enabled')
            ? Database::fetchAll(
                "SELECT line_user_id, display_name, last_seen_at FROM property_line_contacts
                 WHERE property_id = :id AND unfollowed_at IS NULL
                 ORDER BY last_seen_at DESC LIMIT 50",
                ['id' => $id]
              )
            : [];

        // สถิติปฏิทินเดือนนี้ (สรุปให้เจ้าของเห็นภาพรวม)
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');
        $blockedDays = 0;
        $bookedDays  = 0;
        if (!empty($units)) {
            $unitIds = array_column($units, 'id');
            $in      = implode(',', array_map('intval', $unitIds));
            $blockedDays = (int)(Database::fetch(
                "SELECT COUNT(DISTINCT date) c FROM availability
                 WHERE unit_id IN ({$in}) AND date BETWEEN :s AND :e
                   AND status IN ('closed','blocked','fully_booked')",
                ['s' => $monthStart, 'e' => $monthEnd]
            )['c'] ?? 0);

            $bookedDays = (int)(Database::fetch(
                "SELECT COUNT(DISTINCT check_in) c FROM bookings
                 WHERE property_id = :p AND status IN ('pending','confirmed')
                   AND check_in BETWEEN :s AND :e",
                ['p' => $id, 's' => $monthStart, 'e' => $monthEnd]
            )['c'] ?? 0);
        }

        View::render('owner/line_hub/index', [
            'page_title'   => 'LINE & Chatbot — ' . $property['name'],
            'property'     => $property,
            'units'        => $units,
            'lineContacts' => $lineContacts,
            'blockedDays'  => $blockedDays,
            'bookedDays'   => $bookedDays,
        ], 'layouts/owner');
    }

    /** POST /owner/properties/{id}/line — บันทึก LINE settings */
    public function save(int $id): void
    {
        if (!OwnerFeatureGate::denyPage(OwnerTier::FEATURE_LINE_HUB, 'LINE Hub ต้องสมัครแพ็กเกจ Starter ขึ้นไป')) {
            return;
        }
        $property = $this->findOwn($id);
        if (!$property) { http_response_code(404); View::render('errors/404', [], 'layouts/owner'); return; }

        if (!Database::tableHasColumn('properties', 'line_messaging_enabled')) {
            Session::flash('error', 'ระบบยังไม่รองรับ LINE OA');
            redirect(url('/owner/properties/' . $id . '/line'));
        }

        $update = [
            'line_messaging_enabled' => isset($_POST['line_messaging_enabled']) ? 1 : 0,
        ];
        if (array_key_exists('line_channel_access_token', $_POST)) {
            $tok = trim((string)$_POST['line_channel_access_token']);
            $update['line_channel_access_token'] = $tok !== '' ? $tok : null;
        }
        if (array_key_exists('line_channel_secret', $_POST)) {
            $sec = trim((string)$_POST['line_channel_secret']);
            $update['line_channel_secret'] = $sec !== '' ? $sec : null;
        }

        Property::update($id, $update);
        Session::flash('success', 'บันทึกการตั้งค่า LINE เรียบร้อย');
        redirect(url('/owner/properties/' . $id . '/line'));
    }
}
