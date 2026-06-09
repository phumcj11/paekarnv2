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

    /** @return array{ok:bool,code:int,detail:string} */
    public static function pushResult(int $propertyId, string $lineUserId, array $messages): array
    {
        $token = self::token($propertyId);
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
