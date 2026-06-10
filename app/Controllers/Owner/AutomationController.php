<?php

namespace App\Controllers\Owner;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\View;

class AutomationController extends Controller
{
    /** Event definitions — event_type => meta */
    public static function eventTypes(): array
    {
        return [
            'booking_confirmed' => [
                'label'       => 'จองสำเร็จ / ยืนยันการจอง',
                'icon'        => 'calendar-check',
                'color'       => 'emerald',
                'description' => 'ส่งทันทีเมื่อแอดมินยืนยันการจอง',
                'default'     => "✅ ยืนยันการจองเรียบร้อย!\n\n📋 รหัสจอง: {{booking_code}}\n🏡 {{property_name}}\n📅 เช็คอิน: {{check_in_date}}\n📅 เช็คเอาท์: {{check_out_date}}\n\nหากมีข้อสงสัยติดต่อเราได้เลยครับ 🙏",
            ],
            'deposit_received' => [
                'label'       => 'รับมัดจำแล้ว',
                'icon'        => 'banknote',
                'color'       => 'blue',
                'description' => 'ส่งเมื่อตรวจสอบสลิปมัดจำแล้ว',
                'default'     => "💳 ได้รับมัดจำเรียบร้อยแล้วครับ\n\n📋 การจอง #{{booking_code}}\n🏡 {{property_name}}\n📅 เช็คอิน: {{check_in_date}}\n\nเราจะรักษาห้องไว้ให้คุณแน่นอน 😊",
            ],
            'checkin_reminder_1d' => [
                'label'       => 'เตือนเช็คอิน 1 วันล่วงหน้า',
                'icon'        => 'alarm-clock',
                'color'       => 'amber',
                'description' => 'ส่งก่อนวันเช็คอิน 1 วัน (แทนค่าเริ่มต้น 2 วัน)',
                'default'     => "⏰ พรุ่งนี้เช็คอินแล้วนะครับ!\n\n🏡 {{property_name}}\n📅 เช็คอิน: {{check_in_date}}\n\nหากต้องการข้อมูลเพิ่มเติมหรือ GPS ติดต่อได้เลยครับ 🗺️",
            ],
            'checkout_followup' => [
                'label'       => 'ติดตามหลังเช็คเอาท์',
                'icon'        => 'heart',
                'color'       => 'rose',
                'description' => 'ส่งหลัง checkout ไม่เกิน 3 ชั่วโมง — ถามความพอใจ + ขอรีวิว',
                'default'     => "🙏 ขอบคุณที่เลือกพักที่ {{property_name}} นะครับ\n\nหวังว่าจะประทับใจ 😊 ถ้ามีโอกาสอยากให้ฝากรีวิวสักนิดนะครับ\n\n⭐ รีวิวได้ที่: {{review_url}}\n\nยินดีต้อนรับกลับมาเสมอครับ 🏕️",
            ],
            'review_request' => [
                'label'       => 'ขอรีวิว (3 วันหลัง checkout)',
                'icon'        => 'star',
                'color'       => 'violet',
                'description' => 'ส่ง 3 วันหลัง checkout — เตือนขอรีวิวอีกครั้ง',
                'default'     => "⭐ ยังไม่ได้ฝากรีวิวใช่ไหมครับ?\n\nรีวิวของคุณมีค่ามากสำหรับเรา ใช้เวลาแค่ 1 นาที 🙏\n\n🔗 {{review_url}}\n\nขอบคุณมากครับ 😊",
            ],
            'reengagement_30d' => [
                'label'       => 'ดึงลูกค้าเก่ากลับมา (30 วัน)',
                'icon'        => 'refresh-cw',
                'color'       => 'teal',
                'description' => 'ส่งหาลูกค้าที่เคยพักแต่ไม่ได้จองใน 30 วัน',
                'default'     => "🌿 คิดถึงครับ!\n\nนานแล้วที่ไม่ได้เจอกัน มีวันหยุดว่าง ลองแวะมาที่ {{property_name}} อีกสักครั้งนะครับ 😊\n\nช่วงนี้มีห้องว่างหลายช่วง ทักมาถามได้เลยครับ!",
            ],
        ];
    }

    /** GET /owner/automation */
    public function index(): void
    {
        $ownerId    = Auth::ownerId();
        $properties = $ownerId
            ? Database::fetchAll("SELECT id, name FROM properties WHERE owner_id = :o ORDER BY id", ['o' => $ownerId])
            : [];

        if (Auth::isAdmin()) {
            $properties = Database::fetchAll("SELECT id, name FROM properties ORDER BY id");
        }

        $propertyId = (int)($_GET['property_id'] ?? ($properties[0]['id'] ?? 0));
        if ($ownerId && $propertyId) {
            $owns = false;
            foreach ($properties as $p) { if ((int)$p['id'] === $propertyId) { $owns = true; break; } }
            if (!$owns) $propertyId = (int)($properties[0]['id'] ?? 0);
        }

        $templates = [];
        $hasTable  = Database::tableHasColumn('property_message_templates', 'id');

        if ($propertyId && $hasTable) {
            $rows = Database::fetchAll(
                "SELECT event_type, is_enabled, message_text, send_delay_hours
                 FROM property_message_templates WHERE property_id = :p",
                ['p' => $propertyId]
            );
            foreach ($rows as $r) {
                $templates[$r['event_type']] = $r;
            }
        }

        View::render('owner/automation/index', [
            'page_title'  => 'Automation — ส่งข้อความอัตโนมัติ',
            'properties'  => $properties,
            'propertyId'  => $propertyId,
            'templates'   => $templates,
            'eventTypes'  => self::eventTypes(),
            'hasTable'    => $hasTable,
        ], 'layouts/owner');
    }

