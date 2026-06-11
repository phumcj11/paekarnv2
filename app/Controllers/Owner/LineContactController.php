<?php
namespace App\Controllers\Owner;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\View;
use App\Services\AIService;
use App\Services\OwnerTier;
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

        $contacts     = [];
        $total        = 0;
        $allTags      = [];
        $filterTag    = trim((string)($_GET['tag'] ?? ''));
        $filterSegment = trim((string)($_GET['segment'] ?? ''));

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

            $tagsCol  = Database::tableHasColumn('property_line_contacts', 'tags')  ? ', plc.tags'  : '';
            $notesCol = Database::tableHasColumn('property_line_contacts', 'notes') ? ', plc.notes' : '';

            // filter by tag
            $filterTag = trim((string)($_GET['tag'] ?? ''));
            if ($filterTag !== '' && $tagsCol !== '') {
                $where .= " AND JSON_CONTAINS(plc.tags, :ft, '$')";
                $params['ft'] = json_encode($filterTag, JSON_UNESCAPED_UNICODE);
            }

            $hasLineUid = Database::tableHasColumn('bookings', 'guest_line_user_id');
            if ($hasLineUid) {
                $contacts = Database::fetchAll(
                    "SELECT plc.id, plc.line_user_id, plc.display_name, plc.picture_url,
                            plc.followed_at, plc.unfollowed_at, plc.last_seen_at{$phoneCol}{$tagsCol}{$notesCol},
                            COUNT(b.id) AS booking_count,
                            MAX(b.check_in) AS last_booking_date,
                            (SELECT b2.status FROM bookings b2
                             WHERE b2.guest_line_user_id = plc.line_user_id
                               AND b2.property_id = plc.property_id
                             ORDER BY b2.created_at DESC LIMIT 1) AS last_booking_status,
                            (SELECT b3.total_price FROM bookings b3
                             WHERE b3.guest_line_user_id = plc.line_user_id
                               AND b3.property_id = plc.property_id
                               AND b3.status IN ('confirmed','completed')
                             ORDER BY b3.created_at DESC LIMIT 1) AS last_booking_price
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
                            plc.followed_at, plc.unfollowed_at, plc.last_seen_at{$phoneCol}{$tagsCol}{$notesCol},
                            0 AS booking_count, NULL AS last_booking_date,
                            NULL AS last_booking_status, NULL AS last_booking_price
                     FROM property_line_contacts plc
                     WHERE {$where}
                     ORDER BY plc.last_seen_at DESC
                     LIMIT {$perPage} OFFSET {$offset}",
                    $params
                );
            }

            // เพิ่ม auto-segment ให้แต่ละ contact
            foreach ($contacts as &$c) {
                $bookings = (int)($c['booking_count'] ?? 0);
                $lastDate = $c['last_booking_date'] ?? null;
                $daysSince = $lastDate ? (int)((time() - strtotime($lastDate)) / 86400) : null;
                if ($bookings === 0) {
                    $c['auto_segment'] = 'ทักแต่ไม่จอง';
                } elseif ($daysSince !== null && $daysSince >= 90) {
                    $c['auto_segment'] = 'ลูกค้าเก่า 90+ วัน';
                } else {
                    $c['auto_segment'] = null;
                }
            }
            unset($c);

            // เก็บ tag ทั้งหมดที่ใช้ใน property นี้ (สำหรับ filter sidebar)
            if ($tagsCol !== '') {
                $allTagRows = Database::fetchAll(
                    "SELECT DISTINCT tags FROM property_line_contacts
                     WHERE property_id = :p AND tags IS NOT NULL AND tags != 'null'",
                    ['p' => $propertyId]
                );
                foreach ($allTagRows as $tr) {
                    $decoded = json_decode((string)$tr['tags'], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $t) {
                            $allTags[$t] = ($allTags[$t] ?? 0) + 1;
                        }
                    }
                }
                arsort($allTags);
            }
        }

        // Client-side segment filter (done in PHP after query — avoids complex SQL)
        if ($filterSegment !== '') {
            $contacts = array_values(array_filter($contacts, fn($c) => ($c['auto_segment'] ?? '') === $filterSegment));
        }

        View::render('owner/line_contacts/index', [
            'page_title'    => 'รายชื่อแชท LINE',
            'properties'    => $properties,
            'propertyId'    => $propertyId,
            'contacts'      => $contacts,
            'total'         => $total,
            'page'          => $page,
            'perPage'       => $perPage,
            'q'             => $q,
            'allTags'       => $allTags,
            'filterTag'     => $filterTag,
            'filterSegment' => $filterSegment,
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

    /** POST /owner/line-contacts/{id}/tags — เพิ่ม/ลบ tag บน contact */
    public function updateTags(int $id): void
    {
        $contact = $this->ownedContact($id);
        if (!$contact) { $this->json(['ok' => false, 'error' => 'ไม่พบข้อมูล']); return; }

        if (!Database::tableHasColumn('property_line_contacts', 'tags')) {
            $this->json(['ok' => false, 'error' => 'ยังไม่ได้รัน migration tags']); return;
        }

        $rawTags = $_POST['tags'] ?? [];
        if (is_string($rawTags)) {
            $rawTags = json_decode($rawTags, true) ?: explode(',', $rawTags);
        }
        $tags = array_values(array_unique(array_filter(array_map('trim', (array)$rawTags))));
        // จำกัด tag ไม่เกิน 10 อัน, ยาวไม่เกิน 30 ตัวอักษรต่ออัน
        $tags = array_slice(array_filter($tags, fn($t) => mb_strlen($t) <= 30), 0, 10);

        Database::update(
            'property_line_contacts',
            ['tags' => !empty($tags) ? json_encode($tags, JSON_UNESCAPED_UNICODE) : null],
            'id = :i',
            ['i' => $id]
        );
        $this->json(['ok' => true, 'tags' => $tags]);
    }

    /** POST /owner/line-contacts/{id}/notes — บันทึกโน้ต */
    public function updateNotes(int $id): void
    {
        $contact = $this->ownedContact($id);
        if (!$contact) { $this->json(['ok' => false, 'error' => 'ไม่พบข้อมูล']); return; }

        $notes = mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 500);
        if (Database::tableHasColumn('property_line_contacts', 'notes')) {
            Database::update('property_line_contacts', ['notes' => $notes ?: null], 'id = :i', ['i' => $id]);
        }
        $this->json(['ok' => true]);
    }

    /**
     * GET /owner/line-contacts/{id}/ai-reply
     * AI ช่วยร่างข้อความตอบลูกค้าตาม context ของ contact นั้น (ชื่อ, segment, ประวัติจอง)
     */
    public function aiReply(): void
    {
        $id      = (int)($this->params['id'] ?? 0);
        $context = mb_substr(trim((string)($_GET['context'] ?? '')), 0, 300);

        $contact = $this->ownedContact($id);
        if (!$contact) { $this->json(['ok' => false, 'error' => 'ไม่พบข้อมูล']); return; }

        $ownerId = Auth::ownerId();
        if ($ownerId && !OwnerTier::can($ownerId, OwnerTier::FEATURE_AI_DRAFT)) {
            $this->json(['ok' => false, 'error' => 'ฟีเจอร์นี้ต้องใช้แพ็กเกจ Starter ขึ้นไป']); return;
        }

        $property = Database::fetch(
            "SELECT name, type, zone FROM properties WHERE id = :i LIMIT 1",
            ['i' => $contact['property_id']]
        );
        if (!$property) { $this->json(['ok' => false, 'error' => 'ไม่พบที่พัก']); return; }

        // ดึง booking history
        $bookings = Database::fetchAll(
            "SELECT check_in, check_out, status, total_price FROM bookings
              WHERE guest_line_user_id = :uid AND property_id = :pid
              ORDER BY created_at DESC LIMIT 5",
            ['uid' => $contact['line_user_id'], 'pid' => $contact['property_id']]
        );
        $historyParts = [];
        foreach ($bookings as $b) {
            $historyParts[] = "{$b['check_in']} – {$b['check_out']} ({$b['status']}, ฿" . number_format((float)$b['total_price']) . ')';
        }
        $historyStr = $historyParts ? implode('; ', $historyParts) : 'ไม่มีประวัติการจอง';

        // Auto segment
        $segment = '';
        if (!empty($contact['auto_segment'])) $segment = $contact['auto_segment'];

        $guestName = $contact['display_name'] ?: 'ลูกค้า LINE';

        $instruction = <<<PROMPT
คุณคือพนักงานต้อนรับของ "{$property['name']}" ที่พักในกาญจนบุรี
กำลังจะส่งข้อความตอบลูกค้าชื่อ "{$guestName}" ผ่าน LINE
ประวัติการจอง: {$historyStr}
กลุ่ม: {$segment}
{$context}

เขียนข้อความตอบกลับ LINE สั้นๆ (ไม่เกิน 200 ตัวอักษร) เป็นกันเอง ภาษาไทย
กฎ: ตอบเฉพาะเนื้อหาข้อความ — ห้ามใส่คำอธิบายนอกเนื้อหา
PROMPT;

        $text = AIService::generate($instruction, '', 0.75, 200);
        if ($text === null) {
            $this->json(['ok' => false, 'error' => 'AI ไม่พร้อมใช้งาน']); return;
        }
        $this->json(['ok' => true, 'text' => trim($text)]);
    }

    /** POST /owner/line-contacts/broadcast?property_id=N — push ให้ทุกคน (หรือ filtered) */
    public function broadcast(): void
    {
        $propertyId = (int)($_POST['property_id'] ?? 0);
        if (!$propertyId) { $this->json(['ok' => false, 'error' => 'ไม่ระบุ property_id']); return; }

        if (!$this->canAccessProperty($propertyId)) {
            $this->json(['ok' => false, 'error' => 'ไม่มีสิทธิ์']); return;
        }

        $ownerId = Auth::ownerId();
        if ($ownerId && !OwnerTier::can($ownerId, OwnerTier::FEATURE_BROADCAST)) {
            $this->json(['ok' => false, 'error' => 'ฟีเจอร์นี้ต้องใช้แพ็กเกจ Standard ขึ้นไป']); return;
        }

        $text = trim((string)($_POST['text'] ?? ''));
        if ($text === '') { $this->json(['ok' => false, 'error' => 'ข้อความว่าง']); return; }
        if (mb_strlen($text) > 2000) { $this->json(['ok' => false, 'error' => 'ข้อความยาวเกิน 2000 ตัวอักษร']); return; }

        // filter by tag ถ้าระบุมา
        $filterTag  = trim((string)($_POST['tag'] ?? ''));
        $tagWhere   = '';
        $tagParams  = ['p' => $propertyId];

        if ($filterTag !== '' && Database::tableHasColumn('property_line_contacts', 'tags')) {
            $tagWhere = " AND JSON_CONTAINS(tags, :tag, '$')";
            $tagParams['tag'] = json_encode($filterTag, JSON_UNESCAPED_UNICODE);
        }

        $contacts = Database::fetchAll(
            "SELECT line_user_id FROM property_line_contacts
             WHERE property_id = :p AND unfollowed_at IS NULL {$tagWhere}
             ORDER BY last_seen_at DESC",
            $tagParams
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
