<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\User;
use App\Services\AIService;
use App\Services\LineService;

class LineController extends Controller
{
    /** Initiate LINE Login (link account) */
    public function login(): void
    {
        $state = bin2hex(random_bytes(8));
        Session::set('line_oauth_state', $state);
        $redirect = rtrim((string)\App\Core\Application::$publicUrl, '/') . '/line/callback';
        redirect(LineService::loginUrl($state, $redirect));
    }

    public function callback(): void
    {
        $code  = $_GET['code']  ?? '';
        $state = $_GET['state'] ?? '';
        $expected = Session::get('line_oauth_state');
        Session::remove('line_oauth_state');

        if (!$code || !$state || $state !== $expected) {
            Session::flash('error', 'LINE OAuth ไม่ถูกต้อง');
            redirect(url('/account/profile'));
        }

        $redirect = rtrim((string)\App\Core\Application::$publicUrl, '/') . '/line/callback';
        $profile  = LineService::exchangeCode($code, $redirect);
        if (!$profile || empty($profile['userId'])) {
            Session::flash('error', 'ดึงข้อมูล LINE ไม่สำเร็จ');
            redirect(url('/account/profile'));
        }

        $userId = Auth::id();
        if (!$userId) {
            Session::flash('error', 'กรุณาเข้าสู่ระบบก่อน');
            redirect(url('/login'));
        }

        // Check duplicate
        $existing = Database::fetch("SELECT id FROM users WHERE line_user_id = :l AND id <> :u", ['l' => $profile['userId'], 'u' => $userId]);
        if ($existing) {
            Session::flash('error', 'LINE บัญชีนี้ถูกผูกกับ user อื่นแล้ว');
            redirect(url('/account/profile'));
        }

        User::update($userId, [
            'line_user_id' => $profile['userId'],
            'avatar'       => $profile['pictureUrl'] ?? null,
        ]);
        Session::flash('success', 'ผูกบัญชี LINE เรียบร้อย — จะได้รับการแจ้งเตือนผ่าน LINE');
        redirect(url('/account/profile'));
    }

    public function unlink(): void
    {
        $userId = Auth::id();
        if ($userId) {
            User::update($userId, ['line_user_id' => null]);
            Session::flash('success', 'ยกเลิกการผูก LINE เรียบร้อย');
        }
        redirect(url('/account/profile'));
    }

    /** LINE Webhook receiver — auto-reply ด้วย AI/KB */
    public function webhook(): void
    {
        $rawBody = file_get_contents('php://input') ?: '';
        $sig     = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? null;
        $sigOk   = LineService::verifySignature($rawBody, $sig);

        if (!$sigOk) {
            LineService::logWebhook($rawBody, false, 'invalid signature');
            http_response_code(401);
            echo json_encode(['ok' => false]);
            return;
        }

        $logId = LineService::logWebhook($rawBody, true);

        try {
            $data = json_decode($rawBody, true);
            foreach ($data['events'] ?? [] as $event) {
                if (($event['type'] ?? '') === 'message' && ($event['message']['type'] ?? '') === 'text') {
                    $text = (string)$event['message']['text'];
                    $replyToken = $event['replyToken'] ?? '';
                    $userId     = $event['source']['userId'] ?? null;

                    // log incoming as user message in ai_chats
                    $sessionId = 'line:' . ($userId ?: 'unknown');
                    Database::insert('ai_chats', [
                        'session_id' => $sessionId,
                        'user_id'    => null,
                        'role'       => 'user',
                        'content'    => $text,
                        'meta'       => json_encode(['source' => 'line', 'line_user_id' => $userId]),
                    ]);

                    $reply = AIService::replyChat($text, $sessionId);

                    Database::insert('ai_chats', [
                        'session_id' => $sessionId,
                        'role'       => 'assistant',
                        'content'    => $reply,
                        'meta'       => json_encode(['source' => 'line']),
                    ]);

                    if ($replyToken) {
                        LineService::reply($replyToken, $reply);
                    }
                }
                if (($event['type'] ?? '') === 'follow') {
                    $replyToken = $event['replyToken'] ?? '';
                    if ($replyToken) {
                        LineService::reply($replyToken, "ยินดีต้อนรับเข้าสู่แพกาญ.com 🌊\nสอบถามที่พัก/คูปองได้เลยค่ะ มีน้องแพคอยช่วยอยู่นะคะ");
                    }
                }
            }
            Database::update('webhook_logs', ['processed' => 1], 'id = :i', ['i' => $logId]);
        } catch (\Throwable $e) {
            Database::update('webhook_logs', ['error' => $e->getMessage()], 'id = :i', ['i' => $logId]);
        }

        echo json_encode(['ok' => true]);
    }
}
