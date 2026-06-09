<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Setting;
use App\Models\Zone;

/**
 * Generic OpenAI-compatible chat completion wrapper.
 * รองรับ:
 *   - OpenAI         (api.openai.com/v1)
 *   - OpenRouter     (openrouter.ai/api/v1)
 *   - Together AI    (api.together.xyz/v1)
 *   - Local (LM Studio / Ollama proxy / vLLM)
 *
 * ถ้า ai_enabled=0 หรือไม่มี api_key → ใช้โหมด KB-only fallback
 */
class AIService
{
    /** Knowledge-base lookup: คืน answer ที่ตรงกับคำถามมากที่สุด (keyword based, lightweight) */
    public static function searchKB(string $query): ?array
    {
        $q = trim($query);
        if ($q === '') return null;

        // exact / contains question
        $like = '%' . $q . '%';
        $row = Database::fetch(
            "SELECT * FROM ai_knowledge_base WHERE is_active=1 AND (question LIKE :q1 OR keywords LIKE :q2) ORDER BY hit_count DESC LIMIT 1",
            ['q1' => $like, 'q2' => $like]
        );
        if ($row) {
            Database::query("UPDATE ai_knowledge_base SET hit_count = hit_count + 1 WHERE id = :i", ['i' => $row['id']]);
            return $row;
        }

        // word-by-word fallback
        $words = preg_split('/[\s,]+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($words as $w) {
            if (mb_strlen($w) < 3) continue;
            $like = '%' . $w . '%';
            $row = Database::fetch(
                "SELECT * FROM ai_knowledge_base WHERE is_active=1 AND (question LIKE :a OR keywords LIKE :b OR answer LIKE :c) ORDER BY hit_count DESC LIMIT 1",
                ['a' => $like, 'b' => $like, 'c' => $like]
            );
            if ($row) {
                Database::query("UPDATE ai_knowledge_base SET hit_count = hit_count + 1 WHERE id = :i", ['i' => $row['id']]);
                return $row;
            }
        }
        return null;
    }

    /** Build system prompt with KB context */
    private static function systemPrompt(): string
    {
        $persona = (string)Setting::get('ai_chatbot_persona',
            'คุณคือผู้ช่วย AI ของแพกาญ.com ตอบเป็นภาษาไทย สุภาพและกระชับ');
        $kbRows = Database::fetchAll("SELECT question, answer FROM ai_knowledge_base WHERE is_active=1 ORDER BY sort_order, id LIMIT 30");
        $kb = '';
        foreach ($kbRows as $row) {
            $kb .= "- Q: {$row['question']}\n  A: {$row['answer']}\n";
        }
        return $persona . "\n\nข้อมูลอ้างอิงเกี่ยวกับ แพกาญ.com:\n" . $kb;
    }

    /** Reply chatbot — KB-first then LLM if available */
    public static function replyChat(string $userMsg, ?string $sessionId = null): string
    {
        // KB instant answer (fast path)
        $kb = self::searchKB($userMsg);
        $kbHit = $kb ? "💡 จาก FAQ: {$kb['answer']}\n\n" : '';

        if (!Setting::get('ai_enabled', '0') || !Setting::get('ai_api_key')) {
            // KB-only fallback
            if ($kb) return $kb['answer'];
            return "ขอบคุณสำหรับคำถามค่ะ 🙏 ขณะนี้ระบบ AI ยังไม่ได้เปิดใช้งาน — แอดมินจะติดต่อกลับโดยเร็วที่สุดค่ะ\nหากเร่งด่วน โทร " . Setting::get('site_phone', '034-000-000');
        }

        // LLM call
        $messages = [
            ['role' => 'system', 'content' => self::systemPrompt()],
        ];
        // เอา history สั้นๆจาก session
        if ($sessionId) {
            $hist = Database::fetchAll("SELECT role, content FROM ai_chats WHERE session_id = :s ORDER BY id DESC LIMIT 6", ['s' => $sessionId]);
            $hist = array_reverse($hist);
            foreach ($hist as $h) $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMsg];

        $resp = self::chatCompletion($messages, 0.6);
        if (!$resp) return $kb ? $kb['answer'] : 'ขออภัย ระบบขัดข้องชั่วคราว กรุณาติดต่อ ' . Setting::get('site_phone', '');
        return $resp;
    }

