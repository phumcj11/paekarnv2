<?php
namespace App\Controllers\Owner;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\ContentPlan;
use App\Services\AIService;

class ContentPlanController extends Controller
{
    private function ownerId(): int
    {
        $id = Auth::ownerId();
        if (!$id && Auth::isAdmin()) {
            return 0;
        }
        return (int)$id;
    }

    /** GET /owner/content-plans?month=Y-m&tab=calendar|groups|leads */
    public function index(): void
    {
        $ownerId = $this->ownerId();
        $tab     = in_array($_GET['tab'] ?? '', ['calendar', 'groups', 'leads']) ? $_GET['tab'] : 'calendar';

        // ---- Calendar data ----
        $monthParam = $_GET['month'] ?? date('Y-m');
        [$year, $month] = array_map('intval', explode('-', $monthParam . '-0'));
        if ($year < 2020 || $year > 2050 || $month < 1 || $month > 12) {
            $year  = (int)date('Y');
            $month = (int)date('n');
        }

        $plans   = $ownerId ? ContentPlan::forMonth($ownerId, $year, $month) : [];
        $counts  = $ownerId ? ContentPlan::countThisMonth($ownerId) : array_fill_keys(ContentPlan::STATUSES, 0);
        $calMap  = [];
        foreach ($plans as $p) {
            $calMap[$p['post_date']][] = $p;
        }
        $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
        $firstDow    = (int)date('N', mktime(0, 0, 0, $month, 1, $year));
        $prevMonth   = $month === 1 ? ['year' => $year - 1, 'month' => 12] : ['year' => $year, 'month' => $month - 1];
        $nextMonth   = $month === 12 ? ['year' => $year + 1, 'month' => 1]  : ['year' => $year, 'month' => $month + 1];

        // ---- Properties ----
        $properties = $ownerId
            ? Database::fetchAll("SELECT id, name FROM properties WHERE owner_id = :o ORDER BY name", ['o' => $ownerId])
            : [];

        $hasMarketingTables = self::hasMarketingTables();

        // ---- Groups (FB) ----
        $groups = ($ownerId && $hasMarketingTables)
            ? Database::fetchAll("SELECT * FROM marketing_fb_groups WHERE owner_id = :o ORDER BY created_at ASC", ['o' => $ownerId])
            : [];

        // ---- Leads ----
        $leads = ($ownerId && $hasMarketingTables)
            ? Database::fetchAll(
                "SELECT l.*, p.name AS property_name FROM marketing_leads l
                 LEFT JOIN properties p ON p.id = l.property_id
                 WHERE l.owner_id = :o ORDER BY l.found_at DESC, l.id DESC LIMIT 200",
                ['o' => $ownerId]
            )
            : [];

        View::render('owner/content_plans/index', [
            'page_title'   => 'Marketing Center',
            'tab'          => $tab,
            'hasMarketingTables' => $hasMarketingTables,
            // calendar
            'year'         => $year,
            'month'        => $month,
            'monthLabel'   => self::thMonthName($month) . ' ' . ($year + 543),
            'daysInMonth'  => $daysInMonth,
            'firstDow'     => $firstDow,
            'calMap'       => $calMap,
            'plans'        => $plans,
            'counts'       => $counts,
            'prevMonth'    => $prevMonth,
            'nextMonth'    => $nextMonth,
            'today'        => date('Y-m-d'),
            // shared
            'properties'   => $properties,
            // groups
            'groups'       => $groups,
            // leads
            'leads'        => $leads,
        ], 'layouts/owner');
    }

    /** POST /owner/content-plans */
    public function store(): void
    {
        $ownerId = $this->ownerId();
        if (!$ownerId) { $this->json(['ok' => false, 'error' => 'ไม่พบ owner']); return; }

        $data     = $this->input();
        $postDate = trim((string)($data['post_date'] ?? ''));
        $body     = trim((string)($data['body'] ?? ''));
        if (!$postDate || !$body) {
            $this->json(['ok' => false, 'error' => 'กรุณากรอกวันที่และเนื้อหา']);
            return;
        }
        $id  = ContentPlan::create(array_merge($data, ['owner_id' => $ownerId]));
        $row = ContentPlan::find($id);
        $this->json(['ok' => true, 'id' => $id, 'plan' => $row]);
    }

    /** POST /owner/content-plans/{id}/update */
    public function update(int $id): void
    {
        $ownerId = $this->ownerId();
        $plan    = ContentPlan::findForOwner($id, $ownerId);
        if (!$plan && !Auth::isAdmin()) { $this->json(['ok' => false, 'error' => 'ไม่พบรายการ']); return; }
        $data = $this->input();
        ContentPlan::update($id, array_merge($data, ['owner_id' => $plan['owner_id'] ?? $ownerId]));
        $this->json(['ok' => true, 'plan' => ContentPlan::find($id)]);
    }