    /** POST /owner/automation/save — upsert template */
    public function save(): void
    {
        $propertyId = (int)($_POST['property_id'] ?? 0);
        $eventType  = trim((string)($_POST['event_type'] ?? ''));
        $enabled    = (int)($_POST['is_enabled'] ?? 0);
        $text       = mb_substr(trim((string)($_POST['message_text'] ?? '')), 0, 2000);

        if (!$propertyId || !$eventType || !array_key_exists($eventType, self::eventTypes())) {
            $this->json(['ok' => false, 'error' => 'ข้อมูลไม่ครบ']); return;
        }
        if (!$this->canAccess($propertyId)) {
            $this->json(['ok' => false, 'error' => 'ไม่มีสิทธิ์']); return;
        }
        if (!Database::tableHasColumn('property_message_templates', 'id')) {
            $this->json(['ok' => false, 'error' => 'ยังไม่ได้รัน migration']); return;
        }

        $existing = Database::fetch(
            "SELECT id FROM property_message_templates WHERE property_id = :p AND event_type = :e",
            ['p' => $propertyId, 'e' => $eventType]
        );

        if ($existing) {
            Database::update('property_message_templates',
                ['is_enabled' => $enabled, 'message_text' => $text],
                'id = :i', ['i' => $existing['id']]);
        } else {
            Database::insert('property_message_templates', [
                'property_id'  => $propertyId,
                'event_type'   => $eventType,
                'is_enabled'   => $enabled,
                'message_text' => $text,
            ]);
        }
        $this->json(['ok' => true]);
    }

    /** POST /owner/automation/ai-draft — AI ช่วยร่างข้อความ */
    public function aiDraft(): void
    {
        $propertyId = (int)($_POST['property_id'] ?? 0);
        $eventType  = trim((string)($_POST['event_type'] ?? ''));
        $context    = mb_substr(trim((string)($_POST['context'] ?? '')), 0, 300);

        if (!$propertyId || !$eventType) {
            $this->json(['ok' => false, 'error' => 'ข้อมูลไม่ครบ']); return;
        }
        if (!$this->canAccess($propertyId)) {
            $this->json(['ok' => false, 'error' => 'ไม่มีสิทธิ์']); return;
        }

        $property = \App\Core\Database::fetch(
            "SELECT name, type, zone FROM properties WHERE id = :i", ['i' => $propertyId]
        );
        if (!$property) { $this->json(['ok' => false, 'error' => 'ไม่พบที่พัก']); return; }

        $eventTypes = self::eventTypes();
        $eventMeta  = $eventTypes[$eventType] ?? null;
        // 'broadcast' เป็น special type ที่ไม่มีใน eventTypes — ยังคง allow
        if (!$eventMeta && $eventType !== 'broadcast') {
            $this->json(['ok' => false, 'error' => 'event type ไม่รู้จัก']); return;
        }
        if (!$eventMeta) {
            $eventMeta = ['label' => 'ข้อความโปรโมทหรือแคมเปญ'];
        }

        $propName = $property['name'];
        $propType = ['raft' => 'แพพัก', 'resort' => 'รีสอร์ท', 'homestay' => 'โฮมสเตย์',
                     'house' => 'บ้านพัก', 'pool_villa' => 'บ้านพูลวิลล่า',
                     'hotel' => 'โรงแรม', 'camping' => 'แคมป์ปิ้ง'][$property['type']] ?? $property['type'];
        $zone = $property['zone'] ?: 'กาญจนบุรี';
        $eventLabel = $eventMeta['label'];

        $instruction = <<<PROMPT
คุณคือผู้ช่วยเขียน LINE message สำหรับธุรกิจที่พักในกาญจนบุรี
เขียนข้อความ LINE สำหรับ event: {$eventLabel}
ที่พัก: {$propName} ({$propType}) ใน {$zone}
ข้อมูลเพิ่มเติมจากเจ้าของ: {$context}

กฎการเขียน:
- ใช้ภาษาไทยที่เป็นมิตร สั้นกระชับ อบอุ่น
- ใช้ emoji บ้าง ไม่มากเกินไป
- ความยาวไม่เกิน 300 ตัวอักษร
- ใช้ตัวแปร {{guest_name}}, {{property_name}}, {{check_in_date}}, {{check_out_date}}, {{booking_code}}, {{review_url}} ตามเหมาะสม
- ห้ามใส่คำอธิบายนอกเนื้อหาข้อความ — ตอบด้วยเนื้อหาข้อความ LINE เท่านั้น

PROMPT;

        $draft = \App\Services\AIService::generate($instruction, '', 0.75, 300);
        if ($draft === null) {
            $this->json(['ok' => false, 'error' => 'AI ไม่พร้อมใช้งานตอนนี้']); return;
        }

        $this->json(['ok' => true, 'text' => trim($draft)]);
    }

    private function canAccess(int $propertyId): bool
    {
        if (Auth::isAdmin()) return true;
        $p = Database::fetch("SELECT owner_id FROM properties WHERE id = :i", ['i' => $propertyId]);
        return $p && ((int)$p['owner_id'] === Auth::ownerId());
    }
}