    /** Plain text generation for tasks (description generator, translation etc.) */
    public static function generate(string $instruction, string $input = '', float $temperature = 0.7, int $maxTokens = 800): ?string
    {
        if (!Setting::get('ai_enabled', '0') || !Setting::get('ai_api_key')) return null;
        $messages = [
            ['role' => 'system', 'content' => $instruction],
            ['role' => 'user',   'content' => $input],
        ];
        return self::chatCompletion($messages, $temperature, $maxTokens);
    }

    /** Smart Search: NL query → JSON filters (extended) */
    public static function smartSearch(string $query): array
    {
        $instruction = <<<'P'
You are a search filter extractor for แพกาญ.com (Thai accommodation site in Kanchanaburi province).
Extract filters from a natural language Thai query as JSON:
{
  "q": "concrete keywords likely in listing text (e.g. วิวภูเขา, Wi-Fi, คาราโอเกะ, สังขละ). Omit if query only specifies type/guests/zone/budget/pet/coupon.",
  "type": "raft|resort|homestay|house|pool_villa|hotel|camping|null",
  "zone": "Thai zone/district name or null",
  "guests": integer or null,
  "budget_max": integer (per night THB) or null,
  "pet": true/false,
  "coupon": true/false,
  "group_type": "couple|family|friends|group|null",
  "must_have": ["concrete feature list e.g. สระว่ายน้ำ, คาราโอเกะ, บาร์บีคิว, ริมน้ำ, ครัว, WiFi — NO mood words"],
  "intent": "find|recommend"
}
Rules:
- NEVER put mood/atmosphere words in q or must_have: เงียบ, ฟิน, สงบ, โรแมนติก, ส่วนตัว, บรรยากาศดี, ชิล, ผ่อนคลาย
- When type=raft, omit ริมน้ำ/ริมแม่น้ำ/ลอยน้ำ from must_have — raft already implies riverside
- Budget: สามพัน=3000, ห้าพัน=5000, หมื่น=10000 → budget_max
- group_type: couple=คู่/ฮันนีมูน, family=ครอบครัว/พาเด็ก, friends=เพื่อน, group=หมู่คณะ/บริษัท
- intent: "recommend" if user asks to suggest/help choose; "find" if filtering by criteria
- must_have: only concrete features that could appear in a listing description
Respond ONLY with the JSON object.
P;
        if (!Setting::get('ai_enabled', '0') || !Setting::get('ai_api_key')) {
            return self::heuristicSearch($query);
        }
        $resp = self::chatCompletion([
            ['role' => 'system', 'content' => $instruction],
            ['role' => 'user',   'content' => $query],
        ], 0.0, 300);
        if (!$resp) return self::heuristicSearch($query);
        // try parse json
        if (preg_match('/\{[\s\S]*\}/', $resp, $m)) {
            $data = json_decode($m[0], true);
            if (is_array($data)) {
                return self::normalizeSmartSearchFilters(self::cleanFilters($data));
            }
        }
        return self::heuristicSearch($query);
    }

    /** Lightweight regex-based fallback when no AI */
    private static function heuristicSearch(string $q): array
    {
        $f = [];
        if (preg_match('/(\d+)\s*(คน|ท่าน|people|persons?)/u', $q, $m)) $f['guests'] = (int)$m[1];
        if (preg_match('/(\d+,?\d{3,})/u', $q, $m)) $f['budget_max'] = (int)str_replace(',', '', $m[1]);
        if (empty($f['budget_max'])) {
            $f['budget_max'] = self::parseThaiBudgetMax($q);
        }
        if (preg_match('/(หมา|แมว|สัตว์|pet)/u', $q)) $f['pet'] = true;
        if (preg_match('/(คูปอง|coupon|ส่วนลด)/u', $q)) $f['coupon'] = true;
        $types = ['พูลวิลล่า'=>'pool_villa','พูลวิลลา'=>'pool_villa','แพ'=>'raft','รีสอร์ท'=>'resort','โฮมสเตย์'=>'homestay','บ้าน'=>'house','โรงแรม'=>'hotel','แคมป์'=>'camping'];
        foreach ($types as $kw => $en) if (str_contains($q, $kw)) { $f['type'] = $en; break; }
        $zoneCandidates = Zone::namesForSelectMerged(null);
        usort($zoneCandidates, static fn ($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));
        foreach ($zoneCandidates as $z) {
            if ($z !== '' && str_contains($q, $z)) {
                $f['zone'] = $z;
                break;
            }
        }
        // Detect group_type heuristically
        if (preg_match('/(คู่|ฮันนีมูน|โรแมนติก)/u', $q))          $f['group_type'] = 'couple';
        elseif (preg_match('/(ครอบครัว|พาเด็ก|พ่อแม่)/u', $q))     $f['group_type'] = 'family';
        elseif (preg_match('/(หมู่คณะ|บริษัท|กรุ๊ป|ทีม)/u', $q))   $f['group_type'] = 'group';
        elseif (preg_match('/(เพื่อน|แก๊ง)/u', $q))                 $f['group_type'] = 'friends';
        $f['q'] = $q;

        return self::normalizeSmartSearchFilters($f);
    }