    /** POST /owner/content-plans/{id}/delete */
    public function destroy(int $id): void
    {
        $ownerId = $this->ownerId();
        $plan    = ContentPlan::findForOwner($id, $ownerId);
        if (!$plan && !Auth::isAdmin()) { $this->json(['ok' => false, 'error' => 'ไม่พบรายการ']); return; }
        ContentPlan::delete($id);
        $this->json(['ok' => true]);
    }

    /** POST /owner/content-plans/ai-generate */
    public function aiGenerate(): void
    {
        $ownerId = $this->ownerId();
        if (!$ownerId) { $this->json(['ok' => false, 'error' => 'ไม่พบ owner']); return; }

        $data        = $this->input();
        $platform    = $data['platform'] ?? 'facebook';
        $postType    = $data['post_type'] ?? 'page';    // page | group | line_broadcast
        $propName    = trim((string)($data['property_name'] ?? ''));
        $propType    = trim((string)($data['property_type'] ?? ''));
        $zone        = trim((string)($data['zone'] ?? ''));
        $prompt      = trim((string)($data['prompt'] ?? ''));
        $postDate    = trim((string)($data['post_date'] ?? date('Y-m-d')));
        $groupRules  = trim((string)($data['group_rules'] ?? ''));

        $platformLabel = ContentPlan::PLATFORM_LABELS[$platform] ?? $platform;

        // Platform-specific writing instruction
        $styleGuide = match($postType) {
            'group' => "สำหรับโพสต์ในกลุ่ม Facebook — เขียนเป็นกันเอง ไม่ฮาร์ดเซลล์ บอกราคาคร่าวๆ เน้นตอบโจทย์กลุ่ม"
                . ($groupRules ? " กติกากลุ่ม: {$groupRules}" : ''),
            'line_broadcast' => "สำหรับ LINE Broadcast — สั้น กระชับ มี emoji เหมาะกับ mobile เน้น CTA ชัดเจน",
            default          => "สำหรับ Facebook Page — เขียนแบบมืออาชีพ เล่าเรื่องที่พัก มี emoji เล็กน้อย",
        };

        $instruction = "คุณเป็นนักการตลาดดิจิทัลสำหรับที่พักท่องเที่ยวไทย\n"
            . "เขียนโพสต์ {$platformLabel} สำหรับที่พัก '{$propName}' ({$propType}) ในโซน {$zone}\n"
            . "วันที่โพสต์: {$postDate}\n"
            . "สไตล์: {$styleGuide}\n"
            . "ผู้ใช้ต้องการ: {$prompt}\n"
            . "ตอบกลับเป็น JSON เท่านั้น:\n"
            . '{"title":"หัวข้อโพสต์สั้น","body":"เนื้อหาโพสต์ 2-4 ย่อหน้า เหมาะกับ'
            . $platformLabel . '","hashtags":"#แพกาญ #กาญจนบุรี #...(5-8 แท็กที่เกี่ยวข้อง)"}';

        $resp = AIService::generate($instruction, $prompt ?: 'เขียนโพสต์แนะนำที่พักนี้', 0.75);

        if (!$resp) {
            $this->json(['ok' => false, 'error' => 'AI ปิดอยู่ — ตั้งค่า API key ใน /admin/ai ก่อน']);
            return;
        }

        if (preg_match('/\{[\s\S]*?\}/u', $resp, $m)) {
            $parsed = json_decode($m[0], true);
            if (is_array($parsed)) {
                $this->json([
                    'ok'       => true,
                    'title'    => (string)($parsed['title'] ?? ''),
                    'body'     => (string)($parsed['body'] ?? $resp),
                    'hashtags' => (string)($parsed['hashtags'] ?? ''),
                ]);
                return;
            }
        }

        $this->json(['ok' => true, 'title' => '', 'body' => $resp, 'hashtags' => '']);
    }

    // ─────────────────────────────────────────────────────────────
    // GROUP MANAGEMENT
    // ─────────────────────────────────────────────────────────────

    private static function hasMarketingTables(): bool
    {
        return Database::tableHasColumn('marketing_fb_groups', 'id')
            && Database::tableHasColumn('marketing_leads', 'id');
    }

