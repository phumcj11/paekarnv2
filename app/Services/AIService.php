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

    /** Smart Search: NL query → JSON filters */
    public static function smartSearch(string $query): array
    {
        $instruction = <<<'P'
You are a search filter extractor for แพกาญ.com (Thai accommodation site).
Given a user's natural language Thai query, extract these filters as JSON:
{
  "q": "optional concrete keywords likely in listing text (e.g. วิวภูเขา, Wi-Fi, สังขละ, karaoke). Omit or null if the query only specifies type, guests, zone, budget, pet, or coupon — do NOT repeat the full sentence here.",
  "type": "raft|resort|homestay|house|pool_villa|hotel|camping|null",
  "zone": "string or null",
  "guests": integer or null,
  "budget_max": integer (per night THB) or null,
  "pet": true/false,
  "coupon": true/false
}
Rules for "q":
- NEVER include mood/atmosphere/subjective words: เงียบ, ฟิน, สงบ, โรแมนติก, ส่วนตัว, บรรยากาศดี, ชิล, ผ่อนคลาย
- When type=raft, omit ริมน้ำ/ริมแม่น้ำ/ลอยน้ำ — type=raft already implies riverside raft
- Thai budget words: สามพัน=3000, ห้าพัน=5000, หมื่น=10000 (put in budget_max, not q)
Respond ONLY with the JSON object. No explanation.
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

        return $out;
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