    /**
     * Drop or shorten `q` when it duplicates structured filters (prevents LIKE '%แพ 4 คน%' wiping results).
     *
     * @param array<string,mixed> $f
     * @return array<string,mixed>
     */
    private static function normalizeSmartSearchFilters(array $f): array
    {
        $raw = isset($f['q']) ? trim((string)$f['q']) : '';
        if ($raw === '') {
            unset($f['q']);

            return $f;
        }

        $stripped = $raw;
        if (!empty($f['guests'])) {
            $stripped = preg_replace('/\d+\s*(คน|ท่าน|people|persons?)/iu', ' ', $stripped) ?? $stripped;
        }
        if (!empty($f['budget_max']) || !empty($f['budget_min'])) {
            $stripped = preg_replace('/\d[\d,]*\s*(บาท|บ\.|฿|baht|thb)?/iu', ' ', $stripped) ?? $stripped;
            $stripped = preg_replace('/(?:งบ\s*)?ไม่เกิน/iu', ' ', $stripped) ?? $stripped;
            foreach (self::thaiBudgetStripKeywords() as $word) {
                $stripped = str_ireplace($word, ' ', $stripped);
            }
        }
        if (!empty($f['zone']) && is_string($f['zone'])) {
            $stripped = str_ireplace($f['zone'], ' ', $stripped);
        }
        if (!empty($f['type']) && is_string($f['type'])) {
            $kw = self::smartSearchTypeStripKeywords($f['type']);
            usort($kw, static fn ($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));
            foreach ($kw as $word) {
                if ($word !== '') {
                    $stripped = str_ireplace($word, ' ', $stripped);
                }
            }
        }
        if (!empty($f['pet'])) {
            foreach (['สัตว์เลี้ยง', 'สัตว์', 'หมา', 'แมว', 'pet'] as $word) {
                $stripped = str_ireplace($word, ' ', $stripped);
            }
        }
        if (!empty($f['coupon'])) {
            foreach (['คูปอง', 'coupon', 'ส่วนลด'] as $word) {
                $stripped = str_ireplace($word, ' ', $stripped);
            }
        }

        foreach (self::smartSearchSubjectiveKeywords(!empty($f['type']) ? (string)$f['type'] : null) as $word) {
            if ($word !== '') {
                $stripped = str_ireplace($word, ' ', $stripped);
            }
        }

        $stripped = trim(preg_replace('/\s+/u', ' ', $stripped) ?? '');
        if ($stripped === '') {
            unset($f['q']);
        } else {
            $f['q'] = $stripped;
        }

        // Also strip must_have keywords from q
        if (!empty($f['must_have']) && is_array($f['must_have'])) {
            foreach ($f['must_have'] as $mw) {
                if ($mw !== '') $f['q'] = str_ireplace($mw, ' ', $f['q'] ?? '');
            }
            $f['q'] = trim(preg_replace('/\s+/u', ' ', $f['q'] ?? '') ?? '');
            if ($f['q'] === '') unset($f['q']);
        }

        return $f;
    }

    /** @return array<int,string> */
    private static function smartSearchTypeStripKeywords(string $type): array
    {
        $map = [
            'pool_villa' => ['พูลวิลล่า', 'พูลวิลลา', 'pool villa', 'villa'],
            'raft'       => ['แพพัก', 'แพลาก', 'แพริมน้ำ', 'แพ'],
            'resort'     => ['รีสอร์ท'],
            'homestay'   => ['โฮมสเตย์'],
            'house'      => ['บ้านพัก', 'บ้าน'],
            'hotel'      => ['โรงแรม'],
            'camping'    => ['แคมป์ปิ้ง', 'แคมป์'],
        ];

        return $map[$type] ?? [];
    }

