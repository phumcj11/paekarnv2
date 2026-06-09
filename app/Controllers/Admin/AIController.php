<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Setting;
use App\Services\AIService;

class AIController extends Controller
{
    public function settings(): void
    {
        $row = Database::fetch("SELECT COUNT(*) c FROM ai_chats WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $kbCount = (int)Database::fetch("SELECT COUNT(*) c FROM ai_knowledge_base WHERE is_active=1")['c'];

        View::render('admin/ai/settings', [
            'page_title' => 'AI Settings',
            'enabled'    => Setting::get('ai_enabled', '0'),
            'provider'   => Setting::get('ai_provider', 'openai'),
            'apiUrl'     => Setting::get('ai_api_url', 'https://api.openai.com/v1'),
            'apiKey'     => Setting::get('ai_api_key', ''),
            'model'      => Setting::get('ai_model', 'gpt-4o-mini'),
            'chatEnabled'=> Setting::get('ai_chatbot_enabled', '1'),
            'greeting'   => Setting::get('ai_chatbot_greeting', ''),
            'persona'    => Setting::get('ai_chatbot_persona', ''),
            'chats7d'    => (int)$row['c'],
            'kbCount'    => $kbCount,
        ], 'layouts/admin');
    }

    public function saveSettings(): void
    {
        foreach (['ai_enabled','ai_provider','ai_api_url','ai_api_key','ai_model',
                  'ai_chatbot_enabled','ai_chatbot_greeting','ai_chatbot_persona'] as $k) {
            $v = $_POST[$k] ?? '';
            if (in_array($k, ['ai_enabled','ai_chatbot_enabled'])) $v = $v ? '1' : '0';
            Setting::set($k, (string)$v);
        }
        Session::flash('success', 'บันทึกการตั้งค่า AI เรียบร้อย');
        redirect(url('/admin/ai'));
    }

    public function test(): void
    {
        $msg = trim((string)($_POST['message'] ?? 'สวัสดี ทดสอบระบบ'));
        $reply = AIService::replyChat($msg);
        Session::flash('success', '✓ AI ตอบกลับ: ' . mb_substr($reply, 0, 200));
        redirect(url('/admin/ai'));
    }

    // -------- Knowledge Base CRUD ---------
    public function kbIndex(): void
    {
        $rows = Database::fetchAll("SELECT * FROM ai_knowledge_base ORDER BY sort_order, id DESC");
        View::render('admin/ai/kb_index', [
            'page_title' => 'AI Knowledge Base', 'rows' => $rows,
        ], 'layouts/admin');
    }

    public function kbForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $kb = $id ? Database::fetch("SELECT * FROM ai_knowledge_base WHERE id = :i", ['i' => $id]) : null;
        View::render('admin/ai/kb_form', [
            'page_title' => $id ? 'แก้ไข KB' : 'เพิ่ม KB ใหม่',
            'kb' => $kb,
        ], 'layouts/admin');
    }

    public function kbSave(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'category' => $_POST['category'] ?? null,
            'question' => trim((string)($_POST['question'] ?? '')),
            'answer'   => trim((string)($_POST['answer'] ?? '')),
            'keywords' => $_POST['keywords'] ?? null,
            'is_active'=> !empty($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        if ($data['question'] === '' || $data['answer'] === '') {
            Session::flash('error', 'กรุณากรอกคำถามและคำตอบ');
            back();
        }
        if ($id) Database::update('ai_knowledge_base', $data, 'id = :i', ['i' => $id]);
        else     Database::insert('ai_knowledge_base', $data);
        Session::flash('success', 'บันทึก KB เรียบร้อย');
        redirect(url('/admin/ai/kb'));
    }

    public function kbDelete(int $id): void
    {
        Database::delete('ai_knowledge_base', 'id = :i', ['i' => $id]);
        Session::flash('success', 'ลบเรียบร้อย');
        redirect(url('/admin/ai/kb'));
    }

    // ---------- Chat history ----------
    public function chats(): void
    {
        $sessions = Database::fetchAll(
            "SELECT session_id, COUNT(*) AS msgs, MAX(created_at) AS last_at,
                    (SELECT content FROM ai_chats c2 WHERE c2.session_id = c.session_id AND c2.role='user' ORDER BY id LIMIT 1) AS first_msg
             FROM ai_chats c GROUP BY session_id ORDER BY last_at DESC LIMIT 100"
        );
        View::render('admin/ai/chats', [
            'page_title' => 'AI Chat History', 'sessions' => $sessions,
        ], 'layouts/admin');
    }
}
