<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\Property;
use App\Services\AIService;

class AIController extends Controller
{
    /** GET /ai-search — mobile-first AI raft search landing page */
    public function landing(): void
    {
        $featured = Property::featuredByType('raft', 8, true);
        if ($featured === []) {
            $featured = Property::newestByType('raft', 8);
            $featured = Property::expandListingRowsForDisplay($featured);
        }
        $featured = Property::attachGalleryThumbnails($featured);
        $featured = Property::attachUnitStats($featured);
        $heroImage = asset('ai-raft-search-mobile-hero.webp');

        $this->view('ai_search/landing', [
            'page'                => 'ai-search',
            'featuredRafts'       => $featured,
            'heroImage'           => $heroImage,
            'preload_lcp_image'   => $heroImage,
            'meta_title'          => 'AI ช่วยค้นหาแพกาญจนบุรีที่ใช่สำหรับคุณ — แพกาญ.com',
            'meta_description'    => 'บอกจำนวนคน งบ และสิ่งที่ต้องการ ให้ AI ช่วยค้นหาและแนะนำแพกาญจนบุรีที่เหมาะกับคุณภายในไม่กี่วินาที',
            'meta_canonical'      => url('/ai-search'),
            'hide_public_footer'  => true,
            'hide_mobile_tab_bar' => true,
            'hide_floating_actions' => true,
        ]);
    }

    /** POST /ai/chat — chatbot widget */
    public function chat(): void
    {
        $msg = trim((string)($this->input()['message'] ?? ''));
        if ($msg === '') { $this->json(['ok' => false, 'error' => 'empty']); return; }

        // 1 session ต่อ user/visitor
        $sessionId = Session::get('ai_chat_session');
        if (!$sessionId) {
            $sessionId = 'web:' . bin2hex(random_bytes(8));
            Session::set('ai_chat_session', $sessionId);
        }

        Database::insert('ai_chats', [
            'session_id' => $sessionId,
            'user_id'    => Auth::id(),
            'role'       => 'user',
            'content'    => $msg,
            'meta'       => json_encode(['source' => 'web']),
        ]);

        $reply = AIService::replyChat($msg, $sessionId);

        Database::insert('ai_chats', [
            'session_id' => $sessionId,
            'user_id'    => Auth::id(),
            'role'       => 'assistant',
            'content'    => $reply,
            'meta'       => json_encode(['source' => 'web']),
        ]);

        $this->json(['ok' => true, 'reply' => $reply, 'session' => $sessionId]);
    }