    /** Mood/atmosphere/filler words — strip from q (longest first). */
    private static function smartSearchSubjectiveKeywords(?string $type = null): array
    {
        $words = [
            'อยากได้ที่พัก', 'บรรยากาศดี', 'งบไม่เกิน', 'ไม่เกิน', 'ที่พัก',
            'เงียบๆ', 'สงบๆ', 'โรแมนติก', 'ส่วนตัว', 'ผ่อนคลาย', 'อยากได้',
            'peaceful', 'romantic', 'private', 'cozy', 'quiet', 'chill',
            'เงียบ', 'สงบ', 'ฟิน', 'ชิล', 'สบาย',
            'ค้นหา', 'หา', 'ขอ', 'เอา',
        ];
        if ($type === 'raft') {
            $words = array_merge(['ริมแม่น้ำแคว', 'ริมแม่น้ำ', 'ริมน้ำ', 'ลอยน้ำ', 'ที่ลอยน้ำ'], $words);
        }
        usort($words, static fn ($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));

        return $words;
    }

    /** Parse Thai budget phrases e.g. สามพัน, ไม่เกินห้าพัน → THB int. */
    private static function parseThaiBudgetMax(string $q): ?int
    {
        $digits = [
            'หนึ่ง' => 1, 'สอง' => 2, 'สาม' => 3, 'สี่' => 4, 'ห้า' => 5,
            'หก' => 6, 'เจ็ด' => 7, 'แปด' => 8, 'เก้า' => 9, 'สิบ' => 10,
        ];
        if (preg_match('/(?:งบ\s*)?ไม่เกิน\s*(\d[\d,]*)\s*(?:บาท|฿|thb)?/iu', $q, $m)) {
            return (int)str_replace(',', '', $m[1]);
        }
        if (preg_match('/(\d[\d,]*)\s*(?:บาท|฿|thb)/iu', $q, $m)) {
            return (int)str_replace(',', '', $m[1]);
        }
        if (preg_match('/(?:งบ\s*)?ไม่เกิน\s*((?:' . implode('|', array_keys($digits)) . ')?)\s*(พัน|หมื่น|แสน|ล้าน)/u', $q, $m)) {
            return self::thaiAmountToInt($m[1], $m[2], $digits);
        }
        if (preg_match('/((?:' . implode('|', array_keys($digits)) . ')?)\s*(พัน|หมื่น|แสน|ล้าน)/u', $q, $m)) {
            return self::thaiAmountToInt($m[1], $m[2], $digits);
        }

        return null;
    }

    /** @param array<string,int> $digits */
    private static function thaiAmountToInt(string $thaiNum, string $unit, array $digits): int
    {
        $n = $thaiNum === '' ? 1 : ($digits[$thaiNum] ?? 1);
        $mult = match ($unit) {
            'พัน'  => 1_000,
            'หมื่น' => 10_000,
            'แสน'  => 100_000,
            'ล้าน' => 1_000_000,
            default => 1,
        };

        return $n * $mult;
    }

    /** @return array<int,string> */
    private static function thaiBudgetStripKeywords(): array
    {
        return [
            'หนึ่งล้าน', 'สองล้าน', 'สามล้าน', 'ห้าล้าน',
            'หนึ่งแสน', 'สองแสน', 'สามแสน', 'ห้าแสน',
            'หนึ่งหมื่น', 'สองหมื่น', 'สามหมื่น', 'ห้าหมื่น',
            'หนึ่งพัน', 'สองพัน', 'สามพัน', 'สี่พัน', 'ห้าพัน', 'หกพัน', 'เจ็ดพัน', 'แปดพัน', 'เก้าพัน', 'สิบพัน',
            'หมื่น', 'แสน', 'ล้าน', 'พัน', 'งบ', 'บาท',
        ];
    }

    private static function cleanFilters(array $f): array
    {
        $out = [];
        foreach (['q','zone','type'] as $k)            if (!empty($f[$k]) && is_string($f[$k])) $out[$k] = $f[$k];
        foreach (['guests','budget_max','budget_min'] as $k) if (!empty($f[$k]) && is_numeric($f[$k])) $out[$k] = (int)$f[$k];
        if (!empty($f['pet']))    $out['pet'] = 1;
        if (!empty($f['coupon'])) $out['coupon'] = 1;
        // Pass through extended fields
        foreach (['group_type','intent'] as $k) if (!empty($f[$k]) && is_string($f[$k])) $out[$k] = $f[$k];
        if (!empty($f['must_have']) && is_array($f['must_have'])) {
            $out['must_have'] = array_values(array_filter(array_map('strval', $f['must_have'])));
        }

        return $out;
    }

