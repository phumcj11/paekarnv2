<?php
namespace App\Services;

use App\Core\Database;
use App\Services\BookingConfirmationService;

/**
 * LINE Messaging API — per-property OA token
 * แต่ละที่พักมี LINE OA ของตัวเอง (line_channel_access_token ใน properties)
 */
class PropertyLineService
{
    /**
     * Push arbitrary messages array ไปลูกค้าผ่าน OA ของที่พัก
     * $messages = LINE Messaging API messages array (text / flex ฯลฯ)
     */
    public static function push(int $propertyId, string $lineUserId, array $messages): bool
    {
        return self::pushResult($propertyId, $lineUserId, $messages)['ok'];
    }

    /** @return array{ok:bool,code:int,detail:string,data?:array} */
    public static function botInfo(int $propertyId, ?string $tokenOverride = null): array
    {
        $token = self::resolveToken($propertyId, $tokenOverride);
        if (!$token) {
            return ['ok' => false, 'code' => 0, 'detail' => 'ไม่มี Channel Access Token'];
        }
        $res = self::get('https://api.line.me/v2/bot/info', $token);
        $data = json_decode($res['body'], true);
        return [
            'ok'     => $res['code'] === 200,
            'code'   => $res['code'],
            'detail' => $res['body'],
            'data'   => is_array($data) ? $data : [],
        ];
    }

    /** @return array{ok:bool,code:int,detail:string,data?:array} */
    public static function userProfile(int $propertyId, string $lineUserId, ?string $tokenOverride = null): array
    {
        $token = self::resolveToken($propertyId, $tokenOverride);
        if (!$token) {
            return ['ok' => false, 'code' => 0, 'detail' => 'ไม่มี Channel Access Token'];
        }
        $res = self::get('https://api.line.me/v2/bot/profile/' . rawurlencode($lineUserId), $token);
        $data = json_decode($res['body'], true);
        return [
            'ok'     => $res['code'] === 200,
            'code'   => $res['code'],
            'detail' => $res['body'],
            'data'   => is_array($data) ? $data : [],
        ];
    }

    /** @return array{ok:bool,code:int,detail:string} */
    public static function pushResult(int $propertyId, string $lineUserId, array $messages, ?string $tokenOverride = null): array
    {
        $token = self::resolveToken($propertyId, $tokenOverride);
        if (!$token) {
            return ['ok' => false, 'code' => 0, 'detail' => 'ยังไม่ได้เปิดใช้ LINE OA หรือยังไม่ได้บันทึก Channel Access Token'];
        }
        if (!$lineUserId) {
            return ['ok' => false, 'code' => 0, 'detail' => 'ไม่มี LINE User ID'];
        }

        $body = json_encode([
            'to'       => $lineUserId,
            'messages' => $messages,
        ], JSON_UNESCAPED_UNICODE);

        $res = self::post('https://api.line.me/v2/bot/message/push', $token, $body);

        return [
            'ok'     => $res['code'] === 200,
            'code'   => $res['code'],
            'detail' => $res['body'],
        ];
    }

