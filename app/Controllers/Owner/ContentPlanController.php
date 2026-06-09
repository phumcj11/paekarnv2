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
            // Admin ใช้ owner_id = 0 (ดูทั้งหมด) — แต่ต้องมี owner_id จริงถึงจะสร้างได้
            return 0;
        }
        return (int)$id;
    }

    /** GET /owner/content-plans?month=Y-m */
    public function index(): void
    {
        $ownerId = $this->ownerId();

        $monthParam = $_GET['month'] ?? date('Y-m');
        [$year, $month] = array_map('intval', explode('-', $monthParam . '-0'));
        if ($year < 2020 || $year > 2050 || $month < 1 || $month > 12) {
            $year  = (int)date('Y');
            $month = (int)date('n');
        }

        $plans = $ownerId ? ContentPlan::forMonth($ownerId, $year, $month) : [];
        $counts = $ownerId ? ContentPlan::countThisMonth($ownerId) : array_fill_keys(ContentPlan::STATUSES, 0);

        // Owner's properties for dropdown
        $properties = $ownerId
            ? Database::fetchAll("SELECT id, name FROM properties WHERE owner_id = :o ORDER BY name", ['o' => $ownerId])
            : [];

        // Calendar grid: date → plans[]
        $calMap = [];
        foreach ($plans as $p) {
            $calMap[$p['post_date']][] = $p;
        }

        $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
        $firstDow    = (int)date('N', mktime(0, 0, 0, $month, 1, $year)); // 1=Mon … 7=Sun

        $prevMonth = $month === 1 ? ['year' => $year - 1, 'month' => 12] : ['year' => $year, 'month' => $month - 1];
        $nextMonth = $month === 12 ? ['year' => $year + 1, 'month' => 1]  : ['year' => $year, 'month' => $month + 1];

        View::render('owner/content_plans/index', [
            'page_title'    => 'ปฏิทินโพสต์',
            'year'          => $year,
            'month'         => $month,
            'monthLabel'    => self::thMonthName($month) . ' ' . ($year + 543),
            'daysInMonth'   => $daysInMonth,
            'firstDow'      => $firstDow,
            'calMap'        => $calMap,
            'plans'         => $plans,
            'counts'        => $counts,
            'properties'    => $properties,
            'prevMonth'     => $prevMonth,
            'nextMonth'     => $nextMonth,
            'today'         => date('Y-m-d'),
        ], 'layouts/owner');
    }

    /** POST /owner/content-plans */
    public function store(): void
    {
        $ownerId = $this->ownerId();
        if (!$ownerId) { $this->json(['ok' => false, 'error' => 'ไม่พบ owner']); return; }

        $data = $this->input();
        $postDate = trim((string)($data['post_date'] ?? ''));
        $body     = trim((string)($data['body'] ?? ''));

        if (!$postDate || !$body) {
            $this->json(['ok' => false, 'error' => 'กรุณากรอกวันที่และเนื้อหา']);
            return;
        }

        $id = ContentPlan::create(array_merge($data, ['owner_id' => $ownerId]));
        $row = ContentPlan::find($id);

        $this->json(['ok' => true, 'id' => $id, 'plan' => $row]);
    }

    /** POST /owner/content-plans/{id}/update */
    public function update(int $id): void
    {
        $ownerId = $this->ownerId();
        $plan = ContentPlan::findForOwner($id, $ownerId);
        if (!$plan && !Auth::isAdmin()) { $this->json(['ok' => false, 'error' => 'ไม่พบรายการ']); return; }

        $data = $this->input();
        ContentPlan::update($id, array_merge($data, ['owner_id' => $plan['owner_id'] ?? $ownerId]));
        $this->json(['ok' => true, 'plan' => ContentPlan::find($id)]);
    }

    /** POST /owner/content-plans/{id}/delete */
    public function destroy(int $id): void
    {
        $ownerId = $this->ownerId();
        $plan = ContentPlan::findForOwner($id, $ownerId);
        if (!$plan && !Auth::isAdmin()) { $this->json(['ok' => false, 'error' => 'ไม่พบรายการ']); return; }

        ContentPlan::delete($id);
        $this->json(['ok' => true]);
    }

    /** POST /owner/content-plans/ai-generate */
    public function aiGenerate(): void
    {
        $ownerId = $this->ownerId();
        if (!$ownerId) { $this->json(['ok' => false, 'error' => 'ไม่พบ owner']); return; }

        $data     = $this->input();
        $platform = $data['platform'] ?? 'facebook';
        $propName = trim((string)($data['property_name'] ?? ''));
        $propType = trim((string)($data['property_type'] ?? ''));
        $zone     = trim((string)($data['zone'] ?? ''));
        $prompt   = trim((string)($data['prompt'] ?? ''));
        $postDate = trim((string)($data['post_date'] ?? date('Y-m-d')));

        $platformLabel = ContentPlan::PLATFORM_LABELS[$platform] ?? $platform;

        $instruction = "คุณเป็นนักการตลาดดิจิทัลสำหรับที่พักท่องเที่ยวไทย\n"
            . "เขียนโพสต์ {$platformLabel} สำหรับที่พัก '{$propName}' ({$propType}) ในโซน {$zone}\n"
            . "วันที่โพสต์: {$postDate}\n"
            . "ผู้ใช้ต้องการ: {$prompt}\n"
            . "รูปแบบตอบกลับ (JSON เท่านั้น):\n"
            . '{"title":"หัวข้อโพสต์สั้น","body":"เนื้อหาโพสต์ 2-4 ย่อหน้า มี emoji เล็กน้อย เหมาะกับ'
            . $platformLabel . '","hashtags":"#แพกาญ #กาญจนบุรี #...(5-8 แท็กที่เกี่ยวข้อง)"}';

        $resp = AIService::generate($instruction, $prompt ?: 'เขียนโพสต์แนะนำที่พักนี้', 0.75);

        if (!$resp) {
            $this->json(['ok' => false, 'error' => 'AI ปิดอยู่ — ตั้งค่า API key ใน /admin/ai ก่อน']);
            return;
        }

        // Try to parse JSON from response
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

    private static function thMonthName(int $m): string
    {
        return ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'][$m] ?? '';
    }
}
