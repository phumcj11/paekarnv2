<?php
namespace App\Controllers\Owner;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\View;
use App\Services\PropertyLineService;

class LineContactController extends Controller
{
    /** GET /owner/line-contacts?property_id=N&q=&page= */
    public function index(): void
    {
        $ownerId    = Auth::ownerId();
        $hasLineCol = Database::tableHasColumn('properties', 'line_messaging_enabled');
        $properties = $ownerId
            ? Database::fetchAll(
                "SELECT p.id, p.name" . ($hasLineCol ? ', p.line_messaging_enabled' : '') . "
                 FROM properties p
                 WHERE p.owner_id = :o
                 ORDER BY p.id ASC",
                ['o' => $ownerId]
            )
            : [];

        if (Auth::isAdmin()) {
            $properties = Database::fetchAll(
                "SELECT id, name" . ($hasLineCol ? ', line_messaging_enabled' : '') . "
                 FROM properties ORDER BY id ASC"
            );
        }

        $propertyId = (int)($_GET['property_id'] ?? ($properties[0]['id'] ?? 0));
        $q          = trim((string)($_GET['q'] ?? ''));
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $perPage    = 30;

        $contacts = [];
        $total    = 0;

        if ($propertyId) {
            $phoneCol = Database::tableHasColumn('property_line_contacts', 'phone') ? ', plc.phone' : '';
            $where    = 'plc.property_id = :p';
            $params   = ['p' => $propertyId];

            if ($q !== '') {
                $like = '%' . $q . '%';
                $where .= ' AND (plc.display_name LIKE :q1 OR plc.line_user_id LIKE :q2';
                $params['q1'] = $like;
                $params['q2'] = $like;
                if ($phoneCol !== '') {
                    $where .= ' OR plc.phone LIKE :q3';
                    $params['q3'] = $like;
                }
                $where .= ')';
            }

            $countRow = Database::fetch(
                "SELECT COUNT(*) AS cnt FROM property_line_contacts plc WHERE {$where}",
                $params
            );
            $total  = (int)($countRow['cnt'] ?? 0);
            $offset = ($page - 1) * $perPage;

            $hasLineUid = Database::tableHasColumn('bookings', 'guest_line_user_id');
            if ($hasLineUid) {
                $contacts = Database::fetchAll(
                    "SELECT plc.id, plc.line_user_id, plc.display_name, plc.picture_url,
                            plc.followed_at, plc.unfollowed_at, plc.last_seen_at{$phoneCol},
                            COUNT(b.id) AS booking_count,
                            MAX(b.check_in) AS last_booking_date
                     FROM property_line_contacts plc
                     LEFT JOIN bookings b
                            ON b.guest_line_user_id = plc.line_user_id
                           AND b.property_id = plc.property_id
                     WHERE {$where}
                     GROUP BY plc.id
                     ORDER BY plc.last_seen_at DESC
                     LIMIT {$perPage} OFFSET {$offset}",
                    $params
                );
            } else {
                $contacts = Database::fetchAll(
                    "SELECT plc.id, plc.line_user_id, plc.display_name, plc.picture_url,
                            plc.followed_at, plc.unfollowed_at, plc.last_seen_at{$phoneCol},
                            0 AS booking_count, NULL AS last_booking_date
                     FROM property_line_contacts plc
                     WHERE {$where}
                     ORDER BY plc.last_seen_at DESC
                     LIMIT {$perPage} OFFSET {$offset}",
                    $params
                );
            }
        }

        View::render('owner/line_contacts/index', [
            'page_title'  => 'รายชื่อแชท LINE',
            'properties'  => $properties,
            'propertyId'  => $propertyId,
            'contacts'    => $contacts,
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'q'           => $q,
        ], 'layouts/owner');
    }