    /** POST /owner/content-plans/groups/save — create or update a group */
    public function groupSave(): void
    {
        $ownerId = $this->ownerId();
        if (!$ownerId) { $this->json(['ok' => false, 'error' => 'ไม่พบ owner']); return; }
        if (!self::hasMarketingTables()) { $this->json(['ok' => false, 'error' => 'ระบบยังไม่ได้อัปเดตฐานข้อมูล — รอ deploy สักครู่']); return; }

        $data = $this->input();
        $id   = (int)($data['id'] ?? 0);
        $name = mb_substr(trim((string)($data['name'] ?? '')), 0, 200);
        $url  = mb_substr(trim((string)($data['url'] ?? '')), 0, 1000);
        $rules = mb_substr(trim((string)($data['rules'] ?? '')), 0, 1000);

        if (!$name || !$url) {
            $this->json(['ok' => false, 'error' => 'ระบุชื่อกลุ่มและ URL']); return;
        }

        if ($id) {
            $existing = Database::fetch("SELECT id FROM marketing_fb_groups WHERE id = :i AND owner_id = :o", ['i' => $id, 'o' => $ownerId]);
            if (!$existing) { $this->json(['ok' => false, 'error' => 'ไม่พบรายการ']); return; }
            Database::update('marketing_fb_groups', ['name' => $name, 'url' => $url, 'rules' => $rules ?: null], 'id = :i', ['i' => $id]);
        } else {
            $id = Database::insert('marketing_fb_groups', ['owner_id' => $ownerId, 'name' => $name, 'url' => $url, 'rules' => $rules ?: null]);
        }

        $row = Database::fetch("SELECT * FROM marketing_fb_groups WHERE id = :i", ['i' => $id]);
        $this->json(['ok' => true, 'group' => $row]);
    }

    /** POST /owner/content-plans/groups/{id}/delete */
    public function groupDelete(int $id): void
    {
        $ownerId = $this->ownerId();
        $row     = Database::fetch("SELECT id FROM marketing_fb_groups WHERE id = :i AND owner_id = :o", ['i' => $id, 'o' => $ownerId]);
        if (!$row) { $this->json(['ok' => false, 'error' => 'ไม่พบรายการ']); return; }
        Database::delete('marketing_fb_groups', 'id = :i', ['i' => $id]);
        $this->json(['ok' => true]);
    }

    /** POST /owner/content-plans/{id}/log-post — log that a content plan was posted to a group */
    public function logPost(int $id): void
    {
        $ownerId = $this->ownerId();
        $plan    = ContentPlan::findForOwner($id, $ownerId);
        if (!$plan) { $this->json(['ok' => false, 'error' => 'ไม่พบโพสต์']); return; }

        $data    = $this->input();
        $groupId = (int)($data['group_id'] ?? 0) ?: null;
        $note    = mb_substr(trim((string)($data['note'] ?? '')), 0, 500);

        $logId = Database::insert('content_plan_post_logs', [
            'content_plan_id' => $id,
            'group_id'        => $groupId,
            'note'            => $note ?: null,
        ]);

        // Auto-mark plan as published
        ContentPlan::update($id, ['status' => 'published', 'owner_id' => $plan['owner_id']]);

        $this->json(['ok' => true, 'log_id' => $logId]);
    }

    /** GET /owner/content-plans/{id}/post-logs — get posting history for a plan */
    public function postLogs(int $id): void
    {
        $ownerId = $this->ownerId();
        $plan    = ContentPlan::findForOwner($id, $ownerId);
        if (!$plan) { $this->json(['ok' => false, 'error' => 'ไม่พบโพสต์']); return; }

        $logs = Database::fetchAll(
            "SELECT l.*, g.name AS group_name, g.url AS group_url
             FROM content_plan_post_logs l
             LEFT JOIN marketing_fb_groups g ON g.id = l.group_id
             WHERE l.content_plan_id = :p ORDER BY l.posted_at DESC",
            ['p' => $id]
        );
        $this->json(['ok' => true, 'logs' => $logs]);
    }

    // ─────────────────────────────────────────────────────────────
    // LEAD WATCHLIST
    // ─────────────────────────────────────────────────────────────

    /** POST /owner/content-plans/leads/save — create or update a lead */
    public function leadSave(): void
    {
        $ownerId = $this->ownerId();
        if (!$ownerId) { $this->json(['ok' => false, 'error' => 'ไม่พบ owner']); return; }
        if (!self::hasMarketingTables()) { $this->json(['ok' => false, 'error' => 'ระบบยังไม่ได้อัปเดตฐานข้อมูล — รอ deploy สักครู่']); return; }

        $data = $this->input();
        $id   = (int)($data['id'] ?? 0);

        $customerText = mb_substr(trim((string)($data['customer_text'] ?? '')), 0, 5000);
        if (!$customerText) { $this->json(['ok' => false, 'error' => 'กรอกข้อความลูกค้า']); return; }

        $validStatuses = ['new', 'replied', 'got_lead', 'closed', 'lost'];
        $payload = [
            'owner_id'      => $ownerId,
            'property_id'   => !empty($data['property_id']) ? (int)$data['property_id'] : null,
            'fb_post_url'   => mb_substr(trim((string)($data['fb_post_url'] ?? '')), 0, 1000) ?: null,
            'customer_text' => $customerText,
            'found_at'      => $data['found_at'] ?? date('Y-m-d'),
            'pax'           => !empty($data['pax']) ? (int)$data['pax'] : null,
            'checkin_date'  => $data['checkin_date'] ?: null,
            'checkout_date' => $data['checkout_date'] ?: null,
            'budget'        => mb_substr(trim((string)($data['budget'] ?? '')), 0, 100) ?: null,
            'zone'          => mb_substr(trim((string)($data['zone'] ?? '')), 0, 100) ?: null,
            'status'        => in_array($data['status'] ?? '', $validStatuses) ? $data['status'] : 'new',
        ];

        if ($id) {
            $existing = Database::fetch("SELECT id FROM marketing_leads WHERE id = :i AND owner_id = :o", ['i' => $id, 'o' => $ownerId]);
            if (!$existing) { $this->json(['ok' => false, 'error' => 'ไม่พบรายการ']); return; }
            Database::update('marketing_leads', $payload, 'id = :i', ['i' => $id]);
        } else {
            $id = Database::insert('marketing_leads', $payload);
        }

        $row = Database::fetch(
            "SELECT l.*, p.name AS property_name FROM marketing_leads l
             LEFT JOIN properties p ON p.id = l.property_id
             WHERE l.id = :i",
            ['i' => $id]
        );
        $this->json(['ok' => true, 'lead' => $row]);
    }