    /**
     * Recommend top properties from candidates based on user intent.
     *
     * @param array<int,array<string,mixed>> $candidates Property rows (max 20 used)
     * @return array<int,array{id:int,reason:string}>
     */
    public static function recommend(string $query, array $candidates): array
    {
        if (empty($candidates)) return [];

        $sample = array_slice($candidates, 0, 20);
        $fallback = array_map(fn($p) => [
            'id'     => (int)$p['id'],
            'reason' => 'ที่พักยอดนิยมในกลุ่มนี้',
        ], array_slice($sample, 0, 5));

        if (!Setting::get('ai_enabled', '0') || !Setting::get('ai_api_key')) {
            return $fallback;
        }

        // Build compact property list for LLM (keep tokens low)
        $lines = [];
        foreach ($sample as $p) {
            $pid    = (int)$p['id'];
            $name   = mb_substr((string)($p['name'] ?? ''), 0, 40);
            $type   = (string)($p['type'] ?? '');
            $zone   = (string)($p['zone'] ?? '');
            $price  = (int)($p['listing_unit_price'] ?? $p['min_price'] ?? 0);
            $coupon = (int)($p['coupon_enabled'] ?? 0) ? 'มีคูปอง' : '';
            $rating = number_format((float)($p['rating_avg'] ?? 0), 1);
            $capMax = (int)($p['_unit_cap_max'] ?? 0);

            $parts = ["ID:{$pid}", $name, $type, $zone, "฿{$price}", "⭐{$rating}"];
            if ($capMax > 0) $parts[] = "รองรับ{$capMax}คน";
            if ($coupon)     $parts[] = $coupon;

            // Include brief owner_intake highlights
            $intake = $p['owner_intake'] ?? '';
            if ($intake !== '' && $intake !== null) {
                $data = is_array($intake) ? $intake : (json_decode((string)$intake, true) ?: []);
                foreach (['group_packages','activities_pricing','whole_house_extra','day_trip_no_overnight'] as $k) {
                    if (!empty($data[$k])) {
                        $parts[] = mb_substr((string)$data[$k], 0, 40);
                        break;
                    }
                }
            }
            $lines[] = implode('|', $parts);
        }

        $propBlock = implode("\n", $lines);
        $sysMsg = "คุณเป็นผู้ช่วยแนะนำที่พักในกาญจนบุรี\n"
            . "จากคำค้นหาและรายการที่พัก ให้เลือก 3-5 รายการที่เหมาะสมที่สุดและอธิบายสั้นๆ เป็นภาษาไทย\n"
            . "ตอบเป็น JSON array เท่านั้น: [{\"id\":N,\"reason\":\"เหตุผลภาษาไทย 1 ประโยค\"},...]\n"
            . "ใช้เฉพาะ ID จากรายการ เรียงจากแนะนำที่สุดไปน้อยสุด ห้ามเพิ่มข้อความนอก JSON";

        $userMsg = "คำค้น: {$query}\n\nรายการที่พัก (ID|ชื่อ|ประเภท|โซน|ราคา/คืน|คะแนน|ข้อมูลเพิ่ม):\n{$propBlock}";

        $resp = self::chatCompletion([
            ['role' => 'system', 'content' => $sysMsg],
            ['role' => 'user',   'content' => $userMsg],
        ], 0.2, 700);

        if (!$resp) return $fallback;

        if (preg_match('/\[[\s\S]*?\]/u', $resp, $m)) {
            $data = json_decode($m[0], true);
            if (is_array($data)) {
                $validIds = array_map(fn($p) => (int)$p['id'], $sample);
                $out = [];
                foreach ($data as $item) {
                    if (!isset($item['id'], $item['reason'])) continue;
                    if (!in_array((int)$item['id'], $validIds, true)) continue;
                    $out[] = ['id' => (int)$item['id'], 'reason' => (string)$item['reason']];
                    if (count($out) >= 5) break;
                }
                return $out ?: $fallback;
            }
        }

        return $fallback;
    }

    /** Low-level chat completion API call */
    private static function chatCompletion(array $messages, float $temperature = 0.7, int $maxTokens = 800): ?string
    {
        $base  = rtrim((string)Setting::get('ai_api_url', 'https://api.openai.com/v1'), '/');
        $key   = (string)Setting::get('ai_api_key', '');
        $model = (string)Setting::get('ai_model', 'gpt-4o-mini');
        if (!$key) return null;

        $body = json_encode([
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($base . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $key,
            ],
            CURLOPT_POSTFIELDS     => $body,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($code !== 200 || !$resp) {
            error_log("[AIService] HTTP $code: $err — " . substr((string)$resp, 0, 200));
            return null;
        }
        $json = json_decode($resp, true);
        return trim((string)($json['choices'][0]['message']['content'] ?? '')) ?: null;
    }
}