    /**
     * ปรับเบอร์โทรให้อยู่ในรูปแบบมาตรฐาน (ตัวเลขล้วน 10 หลัก หรือ +66…)
     * ใช้สำหรับ match ระหว่าง property_line_contacts.phone กับ bookings.guest_phone
     */
    public static function normalizePhone(string $phone): string
    {
        $p = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($p, '+66')) {
            $p = '0' . substr($p, 3);
        } elseif (str_starts_with($p, '66') && strlen($p) >= 11) {
            $p = '0' . substr($p, 2);
        }
        return $p;
    }

    /**
     * หา LINE User ID จากเบอร์โทรใน property_line_contacts ของที่พัก
     * คืนค่า line_user_id ถ้าพบ, null ถ้าไม่พบ หรือยังไม่มีคอลัมน์ phone
     */
    public static function matchContactByPhone(int $propertyId, string $phone): ?string
    {
        if (!\App\Core\Database::tableHasColumn('property_line_contacts', 'phone')) {
            return null;
        }
        $normalized = self::normalizePhone($phone);
        if (strlen($normalized) < 9) return null;

        $row = \App\Core\Database::fetch(
            "SELECT line_user_id FROM property_line_contacts
             WHERE property_id = :p AND phone = :ph AND unfollowed_at IS NULL
             LIMIT 1",
            ['p' => $propertyId, 'ph' => $normalized]
        );
        return $row ? (string)$row['line_user_id'] : null;
    }

    /**
     * Flex Message สรุปการจอง + ปุ่มดูใบยืนยัน
     */
    public static function sendBookingConfirmation(int $bookingId): bool
    {
        $b = Database::fetch(
            "SELECT b.*, p.name AS property_name, p.id AS property_id, u.name AS unit_name
             FROM bookings b
             JOIN properties p ON p.id = b.property_id
             LEFT JOIN property_units u ON u.id = b.unit_id
             WHERE b.id = :i LIMIT 1",
            ['i' => $bookingId]
        );
        if (!$b || empty($b['guest_line_user_id'])) return false;

        $token = self::token((int)$b['property_id']);
        if (!$token) return false;

        $confirmUrl = BookingConfirmationService::publicUrl($b);
        $checkIn    = date('d/m/Y', strtotime($b['check_in']));
        $checkOut   = date('d/m/Y', strtotime($b['check_out']));
        $total      = number_format((float)$b['total_price'], 0);

        $flex = [
            'type' => 'flex',
            'altText' => 'ยืนยันการจอง #' . $b['code'] . ' — ' . $b['property_name'],
            'contents' => [
                'type'       => 'bubble',
                'size'       => 'mega',
                'header'     => [
                    'type'            => 'box',
                    'layout'          => 'vertical',
                    'backgroundColor' => '#1b4f72',
                    'contents' => [[
                        'type'  => 'text',
                        'text'  => 'ยืนยันการจอง',
                        'color' => '#ffffff',
                        'size'  => 'xs',
                        'weight'=> 'bold',
                    ], [
                        'type'  => 'text',
                        'text'  => $b['property_name'],
                        'color' => '#ffffff',
                        'size'  => 'lg',
                        'weight'=> 'bold',
                        'wrap'  => true,
                    ]],
                ],
                'body' => [
                    'type'    => 'box',
                    'layout'  => 'vertical',
                    'spacing' => 'md',
                    'contents' => [
                        self::flexRow('รหัสจอง', '#' . $b['code']),
                        self::flexRow('ผู้จอง',   $b['guest_name']),
                        self::flexRow('ยูนิต',    $b['unit_name'] ?? '-'),
                        self::flexRow('เช็คอิน',  $checkIn),
                        self::flexRow('เช็คเอาท์', $checkOut),
                        self::flexRow('จำนวนคืน', $b['nights'] . ' คืน'),
                        [
                            'type'            => 'separator',
                            'margin'          => 'md',
                        ],
                        [
                            'type'    => 'box',
                            'layout'  => 'horizontal',
                            'margin'  => 'md',
                            'contents' => [[
                                'type'   => 'text',
                                'text'   => 'รวมทั้งสิ้น',
                                'size'   => 'sm',
                                'color'  => '#555555',
                                'flex'   => 1,
                            ], [
                                'type'   => 'text',
                                'text'   => '฿' . $total,
                                'size'   => 'sm',
                                'color'  => '#1b4f72',
                                'weight' => 'bold',
                                'align'  => 'end',
                            ]],
                        ],
                    ],
                ],
                'footer' => [
                    'type'    => 'box',
                    'layout'  => 'vertical',
                    'contents' => [[
                        'type'   => 'button',
                        'style'  => 'primary',
                        'color'  => '#1b4f72',
                        'action' => [
                            'type'  => 'uri',
                            'label' => 'ดูใบยืนยันการจอง',
                            'uri'   => $confirmUrl,
                        ],
                    ]],
                ],
            ],
        ];

        return self::push((int)$b['property_id'], (string)$b['guest_line_user_id'], [$flex]);
    }

    // =========================================================
    //  RICH MENU
    // =========================================================

    /**
     * สร้าง Rich Menu มาตรฐาน 6 ปุ่มสำหรับที่พัก
     * @return array{ok:bool,richMenuId:string,detail:string}
     */
    public static function createPropertyRichMenu(int $propertyId, string $propertyName): array
    {
        $token = self::token($propertyId);
        if (!$token) return ['ok' => false, 'richMenuId' => '', 'detail' => 'ไม่มี token'];

        // Large Rich Menu 2500×1686 (เทมเพลต "ใหญ่" ใน LINE OA Manager)
        $menuH = 1686;
        $rowH  = (int)($menuH / 2); // 843 ต่อแถว
        $menu = [
            'size'        => ['width' => 2500, 'height' => $menuH],
            'selected'    => true,
            'name'        => 'Paekarn Menu — ' . mb_substr($propertyName, 0, 30),
            'chatBarText' => 'เมนูสอบถาม',
            'areas'       => [
                // แถวบน: เช็ควันว่าง | ราคา & โปรโมชั่น | ดูห้องพัก
                [
                    'bounds' => ['x' => 0,    'y' => 0,     'width' => 833,  'height' => $rowH],
                    'action' => [
                        'type'        => 'postback',
                        'label'       => 'เช็ควันว่าง',
                        'data'        => 'avail_calendar',
                        'displayText' => 'เช็ควันว่าง',
                    ],
                ],
                [
                    'bounds' => ['x' => 833,  'y' => 0,     'width' => 834,  'height' => $rowH],
                    'action' => ['type' => 'message', 'label' => 'ราคา & โปรโมชั่น', 'text' => 'ราคาเท่าไหร่'],
                ],
                [
                    'bounds' => ['x' => 1667, 'y' => 0,     'width' => 833,  'height' => $rowH],
                    'action' => ['type' => 'message', 'label' => 'ดูห้องพัก', 'text' => 'ดูห้องพัก'],
                ],
                // แถวล่าง: ที่อยู่ | ติดต่อเรา | จองเลย
                [
                    'bounds' => ['x' => 0,    'y' => $rowH, 'width' => 833,  'height' => $rowH],
                    'action' => ['type' => 'message', 'label' => 'ที่อยู่', 'text' => 'ที่อยู่'],
                ],
                [
                    'bounds' => ['x' => 833,  'y' => $rowH, 'width' => 834,  'height' => $rowH],
                    'action' => ['type' => 'message', 'label' => 'ติดต่อเรา', 'text' => 'เบอร์โทร'],
                ],
                [
                    'bounds' => ['x' => 1667, 'y' => $rowH, 'width' => 833,  'height' => $rowH],
                    'action' => ['type' => 'message', 'label' => 'จองเลย', 'text' => 'จองเลย'],
                ],
            ],
        ];

        $res  = self::post(
            'https://api.line.me/v2/bot/richmenu',
            $token,
            (string)json_encode($menu, JSON_UNESCAPED_UNICODE)
        );
        $data = json_decode($res['body'], true);

        if ($res['code'] !== 200) {
            error_log("[Paekarn] createRichMenu FAIL property={$propertyId} HTTP {$res['code']}: {$res['body']}");
            return ['ok' => false, 'richMenuId' => '', 'detail' => $res['body']];
        }

        $richMenuId = (string)($data['richMenuId'] ?? '');
        if (!$richMenuId) {
            return ['ok' => false, 'richMenuId' => '', 'detail' => 'ไม่ได้รับ richMenuId'];
        }

        // อัปโหลดรูป Rich Menu — LINE ต้องการรูปก่อน set default ได้
        $imgResult = self::uploadRichMenuDefaultImage($token, $richMenuId);
        if (!$imgResult['ok']) {
            // ลบ menu ที่สร้างไว้ (ไม่มีประโยชน์ถ้าไม่มีรูป)
            $delCh = curl_init("https://api.line.me/v2/bot/richmenu/{$richMenuId}");
            curl_setopt_array($delCh, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => 'DELETE', CURLOPT_TIMEOUT => 5, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token]]);
            curl_exec($delCh); curl_close($delCh);

            return ['ok' => false, 'richMenuId' => '', 'detail' => "อัปโหลดรูป Rich Menu ไม่สำเร็จ (HTTP {$imgResult['code']}): {$imgResult['detail']}"];
        }

        return ['ok' => true, 'richMenuId' => $richMenuId, 'detail' => 'สำเร็จ'];
    }

    /**
     * ตั้ง Rich Menu เป็น default ของ OA
     * LINE API: POST https://api.line.me/v2/bot/user/all/richmenu/{richMenuId}
     * @return array{ok:bool,code:int,detail:string}
     */
    public static function setDefaultRichMenu(int $propertyId, string $richMenuId): array
    {
        $token = self::token($propertyId);
        if (!$token) return ['ok' => false, 'code' => 0, 'detail' => 'ไม่มี token'];

        $url = "https://api.line.me/v2/bot/user/all/richmenu/{$richMenuId}";

        // LINE อาจต้องใช้เวลาสักครู่หลัง upload รูป — retry สูงสุด 3 ครั้ง
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            if ($attempt > 1) sleep(2);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
            ]);
            $body = (string)curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200) {
                Database::update('properties', ['line_rich_menu_id' => $richMenuId], 'id = :i', ['i' => $propertyId]);
                return ['ok' => true, 'code' => 200, 'detail' => ''];
            }

            error_log("[Paekarn] setDefaultRichMenu attempt={$attempt} property={$propertyId} HTTP {$code}: {$body}");

            // retry เฉพาะ 404 (menu ยัง propagate ไม่ทัน)
            if ($code !== 404) break;
        }

        return ['ok' => false, 'code' => $code, 'detail' => $body];
    }

    /**
     * ลบ Rich Menu
     */
    public static function deleteRichMenu(int $propertyId): bool
    {
        $token = self::token($propertyId);
        if (!$token) return false;

        $prop = Database::fetch(
            "SELECT line_rich_menu_id FROM properties WHERE id = :i LIMIT 1",
            ['i' => $propertyId]
        );
        $menuId = $prop['line_rich_menu_id'] ?? '';
        if (!$menuId) return false;

        // unlink default rich menu ของ channel
        // LINE API: DELETE https://api.line.me/v2/bot/user/all/richmenu
        $ch = curl_init("https://api.line.me/v2/bot/user/all/richmenu");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
        ]);
        curl_exec($ch);
        curl_close($ch);

        // delete menu
        $ch = curl_init("https://api.line.me/v2/bot/richmenu/{$menuId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
        ]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            Database::update('properties', ['line_rich_menu_id' => null], 'id = :i', ['i' => $propertyId]);
        }
        return $code === 200;
    }

    /**
     * อัปโหลดรูป Rich Menu (branded) ให้ LINE
     * @return array{ok:bool,code:int,detail:string}
     */
    private static function uploadRichMenuDefaultImage(string $token, string $richMenuId): array
    {
        // หาไฟล์รูปจากหลาย path
        $candidates = [
            defined('APP_BASE_PATH') ? APP_BASE_PATH . '/public/assets/line_rich_menu_paekarn.png' : null,
            dirname(__DIR__, 2) . '/public/assets/line_rich_menu_paekarn.png',
        ];

        $png      = null;
        $usedPath = '';
        foreach (array_filter($candidates) as $path) {
            if (is_file($path) && is_readable($path)) {
                $png = file_get_contents($path);
                $usedPath = $path;
                break;
            }
        }

        // GD fallback: สร้างรูปสีน้ำเงินเรียบๆ
        if (!$png && function_exists('imagecreatetruecolor')) {
            $w = 2500; $h = 1686;
            $img = imagecreatetruecolor($w, $h);
            if ($img) {
                $bg  = imagecolorallocate($img, 11, 77, 107);
                $sep = imagecolorallocate($img, 255, 255, 255);
                imagefilledrectangle($img, 0, 0, $w - 1, $h - 1, $bg);
                imageline($img, 833, 0, 833, $h, $sep);
                imageline($img, 1667, 0, 1667, $h, $sep);
                imageline($img, 0, 843, $w, 843, $sep);
                ob_start(); imagepng($img); $png = ob_get_clean();
                imagedestroy($img);
                $usedPath = 'GD fallback';
            }
        }

        if (!$png) {
            $tried = implode(', ', array_filter($candidates));
            error_log("[Paekarn] uploadRichMenuImage: ไม่พบไฟล์รูป tried={$tried}");
            return ['ok' => false, 'code' => 0, 'detail' => "ไม่พบไฟล์รูป ({$tried})"];
        }

        // LINE Large Rich Menu 2500×1686, ไฟล์ ≤ 1MB
        $prepared = self::prepareRichMenuImage($png, 2500, 1686);
        if (!$prepared['ok']) {
            return ['ok' => false, 'code' => 0, 'detail' => $prepared['detail']];
        }

        $imageBytes  = $prepared['bytes'];
        $contentType = $prepared['contentType'];
        error_log("[Paekarn] uploadRichMenuImage: {$usedPath} → {$contentType} " . strlen($imageBytes) . " bytes ({$prepared['detail']})");

        $ch = curl_init("https://api-data.line.me/v2/bot/richmenu/{$richMenuId}/content");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: ' . $contentType,
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS => $imageBytes,
        ]);
        $resBody = (string)curl_exec($ch);
        $code    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            error_log("[Paekarn] uploadRichMenuImage FAIL richmenu={$richMenuId} HTTP {$code}: {$resBody}");
            return ['ok' => false, 'code' => $code, 'detail' => $resBody];
        }

        return ['ok' => true, 'code' => 200, 'detail' => ''];
    }

    /**
     * Sync followers จาก LINE OA → upsert เข้า property_line_contacts
     * Phase 1: ดึง follower IDs ทั้งหมด (max 5 pages = 1500 คน) แล้ว upsert UID ก่อน
     * Phase 2: fetch profile เฉพาะ record ที่ display_name ยังว่าง (max 50 คนต่อครั้ง, timeout 3s/คน)
     * @return array{imported:int,skipped:int,error:string}
     */
    public static function syncFollowers(int $propertyId, int $maxPages = 5): array
    {
        $token = self::token($propertyId);
        if (!$token) {
            return ['imported' => 0, 'skipped' => 0, 'error' => 'ไม่มี Channel Access Token'];
        }

        $imported = 0;
        $skipped  = 0;
        $cursor   = null;
        $now      = date('Y-m-d H:i:s');
        $phoneCol = \App\Core\Database::tableHasColumn('property_line_contacts', 'phone');

        // ── Phase 1: ดึง follower IDs และ upsert เข้า DB (ไม่ fetch profile) ──────
        for ($page = 0; $page < $maxPages; $page++) {
            $url = 'https://api.line.me/v2/bot/followers/ids?limit=300';
            if ($cursor) $url .= '&start=' . rawurlencode($cursor);

            $res  = self::get($url, $token);
            $data = json_decode($res['body'], true);

            if ($res['code'] !== 200 || !is_array($data)) {
                return ['imported' => $imported, 'skipped' => $skipped,
                        'error' => "LINE API error {$res['code']}: {$res['body']}"];
            }

            $userIds = $data['userIds'] ?? [];
            $cursor  = $data['next'] ?? null;

            foreach ($userIds as $uid) {
                $uid = (string)$uid;
                if (!$uid) continue;

                $existing = \App\Core\Database::fetch(
                    "SELECT id FROM property_line_contacts
                     WHERE property_id = :p AND line_user_id = :l LIMIT 1",
                    ['p' => $propertyId, 'l' => $uid]
                );

                if ($existing) {
                    $skipped++;
                } else {
                    $row = [
                        'property_id'  => $propertyId,
                        'line_user_id' => $uid,
                        'display_name' => null,
                        'picture_url'  => null,
                        'followed_at'  => $now,
                        'last_seen_at' => $now,
                    ];
                    if ($phoneCol) $row['phone'] = null;
                    \App\Core\Database::insert('property_line_contacts', $row);
                    $imported++;
                }
            }

            if (!$cursor) break;
        }

        // ── Phase 2: fetch profile เฉพาะคนที่ยังไม่มีชื่อ (max 50 คน, timeout 3s) ──
        $noName = \App\Core\Database::fetchAll(
            "SELECT id, line_user_id FROM property_line_contacts
             WHERE property_id = :p AND (display_name IS NULL OR display_name = '')
             ORDER BY last_seen_at DESC LIMIT 50",
            ['p' => $propertyId]
        );

        foreach ($noName as $row) {
            $uid = (string)$row['line_user_id'];
            $ch  = curl_init('https://api.line.me/v2/bot/profile/' . rawurlencode($uid));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 3,
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
            ]);
            $body = (string)curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200) {
                $prof = json_decode($body, true);
                \App\Core\Database::update(
                    'property_line_contacts',
                    [
                        'display_name' => $prof['displayName'] ?? null,
                        'picture_url'  => $prof['pictureUrl'] ?? null,
                    ],
                    'id = :i',
                    ['i' => $row['id']]
                );
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'error' => ''];
    }

    /**
     * Reply กลับลูกค้าผ่าน replyToken + per-property token
     * $messages = LINE messages array (text/flex)
     */
    public static function reply(int $propertyId, string $replyToken, array $messages): bool
    {
        $token = self::token($propertyId);
        if (!$token || !$replyToken) return false;

        $body = json_encode([
            'replyToken' => $replyToken,
            'messages'   => $messages,
        ], JSON_UNESCAPED_UNICODE);

        $res = self::post('https://api.line.me/v2/bot/message/reply', $token, $body);
        if ($res['code'] !== 200) {
            error_log("[Paekarn] LINE reply FAIL property={$propertyId} HTTP {$res['code']}: {$res['body']}");
        }
        return $res['code'] === 200;
    }

    /** Verify webhook signature ด้วย per-property secret */
    public static function verifySignature(int $propertyId, string $rawBody, ?string $headerSig): bool
    {
        $prop = Database::fetch("SELECT line_channel_secret FROM properties WHERE id = :i LIMIT 1", ['i' => $propertyId]);
        $secret = $prop['line_channel_secret'] ?? '';
        if (!$secret || !$headerSig) return false;
        $expected = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));
        return hash_equals($expected, $headerSig);
    }

    /** ดึง token ของที่พัก (NULL = ไม่ได้ตั้งค่า) */
    private static function token(int $propertyId): ?string
    {
        $prop = Database::fetch(
            "SELECT line_messaging_enabled, line_channel_access_token
             FROM properties WHERE id = :i LIMIT 1",
            ['i' => $propertyId]
        );
        if (!$prop || !(int)$prop['line_messaging_enabled']) return null;
        $tok = trim((string)$prop['line_channel_access_token']);
        return $tok ?: null;
    }

    private static function resolveToken(int $propertyId, ?string $override = null): ?string
    {
        $ov = trim((string)$override);
        if ($ov !== '') return $ov;
        return self::token($propertyId);
    }

    public static function parseLineError(string $body): string
    {
        $data = json_decode($body, true);
        if (is_array($data) && !empty($data['message'])) {
            return (string)$data['message'];
        }
        return '';
    }

    private static function flexRow(string $label, string $value): array
    {
        return [
            'type'     => 'box',
            'layout'   => 'horizontal',
            'contents' => [[
                'type'  => 'text',
                'text'  => $label,
                'size'  => 'sm',
                'color' => '#888888',
                'flex'  => 2,
            ], [
                'type'  => 'text',
                'text'  => $value,
                'size'  => 'sm',
                'color' => '#333333',
                'flex'  => 3,
                'align' => 'end',
                'wrap'  => true,
            ]],
        ];
    }

    /**
     * เตรียมรูป Rich Menu: resize + บีบอัด ≤ 1MB (LINE limit)
     * @return array{ok:bool,bytes:string,contentType:string,detail:string}
     */
    private static function prepareRichMenuImage(string $imageData, int $tw = 2500, int $th = 1686): array
    {
        $fail = static fn(string $msg) => ['ok' => false, 'bytes' => '', 'contentType' => '', 'detail' => $msg];

        if (!function_exists('imagecreatefromstring')) {
            return $fail('เซิร์ฟเวอร์ไม่มี GD extension สำหรับประมวลผลรูป');
        }

        $src = @imagecreatefromstring($imageData);
        if (!$src) return $fail('อ่านไฟล์รูปไม่ได้');
        $dst = imagecreatetruecolor($tw, $th);
        if (!$dst) {
            imagedestroy($src);
            return $fail('สร้าง canvas ไม่ได้');
        }

        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $tw - 1, $th - 1, $white);

        // cover crop เต็มจอ — ไม่มีขอบขาว (LINE แสดงเต็มความกว้าง)
        // ถ้ารูปสูงเกิน → crop ด้านล่างทิ้ง เก็บแถวบน (6 ปุ่ม) ไว้
        $sw = imagesx($src);
        $sh = imagesy($src);
        $targetRatio = $tw / $th;
        $srcRatio    = $sw / $sh;

        if ($srcRatio > $targetRatio) {
            // รูปกว้างเกิน → fit ความสูง, crop ซ้าย-ขวา
            $srcH = $sh;
            $srcW = (int)round($sh * $targetRatio);
            $srcX = (int)(($sw - $srcW) / 2);
            $srcY = 0;
        } else {
            // รูปสูงเกิน → fit ความกว้าง, crop ล่าง (จัดจากบน)
            $srcW = $sw;
            $srcH = (int)round($sw / $targetRatio);
            $srcX = 0;
            $srcY = 0;
        }

        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $tw, $th, $srcW, $srcH);
        imagedestroy($src);

        $maxBytes = 950 * 1024; // LINE จำกัด 1MB — เหลือ buffer

        // JPEG บีบอัดได้ดีกว่า PNG สำหรับรูป AI
        for ($q = 88; $q >= 35; $q -= 3) {
            ob_start();
            imagejpeg($dst, null, $q);
            $bytes = ob_get_clean();
            if ($bytes && strlen($bytes) <= $maxBytes) {
                imagedestroy($dst);
                return [
                    'ok'          => true,
                    'bytes'       => $bytes,
                    'contentType' => 'image/jpeg',
                    'detail'      => "jpeg q={$q}",
                ];
            }
        }

        // fallback PNG บีบอัดสูงสุด
        for ($level = 9; $level >= 0; $level--) {
            ob_start();
            imagepng($dst, null, $level);
            $bytes = ob_get_clean();
            if ($bytes && strlen($bytes) <= $maxBytes) {
                imagedestroy($dst);
                return [
                    'ok'          => true,
                    'bytes'       => $bytes,
                    'contentType' => 'image/png',
                    'detail'      => "png level={$level}",
                ];
            }
        }

        imagedestroy($dst);
        return $fail('บีบอัดรูปแล้วยังใหญ่กว่า 1MB — ลองใช้รูปที่เรียบง่ายกว่านี้');
    }

    /** @return array{code:int,body:string} */
    private static function get(string $url, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
        ]);
        $response = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $code, 'body' => is_string($response) ? $response : ''];
    }

    /** @return array{code:int,body:string} */
    private static function post(string $url, string $token, string $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS => $body,
        ]);
        $response = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $code, 'body' => is_string($response) ? $response : ''];
    }
}
