<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\AIService;

class AIController extends Controller
{
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

    /** POST /ai/smart-search — natural language → JSON filters */
    public function smartSearch(): void
    {
        $q = trim((string)($this->input()['query'] ?? ''));
        if ($q === '') { $this->json(['ok' => false, 'error' => 'empty']); return; }
        $filters = AIService::smartSearch($q);
        $this->json(['ok' => true, 'filters' => $filters, 'redirect' => url('/properties?') . http_build_query($filters)]);
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
}