    /** POST /owner/line-contacts/{id}/phone — อัปเดตเบอร์ */
    public function updatePhone(int $id): void
    {
        if (!Database::tableHasColumn('property_line_contacts', 'phone')) {
            $this->json(['ok' => false, 'error' => 'ฐานข้อมูลยังไม่มีคอลัมน์ phone']); return;
        }

        $contact = $this->ownedContact($id);
        if (!$contact) { $this->json(['ok' => false, 'error' => 'ไม่พบข้อมูล']); return; }

        $raw   = trim((string)($_POST['phone'] ?? ''));
        $phone = $raw !== '' ? PropertyLineService::normalizePhone($raw) : '';

        Database::update('property_line_contacts', ['phone' => $phone ?: null], 'id = :i', ['i' => $id]);
        $this->json(['ok' => true, 'phone' => $phone]);
    }

    /** POST /owner/line-contacts/{id}/message — push ข้อความให้ 1 คน */
    public function sendMessage(int $id): void
    {
        $contact = $this->ownedContact($id);
        if (!$contact) { $this->json(['ok' => false, 'error' => 'ไม่พบข้อมูล']); return; }

        $text = trim((string)($_POST['text'] ?? ''));
        if ($text === '') { $this->json(['ok' => false, 'error' => 'ข้อความว่าง']); return; }
        if (mb_strlen($text) > 2000) { $this->json(['ok' => false, 'error' => 'ข้อความยาวเกิน 2000 ตัวอักษร']); return; }

        $ok = PropertyLineService::push(
            (int)$contact['property_id'],
            (string)$contact['line_user_id'],
            [['type' => 'text', 'text' => $text]]
        );

        $this->json(['ok' => $ok, 'error' => $ok ? '' : 'ส่งไม่สำเร็จ — ตรวจสอบ Channel Access Token']);
    }

    /** POST /owner/line-contacts/broadcast?property_id=N — push ให้ทุกคน (หรือ filtered) */
    public function broadcast(): void
    {
        $propertyId = (int)($_POST['property_id'] ?? 0);
        if (!$propertyId) { $this->json(['ok' => false, 'error' => 'ไม่ระบุ property_id']); return; }

        if (!$this->canAccessProperty($propertyId)) {
            $this->json(['ok' => false, 'error' => 'ไม่มีสิทธิ์']); return;
        }

        $text = trim((string)($_POST['text'] ?? ''));
        if ($text === '') { $this->json(['ok' => false, 'error' => 'ข้อความว่าง']); return; }
        if (mb_strlen($text) > 2000) { $this->json(['ok' => false, 'error' => 'ข้อความยาวเกิน 2000 ตัวอักษร']); return; }

        $contacts = Database::fetchAll(
            "SELECT line_user_id FROM property_line_contacts
             WHERE property_id = :p AND unfollowed_at IS NULL
             ORDER BY last_seen_at DESC",
            ['p' => $propertyId]
        );

        $sent    = 0;
        $failed  = 0;
        $msgs    = [['type' => 'text', 'text' => $text]];

        foreach ($contacts as $c) {
            $ok = PropertyLineService::push($propertyId, (string)$c['line_user_id'], $msgs);
            $ok ? $sent++ : $failed++;
            // throttle — LINE อนุญาต 200 req/sec แต่ conservative
            if (($sent + $failed) % 10 === 0) usleep(100000); // 0.1s per 10 msgs
        }

        $this->json(['ok' => true, 'sent' => $sent, 'failed' => $failed]);
    }

    // ─────────────────────────────────────────────

    private function ownedContact(int $id): ?array
    {
        $contact = Database::fetch(
            "SELECT plc.*, p.owner_id
             FROM property_line_contacts plc
             JOIN properties p ON p.id = plc.property_id
             WHERE plc.id = :i LIMIT 1",
            ['i' => $id]
        );
        if (!$contact) return null;
        if (!Auth::isAdmin() && Auth::ownerId() && (int)$contact['owner_id'] !== Auth::ownerId()) {
            return null;
        }
        return $contact;
    }

    private function canAccessProperty(int $propertyId): bool
    {
        if (Auth::isAdmin()) return true;
        $p = Database::fetch("SELECT owner_id FROM properties WHERE id = :i LIMIT 1", ['i' => $propertyId]);
        return $p && ((int)$p['owner_id'] === Auth::ownerId());
    }
}