    /** POST /owner/content-plans/leads/{id}/delete */
    public function leadDelete(int $id): void
    {
        $ownerId = $this->ownerId();
        $row     = Database::fetch("SELECT id FROM marketing_leads WHERE id = :i AND owner_id = :o", ['i' => $id, 'o' => $ownerId]);
        if (!$row) { $this->json(['ok' => false, 'error' => 'ไม่พบรายการ']); return; }
        Database::delete('marketing_leads', 'id = :i', ['i' => $id]);
        $this->json(['ok' => true]);
    }

    /** GET /owner/content-plans/leads/{id}/ai-comment — AI draft comment */
    public function leadAiComment(int $id): void
    {
        $ownerId = $this->ownerId();
        $lead    = Database::fetch(
            "SELECT l.*, p.name AS property_name, p.type AS property_type, p.zone AS property_zone
             FROM marketing_leads l
             LEFT JOIN properties p ON p.id = l.property_id
             WHERE l.id = :i AND l.owner_id = :o",
            ['i' => $id, 'o' => $ownerId]
        );
        if (!$lead) { $this->json(['ok' => false, 'error' => 'ไม่พบ lead']); return; }

        $pax      = $lead['pax'] ? "{$lead['pax']} คน" : 'ไม่ระบุจำนวน';
        $dates    = $lead['checkin_date']
            ? "วันที่ {$lead['checkin_date']}" . ($lead['checkout_date'] ? " ถึง {$lead['checkout_date']}" : '')
            : 'ไม่ระบุวันที่';
        $budget   = $lead['budget'] ?: 'ไม่ระบุงบ';
        $propName = $lead['property_name'] ?: 'ที่พักของเรา';
        $zone     = $lead['property_zone'] ?: ($lead['zone'] ?: 'กาญจนบุรี');

        $instruction = "คุณเป็นเจ้าของแพที่พักใน{$zone}\n"
            . "ลูกค้าโพสต์ในกลุ่ม Facebook ว่า:\n"
            . "\"{$lead['customer_text']}\"\n\n"
            . "ข้อมูล: {$pax}, {$dates}, งบ {$budget}\n"
            . "ที่พักของคุณ: {$propName}\n\n"
            . "เขียนคอมเมนต์ตอบสั้นๆ 2-3 ประโยค — เป็นกันเอง ไม่ spam ไม่โฆษณาเกินจริง\n"
            . "บอกที่พักและจุดเด่น 1 อย่าง จบด้วย CTA ให้ inbox/ทัก LINE หรือดูรายละเอียด\n"
            . "ตอบเป็น JSON: {\"comment\":\"ข้อความคอมเมนต์\"}";

        $resp = AIService::generate($instruction, 'ร่างคอมเมนต์ตอบลูกค้า', 0.70);

        if (!$resp) {
            $this->json(['ok' => false, 'error' => 'AI ปิดอยู่']); return;
        }

        if (preg_match('/\{[\s\S]*?\}/u', $resp, $m)) {
            $parsed = json_decode($m[0], true);
            if (!empty($parsed['comment'])) {
                Database::update('marketing_leads', ['ai_comment' => $parsed['comment']], 'id = :i', ['i' => $id]);
                $this->json(['ok' => true, 'comment' => $parsed['comment']]);
                return;
            }
        }

        // Fallback: return raw response
        Database::update('marketing_leads', ['ai_comment' => $resp], 'id = :i', ['i' => $id]);
        $this->json(['ok' => true, 'comment' => $resp]);
    }

    private static function thMonthName(int $m): string
    {
        return ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'][$m] ?? '';
    }
}
