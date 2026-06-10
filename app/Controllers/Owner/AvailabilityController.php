<?php
namespace App\Controllers\Owner;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Property;

class AvailabilityController extends Controller
{
    private function findOwn(int $id): ?array
    {
        $p = Property::find($id);
        if (!$p) return null;
        if (Auth::isAdmin()) return $p;
        $oid = Auth::ownerId();
        return ($oid && (int)$p['owner_id'] === (int)$oid) ? $p : null;
    }

    public function index(int $id): void
    {
        $property = $this->findOwn($id);
        if (!$property) { http_response_code(404); View::render('errors/404'); return; }

        $units = Database::fetchAll(
            "SELECT id, name, total_units, moderation_status FROM property_units WHERE property_id = :p AND is_active=1 ORDER BY sort_order, id",
            ['p' => $id]
        );

        $month  = isset($_GET['month']) ? max(1, min(12, (int)$_GET['month'])) : (int)date('n');
        $year   = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $unitId = isset($_GET['unit']) ? (int)$_GET['unit'] : (int)($units[0]['id'] ?? 0);

        $selectedUnit = null;
        foreach ($units as $u) {
            if ((int)$u['id'] === $unitId) { $selectedUnit = $u; break; }
        }
        $totalUnits = max(1, (int)($selectedUnit['total_units'] ?? 1));

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));

        // Load availability rows
        $availMap = [];
        if ($unitId) {
            $rows = Database::fetchAll(
                "SELECT date, status, available_units, note FROM availability WHERE unit_id = :u AND date BETWEEN :s AND :e",
                ['u' => $unitId, 's' => $start, 'e' => $end]
            );
            foreach ($rows as $r) $availMap[$r['date']] = $r;

            // Load existing bookings to mark dates booked
            $bookings = Database::fetchAll(
                "SELECT check_in, check_out FROM bookings
                 WHERE unit_id = :u AND status IN ('pending','confirmed','completed')
                 AND check_in <= :e AND check_out > :s",
                ['u' => $unitId, 's' => $start, 'e' => $end]
            );
            foreach ($bookings as $b) {
                $cur = $b['check_in']; $stop = $b['check_out'];
                while ($cur < $stop) {
                    if ($cur >= $start && $cur <= $end) {
                        $availMap[$cur]['booked'] = ($availMap[$cur]['booked'] ?? 0) + 1;
                    }
                    $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
                }
            }
        }

        $dayMeta = [];
        if ($unitId) {
            for ($d = 1; $d <= (int)date('t', strtotime($start)); $d++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $dayMeta[$date] = self::dayMeta($date, $availMap[$date] ?? null, $totalUnits);
            }
        }

        View::render('owner/availability/index', [
            'page_title' => 'ปฏิทินวันว่าง: ' . $property['name'],
            'property' => $property, 'units' => $units, 'unitId' => $unitId,
            'month' => $month, 'year' => $year, 'availMap' => $availMap,
            'dayMeta' => $dayMeta, 'totalUnits' => $totalUnits,
        ], 'layouts/owner');
    }

    /** สถานะวันสำหรับแสดงในปฏิทิน (ตรงกับที่ LINE bot ใช้เป็นหลัก) */
    private static function dayMeta(string $date, ?array $row, int $totalUnits): array
    {
        if ($date < date('Y-m-d')) {
            return ['key' => 'past', 'label' => '', 'cls' => 'bg-slate-50 border-slate-200 text-slate-400'];
        }
        $status = $row['status'] ?? null;
        if (in_array($status, ['closed', 'blocked', 'fully_booked'], true)) {
            return ['key' => 'closed', 'label' => 'ปิด', 'cls' => 'bg-slate-300 border-slate-400 text-slate-700'];
        }
        $booked = (int)($row['booked'] ?? 0);
        if ($booked >= $totalUnits) {
            return ['key' => 'full', 'label' => 'เต็ม', 'cls' => 'bg-rose-100 border-rose-300 text-rose-800'];
        }
        return ['key' => 'open', 'label' => 'ว่าง', 'cls' => 'bg-emerald-100 border-emerald-300 text-emerald-800'];
    }

    public function save(int $id): void
    {
        $property = $this->findOwn($id);
        if (!$property) { http_response_code(404); View::render('errors/404'); return; }

        $unitId = (int)($_POST['unit_id'] ?? 0);
        $u = Database::fetch("SELECT id FROM property_units WHERE id = :i AND property_id = :p", ['i' => $unitId, 'p' => $id]);
        if (!$u) { Session::flash('error', 'ไม่พบห้อง'); back(); }

        $dates  = (array)($_POST['dates'] ?? []);
        $status = $_POST['status'] ?? 'open';
        $available = (int)($_POST['available_units'] ?? 1);

        if (!in_array($status, ['open','closed','fully_booked','blocked'])) $status = 'open';

        foreach ($dates as $d) {
            $d = trim($d);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) continue;

            $exists = Database::fetch("SELECT id FROM availability WHERE unit_id = :u AND date = :d", ['u' => $unitId, 'd' => $d]);
            if ($exists) {
                Database::update('availability', ['status' => $status, 'available_units' => $available], 'id = :i', ['i' => $exists['id']]);
            } else {
                Database::insert('availability', [
                    'unit_id' => $unitId, 'date' => $d, 'status' => $status, 'available_units' => $available,
                ]);
            }
        }

        Session::flash('success', 'อัปเดตวันว่างเรียบร้อย ' . count($dates) . ' วัน');
        $month = (int)($_POST['month'] ?? date('n'));
        $year  = (int)($_POST['year']  ?? date('Y'));
        redirect(url('/owner/properties/' . $id . '/availability') . '?unit=' . $unitId . '&month=' . $month . '&year=' . $year);
    }
}
