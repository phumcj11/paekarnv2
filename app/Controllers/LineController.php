<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\User;
use App\Services\AIService;
use App\Services\LineService;
use App\Services\PropertyLineBotService;
use App\Services\PropertyLineService;

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

    /**
     * POST /line/property/{id}/webhook
     * Per-property LINE OA webhook — จับ follow/message events และบันทึก property_line_contacts
     */
    public function propertyWebhook(int $id): void
    {
        $rawBody = file_get_contents('php://input') ?: '';
        $sig     = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? null;
        $sigOk   = PropertyLineService::verifySignature($id, $rawBody, $sig);

        if (!$sigOk) {
            error_log("[Paekarn] property webhook #{$id} signature FAIL");
            http_response_code(401);
            echo json_encode(['ok' => false]);
            return;
        }

        if (Database::tableHasColumn('properties', 'line_webhook_verified')) {
            Database::update('properties', ['line_webhook_verified' => 1], 'id = :i', ['i' => $id]);
        }

        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            echo json_encode(['ok' => true]);
            return;
        }

        foreach ($data['events'] ?? [] as $event) {
            $type       = $event['type'] ?? '';
            $lineUserId = $event['source']['userId'] ?? null;
            if (!$lineUserId) continue;

            if ($type === 'unfollow') {
                Database::update(
                    'property_line_contacts',
                    ['unfollowed_at' => date('Y-m-d H:i:s'), 'last_seen_at' => date('Y-m-d H:i:s')],
                    'property_id = :p AND line_user_id = :l',
                    ['p' => $id, 'l' => $lineUserId]
                );
                continue;
            }

            if ($type === 'follow' || $type === 'message') {
                $replyToken = $event['replyToken'] ?? '';

                // ตอบกลับทันที — replyToken หมดอายุเร็ว ห้ามเรียก LINE Profile API ก่อน reply
                if ($replyToken) {
                    if ($type === 'follow') {
                        $property = Database::fetch(
                            "SELECT name FROM properties WHERE id = :i LIMIT 1",
                            ['i' => $id]
                        );
                        $pname = $property ? $property['name'] : 'ที่พักของเรา';
                        PropertyLineService::reply($id, $replyToken, [[
                            'type' => 'text',
                            'text' => "สวัสดีค่ะ ยินดีต้อนรับสู่ {$pname} 🌊\n\n"
                                   . "สอบถามได้เลยนะคะ เช่น\n"
                                   . "• \"ราคาเท่าไหร่\"\n"
                                   . "• \"เสาร์นี้ ว่างไหม 4 คน\"\n"
                                   . "• \"เช็คอินกี่โมง\"\n"
                                   . "• \"ที่อยู่อยู่ที่ไหน\"",
                        ]]);
                    } elseif ($type === 'message' && ($event['message']['type'] ?? '') === 'text') {
                        $text = trim((string)($event['message']['text'] ?? ''));
                        if ($text !== '') {
                            try {
                                $replied = PropertyLineBotService::handle($id, $replyToken, $text);
                                if (!$replied) {
                                    PropertyLineService::push($id, $lineUserId, [[
                                        'type' => 'text',
                                        'text' => 'ขออภัยค่ะ ระบบตอบช้าไปหน่อย — ลองถามอีกครั้งหรือติดต่อเจ้าหน้าที่ได้เลยนะคะ 😊',
                                    ]]);
                                }
                            } catch (\Throwable $e) {
                                error_log("[Paekarn] PropertyLineBotService error: " . $e->getMessage());
                                PropertyLineService::push($id, $lineUserId, [[
                                    'type' => 'text',
                                    'text' => 'ขออภัยค่ะ ระบบขัดข้องชั่วคราว — กรุณาลองใหม่อีกครั้งนะคะ',
                                ]]);
                            }
                        }
                    }
                }

                // บันทึก contact หลัง reply (ดึง profile เฉพาะ contact ใหม่หรือตอน follow)
                $existing = Database::fetch(
                    "SELECT id FROM property_line_contacts WHERE property_id = :p AND line_user_id = :l LIMIT 1",
                    ['p' => $id, 'l' => $lineUserId]
                );
                $displayName = null;
                $pictureUrl  = null;
                if ($type === 'follow' || !$existing) {
                    $profile = PropertyLineService::userProfile($id, $lineUserId);
                    $displayName = $profile['ok'] ? ($profile['data']['displayName'] ?? null) : null;
                    $pictureUrl  = $profile['ok'] ? ($profile['data']['pictureUrl'] ?? null) : null;
                }
                self::upsertContact(
                    $id,
                    $lineUserId,
                    $displayName,
                    $pictureUrl,
                    null,
                    $type === 'follow' ? 'follow' : 'message'
                );
            }
        }

        echo json_encode(['ok' => true]);
    }

    private static function upsertContact(
        int $propertyId,
        string $lineUserId,
        ?string $displayName,
        ?string $pictureUrl,
        ?string $followedAt,
        string $eventType
    ): void {
        $existing = Database::fetch(
            "SELECT id FROM property_line_contacts WHERE property_id = :p AND line_user_id = :l LIMIT 1",
            ['p' => $propertyId, 'l' => $lineUserId]
        );
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $upd = ['last_seen_at' => $now];
            if ($eventType === 'follow') {
                $upd['followed_at']   = $now;
                $upd['unfollowed_at'] = null;
            }
            if ($displayName !== null) $upd['display_name'] = $displayName;
            if ($pictureUrl  !== null) $upd['picture_url']  = $pictureUrl;
            Database::update('property_line_contacts', $upd, 'id = :i', ['i' => $existing['id']]);
        } else {
            Database::insert('property_line_contacts', [
                'property_id'   => $propertyId,
                'line_user_id'  => $lineUserId,
                'display_name'  => $displayName,
                'picture_url'   => $pictureUrl,
                'followed_at'   => $eventType === 'follow' ? $now : null,
                'last_seen_at'  => $now,
            ]);
        }
    }
}