    /** POST /ai/smart-search — natural language → JSON filters + top_picks */
    public function smartSearch(): void
    {
        $q = trim((string)($this->input()['query'] ?? ''));
        if ($q === '') { $this->json(['ok' => false, 'error' => 'empty']); return; }
        if (mb_strlen($q, 'UTF-8') > 800) {
            $this->json(['ok' => false, 'error' => 'query_too_long'], 422);
            return;
        }

        $now = time();
        $hits = Session::get('ai_smart_search_hits', []);
        $hits = is_array($hits)
            ? array_values(array_filter($hits, static fn ($ts): bool => is_int($ts) && $ts > $now - 60))
            : [];
        if (count($hits) >= 10) {
            $this->json([
                'ok'    => false,
                'error' => 'ค้นหาบ่อยเกินไป กรุณารอสักครู่แล้วลองใหม่',
            ], 429);
            return;
        }
        $hits[] = $now;
        Session::set('ai_smart_search_hits', $hits);

        $filters = AIService::smartSearch($q);
        if (($this->input()['scope'] ?? '') === 'raft') {
            $filters['type'] = 'raft';
        }
        // Build redirect: strip non-PropertyController fields, append original query for display notice
        $redirectFilters = $filters;
        unset($redirectFilters['group_type'], $redirectFilters['must_have'], $redirectFilters['intent']);
        $redirectUrl = url('/properties?') . http_build_query($redirectFilters) . '&_aiq=' . rawurlencode($q);

        // Build search params: relax budget by 40% to widen candidate pool for AI ranking
        $searchFilters = $filters;
        unset($searchFilters['group_type'], $searchFilters['must_have'], $searchFilters['intent']);
        if (!empty($searchFilters['budget_max'])) {
            $searchFilters['budget_max'] = (int)($searchFilters['budget_max'] * 1.4);
        }

        $result     = Property::search($searchFilters, 1, 30);
        $candidates = $result['rows'] ?? [];

        $topPicks = [];
        $summary  = '';

        if (!empty($candidates)) {
            $picks  = AIService::recommend($q, $candidates);
            $propMap = [];
            foreach ($candidates as $p) {
                $pid = (int)$p['id'];
                if (!isset($propMap[$pid])) $propMap[$pid] = $p;
            }

            foreach ($picks as $pick) {
                $p = $propMap[$pick['id']] ?? null;
                if (!$p) continue;
                $coverPath = (string)($p['listing_unit_cover'] ?? $p['cover_image'] ?? '');
                $topPicks[] = [
                    'id'             => $pick['id'],
                    'name'           => (string)($p['name'] ?? ''),
                    'type'           => (string)($p['type'] ?? ''),
                    'zone'           => (string)($p['zone'] ?? ''),
                    'cover'          => upload_img($coverPath, 'thumb'),
                    'url'            => url('/property/' . ($p['slug'] ?? '')),
                    'min_price'      => (int)($p['listing_unit_price'] ?? $p['min_price'] ?? 0),
                    'coupon_enabled' => (bool)($p['coupon_enabled'] ?? false),
                    'rating_avg'     => round((float)($p['rating_avg'] ?? 0), 1),
                    'reason'         => $pick['reason'],
                ];
            }

            // Build human-readable summary
            $parts = [];
            if (!empty($filters['type'])) {
                $tl = ['raft'=>'แพพัก','resort'=>'รีสอร์ท','homestay'=>'โฮมสเตย์','house'=>'บ้านพัก','pool_villa'=>'พูลวิลล่า','hotel'=>'โรงแรม','camping'=>'แคมป์ปิ้ง'];
                $parts[] = $tl[$filters['type']] ?? $filters['type'];
            }
            if (!empty($filters['zone']))       $parts[] = 'โซน ' . $filters['zone'];
            if (!empty($filters['guests']))     $parts[] = $filters['guests'] . ' คน';
            if (!empty($filters['budget_max'])) $parts[] = 'งบไม่เกิน ฿' . number_format((int)$filters['budget_max']);
            if (!empty($filters['coupon']))     $parts[] = 'ใช้คูปองได้';
            if (!empty($filters['pet']))        $parts[] = 'พาสัตว์เลี้ยงได้';

            $total   = (int)($result['total'] ?? count($candidates));
            $summary = 'พบ ' . $total . ' รายการ' . ($parts ? ' · ' . implode(' · ', $parts) : '');
        } else {
            // Loosen: remove budget and try again
            $looseFilters = $searchFilters;
            unset($looseFilters['budget_max']);
            $looseResult = Property::search($looseFilters, 1, 20);
            $loosePicks  = $looseResult['rows'] ?? [];
            if (!empty($loosePicks)) {
                $picks = AIService::recommend($q . ' (ไม่จำกัดงบ)', $loosePicks);
                $propMap = [];
                foreach ($loosePicks as $p) { $pid = (int)$p['id']; if (!isset($propMap[$pid])) $propMap[$pid] = $p; }
                foreach ($picks as $pick) {
                    $p = $propMap[$pick['id']] ?? null;
                    if (!$p) continue;
                    $coverPath = (string)($p['listing_unit_cover'] ?? $p['cover_image'] ?? '');
                    $topPicks[] = [
                        'id'             => $pick['id'],
                        'name'           => (string)($p['name'] ?? ''),
                        'type'           => (string)($p['type'] ?? ''),
                        'zone'           => (string)($p['zone'] ?? ''),
                        'cover'          => upload_img($coverPath, 'thumb'),
                        'url'            => url('/property/' . ($p['slug'] ?? '')),
                        'min_price'      => (int)($p['listing_unit_price'] ?? $p['min_price'] ?? 0),
                        'coupon_enabled' => (bool)($p['coupon_enabled'] ?? false),
                        'rating_avg'     => round((float)($p['rating_avg'] ?? 0), 1),
                        'reason'         => $pick['reason'],
                    ];
                }
                $summary = 'ไม่พบในงบที่กำหนด — ลองดูตัวเลือกใกล้เคียง';
                $redirectUrl = url('/properties?') . http_build_query($looseFilters);
            } else {
                $summary = 'ไม่พบที่พักตามเงื่อนไข ลองปรับคำค้นหา';
                $workingRedirect = self::resolveSmartSearchRedirectFilters($searchFilters);
                if ($workingRedirect !== null) {
                    $redirectUrl = url('/properties?') . http_build_query($workingRedirect) . '&_aiq=' . rawurlencode($q);
                }
            }
        }

        $this->json([
            'ok'        => true,
            'filters'   => $filters,
            'redirect'  => $redirectUrl,
            'summary'   => $summary,
            'top_picks' => $topPicks,
        ]);
    }

    /** POST /ai/generate — owner: generate property description */
    public function generate(): void
    {
        $kind = $this->input()['kind'] ?? 'description';
        $name = trim((string)($this->input()['name'] ?? ''));
        $type = trim((string)($this->input()['type'] ?? ''));
        $zone = trim((string)($this->input()['zone'] ?? ''));
        $features = trim((string)($this->input()['features'] ?? ''));

        $instruction = match ($kind) {
            'description' => "คุณเป็นนักเขียนคำโฆษณาที่พักท่องเที่ยว ภาษาไทย เน้น sale แต่ไม่เกินจริง ใช้ภาษากระชับน่าอ่าน ใช้ emoji เล็กน้อย ความยาว 3-5 ย่อหน้า",
            'rules'       => "คุณเป็นเจ้าของแพ ช่วยเขียน 'กฎการเข้าพัก' ที่กระชับ ชัดเจน เป็นรายการ bullet points 5-10 ข้อ ภาษาไทย",
            'meta'        => "ช่วยเขียน meta description SEO 1 ประโยค ภาษาไทย ความยาวไม่เกิน 160 ตัวอักษร เน้น key benefit",
            default       => "ช่วยปรับปรุงข้อความให้น่าอ่านยิ่งขึ้น ภาษาไทย",
        };

        $input = "ที่พัก: $name\nประเภท: $type\nโซน: $zone\nจุดเด่น: $features";
        $resp  = AIService::generate($instruction, $input);
        if (!$resp) { $this->json(['ok' => false, 'error' => 'AI ปิดอยู่ หรือยังไม่ได้ตั้งค่า API key (ดูที่ /admin/ai)']); return; }
        $this->json(['ok' => true, 'text' => $resp]);
    }

    /** POST /ai/translate — TH ↔ EN */
    public function translate(): void
    {
        $text = trim((string)($this->input()['text'] ?? ''));
        $to   = ($this->input()['to'] ?? 'en') === 'th' ? 'th' : 'en';
        if ($text === '') { $this->json(['ok' => false, 'error' => 'empty']); return; }
        $instruction = $to === 'en'
            ? 'Translate the following Thai text to natural professional English for a hotel listing. Reply ONLY with the translation.'
            : 'แปลข้อความต่อไปนี้เป็นภาษาไทย สำหรับหน้ารายการที่พัก ตอบเฉพาะคำแปลเท่านั้น';
        $resp = AIService::generate($instruction, $text, 0.3);
        if (!$resp) { $this->json(['ok' => false, 'error' => 'AI ปิดอยู่']); return; }
        $this->json(['ok' => true, 'text' => $resp]);
    }

    /**
     * When strict AI filters return nothing, pick looser filters that still yield listings for redirect.
     *
     * @param array<string,mixed> $filters
     * @return array<string,mixed>|null
     */
    private static function resolveSmartSearchRedirectFilters(array $filters): ?array
    {
        $base = $filters;
        unset($base['group_type'], $base['must_have'], $base['intent'], $base['budget_max'], $base['budget_min']);

        $attempts = [
            $base,
            array_diff_key($base, ['q' => true]),
            array_diff_key($base, ['q' => true, 'guests' => true]),
            array_diff_key($base, ['guests' => true]),
            ['type' => $base['type'] ?? ''],
        ];

        foreach ($attempts as $attempt) {
            $attempt = array_filter(
                $attempt,
                static fn ($v) => $v !== '' && $v !== null && $v !== [] && $v !== false
            );
            if ($attempt === []) {
                continue;
            }
            if ((int)(Property::search($attempt, 1, 1)['total'] ?? 0) > 0) {
                return $attempt;
            }
        }

        return null;
    }
}
